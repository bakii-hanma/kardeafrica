@extends('vendor.layouts.vendor-auth')

@section('title', 'Mot de passe oublié')

@section('content')
<div class="va-card">
    <div class="va-ico">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
        </svg>
    </div>

    <h1 class="va-title">Mot de passe oublié&nbsp;?</h1>
    <p class="va-lead">
        Saisis ton <strong>code vendeur</strong> ou ton <strong>numéro de téléphone</strong>.
        Nous t'enverrons un code de vérification sur WhatsApp.
    </p>

    @if(session('status'))
        <div class="va-status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('vendor.password.send') }}" class="va-form">
        @csrf
        <label class="va-label" for="identifier">Code vendeur ou téléphone</label>
        <input id="identifier" name="identifier" type="text" required autofocus
               placeholder="KA-V-XXXX ou 06 12 34 56"
               value="{{ old('identifier') }}"
               class="va-input {{ $errors->has('identifier') ? 'has-error' : '' }}">
        @error('identifier')<p class="va-error">{{ $message }}</p>@enderror

        <button type="submit" class="va-submit">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            Recevoir le code sur WhatsApp
        </button>
    </form>

    <a href="{{ route('vendor.login') }}" class="va-back">Retour à la connexion</a>
</div>
@endsection
