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
     * Test Case 1: Successfully retrieve authenticated user profile
     */
    public function test_successfully_retrieve_authenticated_user_profile()
    {
        Sanctum::actingAs($this->testUser);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                    'message' => 'User retrieved successfully',
                    'data' => [
                        'id' => $this->testUser->id,
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'email' => 'john.doe@example.com'
                    ]
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
     * Test Case 4: Profile response excludes sensitive data
     */
    public function test_profile_response_excludes_sensitive_data()
    {
        Sanctum::actingAs($this->testUser);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200);
        
        $responseData = $response->json();
        
        // Ensure sensitive fields are not included
        $this->assertArrayNotHasKey('password', $responseData['data']);
        $this->assertArrayNotHasKey('remember_token', $responseData['data']);
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
     * Test Case 7: Profile returns updated user data after modification
     */
    public function test_profile_returns_updated_user_data()
    {
        Sanctum::actingAs($this->testUser);

        // Update user data
        $this->testUser->update([
            'first_name' => 'Jane',
            'last_name' => 'Smith'
        ]);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'data' => [
                        'first_name' => 'Jane',
                        'last_name' => 'Smith'
                    ]
                ]);
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
     * Test Case 14: Profile with soft deleted user should fail
     */
    public function test_profile_with_soft_deleted_user_fails()
    {
        Sanctum::actingAs($this->testUser);
        
        // Delete the user
        $this->testUser->delete();

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(404)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'User not found'
                ]);
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
     * Test Case 16: Profile response has correct data types
     */
    public function test_profile_response_has_correct_data_types()
    {
        Sanctum::actingAs($this->testUser);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200);
        
        $userData = $response->json()['data'];
        
        $this->assertIsInt($userData['id']);
        $this->assertIsString($userData['first_name']);
        $this->assertIsString($userData['last_name']);
        $this->assertIsString($userData['email']);
        $this->assertIsString($userData['created_at']);
        $this->assertIsString($userData['updated_at']);
    }

    /**
     * Test Case 17: Profile with special characters in user data
     */
    public function test_profile_with_special_characters_in_user_data()
    {
        $userWithSpecialChars = User::factory()->create([
            'first_name' => 'José',
            'last_name' => 'Müller-Schmidt',
            'email' => 'josé.müller@example.com'
        ]);
        
        Sanctum::actingAs($userWithSpecialChars);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'first_name' => 'José',
                        'last_name' => 'Müller-Schmidt',
                        'email' => 'josé.müller@example.com'
                    ]
                ]);
    }

    /**
     * Test Case 18: Profile endpoint performance test
     */
    public function test_profile_endpoint_performance()
    {
        Sanctum::actingAs($this->testUser);
        
        $startTime = microtime(true);
        
        $response = $this->getJson('/api/auth/me');
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Assert response time is under 500ms
        $this->assertLessThan(0.5, $responseTime, 'Profile endpoint should respond within 500ms');
    }

    /**
     * Test Case 19: Multiple concurrent profile requests
     */
    public function test_multiple_concurrent_profile_requests()
    {
        Sanctum::actingAs($this->testUser);
        
        $responses = [];
        
        // Make multiple concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->getJson('/api/auth/me');
        }

        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200)
                    ->assertJson([
                        'status' => 'success',
                        'data' => [
                            'email' => 'john.doe@example.com'
                        ]
                    ]);
        }
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