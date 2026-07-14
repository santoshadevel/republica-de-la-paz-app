<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Portal\Dashboard;
use App\Models\Booking;
use App\Models\ScheduledSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use RuntimeException;
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

    /** The signed link the framework mails out on registration. */
    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->getKey(),
            'hash' => sha1($user->email),
        ]);
    }

    public function test_a_visitor_can_register_as_a_student(): void
    {
        Notification::fake();

        Livewire::test(Register::class)
            ->set('name', 'Ana Pérez')
            ->set('email', 'ana@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertTrue($user->isStudent());
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_verifying_the_email_creates_the_ficha(): void
    {
        $user = User::registerStudent('Ana Pérez', 'ana@example.com', 'password123');
        $this->assertNull($user->student);

        $this->actingAs($user)->get($this->verificationUrl($user))
            ->assertRedirect(route('portal.dashboard'));

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('Ana', $user->student->first_name);
        $this->assertSame('Pérez', $user->student->last_name);
    }

    /**
     * The v1 hole: reception loads a ficha with the student's credits and history,
     * and whoever registers that address inherits it. The ficha must stay detached
     * until the address is proven.
     */
    public function test_registration_does_not_touch_an_existing_ficha_until_verified(): void
    {
        $ficha = Student::factory()->create(['email' => 'zoe@example.com', 'user_id' => null]);

        Livewire::test(Register::class)
            ->set('name', 'Impostor')
            ->set('email', 'zoe@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $user = User::where('email', 'zoe@example.com')->firstOrFail();
        $this->assertNull($user->student);
        $this->assertNull($ficha->fresh()->user_id);

        // ...and the portal stays shut while unverified.
        $this->actingAs($user)->get('/portal')->assertRedirect(route('verification.notice'));
    }

    public function test_verification_links_the_existing_ficha_instead_of_duplicating_it(): void
    {
        $ficha = Student::factory()->create(['email' => 'zoe@example.com', 'user_id' => null]);
        $user = User::registerStudent('Zoe G', 'zoe@example.com', 'password123');

        $this->actingAs($user)->get($this->verificationUrl($user));

        $this->assertSame($ficha->id, $user->fresh()->student->id);
        $this->assertSame(1, Student::where('email', 'zoe@example.com')->count());
    }

    /**
     * A ficha's email can drift from its owner's login (staff edit it from the
     * panel), so `users.email` being unique is not enough on its own: registering
     * the ficha's address must still never hand the ficha over.
     */
    public function test_a_ficha_already_owned_by_another_account_is_never_reassigned(): void
    {
        $owner = User::registerStudent('Zoe G', 'owner@example.com', 'password123');
        $ficha = Student::factory()->create(['email' => 'zoe@example.com', 'user_id' => $owner->id]);

        $intruder = User::registerStudent('Impostor', 'zoe@example.com', 'password123');

        try {
            Student::registerFrom($intruder, 'Impostor');
            $this->fail('Expected the ficha takeover to be rejected.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('ya pertenece a otra cuenta', $e->getMessage());
        }

        $this->assertSame($owner->id, $ficha->fresh()->user_id);
    }

    /** Reception soft-deletes fichas; the email stays unique across trashed rows. */
    public function test_verification_reclaims_a_soft_deleted_ficha_instead_of_colliding(): void
    {
        $ficha = Student::factory()->create(['email' => 'zoe@example.com', 'user_id' => null]);
        $ficha->delete();

        $user = User::registerStudent('Zoe G', 'zoe@example.com', 'password123');

        $this->actingAs($user)->get($this->verificationUrl($user))
            ->assertRedirect(route('portal.dashboard'));

        $this->assertSame($ficha->id, $user->fresh()->student->id);
        $this->assertNull($ficha->fresh()->deleted_at);
        $this->assertSame(1, Student::withTrashed()->where('email', 'zoe@example.com')->count());
    }

    /**
     * If attaching the ficha fails, the verification must roll back with it —
     * otherwise the account is verified, fichaless, and the link is spent (the
     * framework never re-fires verification for an already-verified address).
     */
    public function test_a_failed_ficha_link_rolls_the_verification_back_and_stays_retryable(): void
    {
        $owner = User::registerStudent('Zoe G', 'owner@example.com', 'password123');
        Student::factory()->create(['email' => 'zoe@example.com', 'user_id' => $owner->id]);

        $intruder = User::registerStudent('Impostor', 'zoe@example.com', 'password123');

        try {
            $this->actingAs($intruder)->get($this->verificationUrl($intruder));
        } catch (RuntimeException) {
            // The route surfaces the invariant; what matters is the state below.
        }

        $this->assertFalse($intruder->fresh()->hasVerifiedEmail());
        $this->assertNull($intruder->fresh()->student);
    }

    public function test_the_verification_notice_renders_and_resends_the_link(): void
    {
        Notification::fake();
        $user = User::registerStudent('Ana', 'ana@example.com', 'password123');

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('ana@example.com');

        Livewire::actingAs($user)->test(VerifyEmail::class)->call('resend');

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_resending_the_verification_link_is_throttled(): void
    {
        Notification::fake();
        $user = User::registerStudent('Ana', 'ana@example.com', 'password123');

        $component = Livewire::actingAs($user)->test(VerifyEmail::class);
        foreach (range(1, 4) as $ignored) {
            $component->call('resend');
        }

        // Fourth attempt is refused rather than mailed.
        Notification::assertSentToTimes($user, VerifyEmailNotification::class, 3);
    }

    public function test_a_verified_student_is_sent_on_to_the_portal(): void
    {
        $user = User::factory()->create(); // factory verifies by default
        $user->assignRole(Role::Student->value);

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertRedirect(route('portal.dashboard'));
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
