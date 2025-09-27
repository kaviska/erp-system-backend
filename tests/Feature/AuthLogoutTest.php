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