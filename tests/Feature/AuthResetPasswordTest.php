<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;

class AuthResetPasswordTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $testUser;
    protected $verifiedOtp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword123')
        ]);
        
        // Create a verified OTP record
        $this->verifiedOtp = PasswordReset::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(5),
            'is_verified' => true
        ]);
    }

    /**
     * Test Case 1: Successfully reset password with valid data
     */
    public function test_successfully_reset_password_with_valid_data()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Password reset successfully. Please login with your new password.'
                ]);

        // Verify password is updated
        $this->testUser->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->testUser->password));

        // Verify OTP record is deleted
        $this->assertDatabaseMissing('password_resets', [
            'email' => 'test@example.com'
        ]);

        // Verify all tokens are revoked
        $this->assertEquals(0, $this->testUser->tokens()->count());
    }

    /**
     * Test Case 2: Reset fails without verified OTP
     */
    public function test_reset_fails_without_verified_otp()
    {
        // Delete the verified OTP
        $this->verifiedOtp->delete();

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'No verified OTP found. Please verify OTP first.'
                ]);

        // Verify password is not changed
        $this->testUser->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $this->testUser->password));
    }

    /**
     * Test Case 3: Reset fails with unverified OTP
     */
    public function test_reset_fails_with_unverified_otp()
    {
        // Update OTP to unverified
        $this->verifiedOtp->update(['is_verified' => false]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'No verified OTP found. Please verify OTP first.'
                ]);
    }

    /**
     * Test Case 4: Reset fails with expired verified OTP
     */
    public function test_reset_fails_with_expired_verified_otp()
    {
        // Create expired verified OTP (expired more than 10 minutes ago)
        $this->verifiedOtp->update(['expires_at' => now()->subMinutes(15)]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'No verified OTP found. Please verify OTP first.'
                ]);
    }

    /**
     * Test Case 5: Validation fails with missing email
     */
    public function test_validation_fails_with_missing_email()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 6: Validation fails with missing password
     */
    public function test_validation_fails_with_missing_password()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test Case 7: Validation fails with missing password confirmation
     */
    public function test_validation_fails_with_missing_password_confirmation()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test Case 8: Validation fails with password mismatch
     */
    public function test_validation_fails_with_password_mismatch()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test Case 9: Validation fails with password too short
     */
    public function test_validation_fails_with_password_too_short()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test Case 10: Validation fails with invalid email format
     */
    public function test_validation_fails_with_invalid_email_format()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'invalid-email',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 11: Reset fails with non-existent user
     */
    public function test_reset_fails_with_non_existent_user()
    {
        // Create verified OTP for non-existent user
        PasswordReset::create([
            'email' => 'nonexistent@example.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(5),
            'is_verified' => true
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'nonexistent@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'User not found'
                ]);
    }

    /**
     * Test Case 12: All existing tokens are revoked after password reset
     */
    public function test_all_tokens_revoked_after_password_reset()
    {
        // Create some tokens for the user
        $token1 = $this->testUser->createToken('token1');
        $token2 = $this->testUser->createToken('token2');
        
        $this->assertEquals(2, $this->testUser->tokens()->count());

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200);
        
        // Verify all tokens are deleted
        $this->assertEquals(0, $this->testUser->fresh()->tokens()->count());
    }

    /**
     * Test Case 13: Password reset with special characters
     */
    public function test_password_reset_with_special_characters()
    {
        $specialPassword = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => $specialPassword,
            'password_confirmation' => $specialPassword
        ]);

        $response->assertStatus(200);
        
        // Verify special character password is set correctly
        $this->testUser->refresh();
        $this->assertTrue(Hash::check($specialPassword, $this->testUser->password));
    }

    /**
     * Test Case 14: Password reset with unicode characters
     */
    public function test_password_reset_with_unicode_characters()
    {
        $unicodePassword = 'пароль123привет';
        
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => $unicodePassword,
            'password_confirmation' => $unicodePassword
        ]);

        $response->assertStatus(200);
        
        // Verify unicode password is set correctly
        $this->testUser->refresh();
        $this->assertTrue(Hash::check($unicodePassword, $this->testUser->password));
    }

    /**
     * Test Case 15: Very long password handling
     */
    public function test_very_long_password_handling()
    {
        $longPassword = str_repeat('a', 255);
        
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => $longPassword,
            'password_confirmation' => $longPassword
        ]);

        $response->assertStatus(200);
        
        // Verify long password is set correctly
        $this->testUser->refresh();
        $this->assertTrue(Hash::check($longPassword, $this->testUser->password));
    }

    /**
     * Test Case 17: Reset password multiple times with same verified OTP should fail
     */
    public function test_multiple_reset_attempts_with_same_otp_fails()
    {
        // First reset should succeed
        $response1 = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);
        $response1->assertStatus(200);

        // Second reset should fail (OTP deleted)
        $response2 = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'anotherpassword123',
            'password_confirmation' => 'anotherpassword123'
        ]);

        $response2->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'No verified OTP found. Please verify OTP first.'
                ]);
    }

    /**
     * Test Case 18: Empty string password validation
     */
    public function test_empty_string_password_validation()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => ''
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test Case 19: Null password validation
     */
    public function test_null_password_validation()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => null,
            'password_confirmation' => null
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test Case 20: HTTP method validation (only POST allowed)
     */
    public function test_http_method_validation()
    {
        $response = $this->getJson('/api/auth/reset-password');
        $response->assertStatus(405); // Method Not Allowed

        $response = $this->putJson('/api/auth/reset-password');
        $response->assertStatus(405); // Method Not Allowed

        $response = $this->deleteJson('/api/auth/reset-password');
        $response->assertStatus(405); // Method Not Allowed
    }

    /**
     * Test Case 21: Password reset performance test
     */
    public function test_password_reset_performance()
    {
        $startTime = microtime(true);
        
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Assert response time is under 1 second
        $this->assertLessThan(1.0, $responseTime, 'Password reset should complete within 1 second');
    }

    /**
     * Test Case 22: Concurrent password reset attempts should be handled safely
     */
    public function test_concurrent_password_reset_attempts()
    {
        // Create multiple verified OTP records for testing
        PasswordReset::create([
            'email' => 'concurrent@example.com',
            'otp' => '111111',
            'expires_at' => now()->addMinutes(5),
            'is_verified' => true
        ]);

        $concurrentUser = User::factory()->create(['email' => 'concurrent@example.com']);

        $responses = [];
        
        // Make multiple concurrent reset attempts
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/auth/reset-password', [
                'email' => 'concurrent@example.com',
                'password' => "password{$i}123",
                'password_confirmation' => "password{$i}123"
            ]);
        }

        // Only one should succeed
        $successCount = 0;
        foreach ($responses as $response) {
            if ($response->getStatusCode() === 200) {
                $successCount++;
            }
        }

        $this->assertEquals(1, $successCount, 'Only one concurrent reset should succeed');
    }

    /**
     * Test Case 24: SQL injection attempt in password field
     */
    public function test_sql_injection_attempt_in_password()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => "'; DROP TABLE users; --",
            'password_confirmation' => "'; DROP TABLE users; --"
        ]);

        $response->assertStatus(200);
        
        // Verify password is set as-is (Laravel handles this safely)
        $this->testUser->refresh();
        $this->assertTrue(Hash::check("'; DROP TABLE users; --", $this->testUser->password));
        
        // Verify users table still exists
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }

    /**
     * Test Case 25: Password reset with additional malicious fields
     */
    public function test_password_reset_with_additional_fields()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'admin' => true,
            'role' => 'administrator',
            'malicious_field' => 'hack_attempt'
        ]);

        $response->assertStatus(200);
        
        // Should work normally and ignore extra fields
        $this->testUser->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->testUser->password));
        
        // Verify the user exists but no admin field was added (since admin column doesn't exist)
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }
}