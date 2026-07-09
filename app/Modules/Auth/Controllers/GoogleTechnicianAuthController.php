<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleTechnicianAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect('/?technician_error='.urlencode('Google sign-in is not configured yet.'));
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect('/?technician_error='.urlencode('Google sign-in could not be completed.'));
        }

        if (! $googleUser->getEmail()) {
            return redirect('/?technician_error='.urlencode('Google did not provide a verified email address.'));
        }

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user && ! in_array($user->role, ['technician_applicant', 'technician'], true)) {
            return redirect('/?technician_error='.urlencode('This Google account belongs to a different NimbusOps portal.'));
        }

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: 'Technician Applicant',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'auth_provider' => 'google',
                'email_verified_at' => now(),
                'password' => Str::password(32),
                'role' => 'technician_applicant',
                'status' => 'active',
            ]);
        } else {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'auth_provider' => 'google',
                'email_verified_at' => $user->email_verified_at ?: now(),
            ]);
        }

        $code = Str::random(64);
        Cache::put(
            'technician-google:'.hash('sha256', $code),
            $user->id,
            now()->addMinutes(2)
        );

        return redirect('/?technician_oauth_code='.urlencode($code));
    }

    public function exchange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:64'],
        ]);

        $userId = Cache::pull(
            'technician-google:'.hash('sha256', $validated['code'])
        );

        if (! $userId) {
            return response()->json([
                'message' => 'This Google sign-in session has expired or was already used.',
            ], 422);
        }

        $user = User::with([
            'technician.serviceArea',
            'technicianApplication.preferredServiceArea',
        ])->findOrFail($userId);

        return response()->json([
            'message' => 'Google sign-in successful.',
            'user' => $user,
            'token' => $user->createToken('technician-google')->plainTextToken,
        ]);
    }
}
