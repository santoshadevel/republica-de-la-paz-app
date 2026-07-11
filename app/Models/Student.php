<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A client/member of the center. The record is unique by email; identity_number
 * is an optional generic document (white-label friendly).
 */
#[Fillable([
    'first_name',
    'last_name',
    'email',
    'phone',
    'identity_number',
    'birth_date',
    'notes',
    'is_active',
])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** Full display name. */
    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
