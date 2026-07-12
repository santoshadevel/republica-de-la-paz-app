<?php

namespace App\Models;

use App\Enums\RoomType;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A room/space (physical or virtual) where activities take place. */
#[Fillable(['name', 'type', 'capacity', 'meeting_url', 'color', 'description', 'is_active'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => RoomType::class,
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Whether this is a virtual (online) room. */
    public function isVirtual(): bool
    {
        return $this->type === RoomType::Virtual;
    }

    /** Activities that use this room as their default location. */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'default_room_id');
    }
}
