@extends('layouts.app')
@section('content')
    <nav class="navbar navbar-dark">
        <div class="container">
            <span class="navbar-brand">🛒 Ecommerce App</span>
            <div>
                <span class="text-white me-3">{{ auth()->user()->name }}</span>
                <a href="{{ route('logout') }}" class="btn btn-sm btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="card shadow p-4">
                    <h3>Welcome to Ecommerce! 🎉</h3>
                    <p class="text-muted">You are logged in as <strong>{{ auth()->user()->email }}</strong></p>
                    <hr>
                    <p>Click below to go to <strong>Foodpanda</strong> without logging in again:</p>
                    <a href="{{ route('sso.redirect') }}" class="btn btn-warning btn-lg">
                        🍔 Go to Foodpanda (SSO)
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
