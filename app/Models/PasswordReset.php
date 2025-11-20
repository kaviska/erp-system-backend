<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PasswordReset extends Model
{
    use HasFactory;
    protected $fillable = [
        'email',
        'otp',
        'expires_at',
        'is_verified'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_verified' => 'boolean'
    ];

    /**
     * Generate a secure 6-digit OTP
     */
    public static function generateOTP(): string
    {
        return sprintf('%06d', random_int(100000, 999999));
    }

    /**
     * Create or update password reset record
     */
    public static function createReset(string $email): self
    {
        // Delete existing records for this email
        self::where('email', $email)->delete();

        return self::create([
            'email' => $email,
            'otp' => self::generateOTP(),
            'expires_at' => Carbon::now()->addSeconds(45), // 45 seconds expiration
            'is_verified' => false
        ]);
    }

    /**
     * Check if OTP is valid and not expired
     */
    public function isValid(): bool
    {
        return !$this->is_verified && 
               $this->expires_at->isFuture();
    }

    /**
     * Verify the OTP
     */
    public function verify(): bool
    {
        if ($this->isValid()) {
            $this->update(['is_verified' => true]);
            return true;
        }
        return false;
    }

    /**
     * Find valid reset record by email and OTP
     */
    public static function findValidReset(string $email, string $otp): ?self
    {
        return self::where('email', $email)
                  ->where('otp', $otp)
                  ->where('is_verified', false)
                  ->where('expires_at', '>', Carbon::now())
                  ->first();
    }

    /**
     * Find verified reset record by email
     */
    public static function findVerifiedReset(string $email): ?self
    {
        return self::where('email', $email)
                  ->where('is_verified', true)
                  ->where('expires_at', '>', Carbon::now()->subMinutes(10)) // 10 minutes window after verification
                  ->first();
    }
}
