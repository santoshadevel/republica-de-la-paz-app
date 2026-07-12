<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Practitioners\Pages\CreatePractitioner;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Models\Activity;
use App\Models\Practitioner;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_student_persists_crm_fields(): void
    {
        $student = Student::factory()->create([
            'tax_id' => '80012345-6',
            'acquisition_source' => 'referral',
            'goals' => 'Reducir el estrés y mejorar la postura.',
        ]);

        $student->refresh();

        $this->assertSame('80012345-6', $student->tax_id);
        $this->assertSame('referral', $student->acquisition_source);
        $this->assertSame('Reducir el estrés y mejorar la postura.', $student->goals);
    }

    public function test_practitioner_and_activity_share_a_specialties_pivot(): void
    {
        $practitioner = Practitioner::factory()->create();
        $yoga = Activity::factory()->create(['name' => 'Yoga']);
        $reiki = Activity::factory()->create(['name' => 'Reiki']);

        $practitioner->activities()->attach([$yoga->id, $reiki->id]);

        $this->assertEqualsCanonicalizing(
            [$yoga->id, $reiki->id],
            $practitioner->activities()->pluck('activities.id')->all(),
        );
        // And the inverse side resolves back to the practitioner.
        $this->assertTrue($yoga->practitioners->contains($practitioner));
    }

    public function test_student_form_saves_crm_fields(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(CreateStudent::class)
            ->fillForm([
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'email' => 'juan.perez@example.test',
                'tax_id' => '80099999-1',
                'acquisition_source' => 'instagram',
                'goals' => 'Practicar de forma constante.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('students', [
            'email' => 'juan.perez@example.test',
            'tax_id' => '80099999-1',
            'acquisition_source' => 'instagram',
        ]);
    }

    public function test_practitioner_form_saves_specialties(): void
    {
        $admin = $this->admin();
        $yoga = Activity::factory()->create(['name' => 'Yoga']);

        Livewire::actingAs($admin)->test(CreatePractitioner::class)
            ->fillForm([
                'first_name' => 'Eloisa',
                'last_name' => 'Carmona',
                'email' => 'eloisa@example.test',
                'activities' => [$yoga->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $practitioner = Practitioner::where('email', 'eloisa@example.test')->firstOrFail();
        $this->assertTrue($practitioner->activities->contains($yoga));
    }
}
