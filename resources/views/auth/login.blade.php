@extends('layouts.auth-mobile')

@section('title', 'Connexion - Kardafrica')

@section('content')
    @include('auth.flip-card', ['initialState' => 'true'])

    {{-- Chemin d'entrée des clients servis au comptoir : leur compte a été ouvert
         sur leur seul numéro, ils n'ont ni e-mail ni mot de passe à saisir. --}}
    <div class="login-wa">
        <div class="login-wa-sep"><span>ou</span></div>
        <a href="{{ route('client.whatsapp.login') }}" class="login-wa-btn">
            💬 Se connecter avec WhatsApp
        </a>
        <p class="login-wa-hint">Sans mot de passe — tu reçois un code sur WhatsApp.</p>
    </div>
@endsection

@push('head')
<style>
    .login-wa { max-width: 420px; margin: 18px auto 0; padding: 0 16px 32px; }
    .login-wa-sep {
        display: flex; align-items: center; gap: 12px;
        color: #94A3B8; font-size: 12px; font-weight: 700; margin-bottom: 14px;
    }
    .login-wa-sep::before, .login-wa-sep::after {
        content: ''; flex: 1; height: 1px; background: #E2E8F0;
    }
    .login-wa-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        min-height: 50px; border-radius: 12px;
        background: #25D366; color: #fff; text-decoration: none;
        font-size: 15px; font-weight: 800;
    }
    .login-wa-hint { font-size: 12px; color: #64748B; text-align: center; margin: 10px 0 0; }
</style>
@endpush
