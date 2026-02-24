<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials']);
    }

    public function dashboard()
    {
        return view('dashboard');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        //  notify -> Foodpanda
        $this->notifyFoodpandaLogout($user->email);

        //  Self logout
        Auth::logout();
        $request->session()->invalidate();
//        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
    private function notifyFoodpandaLogout(string $email): void
    {
        $payload = base64_encode(json_encode([
            'email'     => $email,
            'timestamp' => now()->timestamp,
        ]));

        $signature = hash_hmac('sha256', $payload, env('SSO_SECRET'));

        try {
            \Http::timeout(5)->post(env('FOODPANDA_URL') . '/sso/logout', [
                'payload'   => $payload,
                'signature' => $signature,
            ]);
        } catch (\Exception $e) {
            \Log::error('SSO Logout failed: ' . $e->getMessage());
        }
    }
}
