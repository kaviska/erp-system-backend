<?php

namespace Database\Factories;

use App\Models\PasswordReset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PasswordReset>
 */
class PasswordResetFactory extends Factory
{
    protected $model = PasswordReset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'otp' => sprintf('%06d', fake()->numberBetween(100000, 999999)),
            'expires_at' => Carbon::now()->addMinutes(45),
            'is_verified' => false,
        ];
    }

    /**
     * Indicate that the OTP should be verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Indicate that the OTP should be expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->subMinutes(60),
        ]);
    }

    /**
     * Set a specific OTP value.
     */
    public function withOtp(string $otp): static
    {
        return $this->state(fn (array $attributes) => [
            'otp' => $otp,
        ]);
    }

    /**
     * Set a specific email.
     */
    public function forEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }
}