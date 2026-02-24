<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SSOController extends Controller
{
    public function redirectToFoodpanda()
    {
        $user = Auth::user();

        // Create Token: email + timestamp + secret = sign
        $payload = base64_encode(json_encode([
            'email'     => $user->email,
            'name'      => $user->name,
            'timestamp' => now()->timestamp,
        ]));

        $signature = hash_hmac('sha256', $payload, env('SSO_SECRET'));

        $foodpandaUrl = env('FOODPANDA_URL');

        return redirect("{$foodpandaUrl}/sso/login?payload={$payload}&signature={$signature}");
    }
}
