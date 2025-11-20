<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create authenticated user with token
     */
    protected function createAuthenticatedUser(array $attributes = [])
    {
        $user = \App\Models\User::factory()->create($attributes);
        $token = $user->createToken('test-token');
        
        return [
            'user' => $user,
            'token' => $token->plainTextToken,
            'headers' => [
                'Authorization' => 'Bearer ' . $token->plainTextToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ];
    }

    /**
     * Helper method to make authenticated requests
     */
    protected function authenticatedJson($method, $uri, $data = [], $user = null, $headers = [])
    {
        if (!$user) {
            $auth = $this->createAuthenticatedUser();
            $user = $auth['user'];
            $headers = array_merge($auth['headers'], $headers);
        }

        return $this->json($method, $uri, $data, $headers);
    }

    /**
     * Helper to create OTP record for testing
     */
    protected function createOtpRecord($email, $verified = false, $expired = false)
    {
        $expiresAt = $expired ? now()->subMinutes(5) : now()->addMinutes(5);
        
        return \App\Models\PasswordReset::create([
            'email' => $email,
            'otp' => '123456',
            'expires_at' => $expiresAt,
            'is_verified' => $verified
        ]);
    }
}
