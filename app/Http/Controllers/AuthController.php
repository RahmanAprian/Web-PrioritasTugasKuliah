<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(){return view('auth.login');}
    public function register(){return view('auth.register');}
    public function storeUser(Request $r){$d=$r->validate(['name'=>'required|string|max:100','email'=>'required|email|unique:users','password'=>'required|string|min:8|confirmed']);$u=User::create(['name'=>$d['name'],'email'=>$d['email'],'password'=>Hash::make($d['password'])]);Auth::login($u);$r->session()->regenerate();return redirect()->route('dashboard');}
    public function authenticate(Request $r){$d=$r->validate(['email'=>'required|email','password'=>'required']);if(!Auth::attempt($d,$r->boolean('remember')))return back()->withErrors(['email'=>'Email atau kata sandi salah.'])->onlyInput('email');$r->session()->regenerate();return redirect()->intended(route('dashboard'));}
    public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('login');}
}
