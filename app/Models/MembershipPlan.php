<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Database\Factories\MembershipPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
