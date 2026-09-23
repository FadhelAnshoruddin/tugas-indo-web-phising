<?php

namespace Tests\Feature;

use App\Models\Prize;
use App\Models\SpinHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_redirects_to_the_spin_wheel(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/spin-wheel');
    }

    public function test_guest_can_spin_and_authenticated_user_can_claim_the_prize(): void
    {
        Prize::create(['name' => 'Hadiah Uji', 'color' => '#ef8354', 'weight' => 1]);

        $spin = $this->postJson('/spin-wheel/spin');
        $spin->assertOk()->assertJsonStructure(['winner_index', 'prize', 'history_id']);

        $history = SpinHistory::findOrFail($spin->json('history_id'));
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/spin-wheel/claim/{$history->id}")
            ->assertOk()
            ->assertJson(['message' => 'Hadiah berhasil diklaim!']);

        $this->assertDatabaseHas('spin_histories', [
            'id' => $history->id,
            'user_id' => $user->id,
            'is_claimed' => true,
        ]);
    }
}
