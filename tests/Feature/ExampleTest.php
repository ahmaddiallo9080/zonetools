<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_accueil_redirige_vers_le_tableau_de_bord(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_le_tableau_de_bord_exige_une_connexion(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertSee('Tableau de bord');
    }
}
