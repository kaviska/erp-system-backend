<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class AuthLogoutTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser = User::factory()->create();
    }

    /**
     * Test Case 1: Successful logout with valid token
     */
    public function test_successful_logout_with_valid_token()
    {
        Sanctum::actingAs($this->testUser);

        $response = $this->getJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'User logged out successfully'
                ]);
    }

    /**
     * Test Case 2: Logout fails without authentication token
     */
    public function test_logout_fails_without_authentication_token()
    {
        $response = $this->getJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test Case 3: Logout fails with invalid token
     */
    public function test_logout_fails_with_invalid_token()
    {
        $response = $this->getJson('/api/auth/logout', [
            'Authorization' => 'Bearer invalid-token'
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test Case 4: Logout removes the current access token
     */
    public function test_logout_removes_current_access_token()
    {
        $token = $this->testUser->createToken('test-token');
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson('/api/auth/logout');

        $response->assertStatus(200);
        
        // Verify token is deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id
        ]);
    }

    /**
     * Test Case 5: Multiple logout attempts with same token should fail after first
     */
    public function test_multiple_logout_attempts_fail_after_first()
    {
        $token = $this->testUser->createToken('test-token');
        $headers = ['Authorization' => 'Bearer ' . $token->plainTextToken];

        // First logout should succeed
        $response1 = $this->withHeaders($headers)->getJson('/api/auth/logout');
        $response1->assertStatus(200);

        // Second logout with same token should fail
        $response2 = $this->withHeaders($headers)->getJson('/api/auth/logout');
        $response2->assertStatus(401);
    }

    /**
     * Test Case 6: Logout with expired token should fail
     */
    public function test_logout_with_expired_token_fails()
    {
        // Create token and manually expire it
        $token = $this->testUser->createToken('test-token', ['*'], now()->subDay());
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test Case 7: Logout only affects current token, not other user tokens
     */
    public function test_logout_only_affects_current_token()
    {
        $token1 = $this->testUser->createToken('token-1');
        $token2 = $this->testUser->createToken('token-2');
        
        // Logout with first token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1->plainTextToken
        ])->getJson('/api/auth/logout');

        $response->assertStatus(200);
        
        // First token should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token1->accessToken->id
        ]);
        
        // Second token should still exist
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token2->accessToken->id
        ]);
    }

    /**
     * Test Case 8: Logout with malformed Authorization header
     */
    public function test_logout_with_malformed_authorization_header()
    {
        $response = $this->withHeaders([
            'Authorization' => 'InvalidFormat token123'
        ])->getJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test Case 9: Logout with empty Authorization header
     */
    public function test_logout_with_empty_authorization_header()
    {
        $response = $this->withHeaders([
            'Authorization' => ''
        ])->getJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test Case 10: Logout with Bearer but no token
     */
    public function test_logout_with_bearer_but_no_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '
        ])->getJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test Case 11: Logout response structure validation
     */
    public function test_logout_response_structure()
    {
        Sanctum::actingAs($this->testUser);

        $response = $this->getJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message'
                ])
                ->assertJson([
                    'status' => 'success',
                    'message' => 'User logged out successfully'
                ]);
    }

    /**
     * Test Case 12: Logout using POST method should fail (only GET allowed)
     */
    public function test_logout_using_post_method_fails()
    {
        Sanctum::actingAs($this->testUser);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(405); // Method Not Allowed
    }

    /**
     * Test Case 13: Logout using PUT method should fail
     */
    public function test_logout_using_put_method_fails()
    {
        Sanctum::actingAs($this->testUser);

        $response = $this->putJson('/api/auth/logout');

        $response->assertStatus(405); // Method Not Allowed
    }

    /**
     * Test Case 14: Logout using DELETE method should fail
     */
    public function test_logout_using_delete_method_fails()
    {
        Sanctum::actingAs($this->testUser);

        $response = $this->deleteJson('/api/auth/logout');

        $response->assertStatus(405); // Method Not Allowed
    }

    /**
     * Test Case 15: Logout with revoked token should fail
     */
    public function test_logout_with_revoked_token_fails()
    {
        $token = $this->testUser->createToken('test-token');
        
        // Manually revoke the token
        $token->accessToken->delete();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ])->getJson('/api/auth/logout');

        $response->assertStatus(401);
    }
}