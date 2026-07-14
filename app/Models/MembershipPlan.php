<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ActivityType;
use Database\Factories\MembershipPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Catalog of membership plans / passes (e.g. free trial, 4-pass, 12-pass,
 * unlimited). Behaviour is data-driven via the `rules` JSON bag so new plans
 * can be configured without code changes. The actual sale/subscription and
 * balance tracking live in a later phase (see CLAUDE.md, Fase 4).
 */
#[Fillable(['name', 'slug', 'description', 'price', 'rules', 'sort_order', 'is_active'])]
class MembershipPlan extends Model
{
    /** @use HasFactory<MembershipPlanFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'price' => MoneyCast::class,
            'rules' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Auto-generate a unique slug from the name when left blank.
        static::saving(function (self $plan): void {
            if (blank($plan->slug) && filled($plan->name)) {
                $base = Str::slug($plan->name);
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->whereKeyNot($plan->getKey())->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }
                $plan->slug = $slug;
            }
        });
    }

    /** Read a rule from the JSON bag, with a default. */
    public function rule(string $key, mixed $default = null): mixed
    {
        return data_get($this->rules, $key, $default);
    }

    /** Whether this plan grants unlimited practices. */
    public function isUnlimited(): bool
    {
        return (bool) $this->rule('unlimited', false);
    }

    /** Number of practice credits granted (null when unlimited/not applicable). */
    public function credits(): ?int
    {
        $credits = $this->rule('credits');

        return $credits === null ? null : (int) $credits;
    }

    /** Days the plan stays valid from activation (null = no expiry defined). */
    public function validityDays(): ?int
    {
        $days = $this->rule('validity_days');

        return $days === null ? null : (int) $days;
    }

    /**
     * Selling points shown on the public landing, straight from the rules bag
     * so a new plan needs no code change (and no brand copy lives in a view).
     *
     * @return list<string>
     */
    public function features(): array
    {
        return array_values(array_filter(
            (array) $this->rule('features', []),
            static fn (mixed $line): bool => is_string($line) && filled($line),
        ));
    }

    /** Whether the plan is highlighted as the recommended one. */
    public function isFeatured(): bool
    {
        return (bool) $this->rule('featured', false);
    }

    /** Whether the plan is offered at no cost (drives the landing's CTA copy). */
    public function isFree(): bool
    {
        return $this->price->minorAmount === 0;
    }

    /** Plans currently on offer, in catalog order. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /* -----------------------------------------------------------------
     | Activity coverage (which activities the plan includes)
     |
     | Hybrid model: a plan covers whole activity types via the
     | rules.included_types bag, and/or specific activities via the
     | activity_membership_plan pivot.
     * ----------------------------------------------------------------- */

    /** Specific activities explicitly included by this plan. */
    public function includedActivities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class)->withTimestamps();
    }

    /**
     * Activity types this plan covers wholesale.
     *
     * @return array<int, ActivityType>
     */
    public function includedTypes(): array
    {
        return collect((array) $this->rule('included_types', []))
            ->map(fn ($value) => $value instanceof ActivityType ? $value : ActivityType::tryFrom($value))
            ->filter()
            ->values()
            ->all();
    }

    /** Whether this plan grants access to the given activity. */
    public function coversActivity(Activity $activity): bool
    {
        if (in_array($activity->type, $this->includedTypes(), true)) {
            return true;
        }

        return $this->includedActivities()
            ->whereKey($activity->getKey())
            ->exists();
    }

    /** Query of every activity this plan covers (by type or specifically). */
    public function coveredActivities(): Builder
    {
        $typeValues = array_map(fn (ActivityType $type) => $type->value, $this->includedTypes());
        $planId = $this->getKey();

        return Activity::query()->where(function (Builder $query) use ($typeValues, $planId) {
            if ($typeValues !== []) {
                $query->whereIn('type', $typeValues);
            }

            $query->orWhereHas('membershipPlans', fn (Builder $q) => $q->whereKey($planId));
        });
    }
}
