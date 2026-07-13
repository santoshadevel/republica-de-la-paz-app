<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Portal\Dashboard;
use App\Models\Booking;
use App\Models\ScheduledSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (Role::values() as $role) {
            SpatieRole::findOrCreate($role, 'web');
        }
    }

    public function test_a_visitor_can_register_as_a_student(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Ana Pérez')
            ->set('email', 'ana@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('portal.dashboard'));

        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertTrue($user->isStudent());
        $this->assertNotNull($user->student);
        $this->assertSame('Ana', $user->student->first_name);
        $this->assertSame('Pérez', $user->student->last_name);
    }

    public function test_registration_links_to_an_existing_ficha_by_email(): void
    {
        $ficha = Student::factory()->create(['email' => 'zoe@example.com', 'user_id' => null]);

        Livewire::test(Register::class)
            ->set('name', 'Zoe G')
            ->set('email', 'zoe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $user = User::where('email', 'zoe@example.com')->firstOrFail();
        $this->assertSame($ficha->id, $user->student->id); // linked, not duplicated
        $this->assertSame(1, Student::where('email', 'zoe@example.com')->count());
    }

    public function test_a_student_can_log_in(): void
    {
        $user = User::registerStudent('Zoe', 'zoe@example.com', 'password123');
        Student::registerFrom($user, 'Zoe');

        Livewire::test(Login::class)
            ->set('email', 'zoe@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('portal.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_portal_is_gated(): void
    {
        // Guest → redirected to login.
        $this->get('/portal')->assertRedirect(route('login'));

        // Staff (non-student) → forbidden.
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);
        $this->actingAs($admin)->get('/portal')->assertForbidden();
    }

    public function test_portal_shows_the_student_agenda(): void
    {
        $user = User::registerStudent('Zoe', 'zoe@example.com', 'password123');
        $student = Student::registerFrom($user, 'Zoe');

        $session = ScheduledSession::factory()->create([
            'starts_at' => now()->addDays(2)->setTime(9, 0),
            'ends_at' => now()->addDays(2)->setTime(10, 0),
        ]);
        Booking::factory()->create([
            'student_id' => $student->id,
            'scheduled_session_id' => $session->id,
        ]);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertOk()
            ->assertSee($session->activity->name);
    }
}
