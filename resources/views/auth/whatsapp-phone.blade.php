@extends('layouts.auth-mobile')

@section('title', 'Connexion WhatsApp — KardAfrica')

@section('content')
{{--
    Saisie du numéro. Connexion et inscription sont le même geste : demander à un
    client s'il « a déjà un compte » n'a aucun sens quand c'est le vendeur qui
    l'a ouvert pour lui au comptoir.
--}}
<div class="wa-auth">
    <div class="wa-auth-card">
        <div class="wa-auth-ico">💬</div>
        <h1 class="wa-auth-title">Connexion par WhatsApp</h1>
        <p class="wa-auth-lead">
            Entre ton numéro : tu recevras un code à 6 chiffres sur WhatsApp.
            Pas besoin de mot de passe.
        </p>

        @if ($errors->any())
            <div class="wa-auth-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('client.whatsapp.send') }}" class="wa-auth-form">
            @csrf
            <x-phone-input name="phone" required
                           label="Ton numéro WhatsApp"
                           :value="old('phone')"
                           hint="Choisis ton indicatif. Si tu as acheté une carte chez un revendeur, utilise le numéro que tu lui as donné." />

            <button type="submit" class="wa-auth-btn">Recevoir mon code</button>
        </form>

        <p class="wa-auth-alt">
            Tu as un compte avec e-mail et mot de passe ?
            <a href="{{ route('login') }}">Connexion classique</a>
        </p>
    </div>
</div>
@endsection

@push('head')
<style>
    .wa-auth { max-width: 440px; margin: 0 auto; padding: 24px 16px 48px; }
    .wa-auth-card {
        background: #fff; border: 1px solid #E7EBF0; border-radius: 18px;
        padding: 26px 22px;
    }
    .wa-auth-ico { font-size: 34px; text-align: center; }
    .wa-auth-title {
        font-size: 21px; font-weight: 800; color: #0F172A;
        text-align: center; margin: 8px 0 6px;
    }
    .wa-auth-lead {
        font-size: 13.5px; color: #64748B; text-align: center;
        line-height: 1.6; margin: 0 0 20px;
    }
    .wa-auth-form { display: block; }
    .wa-auth-btn {
        width: 100%; margin-top: 16px; min-height: 50px;
        background: #25D366; color: #fff; border: 0; border-radius: 12px;
        font-size: 15px; font-weight: 800; font-family: inherit; cursor: pointer;
    }
    .wa-auth-btn:active { transform: scale(.99); }
    .wa-auth-error {
        background: #FEE2E2; border: 1px solid #FCA5A5; border-radius: 12px;
        padding: 12px 14px; margin-bottom: 16px;
        font-size: 13px; font-weight: 700; color: #B91C1C;
    }
    .wa-auth-info {
        background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 12px;
        padding: 12px 14px; margin-bottom: 16px;
        font-size: 13px; font-weight: 600; color: #1E40AF;
    }
    .wa-auth-alt {
        font-size: 12.5px; color: #64748B; text-align: center; margin: 18px 0 0;
    }
    .wa-auth-alt a { color: #0F9E8E; font-weight: 700; }
</style>
@endpush
