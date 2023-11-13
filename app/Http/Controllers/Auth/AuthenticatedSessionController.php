<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Events\UserInactive;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $user = Auth::user();

        if ($request->user()) {
            // If the email is verified, redirect the user to the intended page after login
            $user = $request->user()->load('profile','organization');
            $user->update(['last_seen' => Carbon::now()->toDateTimeString(), 'online' => true]);
            event(new UserInactive($user, 'online'));
            $request->session()->regenerate();
            return response()->json($user);
        } else {
            // If the email is not verified, log the user out and display a message
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Your email address is not verified. Please check your email for a verification link.',
            ]);
        }

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function checkSessionStatus(Request $request) {
        // Check if the user is authenticated and their session is still valid
        if ($request->user()) {
            return response()->json(['sessionExpired' => false]);
        }
        // If the session has expired or the user is not authenticated
        return response()->json(['sessionExpired' => true]);
    }
}
