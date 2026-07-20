<?php

namespace Database\Seeders;

use App\Actions\Accounting\RecordTransaction;
use App\Actions\Accounting\RecordTransfer;
use App\Actions\Appointments\BookAppointment;
use App\Actions\Bookings\BookSession;
use App\Actions\Events\RegisterForEvent;
use App\Actions\Memberships\SellMembership;
use App\Enums\ActivityType;
use App\Enums\AppointmentStatus;
use App\Enums\EventStatus;
use App\Enums\FeeType;
use App\Enums\Role;
use App\Enums\SessionStatus;
use App\Enums\TransactionType;
use App\Enums\Weekday;
use App\Exceptions\AppointmentException;
use App\Exceptions\BookingException;
use App\Exceptions\EventException;
use App\Models\Account;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\CostCenter;
use App\Models\Event;
use App\Models\FeeScheme;
use App\Models\MembershipPlan;
use App\Models\PaymentMethod;
use App\Models\Practitioner;
use App\Models\PractitionerAvailability;
use App\Models\PractitionerAvailabilityException;
use App\Models\Room;
use App\Models\ScheduledSession;
use App\Models\Student;
use App\Models\StudentMembership;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Full connected demo dataset, built through the real Actions so the numbers add
 * up (sales record income, bookings consume credits, etc.). Runs after the
 * catalog seeders. Idempotent-ish: skips if demo data already exists.
 */
class DemoSeeder extends Seeder
{
    /** Marks the fichas this seeder owns, so backfills never touch real students. */
    private const DEMO_EMAIL_DOMAIN = 'demo.test';

    /** Password for every demo portal login. */
    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        // Idempotent and independent of the demo guard below, so it also backfills
        // availability on an already-seeded database.
        $this->seedAvailability();

        if (StudentMembership::query()->exists()) {
            $this->seedStudentLogins(); // backfills logins on an already-seeded database

            return; // demo already seeded
        }

