@extends('vendor.layouts.vendor-auth')

@section('title', 'Nouveau mot de passe')

@section('content')
<div class="va-card">
    <div class="va-ico">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>

    <h1 class="va-title">Choisis un nouveau mot de passe</h1>
    <p class="va-lead">
        Un code à 6 chiffres a été envoyé sur WhatsApp au
        <strong>{{ $phoneMasked }}</strong>. Il expire dans 10 minutes.
    </p>

    @if(session('status'))
        <div class="va-status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('vendor.password.reset') }}" class="va-form">
        @csrf

        <label class="va-label" for="code">Code reçu</label>
        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
               maxlength="6" pattern="[0-9]{6}" required autofocus
               placeholder="123456"
               class="va-input {{ $errors->has('code') ? 'has-error' : '' }}"
               style="letter-spacing:.35em; font-weight:800; text-align:center;">
        @error('code')<p class="va-error">{{ $message }}</p>@enderror

        <label class="va-label" for="password">Nouveau mot de passe</label>
        <input id="password" name="password" type="password" required autocomplete="new-password"
               class="va-input {{ $errors->has('password') ? 'has-error' : '' }}">
        @error('password')<p class="va-error">{{ $message }}</p>@enderror
        <p class="va-hint">8 caractères minimum.</p>

        <label class="va-label" for="password_confirmation">Confirme le mot de passe</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
               class="va-input">

        <button type="submit" class="va-submit">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Enregistrer le nouveau mot de passe
        </button>
    </form>

    <a href="{{ route('vendor.password.request') }}" class="va-back">Renvoyer un code</a>
</div>
@endsection
