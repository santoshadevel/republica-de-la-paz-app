<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\MembershipPlan;
use App\Models\Practitioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_renders_its_sections(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Un espacio para volver a habitarte')
            ->assertSee('Constitución de la República')
            ->assertSee('Derecho a la desconexión')
            ->assertSee('Ciudadanos de la República')
            ->assertSee('Preguntas frecuentes');
    }

    public function test_plans_and_prices_come_from_the_database(): void
    {
        MembershipPlan::create([
            'name' => 'Pase de prueba del test',
            'slug' => 'test-pass',
            'description' => 'Un pase inventado por el test.',
            'price' => 123456,
            'sort_order' => 1,
            'is_active' => true,
            'rules' => [
                'credits' => 4,
                'unlimited' => false,
                'validity_days' => 30,
                'features' => ['Beneficio inventado por el test'],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Pase de prueba del test')
            ->assertSee('Gs 123.456')
            ->assertSee('Beneficio inventado por el test');
    }

    public function test_inactive_plans_are_not_offered(): void
    {
        MembershipPlan::create([
            'name' => 'Pase retirado',
            'slug' => 'retired-pass',
            'price' => 999000,
            'sort_order' => 1,
            'is_active' => false,
            'rules' => ['credits' => 4],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Pase retirado');
    }

    public function test_a_free_plan_is_shown_without_a_price(): void
    {
        MembershipPlan::create([
            'name' => 'Clase de prueba del test',
            'slug' => 'test-free-trial',
            'price' => 0,
            'sort_order' => 1,
            'is_active' => true,
            'rules' => ['credits' => 1],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Sin costo')
            ->assertSee('Reservar mi clase gratuita');
    }

    public function test_active_practitioners_are_listed_with_their_disciplines(): void
    {
        $practitioner = Practitioner::create([
            'first_name' => 'Ana',
            'last_name' => 'Fernández',
            'email' => 'ana@example.test',
            'bio' => 'Acompaña procesos de respiración consciente.',
            'is_active' => true,
        ]);

        $yoga = Activity::create([
            'name' => 'Hatha Yoga',
            'type' => ActivityType::GroupClass,
            'is_active' => true,
        ]);

        $practitioner->activities()->attach($yoga);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ana Fernández')
            ->assertSee('Hatha Yoga')
            ->assertSee('Acompaña procesos de respiración consciente.');
    }

    public function test_inactive_practitioners_are_hidden(): void
    {
        Practitioner::create([
            'first_name' => 'Ex',
            'last_name' => 'Colaborador',
            'email' => 'ex@example.test',
            'is_active' => false,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Ex Colaborador');
    }

    public function test_individual_sessions_are_listed_as_acompanamientos(): void
    {
        Activity::create([
            'name' => 'Reiki',
            'type' => ActivityType::IndividualSession,
            'description' => 'Sesión individual de energía.',
            'default_duration_minutes' => 60,
            'is_active' => true,
        ]);

        // A group class must not leak into the individual-sessions grid.
        Activity::create([
            'name' => 'Vinyasa Grupal',
            'type' => ActivityType::GroupClass,
            'is_active' => true,
        ]);

        $response = $this->get('/')->assertOk();

        $response->assertSee('Reiki');
        $response->assertSee('Sesión individual de energía.');
        $response->assertDontSee('Vinyasa Grupal');
    }

    public function test_the_whatsapp_link_uses_the_configured_number(): void
    {
        config(['contact.whatsapp' => '+595 981 000-111']);

        $this->get('/')
            ->assertOk()
            // Punctuation is stripped: wa.me only accepts digits.
            ->assertSee('https://wa.me/595981000111', escape: false);
    }

    public function test_contact_channels_left_blank_are_hidden(): void
    {
        config([
            'contact.whatsapp' => null,
            'contact.instagram' => null,
            'contact.map_embed_url' => null,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('wa.me')
            ->assertDontSee('instagram.com')
            ->assertDontSee('<iframe', escape: false);
    }
}
