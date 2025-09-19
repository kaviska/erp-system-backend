<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

class AuthVerifyOTPTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $testUser;
    protected $testOtp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser = User::factory()->create([
            'email' => 'test@example.com'
        ]);
        
        // Create a valid OTP record
        $this->testOtp = PasswordReset::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false
        ]);
    }

    /**
     * Test Case 1: Successfully verify valid OTP
     */
    public function test_successfully_verify_valid_otp()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'email',
                        'verified'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                    'message' => 'OTP verified successfully. You can now reset your password.',
                    'data' => [
                        'email' => 'test@example.com',
                        'verified' => true
                    ]
                ]);

        // Verify OTP is marked as verified in database
        $this->assertDatabaseHas('password_resets', [
            'email' => 'test@example.com',
            'otp' => '123456',
            'is_verified' => true
        ]);
    }

    /**
     * Test Case 2: Verification fails with invalid OTP
     */
    public function test_verification_fails_with_invalid_otp()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '999999'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ]);

        // Verify OTP remains unverified
        $this->assertDatabaseHas('password_resets', [
            'email' => 'test@example.com',
            'otp' => '123456',
            'is_verified' => false
        ]);
    }

    /**
     * Test Case 3: Verification fails with expired OTP
     */
    public function test_verification_fails_with_expired_otp()
    {
        // Create expired OTP
        $expiredOtp = PasswordReset::create([
            'email' => 'expired@example.com',
            'otp' => '654321',
            'expires_at' => now()->subMinutes(5),
            'is_verified' => false
        ]);

        User::factory()->create(['email' => 'expired@example.com']);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'expired@example.com',
            'otp' => '654321'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ]);
    }

    /**
     * Test Case 4: Validation fails with missing email
     */
    public function test_validation_fails_with_missing_email()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'otp' => '123456'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 5: Validation fails with missing OTP
     */
    public function test_validation_fails_with_missing_otp()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['otp']);
    }

    /**
     * Test Case 6: Validation fails with invalid email format
     */
    public function test_validation_fails_with_invalid_email_format()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'invalid-email',
            'otp' => '123456'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 7: Validation fails with OTP not exactly 6 characters
     */
    public function test_validation_fails_with_otp_not_six_characters()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '12345'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['otp']);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '1234567'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['otp']);
    }

    /**
     * Test Case 8: Validation fails with empty values
     */
    public function test_validation_fails_with_empty_values()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => '',
            'otp' => ''
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'otp']);
    }

    /**
     * Test Case 9: Validation fails with null values
     */
    public function test_validation_fails_with_null_values()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => null,
            'otp' => null
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'otp']);
    }

    /**
     * Test Case 10: Verification fails with already verified OTP
     */
    public function test_verification_fails_with_already_verified_otp()
    {
        // First verification
        $response1 = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456'
        ]);
        $response1->assertStatus(200);

        // Second verification attempt
        $response2 = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456'
        ]);

        $response2->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ]);
    }

    /**
     * Test Case 11: Verification fails with non-existent email
     */
    public function test_verification_fails_with_non_existent_email()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'nonexistent@example.com',
            'otp' => '123456'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ]);
    }

    /**
     * Test Case 12: OTP verification with alphabetic characters
     */
    public function test_otp_verification_with_alphabetic_characters()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => 'ABCDEF'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ]);
    }

    /**
     * Test Case 13: OTP verification with special characters
     */
    public function test_otp_verification_with_special_characters()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '12@#$%'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ]);
    }

    /**
     * Test Case 14: Case sensitivity test for email
     */
    public function test_case_sensitivity_for_email()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'TEST@EXAMPLE.COM',
            'otp' => '123456'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ]);
    }

    /**
     * Test Case 15: OTP verification with leading/trailing whitespace
     */
    public function test_otp_verification_with_whitespace()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => ' test@example.com ',
            'otp' => ' 123456 '
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ]);
    }

    /**
     * Test Case 16: Multiple OTP records for same email (should use latest)
     */
    public function test_multiple_otp_records_for_same_email()
    {
        // Create another OTP record for same email
        $newOtp = PasswordReset::create([
            'email' => 'test@example.com',
            'otp' => '789012',
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false
        ]);

        // Old OTP should not work
        $response1 = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456'
        ]);
        $response1->assertStatus(400);

        // New OTP should work
        $response2 = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '789012'
        ]);
        $response2->assertStatus(200);
    }

    /**
     * Test Case 17: OTP verification boundary testing (exactly at expiration)
     */
    public function test_otp_verification_at_expiration_boundary()
    {
        // Create OTP that expires in 1 second
        $boundaryOtp = PasswordReset::create([
            'email' => 'boundary@example.com',
            'otp' => '555555',
            'expires_at' => now()->addSecond(),
            'is_verified' => false
        ]);

        User::factory()->create(['email' => 'boundary@example.com']);

        // Should work immediately
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'boundary@example.com',
            'otp' => '555555'
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test Case 18: OTP verification performance test
     */
    public function test_otp_verification_performance()
    {
        $startTime = microtime(true);
        
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456'
        ]);
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Assert response time is under 500ms
        $this->assertLessThan(0.5, $responseTime, 'OTP verification should complete within 500ms');
    }

    /**
     * Test Case 19: SQL injection attempt in OTP field
     */
    public function test_sql_injection_attempt_in_otp()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => "'; DROP TABLE password_resets; --"
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['otp']);

        // Verify table still exists
        $this->assertDatabaseHas('password_resets', [
            'email' => 'test@example.com'
        ]);
    }

    /**
     * Test Case 20: Concurrent OTP verification attempts
     */
    public function test_concurrent_otp_verification_attempts()
    {
        $responses = [];
        
        // Make multiple concurrent verification attempts
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/auth/verify-otp', [
                'email' => 'test@example.com',
                'otp' => '123456'
            ]);
        }

        // Only first should succeed
        $successCount = 0;
        foreach ($responses as $response) {
            if ($response->getStatusCode() === 200) {
                $successCount++;
            }
        }

        $this->assertEquals(1, $successCount, 'Only one concurrent verification should succeed');
    }

    /**
     * Test Case 21: HTTP method validation (only POST allowed)
     */
    public function test_http_method_validation()
    {
        $response = $this->getJson('/api/auth/verify-otp');
        $response->assertStatus(405); // Method Not Allowed

        $response = $this->putJson('/api/auth/verify-otp');
        $response->assertStatus(405); // Method Not Allowed

        $response = $this->deleteJson('/api/auth/verify-otp');
        $response->assertStatus(405); // Method Not Allowed
    }

    /**
     * Test Case 22: Brute force protection test
     */
    public function test_brute_force_protection()
    {
        $responses = [];
        
        // Make multiple failed verification attempts
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->postJson('/api/auth/verify-otp', [
                'email' => 'test@example.com',
                'otp' => sprintf('%06d', 999999 - $i)
            ]);
        }

        // All should fail with same error message (no information leakage)
        foreach ($responses as $response) {
            $response->assertStatus(400)
                    ->assertJson([
                        'status' => 'error',
                        'message' => 'Invalid or expired OTP'
                    ]);
        }
    }

    /**
     * Test Case 23: Unicode characters in email
     */
    public function test_unicode_characters_in_email()
    {
        $unicodeUser = User::factory()->create(['email' => 'тест@example.com']);
        
        $unicodeOtp = PasswordReset::create([
            'email' => 'тест@example.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'тест@example.com',
            'otp' => '123456'
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test Case 24: OTP verification with additional malicious fields
     */
    public function test_otp_verification_with_additional_fields()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456',
            'admin' => true,
            'verified' => true,
            'malicious_field' => 'hack_attempt'
        ]);

        $response->assertStatus(200);
        
        // Should work normally and ignore extra fields
        $this->assertDatabaseHas('password_resets', [
            'email' => 'test@example.com',
            'is_verified' => true
        ]);
    }

    /**
     * Test Case 25: Verify OTP close to expiration time
     */
    public function test_verify_otp_close_to_expiration()
    {
        // Create OTP that expires in 1 second
        $almostExpiredOtp = PasswordReset::create([
            'email' => 'almost@example.com',
            'otp' => '777777',
            'expires_at' => now()->addSeconds(1),
            'is_verified' => false
        ]);

        User::factory()->create(['email' => 'almost@example.com']);

        // Wait for it to expire
        sleep(2);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'almost@example.com',
            'otp' => '777777'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ]);
    }
}