<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RegistrationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegistrationTokenController extends Controller
{
    public function create(Request $request)
    {
        // Create token and check for uniqueness
        do {
            $token = Str::random(60);
        } while (RegistrationToken::where('token', $token)->exists());

        $expiresAt = now()->addMinutes(40);

        RegistrationToken::create([
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'success' => true,
            'token' => $token,
        ]);
    }
}
