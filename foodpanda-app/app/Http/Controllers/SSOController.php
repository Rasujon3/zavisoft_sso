<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SSOController extends Controller
{
    public function handleSSO(Request $request)
    {
        $payload   = $request->query('payload');
        $signature = $request->query('signature');

        // 1. Signature verify
        $expectedSignature = hash_hmac('sha256', $payload, env('SSO_SECRET'));

        if (!hash_equals($expectedSignature, $signature)) {
            abort(403, 'Invalid SSO token');
        }

        // 2. Payload decode
        $data = json_decode(base64_decode($payload), true);

        // 3. Token expire check
        if (now()->timestamp - $data['timestamp'] > 300) {
            abort(403, 'SSO token expired');
        }

        // 4. Find User or Create New User
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name'     => $data['name'],
                'password' => bcrypt('sso-user-' . $data['email']),
            ]
        );

        // 5. Auto login!
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
