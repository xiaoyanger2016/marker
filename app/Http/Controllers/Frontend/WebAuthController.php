<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * 前台 Web 登录 / 注册 / 登出（session 认证）
 *  - 跟 API AuthController（Sanctum token）并存，互不干扰
 *  - /me、/me/places 等页面没登录 → 跳这里
 */
class WebAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/me');
        }
        return view('frontend.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], true)) {
            throw ValidationException::withMessages([
                'email' => '邮箱或密码错误',
            ]);
        }

        $request->session()->regenerate();
        return redirect()->intended('/me');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect('/me');
        }
        return view('frontend.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:200|unique:users,email',
            'password' => 'required|string|min:6|max:200|confirmed',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => false,
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();
        return redirect('/me');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
