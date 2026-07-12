<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\Role;
use App\Filament\Resources\Activities\Pages\CreateActivity;
use App\Filament\Resources\Activities\Pages\EditActivity;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\MembershipPlans\Pages\CreateMembershipPlan;
use App\Filament\Resources\MembershipPlans\Pages\EditMembershipPlan;
use App\Filament\Resources\MembershipPlans\Pages\ListMembershipPlans;
use App\Filament\Resources\Practitioners\Pages\CreatePractitioner;
use App\Filament\Resources\Practitioners\Pages\EditPractitioner;
use App\Filament\Resources\Practitioners\Pages\ListPractitioners;
use App\Filament\Resources\Rooms\Pages\CreateRoom;
use App\Filament\Resources\Rooms\Pages\EditRoom;
use App\Filament\Resources\Rooms\Pages\ListRooms;
use App\Models\Activity;
use App\Models\MembershipPlan;
use App\Models\Practitioner;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class CoreCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_admin_can_list_each_core_resource(): void
    {
        Room::factory()->count(2)->create();
        Practitioner::factory()->count(2)->create();
        Activity::factory()->count(2)->create();
        MembershipPlan::factory()->count(2)->create();

        $admin = $this->admin();

        Livewire::actingAs($admin)->test(ListRooms::class)->assertOk();
        Livewire::actingAs($admin)->test(ListPractitioners::class)->assertOk();
        Livewire::actingAs($admin)->test(ListActivities::class)->assertOk();
        Livewire::actingAs($admin)->test(ListMembershipPlans::class)->assertOk();
    }

    public function test_plan_seeder_creates_the_four_plans_with_rules(): void
    {
        $this->seed(PlanSeeder::class);

        $this->assertSame(4, MembershipPlan::count());

        $republic = MembershipPlan::where('slug', 'republic-membership')->firstOrFail();
        $this->assertTrue($republic->isUnlimited());
        $this->assertNull($republic->credits());
        $this->assertSame(30, $republic->validityDays());

        $citizen = MembershipPlan::where('slug', 'citizen-pass')->firstOrFail();
        $this->assertFalse($citizen->isUnlimited());
        $this->assertSame(4, $citizen->credits());
    }

    public function test_plan_price_is_stored_in_minor_units_and_formats_via_config(): void
    {
        // 200000 minor units in PYG (0 decimals) => "Gs 200.000".
        $plan = MembershipPlan::factory()->create(['price' => 200000]);

        $this->assertSame(200000, $plan->getRawOriginal('price'));
        $this->assertSame('Gs 200.000', (string) $plan->price);
    }

    public function test_activity_type_defaults_to_group_class(): void
    {
        $activity = Activity::factory()->create();

        $this->assertSame(ActivityType::GroupClass, $activity->type);
        $this->assertSame('Práctica grupal', $activity->type->label());
    }

    public function test_create_pages_mount_their_form_schemas(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(CreateRoom::class)->assertOk();
        Livewire::actingAs($admin)->test(CreatePractitioner::class)->assertOk();
        Livewire::actingAs($admin)->test(CreateActivity::class)->assertOk();
        Livewire::actingAs($admin)->test(CreateMembershipPlan::class)->assertOk();
    }

    public function test_edit_pages_load_an_existing_record(): void
    {
        $admin = $this->admin();

        $room = Room::factory()->create();
        $practitioner = Practitioner::factory()->create();
        $activity = Activity::factory()->create();
        $plan = MembershipPlan::factory()->create(['price' => 200000]);

        Livewire::actingAs($admin)->test(EditRoom::class, ['record' => $room->getKey()])->assertOk();
        Livewire::actingAs($admin)->test(EditPractitioner::class, ['record' => $practitioner->getKey()])->assertOk();
        Livewire::actingAs($admin)->test(EditActivity::class, ['record' => $activity->getKey()])->assertOk();
        // Regression: Money attribute must not break the numeric price field.
        Livewire::actingAs($admin)->test(EditMembershipPlan::class, ['record' => $plan->getKey()])
            ->assertOk()
            ->assertFormSet(['price' => 200000.0]);
    }

    public function test_membership_plan_auto_generates_a_unique_slug_from_the_name(): void
    {
        $a = MembershipPlan::create(['name' => 'Pase Test', 'price' => 0]);
        $b = MembershipPlan::create(['name' => 'Pase Test', 'price' => 0]);

        $this->assertSame('pase-test', $a->slug);
        $this->assertSame('pase-test-2', $b->slug);
    }
}
