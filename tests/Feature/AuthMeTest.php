<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class AuthMeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com'
        ]);
    }

    /**
     * Test Case 2: Fails to retrieve profile without authentication
     */
    public function test_fails_to_retrieve_profile_without_authentication()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test Case 3: Fails with invalid token
     */
    public function test_fails_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token'
        ])->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test Case 5: Profile with expired token should fail
     */
    public function test_profile_with_expired_token_fails()
    {
        $token = $this->testUser->createToken('test-token', ['*'], now()->subDay());
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test Case 6: Profile with revoked token should fail
     */
    public function test_profile_with_revoked_token_fails()
    {
        $token = $this->testUser->createToken('test-token');
        
        // Revoke the token
        $token->accessToken->delete();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test Case 8: Profile with malformed Authorization header
     */
    public function test_profile_with_malformed_authorization_header()
    {
        $response = $this->withHeaders([
            'Authorization' => 'InvalidFormat token123'
        ])->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test Case 9: Profile with empty Authorization header
     */
    public function test_profile_with_empty_authorization_header()
    {
        $response = $this->withHeaders([
            'Authorization' => ''
        ])->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test Case 10: Profile with Bearer but no token
     */
    public function test_profile_with_bearer_but_no_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '
        ])->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test Case 11: Profile using POST method should fail
     */
    public function test_profile_using_post_method_fails()
    {
        Sanctum::actingAs($this->testUser);

        $response = $this->postJson('/api/auth/me');

        $response->assertStatus(405); // Method Not Allowed
    }

    /**
     * Test Case 12: Profile using PUT method should fail
     */
    public function test_profile_using_put_method_fails()
    {
        Sanctum::actingAs($this->testUser);

        $response = $this->putJson('/api/auth/me');

        $response->assertStatus(405); // Method Not Allowed
    }

    /**
     * Test Case 13: Profile using DELETE method should fail
     */
    public function test_profile_using_delete_method_fails()
    {
        Sanctum::actingAs($this->testUser);

        $response = $this->deleteJson('/api/auth/me');

        $response->assertStatus(405); // Method Not Allowed
    }

    /**
     * Test Case 15: Profile with different user tokens
     */
    public function test_profile_with_different_user_tokens()
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        
        $token1 = $user1->createToken('token1');
        
        // Try to access user1's profile with user1's token
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1->plainTextToken
        ])->getJson('/api/auth/me');

        $response1->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'email' => 'user1@example.com'
                    ]
                ]);
    }

    /**
     * Test Case 20: Profile with token from different application
     */
    public function test_profile_with_token_from_different_application()
    {
        // Create token with different name/application
        $token = $this->testUser->createToken('different-app-token');
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'data' => [
                        'email' => 'john.doe@example.com'
                    ]
                ]);
    }
}