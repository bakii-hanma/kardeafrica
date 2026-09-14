@extends('layouts.app')

@section('title', 'Modifier mon profil - Kardafrica')

@php
    $p = optional($user->profile);
    $parts  = preg_split('/\s+/', trim($user->name), 2);
    $prenom = old('first_name', $p->first_name ?: ($parts[0] ?? ''));
    $nom    = old('last_name',  $p->last_name  ?: ($parts[1] ?? ''));
@endphp

@section('content')
<style>
    .ka-e-field {
        width: 100%; border-radius: 12px; padding: 12px 14px 12px 42px;
        background: #fff; border: 1px solid #E2E8F0; color: #0F172A; font-size: 14px;
        transition: border-color .15s, box-shadow .15s;
    }
    .ka-e-field:focus { outline: none; border-color: #44A08D; box-shadow: 0 0 0 3px rgba(68,160,141,.15); }
    .ka-e-field::placeholder { color: #94A3B8; }
    .ka-e-wrap { position: relative; }
    .ka-e-ico { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #94A3B8; pointer-events: none; }
</style>

<div class="min-h-screen bg-[#FAFAF7]">
    {{-- Bandeau titre --}}
    <div style="background:linear-gradient(135deg,#0F172A 0%,#1E293B 100%);">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-[#4ECDC4]">Mon profil</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-white mt-1">Modifier mon profil</h1>
            </div>
            <a href="{{ route('profile.show') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold text-white/90 hover:text-white bg-white/10 hover:bg-white/20 border border-white/15 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour
            </a>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-6">

        @if (session('success'))
            <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-medium">
                <svg class="h-5 w-5 text-emerald-500 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-6">
            {{-- ===== Informations personnelles ===== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-teal-50 text-[#44A08D] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Informations personnelles</h2>
                        <p class="text-xs text-slate-500">Mettez à jour votre nom et votre e-mail.</p>
                    </div>
                </div>
                <div class="p-6">
                    <form action="{{ route('profile.update') }}" method="POST" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="first_name" class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1.5">Prénom</label>
                                <div class="ka-e-wrap">
                                    <svg class="ka-e-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <input type="text" name="first_name" id="first_name" value="{{ $prenom }}" placeholder="Jean" class="ka-e-field">
                                </div>
                                @error('first_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="last_name" class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1.5">Nom</label>
                                <div class="ka-e-wrap">
                                    <svg class="ka-e-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <input type="text" name="last_name" id="last_name" value="{{ $nom }}" placeholder="Dupont" class="ka-e-field">
                                </div>
                                @error('last_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1.5">Adresse e-mail</label>
                            <div class="ka-e-wrap">
                                <svg class="ka-e-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" placeholder="exemple@email.com" class="ka-e-field">
                            </div>
                            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end pt-1">
                            <button type="submit" class="inline-flex items-center gap-2 bg-[#44A08D] text-white px-6 py-2.5 rounded-xl font-semibold hover:bg-[#3b8e7c] transition shadow-lg shadow-[#44A08D]/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ===== Sécurité ===== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Sécurité</h2>
                        <p class="text-xs text-slate-500">Modifiez votre mot de passe.</p>
                    </div>
                </div>
                <div class="p-6">
                    <form action="{{ route('profile.password.update') }}" method="POST" class="space-y-5 max-w-md">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="current_password" class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1.5">Mot de passe actuel</label>
                            <div class="ka-e-wrap">
                                <svg class="ka-e-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <input type="password" name="current_password" id="current_password" class="ka-e-field">
                            </div>
                            @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1.5">Nouveau mot de passe</label>
                            <div class="ka-e-wrap">
                                <svg class="ka-e-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <input type="password" name="password" id="password" class="ka-e-field">
                            </div>
                            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wide text-slate-500 mb-1.5">Confirmer le nouveau mot de passe</label>
                            <div class="ka-e-wrap">
                                <svg class="ka-e-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="ka-e-field">
                            </div>
                        </div>

                        <div class="flex justify-end pt-1">
                            <button type="submit" class="inline-flex items-center gap-2 bg-slate-900 text-white px-6 py-2.5 rounded-xl font-semibold hover:bg-slate-800 transition shadow-lg">
                                Mettre à jour le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
