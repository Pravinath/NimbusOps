<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleTechnicianAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_google_callback_creates_restricted_applicant_and_exchanges_once(): void
    {
        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-applicant-123',
            'name' => 'Google Applicant',
            'email' => 'google.applicant@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ]));

        $callback = $this->get('/auth/technician/google/callback');
        $callback->assertRedirectContains('technician_oauth_code=');

        parse_str(parse_url($callback->headers->get('Location'), PHP_URL_QUERY), $query);

        $exchange = $this->postJson('/api/auth/technician/google/exchange', [
            'code' => $query['technician_oauth_code'],
        ]);

        $exchange->assertOk()
            ->assertJsonPath('user.role', 'technician_applicant')
            ->assertJsonStructure(['user', 'token']);

        $this->postJson('/api/auth/technician/google/exchange', [
            'code' => $query['technician_oauth_code'],
        ])->assertUnprocessable();

        $this->assertDatabaseHas('users', [
            'google_id' => 'google-applicant-123',
            'role' => 'technician_applicant',
            'auth_provider' => 'google',
        ]);
    }

    public function test_customer_google_account_cannot_enter_technician_portal(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'role' => 'customer',
            'status' => 'active',
        ]);

        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'customer-google-id',
            'name' => 'Existing Customer',
            'email' => 'customer@example.com',
        ]));

        $this->get('/auth/technician/google/callback')
            ->assertRedirectContains('technician_error=');

        $this->assertDatabaseMissing('users', [
            'email' => 'customer@example.com',
            'google_id' => 'customer-google-id',
        ]);
    }
}
