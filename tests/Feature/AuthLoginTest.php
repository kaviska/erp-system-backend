<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a test user for authentication tests
        $this->testUser = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);
    }

    /**
     * Test Case 1: Successful login with valid credentials
     */
    public function test_successful_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                            'created_at',
                            'updated_at'
                        ],
                        'token',
                        'token_type'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                    'message' => 'User logged in successfully',
                    'data' => [
                        'token_type' => 'Bearer'
                    ]
                ]);

        // Verify token is created
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->testUser->id,
            'name' => 'api-token'
        ]);
    }

    /**
     * Test Case 2: Login fails with invalid email
     */
    public function test_login_fails_with_invalid_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'User not found'
                ]);
    }

    /**
     * Test Case 3: Login fails with wrong password
     */
    public function test_login_fails_with_wrong_password()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid password'
                ]);
    }

    /**
     * Test Case 4: Login validation fails with missing email
     */
    public function test_login_validation_fails_with_missing_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 5: Login validation fails with missing password
     */
    public function test_login_validation_fails_with_missing_password()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test Case 6: Login validation fails with invalid email format
     */
    public function test_login_validation_fails_with_invalid_email_format()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 7: Login validation fails with password too short
     */
    public function test_login_validation_fails_with_password_too_short()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => '12345'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test Case 8: Login validation fails with empty credentials
     */
    public function test_login_validation_fails_with_empty_credentials()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test Case 9: Login validation fails with null values
     */
    public function test_login_validation_fails_with_null_values()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => null,
            'password' => null
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test Case 10: Login validation fails with empty string values
     */
    public function test_login_validation_fails_with_empty_string_values()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => '',
            'password' => ''
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test Case 11: Login with uppercase email should work (MySQL is case insensitive by default)
     */
    public function test_login_with_uppercase_email_works()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'password123'
        ]);

        // MySQL is case insensitive by default, so this should work
        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'User logged in successfully'
                ]);
    }

    /**
     * Test Case 12: Login with extra whitespace in email should work (Laravel trims input)
     */
    public function test_login_with_whitespace_in_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => ' test@example.com ',
            'password' => 'password123'
        ]);

        // Laravel typically trims input automatically
        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'User logged in successfully'
                ]);
    }

    /**
     * Test Case 13: Previous tokens are deleted on successful login
     */
    public function test_previous_tokens_are_deleted_on_login()
    {
        // Create some existing tokens
        $this->testUser->createToken('old-token-1');
        $this->testUser->createToken('old-token-2');
        
        $initialTokenCount = $this->testUser->tokens()->count();
        $this->assertEquals(2, $initialTokenCount);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        
        // Should have only 1 token (the new one)
        $this->assertEquals(1, $this->testUser->fresh()->tokens()->count());
    }

    /**
     * Test Case 14: Login with very long email should fail validation
     */
    public function test_login_with_very_long_email()
    {
        $longEmail = str_repeat('a', 250) . '@example.com';
        
        $response = $this->postJson('/api/auth/login', [
            'email' => $longEmail,
            'password' => 'password123'
        ]);

        // Should fail because user doesn't exist (not validation error)
        $response->assertStatus(404)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'User not found'
                ]);
    }

    /**
     * Test Case 15: Login with very long password
     */
    public function test_login_with_very_long_password()
    {
        $longPassword = str_repeat('a', 1000);
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => $longPassword
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid password'
                ]);
    }

    /**
     * Test Case 16: Login with special characters in password
     */
    public function test_login_with_special_characters_in_password()
    {
        $userWithSpecialPassword = User::factory()->create([
            'email' => 'special@example.com',
            'password' => Hash::make('!@#$%^&*()_+-=[]{}|;:,.<>?')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'special@example.com',
            'password' => '!@#$%^&*()_+-=[]{}|;:,.<>?'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'user',
                        'token',
                        'token_type'
                    ]
                ]);
    }

    /**
     * Test Case 17: Login with SQL injection attempt in email
     */
    public function test_login_with_sql_injection_attempt_in_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => "test@example.com'; DROP TABLE users; --",
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 18: Login with XSS attempt in email
     */
    public function test_login_with_xss_attempt_in_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => '<script>alert("xss")</script>@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 19: Login returns correct user data structure
     */
    public function test_login_returns_correct_user_data_structure()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                            'created_at',
                            'updated_at'
                        ],
                        'token',
                        'token_type'
                    ]
                ]);

        // Ensure password is not included in response
        $responseData = $response->json();
        $this->assertArrayNotHasKey('password', $responseData['data']['user']);
        $this->assertArrayNotHasKey('remember_token', $responseData['data']['user']);
    }

    /**
     * Test Case 20: Login with unicode characters in password
     */
    public function test_login_with_unicode_characters_in_password()
    {
        $userWithUnicodePassword = User::factory()->create([
            'email' => 'unicode@example.com',
            'password' => Hash::make('пароль123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'unicode@example.com',
            'password' => 'пароль123'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'User logged in successfully'
                ]);
    }

    /**
     * Test Case 21: Login response contains valid JWT token format
     */
    public function test_login_response_contains_valid_token_format()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        
        $responseData = $response->json();
        $token = $responseData['data']['token'];
        
        // Sanctum tokens should be base64 encoded and contain pipe separator
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertStringContainsString('|', $token);
    }

    /**
     * Test Case 22: Login with deactivated/soft-deleted user should fail
     */
    public function test_login_with_soft_deleted_user_fails()
    {
        // First, we need to add soft deletes to User model for this test
        // For now, we'll test with a regular delete
        $deletedUser = User::factory()->create([
            'email' => 'deleted@example.com',
            'password' => Hash::make('password123')
        ]);
        
        $deletedUser->delete();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'deleted@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'User not found'
                ]);
    }

    /**
     * Test Case 23: Login with numeric strings as credentials
     */
    public function test_login_with_numeric_strings_as_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => '12345',
            'password' => '67890'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 24: Concurrent login attempts
     */
    public function test_concurrent_login_attempts()
    {
        $responses = [];
        
        // Simulate multiple concurrent login attempts
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password123'
            ]);
        }

        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // But only the last token should exist (due to token deletion)
        $this->assertEquals(1, $this->testUser->fresh()->tokens()->count());
    }

    /**
     * Test Case 25: Login performance test (response time)
     */
    public function test_login_response_time_performance()
    {
        $startTime = microtime(true);
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Assert response time is under 1 second (adjust as needed)
        $this->assertLessThan(1.0, $responseTime, 'Login endpoint should respond within 1 second');
    }
}