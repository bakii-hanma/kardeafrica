<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#FAFAF7">
    <title>Mes cartes cadeaux #{{ $order->order_number }} — KardAfrica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">

    {{-- Mêmes fonts que le site officiel (orders/show) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|space-grotesk:500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css'])
    {{-- html2canvas-pro pour gérer oklch/color-mix de Tailwind v4 --}}
    <script src="https://cdn.jsdelivr.net/npm/html2canvas-pro@1.5.8/dist/html2canvas-pro.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    @php
        $brandPalette = [
            'Netflix' => '#E50914', 'Spotify' => '#1DB954', 'Apple' => '#000000',
            'iTunes' => '#D60017', 'PlayStation' => '#003791', 'Xbox' => '#107C10',
            'Amazon' => '#FF9900', 'Google' => '#01875F', 'Steam' => '#171A21',
            'Roblox' => '#00A2FF', 'Nintendo' => '#E60012', 'Disney' => '#0E47A1',
            'StarzPlay' => '#7C3AED', 'Talabat' => '#FF5A00', 'HUAWEI' => '#C7000B', 'IKEA' => '#0058A3',
            'Daywatch' => '#44A08D',
        ];
        $brandColorFor = function ($name) use ($brandPalette) {
            if (!$name) return '#0F172A';
            foreach ($brandPalette as $k => $c) if (stripos($name, $k) !== false) return $c;
            $palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
            $hash = 0;
            for ($i = 0; $i < strlen($name); $i++) $hash = (ord($name[$i]) + (($hash << 5) - $hash)) & 0x7FFFFFFF;
            return $palette[(($hash % count($palette)) + count($palette)) % count($palette)];
        };

        $totalFcfa = $order->cards->sum(fn($c) => \App\Support\Money::toFcfa($c->face_value, $c->currency ?: 'XAF'));
    @endphp

    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .font-display { font-family: 'Space Grotesk', 'Inter', sans-serif; }
        .shadow-card       { box-shadow: 0 1px 2px rgb(0 0 0 / 0.04), 0 1px 3px rgb(0 0 0 / 0.06); }
        .shadow-card-hover { box-shadow: 0 10px 24px -8px rgb(0 0 0 / 0.10), 0 4px 8px -4px rgb(0 0 0 / 0.06); }
        @keyframes card-row-in {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card-row { animation: card-row-in .5s cubic-bezier(.22,1,.36,1) backwards; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#FAFAF7] text-slate-900">
@if (!empty($connecte))
    {{-- Ouvrir ce lien a prouvé la possession du numéro WhatsApp : le client est
         connecté, ses cartes sont dans son compte. Plus besoin de capture. --}}
    <div style="max-width:640px;margin:16px auto 0;padding:14px 16px;background:#064E3B;border:1px solid #10B981;border-radius:14px;color:#D1FAE5;font-size:14px;font-weight:700;line-height:1.5;">
        ✅ Ces cartes sont enregistrées dans ton compte KardAfrica.
        <a href="{{ route('cards.index') }}" style="color:#6EE7B7;text-decoration:underline;">Les retrouver quand tu veux</a>.
    </div>
@endif

<div class="min-h-screen pb-20" x-data="claimPage()">

    {{-- ================================================================
         TOP BAR (équivalent breadcrumb de orders/show)
         ================================================================ --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-slate-900 hover:text-[#44A08D] transition">
                <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="KardAfrica" class="w-8 h-8">
                <div>
                    <div class="text-[9px] font-bold tracking-[0.16em] uppercase text-[#44A08D] leading-none">Cartes cadeaux</div>
                    <div class="font-display text-sm font-bold mt-0.5">KardAfrica</div>
                </div>
            </a>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500">
                <span class="hidden sm:inline">Récupération</span>
                <svg class="w-3 h-3 text-slate-300 hidden sm:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 font-mono font-semibold">#{{ $order->order_number }}</span>
            </nav>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 md:pt-10">

        {{-- ================================================================
             HEADER : titre + badges (style orders/show)
             ================================================================ --}}
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                @if($order->cards->count() > 0)
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 mb-3">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-xs font-semibold text-emerald-700">Cartes activées et prêtes</span>
                    </div>
                @else
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-200 mb-3">
                        <svg class="w-3.5 h-3.5 text-amber-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="40 100"/></svg>
                        <span class="text-xs font-semibold text-amber-700">Génération en cours</span>
                    </div>
                @endif
                <h1 class="font-display text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">
                    Mes cartes <span class="font-mono text-[#44A08D]">#{{ $order->order_number }}</span>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Achetées chez <strong class="text-slate-700">{{ $order->reseller->name ?? 'KardAfrica' }}</strong>
                    · {{ $order->created_at->translatedFormat('d F Y à H:i') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-bold bg-emerald-50 text-emerald-700 border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    Active
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-bold bg-slate-50 text-slate-700 border-slate-200">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    {{ $order->cards->count() }} carte{{ $order->cards->count() > 1 ? 's' : '' }}
                </span>
                <button @click="downloadTicket()" :disabled="downloading" type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#44A08D] hover:bg-[#3d9180] text-white text-xs font-bold shadow-md shadow-[#44A08D]/20 transition active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg x-show="!downloading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <svg x-show="downloading" x-cloak class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="50 100"/></svg>
                    <span x-text="dlLabel"></span>
                </button>
            </div>
        </div>

        {{-- ================================================================
             VOS CARTES — layout 2 colonnes par carte (style orders/show)
             ================================================================ --}}
        <div id="ticket-pdf">

            @if($order->cards->count() > 0)
                <section class="mb-10">
                    <div class="flex items-end justify-between mb-4 no-print">
                        <h2 class="font-display text-lg md:text-xl font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] text-white flex items-center justify-center shadow-md shadow-[#44A08D]/20">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </span>
                            Vos cartes
                            <span class="text-xs font-normal text-slate-500">· {{ $order->cards->count() }} reçue{{ $order->cards->count() > 1 ? 's' : '' }}</span>
                        </h2>
                    </div>

                    <div class="space-y-4">
                        @foreach($order->cards as $i => $card)
                            @php
                                $cardCode = $card->card_code ?? '';
                                $cardPin  = $card->pin ?? null;
                                $brandColor = $brandColorFor($card->brand ?? $card->name ?? '');
                                $cardFcfa = \App\Support\Money::toFcfa($card->face_value, $card->currency ?: 'XAF');
                            @endphp
                            <div class="card-row" style="animation-delay: {{ $i * 100 }}ms"
                                 x-data="{ codeShown: false, pinShown: false, copiedField: null,
                                           copy(text, field) {
                                               navigator.clipboard.writeText(text).then(() => {
                                                   this.copiedField = field;
                                                   setTimeout(() => this.copiedField = null, 1800);
                                               });
                                           } }">

                                <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden hover:shadow-card-hover transition-all duration-300">
                                    <div class="grid grid-cols-1 md:grid-cols-[280px_1fr]">

                                        {{-- ===== Visuel carte gauche ===== --}}
                                        <div style="background-color: {{ $brandColor }}" class="relative p-5 overflow-hidden min-h-[180px] flex flex-col justify-between">
                                            {{-- Pattern --}}
                                            <svg class="absolute inset-0 w-full h-full opacity-[0.08]" aria-hidden="true">
                                                <defs>
                                                    <pattern id="cl-{{ $card->id }}" width="32" height="32" patternUnits="userSpaceOnUse">
                                                        <circle cx="16" cy="16" r="13" fill="none" stroke="white" stroke-width="1.2"/>
                                                    </pattern>
                                                </defs>
                                                <rect width="100%" height="100%" fill="url(#cl-{{ $card->id }})"/>
                                            </svg>
                                            {{-- Glow --}}
                                            <div class="absolute -top-12 -right-12 w-40 h-40 rounded-full bg-white/15 blur-2xl"></div>

                                            <div class="relative flex flex-col h-full justify-between">
                                                <div>
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-white/80 text-[10px] font-bold tracking-[0.2em] uppercase">Gift Card</span>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/30 backdrop-blur-sm border border-emerald-300/40 text-white text-[10px] font-bold">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-300"></span>
                                                            Active
                                                        </span>
                                                    </div>
                                                    <h3 class="font-display text-white font-bold text-2xl tracking-tight mt-1.5 leading-tight truncate">
                                                        {{ $card->brand ?: explode(' ', $card->name)[0] }}
                                                    </h3>
                                                </div>

                                                <div class="flex items-end justify-between">
                                                    <div>
                                                        <div class="text-white/60 text-[9px] font-bold uppercase tracking-wider">Valeur</div>
                                                        <div class="text-white font-display text-xl font-bold tabular-nums leading-none mt-0.5">
                                                            {{ number_format($cardFcfa, 0, ',', ' ') }}
                                                            <span class="text-sm font-normal text-white/60">FCFA</span>
                                                        </div>
                                                    </div>
                                                    <div class="w-10 h-7 rounded bg-gradient-to-br from-yellow-200/90 to-yellow-400/70 border border-yellow-300/40 shadow-inner"></div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ===== Détails droite ===== --}}
                                        <div class="p-5 flex flex-col justify-between">
                                            <div>
                                                <h4 class="text-base font-semibold text-slate-900 mb-3 line-clamp-1">{{ $card->name }}</h4>

                                                <div class="space-y-2.5">
                                                    {{-- Code carte --}}
                                                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                                        <div class="flex items-center justify-between mb-1">
                                                            <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Code</span>
                                                            <div class="flex items-center gap-1">
                                                                <button @click="codeShown = !codeShown" type="button" class="p-1 rounded text-slate-400 hover:text-[#44A08D] hover:bg-white transition" :title="codeShown ? 'Masquer' : 'Afficher'">
                                                                    <svg x-show="!codeShown" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                                    <svg x-show="codeShown" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                                                </button>
                                                                <button @click="copy('{{ addslashes($cardCode) }}', 'code')" type="button" class="p-1 rounded text-slate-400 hover:text-[#44A08D] hover:bg-white transition" title="Copier">
                                                                    <svg x-show="copiedField !== 'code'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                                    <svg x-show="copiedField === 'code'" x-cloak class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="font-mono text-sm font-bold text-slate-900 tracking-wider break-all"
                                                             x-text="codeShown ? '{{ $cardCode }}' : '{{ str_repeat('•', max(8, strlen($cardCode))) }}'"></div>
                                                    </div>

                                                    {{-- PIN + expiration --}}
                                                    @if($cardPin || $card->expiration_date)
                                                        <div class="grid grid-cols-2 gap-2.5">
                                                            @if($cardPin)
                                                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                                                    <div class="flex items-center justify-between mb-1">
                                                                        <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">PIN</span>
                                                                        <div class="flex items-center gap-1">
                                                                            <button @click="pinShown = !pinShown" type="button" class="p-1 rounded text-slate-400 hover:text-[#44A08D] hover:bg-white transition" :title="pinShown ? 'Masquer' : 'Afficher'">
                                                                                <svg x-show="!pinShown" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                                                <svg x-show="pinShown" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                                                            </button>
                                                                            <button @click="copy('{{ $cardPin }}', 'pin')" type="button" class="p-1 rounded text-slate-400 hover:text-[#44A08D] hover:bg-white transition" title="Copier">
                                                                                <svg x-show="copiedField !== 'pin'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                                                <svg x-show="copiedField === 'pin'" x-cloak class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    <div class="font-mono text-sm font-bold text-slate-900 tabular-nums"
                                                                         x-text="pinShown ? '{{ $cardPin }}' : '{{ str_repeat('•', strlen($cardPin)) }}'"></div>
                                                                </div>
                                                            @endif
                                                            @if($card->expiration_date)
                                                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                                                    <div class="text-[10px] uppercase tracking-wider font-bold text-slate-400 mb-1">Expiration</div>
                                                                    <div class="text-sm font-bold text-slate-900 tabular-nums">{{ $card->expiration_date->format('d/m/Y') }}</div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    {{-- Serial number (small) --}}
                                                    @if($card->serial_number)
                                                        <div class="flex items-center gap-2 text-[11px] text-slate-500 px-1">
                                                            <span class="font-bold text-[10px] uppercase tracking-wider text-slate-400">Serial:</span>
                                                            <span class="font-mono truncate">{{ $card->serial_number }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Actions footer --}}
                                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-2 no-print">
                                                <button @click="copy('{{ addslashes($cardCode) }}', 'code')" type="button"
                                                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-[#44A08D] hover:bg-[#3d9180] text-white text-xs font-semibold transition active:scale-95">
                                                    <svg x-show="copiedField !== 'code'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    <svg x-show="copiedField === 'code'" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                    <span x-show="copiedField !== 'code'">Copier le code</span>
                                                    <span x-show="copiedField === 'code'" x-cloak>Copié !</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ================================================================
                 RECAPITULATIF (style orders/show)
                 ================================================================ --}}
            <section class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6">

                {{-- Articles --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-teal-50 text-[#44A08D] flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            </span>
                            Articles
                        </h2>
                    </div>

                    <ul class="divide-y divide-slate-100">
                        @foreach($order->items as $item)
                            <li class="px-5 py-3.5 flex items-center gap-3">
                                <div class="shrink-0 w-12 h-12 rounded-xl overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200 border border-slate-200 flex items-center justify-center">
                                    @if($item->image_url)
                                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="font-display text-base font-bold text-slate-500">{{ strtoupper(substr($item->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10px] font-bold tabular-nums">{{ $item->quantity }}×</span>
                                        <p class="text-sm font-semibold text-slate-900 truncate">{{ $item->name }}</p>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5 tabular-nums">
                                        {{ number_format($item->unit_price, 0, ',', ' ') }} FCFA / unité
                                    </p>
                                </div>
                                <span class="text-sm font-black text-slate-900 tabular-nums whitespace-nowrap">
                                    {{ number_format($item->total_price, 0, ',', ' ') }} FCFA
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Aside : récap + infos --}}
                <aside class="space-y-4">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white">
                            <h2 class="font-display text-base font-bold text-slate-900">Récapitulatif</h2>
                        </div>
                        <div class="px-5 py-4 space-y-2.5 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-600">Articles</span>
                                <span class="font-semibold text-slate-900 tabular-nums">{{ $order->items->sum('quantity') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Cartes générées</span>
                                <span class="font-semibold text-slate-900 tabular-nums">{{ $order->cards->count() }}</span>
                            </div>
                            @if($order->cards->count() > 0)
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Valeur totale</span>
                                    <span class="font-semibold text-[#44A08D] tabular-nums">{{ number_format($totalFcfa, 0, ',', ' ') }} FCFA</span>
                                </div>
                            @endif
                            <div class="border-t border-slate-100 pt-2.5 flex justify-between items-end">
                                <span class="text-xs uppercase tracking-wider text-slate-400 font-bold">Total payé</span>
                                <span class="font-display text-xl font-black text-slate-900 tabular-nums leading-none">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</span>
                            </div>
                        </div>
                    </div>

                    {{-- Vendeur --}}
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-card p-4">
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-2">Vendeur</p>
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] text-white flex items-center justify-center font-display font-bold text-sm">
                                {{ strtoupper(substr($order->reseller->name ?? 'K', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-900 truncate">{{ $order->reseller->name ?? 'KardAfrica' }}</div>
                                @if($order->reseller && $order->reseller->vendor_code)
                                    <div class="text-[10px] text-slate-500 font-mono truncate">{{ $order->reseller->vendor_code }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Help --}}
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-card p-4 no-print">
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-2">Besoin d'aide&nbsp;?</p>
                        <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                            Un problème avec tes cartes&nbsp;? Contacte notre support.
                        </p>
                        <a href="mailto:hello@kardafrica.com" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#44A08D] hover:underline">
                            hello@kardafrica.com
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </aside>
            </section>

        </div> {{-- /#ticket-pdf --}}

        {{-- Empty state --}}
        @if($order->cards->count() === 0)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-card text-center py-16 px-6 mt-6">
                <div class="w-20 h-20 mx-auto mb-5 rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="40 100" stroke-linecap="round"/>
                    </svg>
                </div>
                <h3 class="font-display text-xl font-bold text-slate-900 mb-2">Génération en cours</h3>
                <p class="text-sm text-slate-500 mb-6 max-w-sm mx-auto">
                    Tes cartes seront disponibles ici dans quelques instants. Reviens sur cette page après quelques minutes.
                </p>
                <button onclick="location.reload()" type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold transition active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Rafraîchir
                </button>
            </div>
        @endif

        {{-- Footer --}}
        <div class="text-center mt-10 text-xs text-slate-500 leading-relaxed">
            Achat protégé par <strong class="text-slate-700">KardAfrica</strong> · <a href="mailto:hello@kardafrica.com" class="text-[#44A08D] font-semibold">hello@kardafrica.com</a>
            @if($order->expires_at)
                <div class="mt-1 text-[11px] text-slate-400">Lien valable jusqu'au {{ $order->expires_at->format('d/m/Y') }}</div>
            @endif
        </div>
    </div>
</div>

<script>
const { jsPDF } = window.jspdf;

function claimPage() {
    return {
        downloading: false,
        dlLabel: 'Télécharger PDF',

        /**
         * Capture chaque carte individuellement avec html2canvas,
         * puis compose un PDF A4 propre : entête + chaque carte + footer.
         */
        async downloadTicket() {
            this.downloading = true;
            const oldLabel = this.dlLabel;
            try {
                this.dlLabel = 'Préparation…';

                // 1. Récupère toutes les cartes (chaque .card-row)
                const cardRows = document.querySelectorAll('.card-row');
                if (cardRows.length === 0) {
                    this.dlLabel = 'Aucune carte';
                    setTimeout(() => { this.dlLabel = oldLabel; }, 1500);
                    this.downloading = false;
                    return;
                }

                // 2. Force codes + PIN visibles avant capture
                const previousStates = [];
                cardRows.forEach(row => {
                    if (row._x_dataStack && row._x_dataStack[0]) {
                        const data = row._x_dataStack[0];
                        previousStates.push({ row, code: data.codeShown, pin: data.pinShown });
                        data.codeShown = true;
                        data.pinShown = true;
                    }
                });
                await new Promise(r => setTimeout(r, 200));

                // 3. Init du PDF A4 portrait
                const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
                const pageW = 210, pageH = 297;
                const margin = 12;
                const contentW = pageW - margin * 2;

                // ===== ENTÊTE PDF =====
                pdf.setFillColor(15, 23, 42); // navy
                pdf.rect(0, 0, pageW, 32, 'F');
                pdf.setTextColor(255, 255, 255);
                pdf.setFont('helvetica', 'bold');
                pdf.setFontSize(16);
                pdf.text('KardAfrica · Mes cartes cadeaux', margin, 14);
                pdf.setFont('helvetica', 'normal');
                pdf.setFontSize(9);
                pdf.setTextColor(148, 163, 184);
                pdf.text(`Commande #{{ $order->order_number }}  ·  {{ $order->created_at->format('d/m/Y H:i') }}`, margin, 21);
                pdf.text(`Vendeur : {{ addslashes($order->reseller->name ?? 'KardAfrica') }}  ·  {{ $order->cards->count() }} carte(s)`, margin, 27);
                pdf.setTextColor(94, 234, 212); // cyan
                pdf.text('Conserve ce document — codes confidentiels', pageW - margin, 27, { align: 'right' });

                let y = 40; // position de départ après l'entête

                // ===== UNE CARTE PAR LIGNE =====
                for (let i = 0; i < cardRows.length; i++) {
                    this.dlLabel = `Carte ${i + 1}/${cardRows.length}…`;

                    const row = cardRows[i];
                    // Capture la carte (le grid bg-white avec border, contient visuel + détails)
                    const innerCard = row.querySelector('.bg-white.rounded-2xl');
                    const target = innerCard || row;

                    const canvas = await html2canvas(target, {
                        scale: 2.5,
                        backgroundColor: '#FFFFFF',
                        useCORS: true,
                        logging: false,
                    });

                    const imgData = canvas.toDataURL('image/png');
                    const ratio = canvas.height / canvas.width;
                    const imgH = contentW * ratio;

                    // Si la carte ne tient pas sur la page courante, nouvelle page
                    if (y + imgH > pageH - margin - 10) {
                        pdf.addPage();
                        // Re-dessine un mini-header sur la nouvelle page
                        pdf.setFillColor(15, 23, 42);
                        pdf.rect(0, 0, pageW, 12, 'F');
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFontSize(9);
                        pdf.setFont('helvetica', 'bold');
                        pdf.text('KardAfrica · Mes cartes', margin, 8);
                        pdf.setFont('helvetica', 'normal');
                        pdf.setTextColor(148, 163, 184);
                        pdf.text(`#{{ $order->order_number }}`, pageW - margin, 8, { align: 'right' });
                        y = 18;
                    }

                    pdf.addImage(imgData, 'PNG', margin, y, contentW, imgH);
                    y += imgH + 8;
                }

                // ===== FOOTER =====
                this.dlLabel = 'Finalisation…';
                const totalPages = pdf.internal.getNumberOfPages();
                for (let p = 1; p <= totalPages; p++) {
                    pdf.setPage(p);
                    pdf.setFontSize(8);
                    pdf.setFont('helvetica', 'normal');
                    pdf.setTextColor(148, 163, 184);
                    pdf.text(
                        `Page ${p}/${totalPages}  ·  KardAfrica.com  ·  hello@kardafrica.com`,
                        pageW / 2, pageH - 5, { align: 'center' }
                    );
                }

                // 4. Restaure les states reveal
                previousStates.forEach(s => {
                    if (s.row._x_dataStack && s.row._x_dataStack[0]) {
                        s.row._x_dataStack[0].codeShown = s.code;
                        s.row._x_dataStack[0].pinShown = s.pin;
                    }
                });

                pdf.save('cartes-kardafrica-{{ $order->order_number }}.pdf');
                this.dlLabel = 'Téléchargé ✓';
            } catch (e) {
                console.error(e);
                this.dlLabel = 'Erreur — réessaie';
            }
            setTimeout(() => { this.dlLabel = oldLabel; }, 2500);
            this.downloading = false;
        },
    };
}
</script>

</body>
</html>
