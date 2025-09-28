<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneApiControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_update_phone_number()
    {
        $user = User::factory()->create();

        $phoneData = [
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ];

        $response = $this->actingAs($user)->post('/api/phone/update', $phoneData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'phone',
                    'phone_country',
                    'updated_at',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_phone_info()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->get('/api/phone/info');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'phone',
                    'phone_country',
                    'is_verified',
                    'verification_status',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_verify_phone_number()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $verificationData = [
            'verification_code' => '123456',
        ];

        $response = $this->actingAs($user)->post('/api/phone/verify', $verificationData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'phone',
                    'is_verified',
                    'verified_at',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_send_verification_code()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->post('/api/phone/send-verification');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'phone',
                    'code_sent',
                    'expires_at',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_resend_verification_code()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->post('/api/phone/resend-verification');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'phone',
                    'code_sent',
                    'expires_at',
                    'attempts_remaining',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_remove_phone_number()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->delete('/api/phone/remove');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'phone_removed',
                    'removed_at',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => null,
            'phone_country' => null,
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_phone_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/phone/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_phones',
                    'verified_phones',
                    'unverified_phones',
                    'verification_rate',
                    'country_distribution',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_validate_phone_number()
    {
        $user = User::factory()->create();

        $validationData = [
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ];

        $response = $this->actingAs($user)->post('/api/phone/validate', $validationData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'phone',
                    'is_valid',
                    'formatted_phone',
                    'country_code',
                    'national_number',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_phone_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/phone/history');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'history' => [
                        '*' => [
                            'id',
                            'phone',
                            'action',
                            'timestamp',
                            'ip_address',
                            'user_agent',
                        ],
                    ],
                    'total_count',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_verification_attempts()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->get('/api/phone/verification-attempts');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'attempts' => [
                        '*' => [
                            'id',
                            'phone',
                            'attempted_at',
                            'success',
                            'ip_address',
                        ],
                    ],
                    'total_attempts',
                    'successful_attempts',
                    'failed_attempts',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_phone_security_info()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->get('/api/phone/security');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'phone',
                    'is_verified',
                    'security_level',
                    'two_factor_enabled',
                    'backup_codes',
                    'last_verification',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_enable_two_factor_authentication()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->post('/api/phone/enable-2fa');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'two_factor_enabled',
                    'backup_codes',
                    'qr_code',
                    'secret_key',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_disable_two_factor_authentication()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->post('/api/phone/disable-2fa');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'two_factor_disabled',
                    'disabled_at',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_backup_codes()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->get('/api/phone/backup-codes');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'backup_codes' => [
                        '*' => [
                            'code',
                            'used',
                            'created_at',
                        ],
                    ],
                    'total_codes',
                    'unused_codes',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_regenerate_backup_codes()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->post('/api/phone/regenerate-backup-codes');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'new_backup_codes',
                    'regenerated_at',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->post('/api/phone/update', [
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_phone_number_format()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/phone/update', [
            'phone' => 'invalid-phone',
            'phone_country' => 'US',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /**
     * @test
     */
    public function it_validates_country_code()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/phone/update', [
            'phone' => '+1234567890',
            'phone_country' => 'INVALID',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone_country']);
    }

    /**
     * @test
     */
    public function it_validates_verification_code()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->post('/api/phone/verify', [
            'verification_code' => 'invalid',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['verification_code']);
    }

    /**
     * @test
     */
    public function it_handles_verification_code_expiry()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->post('/api/phone/verify', [
            'verification_code' => '123456',
        ]);

        $response
            ->assertStatus(400)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_handles_rate_limiting()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        // Send multiple verification requests quickly
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)->post('/api/phone/send-verification');
        }

        $response->assertStatus(429);
    }

    /**
     * @test
     */
    public function it_handles_invalid_verification_code()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this->actingAs($user)->post('/api/phone/verify', [
            'verification_code' => '999999',
        ]);

        $response
            ->assertStatus(400)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_handles_phone_number_already_exists()
    {
        $user1 = User::factory()->create([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $user2 = User::factory()->create();

        $response = $this->actingAs($user2)->post('/api/phone/update', [
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response
            ->assertStatus(409)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }
}
