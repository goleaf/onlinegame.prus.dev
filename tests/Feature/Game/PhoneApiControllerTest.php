<?php

namespace Tests\Feature\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PhoneApiControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function it_can_update_user_phone()
    {
        $phoneData = [
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/api/phone/update', $phoneData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'phone',
                    'phone_country',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($phoneData['phone'], $response->json('data.phone'));
        $this->assertEquals($phoneData['phone_country'], $response->json('data.phone_country'));

        // Verify database update
        $this->user->refresh();
        $this->assertEquals($phoneData['phone'], $this->user->phone);
        $this->assertEquals($phoneData['phone_country'], $this->user->phone_country);
    }

    /**
     * @test
     */
    public function it_can_get_user_phone_information()
    {
        $this->user->update([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/phone/get');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'phone',
                    'phone_country',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('+1234567890', $response->json('data.phone'));
        $this->assertEquals('US', $response->json('data.phone_country'));
    }

    /**
     * @test
     */
    public function it_validates_phone_update_data()
    {
        $invalidData = [
            'phone' => 'invalid-phone',
            'phone_country' => 'INVALID',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/api/phone/update', $invalidData);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);

        $this->assertFalse($response->json('success'));
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_phone_update()
    {
        $phoneData = [
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ];

        $response = $this->postJson('/api/phone/update', $phoneData);

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'User not authenticated',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_phone_get()
    {
        $response = $this->getJson('/api/phone/get');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'User not authenticated',
            ]);
    }

    /**
     * @test
     */
    public function it_can_clear_phone_information()
    {
        $this->user->update([
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->postJson('/api/phone/update', [
                'phone' => null,
                'phone_country' => null,
            ]);

        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertNull($this->user->phone);
        $this->assertNull($this->user->phone_country);
    }
}