        $students = $this->seedStudents();
        $this->seedStudentLogins();
        $this->sellMemberships($students);
        $sessions = $this->seedGroupSessions();
        $this->bookSessions($students, $sessions);
        $this->seedAppointments($students);
        $this->seedEvents($students);
        $this->seedExpenses();
        $this->seedTransfers();
        $this->seedFeeSchemes();
    }

    private function seedAvailability(): void
    {
        // Every practitioner works Mon/Wed/Fri, mornings and late afternoons.
        // Tue/Thu/weekends stay unavailable, so recurring generation there is skipped.
        $blocks = [
            [Weekday::Monday, '09:00', '13:00'],
            [Weekday::Monday, '16:00', '20:00'],
            [Weekday::Wednesday, '09:00', '13:00'],
            [Weekday::Wednesday, '16:00', '20:00'],
            [Weekday::Friday, '09:00', '13:00'],
        ];

        foreach (Practitioner::all() as $practitioner) {
            foreach ($blocks as [$day, $start, $end]) {
                PractitionerAvailability::updateOrCreate(
                    ['practitioner_id' => $practitioner->id, 'day_of_week' => $day->value, 'start_time' => $start],
                    ['end_time' => $end],
                );
            }
        }

        // One demo closed day (holiday) for the first practitioner.
        $first = Practitioner::orderBy('id')->first();
        if ($first !== null) {
            PractitionerAvailabilityException::updateOrCreate(
                ['practitioner_id' => $first->id, 'date' => now()->addWeeks(2)->startOfWeek()->toDateString()],
                ['is_available' => false, 'reason' => 'Feriado (demo)'],
            );
        }
    }

    private function seedFeeSchemes(): void
    {
        // Fixed amount per group class as each practitioner's default, plus a few
        // percentage rules for specific therapies. Idempotent by practitioner+activity.
        $activities = Activity::whereIn('name', ['Tarot', 'Reiki', 'KAP', 'Diseño Humano'])
            ->get()->keyBy('name');

        foreach (Practitioner::all() as $practitioner) {
            FeeScheme::updateOrCreate(
                ['practitioner_id' => $practitioner->id, 'activity_id' => null],
                ['type' => FeeType::FixedPerSession->value, 'fixed_amount' => 80_000],
            );
        }

        $percentages = [
            ['Valentina', 'Tarot', 80],
            ['Camila', 'Reiki', 70],
            ['Camila', 'KAP', 70],
            ['Mateo', 'Diseño Humano', 60],
        ];

        foreach ($percentages as [$firstName, $activityName, $percent]) {
            $practitioner = Practitioner::where('first_name', $firstName)->first();
            $activity = $activities->get($activityName);

            if ($practitioner && $activity) {
                FeeScheme::updateOrCreate(
                    ['practitioner_id' => $practitioner->id, 'activity_id' => $activity->id],
                    ['type' => FeeType::Percentage->value, 'percentage' => $percent],
                );
            }
        }
    }

    /** @return Collection<int, Student> */
    private function seedStudents(): Collection
    {
        $sources = ['instagram', 'facebook', 'google', 'referral', 'event', 'walk_in'];

        return collect(range(1, 16))->map(fn (int $i) => Student::firstOrCreate(
            ['email' => "alumno{$i}@".self::DEMO_EMAIL_DOMAIN],
            [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'phone' => fake()->numerify('09## ######'),
                'acquisition_source' => fake()->randomElement($sources),
                'goals' => fake()->optional()->sentence(),
                'is_active' => true,
            ],
        ));
    }

    /**
     * Portal login for each demo ficha, so the student portal can be exercised
     * without going through registration + email verification by hand. Idempotent,
     * and runs outside the demo guard so it also backfills an already-seeded database.
     */
    private function seedStudentLogins(): void
    {
        $students = Student::query()->where('email', 'like', '%@'.self::DEMO_EMAIL_DOMAIN)->get();

        foreach ($students as $student) {
            $user = User::firstOrCreate(
                ['email' => $student->email],
                [
                    'name' => $student->fullName(),
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([Role::Student->value]);

            Student::registerFrom($user, $student->fullName());
        }
    }

    /** @param Collection<int, Student> $students */
    private function sellMemberships(Collection $students): void
    {
        $plans = MembershipPlan::whereIn('slug', ['citizen-pass', 'community-pass', 'republic-membership'])
            ->get()->keyBy('slug');
        $methods = PaymentMethod::all();
        $sell = app(SellMembership::class);

        foreach ($students as $i => $student) {
            $slug = match (true) {
                $i % 5 === 0 => 'republic-membership',
                $i % 2 === 0 => 'community-pass',
                default => 'citizen-pass',
            };
            $sell->execute(
                $student,
                $plans[$slug],
                now()->subDays(fake()->numberBetween(0, 15)),
                $methods->random(),
            );
        }
    }

    /** @return Collection<int, ScheduledSession> */
    private function seedGroupSessions(): Collection
    {
        $activities = Activity::where('type', ActivityType::GroupClass->value)->get();
        $practitioners = Practitioner::all();
        $rooms = Room::whereIn('name', ['Sala Principal', 'Sala Secundaria'])->get();
        $sessions = collect();

        foreach (range(1, 12) as $dayOffset) {
            foreach ([9, 18] as $hour) {
                $startsAt = now()->addDays($dayOffset)->setTime($hour, 0);
                $sessions->push(ScheduledSession::create([
                    'activity_id' => $activities->random()->id,
                    'practitioner_id' => $practitioners->random()->id,
                    'room_id' => $rooms->random()->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addHour(),
                    'capacity' => fake()->numberBetween(8, 15),
                    'status' => SessionStatus::Scheduled,
                ]));
            }
        }

        return $sessions;
    }

    /**
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, ScheduledSession>  $sessions
     */
    private function bookSessions(Collection $students, Collection $sessions): void
    {
        $book = app(BookSession::class);

        foreach ($sessions as $session) {
            foreach ($students->random(fake()->numberBetween(3, 8)) as $student) {
                try {
                    $book->execute($student, $session);
                } catch (BookingException) {
                    // no balance / already booked / full — expected, skip.
                }
            }
        }
    }

    /** @param Collection<int, Student> $students */
    private function seedAppointments(Collection $students): void
    {
        $activities = Activity::where('type', ActivityType::IndividualSession->value)->get();
        $practitioners = Practitioner::all();
        $consultorio = Room::where('name', 'Consultorio')->first();
        $book = app(BookAppointment::class);

        foreach ($practitioners as $practitioner) {
            foreach (range(1, 6) as $slot) {
                $startsAt = now()->addDays(fake()->numberBetween(1, 10))->setTime(fake()->numberBetween(9, 17), 0);
                $appointment = Appointment::create([
                    'practitioner_id' => $practitioner->id,
                    'activity_id' => $activities->random()->id,
                    'room_id' => $consultorio?->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addHour(),
                    'status' => AppointmentStatus::Available,
                    'price' => fake()->numberBetween(120_000, 250_000),
                ]);

                if (fake()->boolean(60)) {
                    try {
                        $book->execute($students->random(), $appointment);
                    } catch (AppointmentException) {
                        // slot not bookable — skip.
                    }
                }
            }
        }
    }

    /** @param Collection<int, Student> $students */
    private function seedEvents(Collection $students): void
    {
        $practitioners = Practitioner::all();
        $names = ['Círculo de mujeres', 'Taller de respiración', 'Retiro de fin de semana', 'Charla de bienestar'];
        $register = app(RegisterForEvent::class);

        foreach ($names as $i => $name) {
            $startsAt = now()->addDays(($i + 1) * 5)->setTime(19, 0);
            $event = Event::create([
                'name' => $name,
                'description' => fake()->paragraph(),
                'location' => 'Sala Principal',
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addHours(2),
                'price' => fake()->numberBetween(80_000, 200_000),
                'capacity' => fake()->numberBetween(10, 25),
                'status' => EventStatus::Scheduled,
            ]);
            $event->facilitators()->sync($practitioners->random(fake()->numberBetween(1, 2))->pluck('id'));

            foreach ($students->random(fake()->numberBetween(4, 9)) as $student) {
                try {
                    $register->execute($student, $event);
                } catch (EventException) {
                    // already registered / full — skip.
                }
            }
        }
    }

    private function seedExpenses(): void
    {
        $record = app(RecordTransaction::class);
        $method = PaymentMethod::where('name', 'Transferencia bancaria')->first();

        $expenses = [
            ['Infraestructura', 'Alquiler', 'Administración', 3_500_000, 'Alquiler del local'],
            ['Infraestructura', 'Electricidad', 'Administración', 650_000, 'Factura de electricidad'],
            ['Honorarios', 'Profesores', 'Yoga', 2_800_000, 'Honorarios de profesores'],
            ['Honorarios', 'Terapeutas', 'Terapias', 1_900_000, 'Honorarios de terapeutas'],
            ['Marketing', 'Redes sociales', 'Administración', 450_000, 'Gestión de redes'],
            ['Gastos bancarios', 'Comisiones', 'Administración', 180_000, 'Comisiones POS/Bancard'],
        ];

        foreach ($expenses as [$parentName, $childName, $centerName, $amount, $description]) {
            $category = Category::query()->expense()
                ->whereHas('parent', fn ($q) => $q->where('name', $parentName))
                ->where('name', $childName)
                ->first();
            $costCenter = CostCenter::where('name', $centerName)->first();

            $record->execute(
                type: TransactionType::Expense,
                amount: Money::ofMinor($amount),
                category: $category,
                costCenter: $costCenter,
                paymentMethod: $method,
                attributes: [
                    'description' => $description,
                    'occurred_on' => now()->subDays(fake()->numberBetween(0, 20))->toDateString(),
                ],
            );
        }
    }

    private function seedTransfers(): void
    {
        $cashBox = Account::where('name', 'Caja chica')->first();
        $bank = Account::where('name', 'Cuenta Banco 0082')->first();

        if ($cashBox && $bank) {
            app(RecordTransfer::class)->execute(
                $cashBox,
                $bank,
                Money::ofMinor(500_000),
                [
                    'description' => 'Depósito de caja al banco',
                    'occurred_on' => now()->subDays(3)->toDateString(),
                ],
            );
        }
    }
}
