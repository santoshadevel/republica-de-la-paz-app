<?php

namespace App\Models;

use App\Enums\ActivityType;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Catalog entry for a practice/class/event that can be scheduled later. */
#[Fillable([
    'name',
    'type',
    'description',
    'default_duration_minutes',
    'color',
    'default_room_id',
    'is_active',
])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'default_duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Activities currently on offer, in a stable order. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('name');
    }

    /** Narrow the catalog to a single kind of activity. */
    public function scopeOfType(Builder $query, ActivityType $type): Builder
    {
        return $query->where('type', $type);
    }

    /** Default room where this activity usually takes place. */
    public function defaultRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'default_room_id');
    }

    /** Plans that include this activity specifically (via the pivot). */
    public function membershipPlans(): BelongsToMany
    {
        return $this->belongsToMany(MembershipPlan::class)->withTimestamps();
    }

    /** Practitioners who lead this activity (specialty pivot). */
    public function practitioners(): BelongsToMany
    {
        return $this->belongsToMany(Practitioner::class)->withTimestamps();
    }
}
