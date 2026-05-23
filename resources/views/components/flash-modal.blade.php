@props([
    'type'    => 'info',     // success | error | info
    'message' => '',
    'title'   => null,
])

@php
    $config = match ($type) {
        'success' => [
            'title' => $title ?? 'C\'est fait !',
            'gradient' => 'from-emerald-400 to-emerald-600',
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'iconBg' => 'bg-emerald-50',
            'iconColor' => 'text-emerald-600',
            'ringColor' => 'rgba(16,185,129,0.2)',
            'iconPath' => 'M5 13l4 4L19 7',
        ],
        'error' => [
            'title' => $title ?? 'Oups...',
            'gradient' => 'from-rose-500 to-rose-700',
            'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            'iconBg' => 'bg-rose-50',
            'iconColor' => 'text-rose-600',
            'ringColor' => 'rgba(244,63,94,0.2)',
            'iconPath' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        ],
        default => [
            'title' => $title ?? 'Information',
            'gradient' => 'from-sky-400 to-sky-600',
            'badge' => 'bg-sky-50 text-sky-700 border-sky-200',
            'iconBg' => 'bg-sky-50',
            'iconColor' => 'text-sky-600',
            'ringColor' => 'rgba(56,189,248,0.2)',
            'iconPath' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
    };
@endphp

<div x-data="{ open: true }"
     x-show="open"
     x-init="setTimeout(() => { /* auto close apres 8s pour les success seulement */ @if($type === 'success') open = false @endif }, 8000)"
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-[1100] flex items-center justify-center p-4"
     style="background: rgba(15,23,42,0.55); backdrop-filter: blur(6px);">

    <div @click.outside="open = false"
         x-show="open"
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0 scale-50"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="flash-modal-card relative w-full max-w-md bg-white rounded-3xl overflow-hidden shadow-2xl border border-slate-100">

        {{-- Header colore avec icone large --}}
        <div class="relative bg-gradient-to-br {{ $config['gradient'] }} p-8 pb-12 text-white text-center overflow-hidden">
            {{-- Pattern subtle --}}
            <svg class="absolute inset-0 w-full h-full opacity-[0.12]" aria-hidden="true">
                <defs>
                    <pattern id="fm-pattern-{{ $type }}" width="32" height="32" patternUnits="userSpaceOnUse">
                        <circle cx="16" cy="16" r="13" fill="none" stroke="white" stroke-width="1.2"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#fm-pattern-{{ $type }})"/>
            </svg>

            {{-- Glow rings --}}
            <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/20 blur-2xl"></div>
            <div class="absolute -bottom-12 -left-12 w-44 h-44 rounded-full bg-white/15 blur-3xl"></div>

            {{-- Bouton close --}}
            <button @click="open = false" type="button"
                    class="absolute top-4 right-4 w-9 h-9 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 hover:bg-white/25 active:scale-95 transition flex items-center justify-center text-white"
                    aria-label="Fermer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Icone bouncing --}}
            <div class="relative mx-auto flash-modal-icon-wrapper">
                <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-sm border border-white/25 flex items-center justify-center mx-auto"
                     style="box-shadow: 0 12px 28px {{ $config['ringColor'] }};">
                    <svg class="w-10 h-10 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $config['iconPath'] }}"/>
                    </svg>
                </div>
                {{-- Petit badge type --}}
                <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-white text-[10px] font-bold uppercase tracking-wider shadow-md"
                     style="color: {{ $type === 'success' ? '#047857' : ($type === 'error' ? '#BE123C' : '#0369A1') }};">
                    {{ $type === 'success' ? 'Succès' : ($type === 'error' ? 'Erreur' : 'Info') }}
                </div>
            </div>
        </div>

        {{-- Body avec titre + message --}}
        <div class="px-8 py-6 pt-10 text-center">
            <h3 class="font-display text-2xl font-bold text-slate-900 mb-2 tracking-tight">{{ $config['title'] }}</h3>
            <p class="text-sm text-slate-600 leading-relaxed">{{ $message }}</p>
        </div>

        {{-- Action --}}
        <div class="px-6 pb-6">
            <button @click="open = false" type="button"
                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold shadow-lg transition active:scale-95">
                @if($type === 'error')
                    Compris
                @else
                    Continuer
                @endif
            </button>
        </div>

        {{-- Décor cards façon "carte cadeau" en arrière-plan --}}
        <div class="absolute -bottom-4 left-4 w-12 h-8 rounded bg-gradient-to-br from-yellow-200 to-yellow-400 opacity-20 rotate-[-12deg]"></div>
        <div class="absolute -bottom-4 right-4 w-12 h-8 rounded bg-gradient-to-br from-slate-300 to-slate-400 opacity-20 rotate-[12deg]"></div>
    </div>
</div>

<style>
    @keyframes flash-modal-bounce-in {
        0%   { transform: translateY(-30px) scale(0.7); opacity: 0; }
        50%  { transform: translateY(15px)  scale(1.05); opacity: 1; }
        70%  { transform: translateY(-8px)  scale(0.98); }
        85%  { transform: translateY(4px)   scale(1.01); }
        100% { transform: translateY(0)     scale(1); }
    }
    .flash-modal-card {
        animation: flash-modal-bounce-in 700ms cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    @keyframes flash-modal-icon-pop {
        0%   { transform: scale(0); opacity: 0; }
        60%  { transform: scale(1.15); opacity: 1; }
        80%  { transform: scale(0.95); }
        100% { transform: scale(1); }
    }
    .flash-modal-icon-wrapper {
        animation: flash-modal-icon-pop 800ms cubic-bezier(0.34, 1.56, 0.64, 1) 200ms backwards;
    }

    @media (prefers-reduced-motion: reduce) {
        .flash-modal-card,
        .flash-modal-icon-wrapper { animation: none; }
    }
</style>
