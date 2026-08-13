@extends('layouts.auth-mobile')

@section('title', 'Ton code — KardAfrica')

@section('content')
<div class="wa-auth">
    <div class="wa-auth-card">
        <div class="wa-auth-ico">🔑</div>
        <h1 class="wa-auth-title">Entre ton code</h1>
        <p class="wa-auth-lead">
            Un code à 6 chiffres vient d'être envoyé sur WhatsApp au
            <strong>{{ $phoneMasked }}</strong>.
        </p>

        @if (session('info'))
            <div class="wa-auth-info">{{ session('info') }}</div>
        @endif
        @if (session('success'))
            <div class="wa-auth-info">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="wa-auth-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('client.whatsapp.verify') }}">
            @csrf
            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" required autofocus placeholder="••••••"
                   class="wa-code-input" aria-label="Code à 6 chiffres">
            <button type="submit" class="wa-auth-btn">Me connecter</button>
        </form>

        <form method="POST" action="{{ route('client.whatsapp.resend') }}" class="wa-resend">
            @csrf
            <button type="submit" class="wa-resend-btn">Je n'ai rien reçu — renvoyer un code</button>
        </form>

        <p class="wa-auth-alt">
            Mauvais numéro ? <a href="{{ route('client.whatsapp.login') }}">Recommencer</a>
        </p>
    </div>
</div>
@endsection

@push('head')
<style>
    .wa-code-input {
        width: 100%; min-height: 62px; padding: 12px;
        border: 1px solid #CBD5E1; border-radius: 14px;
        font-size: 30px; font-weight: 800; text-align: center;
        letter-spacing: .32em; font-family: inherit; color: #0F172A;
    }
    .wa-code-input:focus {
        outline: none; border-color: #25D366; box-shadow: 0 0 0 3px rgba(37,211,102,.16);
    }
    .wa-resend { margin-top: 14px; text-align: center; }
    .wa-resend-btn {
        background: none; border: 0; padding: 8px;
        font-size: 12.5px; font-weight: 700; color: #64748B;
        font-family: inherit; cursor: pointer; text-decoration: underline;
    }
</style>
@endpush
