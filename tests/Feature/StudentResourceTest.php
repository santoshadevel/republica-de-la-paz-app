<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class StudentResourceTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(Role $role): User
    {
        SpatieRole::findOrCreate($role->value, 'web');
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    public function test_staff_can_list_students(): void
    {
        Student::factory()->count(3)->create();

        Livewire::actingAs($this->staffUser(Role::Admin))
            ->test(ListStudents::class)
            ->assertOk()
            ->assertCanSeeTableRecords(Student::all());
    }

    public function test_a_student_role_cannot_access_the_admin_panel(): void
    {
        $user = $this->staffUser(Role::Student);

        $this->assertFalse($user->canAccessPanel(filament()->getDefaultPanel()));
    }

    public function test_email_must_be_unique_when_creating_a_student(): void
    {
        Student::factory()->create(['email' => 'taken@example.com']);

        Livewire::actingAs($this->staffUser(Role::Admin))
            ->test(ListStudents::class)
            ->callAction('create', data: [
                'first_name' => 'Ana',
                'last_name' => 'García',
                'email' => 'taken@example.com',
            ])
            ->assertHasActionErrors(['email']);
    }

    public function test_identity_number_is_optional_and_may_repeat_when_null(): void
    {
        Student::factory()->create(['identity_number' => null]);
        Student::factory()->create(['identity_number' => null]);

        $this->assertSame(2, Student::whereNull('identity_number')->count());
    }
}
