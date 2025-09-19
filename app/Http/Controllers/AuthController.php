<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helper\Response;
use App\Models\User;
use App\Models\PasswordReset;
use App\Mail\ForgotPasswordOTP;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    //register method
    public function register(Request $request)
    {
        try {
            //validate request
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            //create user
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Create API token
            $token = $user->createToken('api-token')->plainTextToken;

            //return success response
            return Response::success([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ], 'User registered successfully', 201);
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    //login method
    public function login(Request $request)
    {
        //validate request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            //check user is exist
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return Response::error('', 'User not found', 404);
            }

            //check password
            if (!Hash::check($request->password, $user->password)) {
                return Response::error('', 'Invalid password', 401);
            }

            // Delete all existing tokens for this user (optional - for single session)
            $user->tokens()->delete();

            // Create a new API token
            $token = $user->createToken('api-token')->plainTextToken;

            //return success response with token
            return Response::success([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ], 'User logged in successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    //logout method
    public function logout(Request $request)
    {
        try {
            // Delete the current access token
            $request->user()->currentAccessToken()->delete();
            
            return Response::success('', 'User logged out successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    //get authenticated user
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return Response::error('', 'User not found', 404);
            }
            return Response::success($user, 'User retrieved successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Send OTP to user's email for password reset
     */
    public function forgotPassword(Request $request)
    {
        // Validate request
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        try {
            // Find user
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return Response::error('', 'User not found with this email', 404);
            }

            // Create password reset record with OTP
            $passwordReset = PasswordReset::createReset($request->email);

            // Send OTP via email using mailable
            try {
                Mail::to($request->email)->send(
                    new ForgotPasswordOTP(
                        $passwordReset->otp,
                        $user->first_name . ' ' . $user->last_name,
                        45
                    )
                );
            } catch (\Exception $e) {
                return Response::error('', 'Failed to send OTP email', 500);
            }

            return Response::success([
                'email' => $request->email,
                'expires_in' => 45
            ], 'OTP sent to your email successfully');

        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Verify OTP sent to user's email
     */
    public function verifyOTP(Request $request)
    {
        // Validate request
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6'
        ]);

        try {
            // Find valid reset record
            $passwordReset = PasswordReset::findValidReset($request->email, $request->otp);
            
            if (!$passwordReset) {
                return Response::error('', 'Invalid or expired OTP', 400);
            }

            // Verify the OTP
            if ($passwordReset->verify()) {
                return Response::success([
                    'email' => $request->email,
                    'verified' => true
                ], 'OTP verified successfully. You can now reset your password.');
            }

            return Response::error('', 'Failed to verify OTP', 400);

        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Reset password after OTP verification
     */
    public function resetPassword(Request $request)
    {
        // Validate request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        try {
            // Check if there's a verified reset record
            $passwordReset = PasswordReset::findVerifiedReset($request->email);
            
            if (!$passwordReset) {
                return Response::error('', 'No verified OTP found. Please verify OTP first.', 400);
            }

            // Find user
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return Response::error('', 'User not found', 404);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Delete the password reset record
            $passwordReset->delete();

            // Revoke all existing tokens for security
            $user->tokens()->delete();

            return Response::success('', 'Password reset successfully. Please login with your new password.');

        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }
}
