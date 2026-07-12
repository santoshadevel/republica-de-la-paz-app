<?php

namespace App\Models;

use App\Enums\ActivityType;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /** Default room where this activity usually takes place. */
    public function defaultRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'default_room_id');
    }
}
