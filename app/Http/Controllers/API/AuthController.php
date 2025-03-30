<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:15|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'national_id' => 'nullable|string|unique:users'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'national_id' => $request->national_id,
                'password' => Hash::make($request->password),
            ]);

            // Create token with all scopes
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user and create token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:phone|string|email|max:255',
            'phone' => 'required_without:email|string|max:15',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create credentials array based on whether email or phone is provided
        if ($request->has('email')) {
            $credentials = [
                'email' => $request->email,
                'password' => $request->password
            ];
        } else {
            $credentials = [
                'phone' => $request->phone,
                'password' => $request->password
            ];
        }

        // Attempt to authenticate the user
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = User::where(function ($query) use ($request) {
            if ($request->has('email')) {
                $query->where('email', $request->email);
            } else {
                $query->where('phone', $request->phone);
            }
        })->first();

        // Check if two-factor authentication is enabled for this user
        if ($user->two_factor_enabled) {
            // Generate and send OTP via SMS
            $otp = $this->generateAndSendOTP($user);

            return response()->json([
                'success' => true,
                'message' => 'OTP has been sent to your phone',
                'requires_two_factor' => true,
                'temp_token' => $this->generateTempToken($user)
            ]);
        }

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    }

    /**
     * Verify two-factor authentication
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verifyTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'temp_token' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Extract user ID from temp token
        $userId = $this->getUserIdFromTempToken($request->temp_token);
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Verify OTP
        if (!$this->verifyOTP($user, $request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP'
            ], 401);
        }

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication successful',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        // Revoke all tokens...
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    /**
     * Update user profile
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:15|unique:users,phone,' . $user->id,
            'national_id' => 'nullable|string|unique:users,national_id,' . $user->id,
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update basic info
            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            if ($request->has('phone')) {
                $user->phone = $request->phone;
            }

            if ($request->has('national_id')) {
                $user->national_id = $request->national_id;
            }

            // Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store('profile-photos', 'public');
                $user->profile_photo_path = $path;
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change user password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Check if current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 401);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Send password reset link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Send password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link has been sent to your email'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send password reset link',
            'error' => __($status)
        ], 500);
    }

    /**
     * Reset password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Reset password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to reset password',
            'error' => __($status)
        ], 500);
    }

    /**
     * Enable Two-Factor Authentication
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function enableTwoFactor(Request $request)
    {
        $user = $request->user();

        // Generate and send OTP for verification
        $otp = $this->generateAndSendOTP($user);

        return response()->json([
            'success' => true,
            'message' => 'OTP has been sent to your phone. Please verify to enable two-factor authentication.',
            'temp_token' => $this->generateTempToken($user)
        ]);
    }

    /**
     * Verify and Enable Two-Factor Authentication
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verifyAndEnableTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'temp_token' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Extract user ID from temp token
        $userId = $this->getUserIdFromTempToken($request->temp_token);
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Verify OTP
        if (!$this->verifyOTP($user, $request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP'
            ], 401);
        }

        // Enable two-factor authentication
        $user->two_factor_enabled = true;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication has been enabled successfully'
        ]);
    }

    /**
     * Disable Two-Factor Authentication
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function disableTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect'
            ], 401);
        }

        // Disable two-factor authentication
        $user->two_factor_enabled = false;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication has been disabled successfully'
        ]);
    }

    /**
     * Generate and send OTP
     *
     * @param  \App\Models\User  $user
     * @return string
     */
    private function generateAndSendOTP($user)
    {
        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in database (In a real implementation, you'd have an OTP table)
        // For now, we'll store it in the user's two_factor_secret field
        $user->two_factor_secret = Hash::make($otp);
        $user->save();

        // Send OTP via SMS (This is where you'd integrate with an SMS gateway)
        // In a real implementation, you'd send the OTP to the user's phone
        // For now, we'll just log it
        \Log::info("OTP for user {$user->id}: {$otp}");

        return $otp;
    }

    /**
     * Verify OTP
     *
     * @param  \App\Models\User  $user
     * @param  string  $otp
     * @return bool
     */
    private function verifyOTP($user, $otp)
    {
        // In a real implementation, you'd verify against an OTP table
        // For now, we'll check against the user's two_factor_secret field
        return Hash::check($otp, $user->two_factor_secret);
    }

    /**
     * Generate temporary token for two-factor authentication
     *
     * @param  \App\Models\User  $user
     * @return string
     */
    private function generateTempToken($user)
    {
        // In a real implementation, you'd use a more secure method
        // For now, we'll just use a simple encoded string with an expiry
        $payload = [
            'id' => $user->id,
            'exp' => time() + 300 // 5 minutes expiry
        ];

        return base64_encode(json_encode($payload));
    }

    /**
     * Extract user ID from temporary token
     *
     * @param  string  $token
     * @return int|null
     */
    private function getUserIdFromTempToken($token)
    {
        try {
            $payload = json_decode(base64_decode($token), true);

            // Check if token is expired
            if (!isset($payload['exp']) || $payload['exp'] < time()) {
                return null;
            }

            return $payload['id'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
