<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#FAFAF7">
    <title>Mes cartes cadeaux — KardAfrica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">
    @vite(['resources/css/app.css'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
            for ($i = 0; $i < strlen($name); $i++) $hash = ord($name[$i]) + (($hash << 5) - $hash);
            return $palette[(($hash % count($palette)) + count($palette)) % count($palette)];
        };
    @endphp
</head>
<body class="bg-[#FAFAF7] text-slate-900" style="font-family: 'Inter', system-ui, sans-serif;">

<div class="min-h-screen pb-20">

    {{-- =================== TOP BAR =================== --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-slate-900 hover:text-[#44A08D] transition">
                <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="KardAfrica" class="w-8 h-8">
                <div>
                    <div class="text-[9px] font-bold tracking-[0.16em] uppercase text-[#44A08D] leading-none">Cartes cadeaux</div>
                    <div class="font-display text-sm font-bold mt-0.5">KardAfrica</div>
                </div>
            </a>
            <div class="font-mono text-xs text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">
                #{{ $order->order_number }}
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 md:pt-10">
        {{-- ====== Wrapper du ticket capturable via html2canvas ====== --}}
        <div id="ticket-pdf">

        {{-- =================== HERO COMMANDE =================== --}}
        <div class="bg-gradient-to-br from-[#0F172A] via-[#1E293B] to-[#0F172A] rounded-3xl overflow-hidden shadow-xl text-white relative mb-6">
            <div class="absolute top-0 right-0 w-72 h-72 -translate-y-32 translate-x-16 rounded-full opacity-30" style="background:radial-gradient(circle,#5EEAD4,transparent 70%);"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 translate-y-24 -translate-x-12 rounded-full opacity-20" style="background:radial-gradient(circle,#7DD3FC,transparent 70%);"></div>

            <div class="relative p-6 md:p-8">

                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-[0.14em]"
                             style="background:rgba(78,205,196,0.15);color:#5EEAD4;border:1px solid rgba(78,205,196,0.30);">
                            <span class="relative flex w-2 h-2">
                                <span class="absolute inset-0 rounded-full bg-[#5EEAD4] opacity-50 animate-ping"></span>
                                <span class="relative w-2 h-2 rounded-full bg-[#5EEAD4]"></span>
                            </span>
                            Cartes activées
                        </div>
                        <h1 class="font-display text-2xl md:text-3xl font-bold mt-3 leading-tight">
                            Tes cartes sont prêtes ! 🎁
                        </h1>
                        <p class="text-sm text-slate-300 mt-2">
                            Achetées chez <strong class="text-white">{{ $order->reseller->name ?? 'KardAfrica' }}</strong>
                            · {{ $order->created_at->translatedFormat('d F Y') }}
                        </p>
                    </div>
                </div>

                @if($order->cards->count() > 0)
                    @php
                        $totalFcfa = $order->cards->sum(fn($c) => \App\Support\Money::toFcfa($c->face_value, $c->currency ?: 'XAF'));
                    @endphp
                    {{-- Stats grid (style orders/show) --}}
                    <div class="grid grid-cols-3 gap-2 sm:gap-3 pt-4 border-t border-white/10">
                        <div class="bg-white/5 border border-white/10 rounded-xl p-3">
                            <div class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Cartes</div>
                            <div class="font-display text-xl font-bold mt-1 tabular-nums">{{ $order->cards->count() }}</div>
                        </div>
                        <div class="rounded-xl p-3" style="background:linear-gradient(135deg,rgba(78,205,196,0.18),rgba(94,234,212,0.06));border:1px solid rgba(78,205,196,0.30);">
                            <div class="text-[10px] uppercase tracking-wider font-bold text-[#5EEAD4]">Valeur totale</div>
                            <div class="font-display text-xl font-bold mt-1 text-[#5EEAD4] tabular-nums">
                                {{ number_format($totalFcfa, 0, ',', ' ') }}
                                <span class="text-[10px] font-medium text-slate-400">FCFA</span>
                            </div>
                        </div>
                        <div class="bg-white/5 border border-white/10 rounded-xl p-3">
                            <div class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Récup. le</div>
                            <div class="font-display text-base font-bold mt-1 tabular-nums">{{ ($order->claimed_at ?? now())->format('d/m/Y') }}</div>
                        </div>
                    </div>

                    {{-- Action button : télécharger le ticket entier en PDF via html2canvas --}}
                    <div class="mt-5" x-data="claimActions()">
                        <button @click="downloadTicket()" :disabled="downloading" type="button"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-gradient-to-br from-[#44A08D] to-[#4ECDC4] text-white font-bold text-sm shadow-lg shadow-[#44A08D]/30 hover:shadow-xl hover:scale-[1.02] active:scale-95 transition disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg x-show="!downloading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <svg x-show="downloading" x-cloak class="w-4 h-4 animate-spin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="50 100"/></svg>
                            <span x-text="label"></span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        @if($order->cards->count() > 0)
            {{-- =================== CARDS GRID (style /cards) =================== --}}
            <div class="flex items-center justify-between mb-4 no-print">
                <h2 class="font-display text-lg font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-teal-50 text-[#44A08D] flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </span>
                    {{ $order->cards->count() }} carte{{ $order->cards->count() > 1 ? 's' : '' }}
                </h2>
                <span class="text-xs text-slate-500 font-medium hidden sm:inline">Touche l'œil pour révéler</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($order->cards as $card)
                    @php
                        $brandColor = $brandColorFor($card->brand ?? $card->name ?? '');
                        $cardCode   = $card->card_code ?? '';
                        $cardPin    = $card->pin ?? null;
                        $cardFcfa   = \App\Support\Money::toFcfa($card->face_value, $card->currency ?: 'XAF');
                    @endphp

                    <article x-data="{ codeShown: false, pinShown: false, copiedField: null,
                                       copy(text, field) {
                                           navigator.clipboard.writeText(text).then(() => {
                                               this.copiedField = field;
                                               setTimeout(() => this.copiedField = null, 1800);
                                           });
                                       } }"
                             class="group">

                        <div id="card-{{ $card->id }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">

                            {{-- ===== Visuel carte (style cards/index) ===== --}}
                            <div style="background-color: {{ $brandColor }}" class="relative h-40 p-5 overflow-hidden">
                                <svg class="absolute inset-0 w-full h-full opacity-[0.08]" aria-hidden="true">
                                    <defs>
                                        <pattern id="cl-{{ $card->id }}" width="32" height="32" patternUnits="userSpaceOnUse">
                                            <circle cx="16" cy="16" r="13" fill="none" stroke="white" stroke-width="1.2"/>
                                        </pattern>
                                    </defs>
                                    <rect width="100%" height="100%" fill="url(#cl-{{ $card->id }})"/>
                                </svg>
                                <div class="absolute -top-12 -right-12 w-40 h-40 rounded-full bg-white/15 blur-2xl"></div>

                                <div class="relative h-full flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-white/80 text-[10px] font-bold tracking-[0.2em] uppercase">Gift Card</span>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold backdrop-blur-sm border border-emerald-300/30 bg-emerald-500/30 text-white">
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
                                            <div class="text-white font-display text-lg font-bold tabular-nums leading-none mt-0.5">
                                                {{ number_format($cardFcfa, 0, ',', ' ') }}
                                                <span class="text-xs font-normal text-white/60">FCFA</span>
                                            </div>
                                        </div>
                                        <div class="w-9 h-6 rounded bg-gradient-to-br from-yellow-200/90 to-yellow-400/70 border border-yellow-300/40 shadow-inner"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- ===== Détails (style cards/index) ===== --}}
                            <div class="p-4 space-y-3 no-print">
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="text-sm font-semibold text-slate-900 line-clamp-1">{{ $card->name }}</h4>
                                </div>

                                {{-- Code masqué + reveal --}}
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Code</span>
                                        <div class="flex items-center gap-0.5">
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
                                    <div class="font-mono text-xs font-bold text-slate-900 tracking-wider truncate"
                                         x-text="codeShown ? '{{ $cardCode }}' : '{{ str_repeat('•', max(8, strlen($cardCode))) }}'"></div>
                                </div>

                                {{-- PIN + Expiration --}}
                                @if($cardPin || $card->expiration_date)
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        @if($cardPin)
                                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">PIN</span>
                                                    <button @click="pinShown = !pinShown" type="button" class="p-0.5 rounded text-slate-400 hover:text-[#44A08D] transition">
                                                        <svg x-show="!pinShown" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                        <svg x-show="pinShown" x-cloak class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                                    </button>
                                                </div>
                                                <div class="font-mono text-xs font-bold text-slate-900 tabular-nums"
                                                     x-text="pinShown ? '{{ $cardPin }}' : '{{ str_repeat('•', strlen($cardPin)) }}'"></div>
                                            </div>
                                        @endif
                                        @if($card->expiration_date)
                                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5">
                                                <div class="text-[10px] uppercase tracking-wider font-bold text-slate-400 mb-1">Expire</div>
                                                <div class="text-xs font-bold text-slate-900 tabular-nums">{{ $card->expiration_date->format('d/m/Y') }}</div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Footer : date + bouton PDF --}}
                                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                                    <span class="text-[10px] text-slate-500">
                                        {{ $card->created_at->format('d/m/Y · H\hi') }}
                                    </span>
                                    <button @click="copy('{{ addslashes($cardCode) }}', 'code')" type="button"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-[#44A08D] hover:bg-[#3d9180] text-white text-[11px] font-semibold transition active:scale-95">
                                        <svg x-show="copiedField !== 'code'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <svg x-show="copiedField === 'code'" x-cloak class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <span x-show="copiedField !== 'code'">Copier le code</span>
                                        <span x-show="copiedField === 'code'" x-cloak>Copié !</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            {{-- Empty state --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm text-center py-16 px-6">
                <div class="w-20 h-20 mx-auto mb-5 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-[#44A08D] animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="40 100" stroke-linecap="round"/>
                    </svg>
                </div>
                <h3 class="font-display text-xl font-bold text-slate-900 mb-2">Cartes en cours de génération</h3>
                <p class="text-sm text-slate-500 mb-6 max-w-sm mx-auto">
                    Tes cartes seront disponibles ici dans quelques instants. Reviens sur cette page après quelques minutes.
                </p>
                <button onclick="location.reload()" type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold transition active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Rafraîchir
                </button>
            </div>
        @endif

        </div> {{-- /#ticket-pdf --}}

        {{-- =================== FOOTER =================== --}}
        <div class="text-center mt-10 text-xs text-slate-500 leading-relaxed">
            Achat protégé par <strong class="text-slate-700">KardAfrica</strong> · <a href="mailto:hello@kardafrica.com" class="text-[#44A08D] font-semibold">hello@kardafrica.com</a>
            @if($order->expires_at)
                <div class="mt-1 text-[11px] text-slate-400">Lien valable jusqu'au {{ $order->expires_at->format('d/m/Y') }}</div>
            @endif
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .animate-spin { animation: spin 1s linear infinite; }
    .animate-ping {
        animation: ping 1.6s cubic-bezier(0,0,0.2,1) infinite;
    }
    @keyframes ping { 75%, 100% { transform: scale(2); opacity: 0; } }

    @media print {
        body { background: white !important; }
        .no-print { display: none !important; }
        article { break-inside: avoid; page-break-inside: avoid; }
    }
</style>

<script>
const { jsPDF } = window.jspdf;

function claimActions() {
    return {
        downloading: false,
        label: 'Télécharger le ticket (PDF)',

        async downloadTicket() {
            this.downloading = true;
            const oldLabel = this.label;
            try {
                this.label = 'Préparation du ticket…';

                const ticket = document.getElementById('ticket-pdf');
                if (!ticket) throw new Error('ticket-pdf introuvable');

                // Forcer tous les codes/PIN en clair pour qu'ils apparaissent dans le PDF
                const reveals = ticket.querySelectorAll('[x-data]');
                const previousStates = [];
                reveals.forEach(el => {
                    if (el._x_dataStack && el._x_dataStack[0]) {
                        const data = el._x_dataStack[0];
                        previousStates.push({ el, code: data.codeShown, pin: data.pinShown });
                        if ('codeShown' in data) data.codeShown = true;
                        if ('pinShown' in data)  data.pinShown = true;
                    }
                });
                await new Promise(r => setTimeout(r, 150));

                this.label = 'Capture en cours…';

                // Capture la zone ticket entière en image haute résolution
                const canvas = await html2canvas(ticket, {
                    scale: 2,
                    backgroundColor: '#FAFAF7',
                    useCORS: true,
                    logging: false,
                    windowWidth: ticket.scrollWidth,
                });

                // Restaure les états reveal
                previousStates.forEach(s => {
                    if (s.el._x_dataStack && s.el._x_dataStack[0]) {
                        if ('codeShown' in s.el._x_dataStack[0]) s.el._x_dataStack[0].codeShown = s.code;
                        if ('pinShown'  in s.el._x_dataStack[0]) s.el._x_dataStack[0].pinShown  = s.pin;
                    }
                });

                this.label = 'Génération PDF…';

                // Construit le PDF A4 portrait, multi-pages si nécessaire
                const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
                const pageW = 210, pageH = 297;
                const imgW  = pageW;
                const imgH  = (canvas.height * imgW) / canvas.width;

                let heightLeft = imgH;
                let position   = 0;
                const imgData  = canvas.toDataURL('image/png');

                pdf.addImage(imgData, 'PNG', 0, position, imgW, imgH);
                heightLeft -= pageH;

                while (heightLeft > 0) {
                    position = heightLeft - imgH;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgW, imgH);
                    heightLeft -= pageH;
                }

                pdf.save('ticket-kardafrica-{{ $order->order_number }}.pdf');
                this.label = 'Téléchargé ✓';
            } catch (e) {
                console.error(e);
                this.label = 'Erreur — réessaie';
            }
            setTimeout(() => { this.label = oldLabel; }, 2500);
            this.downloading = false;
        },
    };
}
</script>

</body>
</html>
