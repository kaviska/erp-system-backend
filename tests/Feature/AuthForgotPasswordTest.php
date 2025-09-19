<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PasswordReset;
use App\Mail\ForgotPasswordOTP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class AuthForgotPasswordTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->testUser = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com'
        ]);
    }

    /**
     * Test Case 1: Successfully send OTP to existing user email
     */
    public function test_successfully_send_otp_to_existing_user_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'email',
                        'expires_in'
                    ]
                ])
                ->assertJson([
                    'status' => 'success',
                    'message' => 'OTP sent to your email successfully',
                    'data' => [
                        'email' => 'test@example.com',
                        'expires_in' => 45
                    ]
                ]);

        // Verify OTP record is created in database
        $this->assertDatabaseHas('password_resets', [
            'email' => 'test@example.com',
            'is_verified' => false
        ]);

        // Verify email was sent
        Mail::assertSent(ForgotPasswordOTP::class);
    }

    /**
     * Test Case 2: Fails with non-existent email
     */
    public function test_fails_with_non_existent_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

        // Verify no OTP record is created
        $this->assertDatabaseMissing('password_resets', [
            'email' => 'nonexistent@example.com'
        ]);

        // Verify no email was sent
        Mail::assertNotSent(ForgotPasswordOTP::class);
    }

    /**
     * Test Case 3: Validation fails with missing email
     */
    public function test_validation_fails_with_missing_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 4: Validation fails with invalid email format
     */
    public function test_validation_fails_with_invalid_email_format()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'invalid-email-format'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 5: Validation fails with empty email
     */
    public function test_validation_fails_with_empty_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => ''
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 6: Validation fails with null email
     */
    public function test_validation_fails_with_null_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => null
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 7: Previous OTP records are deleted when creating new one
     */
    public function test_previous_otp_records_are_deleted()
    {
        // Create existing OTP record
        $existingOtp = PasswordReset::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(200);

        // Verify old OTP is deleted
        $this->assertDatabaseMissing('password_resets', [
            'id' => $existingOtp->id
        ]);

        // Verify new OTP is created
        $this->assertDatabaseHas('password_resets', [
            'email' => 'test@example.com',
            'is_verified' => false
        ]);
    }

    /**
     * Test Case 8: OTP is generated with correct format (6 digits)
     */
    public function test_otp_is_generated_with_correct_format()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(200);

        $passwordReset = PasswordReset::where('email', 'test@example.com')->first();
        
        $this->assertNotNull($passwordReset);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $passwordReset->otp);
    }

    /**
     * Test Case 9: OTP expiration is set correctly (45 seconds)
     */
    public function test_otp_expiration_is_set_correctly()
    {
        $before = now();
        
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);

        $after = now();
        $response->assertStatus(200);

        $passwordReset = PasswordReset::where('email', 'test@example.com')->first();
        
        $this->assertNotNull($passwordReset);
        $this->assertTrue($passwordReset->expires_at->between(
            $before->addSeconds(44),
            $after->addSeconds(46)
        ));
    }

    /**
     * Test Case 10: Email contains correct OTP and user information
     */
    public function test_email_contains_correct_otp_and_user_information()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(200);

        $passwordReset = PasswordReset::where('email', 'test@example.com')->first();

        Mail::assertSent(ForgotPasswordOTP::class, function ($mail) use ($passwordReset) {
            return $mail->otp === $passwordReset->otp &&
                   $mail->userName === 'John Doe' &&
                   $mail->expiryMinutes === 45;
        });
    }

    /**
     * Test Case 11: Multiple requests to same email generate different OTPs
     */
    public function test_multiple_requests_generate_different_otps()
    {
        // First request
        $response1 = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);
        $response1->assertStatus(200);
        
        $firstOtp = PasswordReset::where('email', 'test@example.com')->first()->otp;

        // Wait a moment and make second request
        sleep(1);
        
        $response2 = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);
        $response2->assertStatus(200);
        
        $secondOtp = PasswordReset::where('email', 'test@example.com')->first()->otp;

        $this->assertNotEquals($firstOtp, $secondOtp);
    }

    /**
     * Test Case 12: Case sensitive email handling
     */
    public function test_case_sensitive_email_handling()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'TEST@EXAMPLE.COM'
        ]);

        // Should fail as email doesn't exist in uppercase
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 13: Email with extra whitespace
     */
    public function test_email_with_extra_whitespace()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => ' test@example.com '
        ]);

        // Should fail as email doesn't exist with whitespace
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 14: Very long email validation
     */
    public function test_very_long_email_validation()
    {
        $longEmail = str_repeat('a', 250) . '@example.com';
        
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $longEmail
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 15: SQL injection attempt in email
     */
    public function test_sql_injection_attempt_in_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => "test@example.com'; DROP TABLE users; --"
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 16: XSS attempt in email
     */
    public function test_xss_attempt_in_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => '<script>alert("xss")</script>@example.com'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Case 17: Unicode characters in email
     */
    public function test_unicode_characters_in_email()
    {
        $unicodeUser = User::factory()->create([
            'email' => 'тест@example.com'
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'тест@example.com'
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('password_resets', [
            'email' => 'тест@example.com'
        ]);
    }

    /**
     * Test Case 18: Rate limiting test (if implemented)
     */
    public function test_rate_limiting_for_forgot_password()
    {
        // Make multiple rapid requests
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->postJson('/api/auth/forgot-password', [
                'email' => 'test@example.com'
            ]);
        }

        // First few should succeed, later ones might be rate limited
        // This depends on your rate limiting configuration
        $responses[0]->assertStatus(200);
        // Add assertions based on your rate limiting setup
    }

    /**
     * Test Case 19: HTTP method validation (only POST allowed)
     */
    public function test_http_method_validation()
    {
        $response = $this->getJson('/api/auth/forgot-password');
        $response->assertStatus(405); // Method Not Allowed

        $response = $this->putJson('/api/auth/forgot-password');
        $response->assertStatus(405); // Method Not Allowed

        $response = $this->deleteJson('/api/auth/forgot-password');
        $response->assertStatus(405); // Method Not Allowed
    }

    /**
     * Test Case 20: Performance test for forgot password endpoint
     */
    public function test_forgot_password_performance()
    {
        $startTime = microtime(true);
        
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Assert response time is under 2 seconds
        $this->assertLessThan(2.0, $responseTime, 'Forgot password endpoint should respond within 2 seconds');
    }

    /**
     * Test Case 21: Concurrent forgot password requests
     */
    public function test_concurrent_forgot_password_requests()
    {
        $responses = [];
        
        // Make multiple concurrent requests
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/auth/forgot-password', [
                'email' => 'test@example.com'
            ]);
        }

        // All should succeed but only one OTP record should exist
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Should have only one record (latest one)
        $this->assertEquals(1, PasswordReset::where('email', 'test@example.com')->count());
    }

    /**
     * Test Case 22: Mail sending failure handling
     */
    public function test_mail_sending_failure_handling()
    {
        // Mock mail failure
        Mail::shouldReceive('to')
            ->andThrow(new \Exception('Mail server unavailable'));

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(500)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Failed to send OTP email'
                ]);
    }

    /**
     * Test Case 23: User with special characters in name
     */
    public function test_user_with_special_characters_in_name()
    {
        $specialUser = User::factory()->create([
            'first_name' => 'José María',
            'last_name' => 'García-López',
            'email' => 'jose@example.com'
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'jose@example.com'
        ]);

        $response->assertStatus(200);

        Mail::assertSent(ForgotPasswordOTP::class, function ($mail) {
            return $mail->userName === 'José María García-López';
        });
    }

    /**
     * Test Case 24: Soft deleted user should not receive OTP
     */
    public function test_soft_deleted_user_should_not_receive_otp()
    {
        // Delete the user
        $this->testUser->delete();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

        Mail::assertNotSent(ForgotPasswordOTP::class);
    }

    /**
     * Test Case 25: Additional data fields in request are ignored
     */
    public function test_additional_data_fields_are_ignored()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com',
            'malicious_field' => 'hack_attempt',
            'admin' => true,
            'password' => 'should_be_ignored'
        ]);

        $response->assertStatus(200);
        
        // Should work normally and ignore extra fields
        $this->assertDatabaseHas('password_resets', [
            'email' => 'test@example.com',
            'is_verified' => false
        ]);
    }
}