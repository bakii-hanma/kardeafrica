@extends('layouts.app')

@section('title', 'Comment ça marche — Acheter une carte cadeau | KardAfrica')
@section('meta_description', 'Choisissez parmi plus de 300 marques, payez par Airtel Money, Moov Money ou carte bancaire, recevez votre code en 30 secondes dans votre espace client. Voici comment KardAfrica fonctionne, en 3 étapes.')

@push('head')
{{-- Schema HowTo (3 étapes) + Breadcrumb — via json_encode (piège @@context Blade) --}}
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'HowTo',
    'name'     => 'Comment acheter une carte cadeau sur KardAfrica',
    'description' => 'Trois étapes pour obtenir votre carte cadeau et l\'utiliser tout de suite.',
    'totalTime' => 'PT2M',
    'step' => [
        ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Choisissez',
         'text' => 'Parcourez le catalogue : Netflix, Steam, Apple, plus de 300 marques disponibles.'],
        ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Payez',
         'text' => 'Airtel Money, Moov Money ou carte bancaire. Paiement sécurisé via E-Billing, opérateur de paiement gabonais agréé.'],
        ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Recevez',
         'text' => 'Votre code apparaît aussitôt dans votre espace client, rubrique « Mes cartes » — prêt à utiliser.'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Comment ça marche', 'item' => url()->current()],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
@php
    $steps = [
        ['n' => 1, 'title' => 'Choisissez',
         'text' => 'Parcourez le catalogue : Netflix, Steam, Apple, plus de 300 marques disponibles.'],
        ['n' => 2, 'title' => 'Payez',
         'text' => 'Airtel Money, Moov Money ou carte bancaire. Paiement sécurisé via E-Billing, opérateur de paiement gabonais agréé.'],
        ['n' => 3, 'title' => 'Recevez',
         'text' => 'Votre code apparaît aussitôt dans votre espace client, rubrique « Mes cartes » — prêt à utiliser.'],
    ];
@endphp

<div class="bg-[#FAFAF7] min-h-screen">

    {{-- ================================================================
         TITRE DE LA PAGE
         ================================================================ --}}
    <section class="max-w-3xl mx-auto px-4 sm:px-6 pt-14 pb-8 text-center">
        <span class="inline-block px-3 py-1 rounded-full bg-teal-50 text-[#44A08D] text-xs font-bold tracking-wider uppercase mb-4 border border-teal-100">Simple et rapide</span>
        <h1 class="font-display text-3xl md:text-5xl font-bold text-slate-900 tracking-tight leading-tight">
            Comment ça marche
        </h1>
        <p class="text-base md:text-lg text-slate-500 mt-4">
            Trois étapes pour obtenir votre carte cadeau et l'utiliser tout de suite.
        </p>
    </section>

    {{-- ================================================================
         SECTION SCRUB SCROLL — la vidéo (15 s, 3 chapitres de 5 s) est
         pilotée par le scroll ; les 3 textes s'activent par tiers.
         Fallback automatique (cartes statiques) si : vidéo absente,
         prefers-reduced-motion, ou échec de chargement.
         ================================================================ --}}
    {{-- Deux masters : 16:9 (desktop) et 9:16 (mobile, 1,7 Mo — data chère au
         Gabon). Le JS choisit selon la largeur et rebascule à la rotation. --}}
    <section id="ccm-scrub" class="relative" style="height: 340vh;"
             data-video="{{ asset('assets/videos/comment-ca-marche-scrub.mp4') }}"
             data-video-mobile="{{ asset('assets/videos/comment-ca-marche-scrub-mobile.mp4') }}">
        {{-- Plein écran, sans encart : la vidéo occupe TOUTE la section épinglée
             (object-cover) — plus de cadre qui « monte » au scroll. --}}
        <div class="sticky top-0 h-screen w-full overflow-hidden bg-[#F5EFE2]">

            {{-- Vidéo scrubbée, plein cadre --}}
            <video id="ccm-video" muted playsinline preload="auto"
                   class="absolute inset-0 w-full h-full object-cover" aria-hidden="true"></video>

            {{-- Voile bas pour la lisibilité du texte --}}
            <div class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-slate-900/80 via-slate-900/35 to-transparent pointer-events-none"></div>

            {{-- Overlay texte de l'étape courante (change par tiers de scroll) --}}
            {{-- pb/pr généreux sur mobile : le bouton WhatsApp flottant occupe
                 le coin bas-droit, le texte ne doit pas passer dessous. --}}
            <div class="absolute inset-x-0 bottom-0 pointer-events-none">
                <div class="max-w-5xl mx-auto px-5 sm:px-8 pb-24 sm:pb-14 pr-20 sm:pr-8">

                    {{-- Progression : 3 points, AU-DESSUS du texte (sinon un
                         libellé sur 3 lignes vient les recouvrir). --}}
                    <div class="flex items-center gap-2 mb-4" aria-hidden="true">
                        @foreach($steps as $i => $step)
                            <span class="ccm-dot h-1.5 rounded-full transition-all duration-300 {{ $i === 0 ? 'w-10 bg-[#4ECDC4]' : 'w-5 bg-white/35' }}" data-dot="{{ $i }}"></span>
                        @endforeach
                    </div>

                    {{-- Tous les libellés sont superposés (absolute) dans une zone
                         de hauteur réservée : le texte le plus long ne décale rien. --}}
                    <div class="relative min-h-[104px] sm:min-h-[112px]">
                        @foreach($steps as $i => $step)
                            <div class="ccm-step-label absolute inset-x-0 top-0 transition-all duration-300 {{ $i === 0 ? '' : 'opacity-0 translate-y-3' }}" data-step="{{ $i }}">
                                <div class="flex items-start gap-4">
                                    <span class="w-11 h-11 md:w-14 md:h-14 rounded-full bg-gradient-to-br from-[#44A08D] to-[#4ECDC4] text-white font-display font-bold text-xl md:text-2xl flex items-center justify-center shadow-lg shrink-0">{{ $step['n'] }}</span>
                                    <div class="min-w-0">
                                        <div class="font-display text-2xl md:text-4xl font-bold text-white leading-tight">{{ $step['title'] }}</div>
                                        <p class="text-sm md:text-base text-slate-200 mt-1.5 max-w-2xl">{{ $step['text'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================================
         FALLBACK STATIQUE — affiché si la vidéo est indisponible ou si
         l'utilisateur préfère réduire les animations. Masqué sinon (JS).
         ================================================================ --}}
    <section id="ccm-static" class="max-w-5xl mx-auto px-4 sm:px-6 pb-4 hidden">
        <div class="grid md:grid-cols-3 gap-5">
            @foreach($steps as $step)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-7 text-center">
                    <span class="mx-auto w-12 h-12 rounded-full bg-gradient-to-br from-[#44A08D] to-[#4ECDC4] text-white font-display font-bold text-xl flex items-center justify-center shadow-lg mb-4">{{ $step['n'] }}</span>
                    <h2 class="font-display text-xl font-bold text-slate-900">{{ $step['title'] }}</h2>
                    <p class="text-sm text-slate-500 mt-2 leading-relaxed">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================================================================
         RÉASSURANCE + CTA
         ================================================================ --}}
    <section class="max-w-5xl mx-auto px-4 sm:px-6 py-14">
        <div class="grid sm:grid-cols-3 gap-4 mb-10">
            @foreach ([
                ['⚡', 'Code en 30 secondes', 'Livraison instantanée après confirmation du paiement.'],
                ['🛡️', 'Paiement sécurisé', 'E-Billing, opérateur de paiement gabonais agréé.'],
                ['✅', 'Garantie 24 h', 'Code invalide ou non reçu = remboursé sous 24 h.'],
            ] as [$emoji, $t, $d])
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-5 flex items-start gap-3">
                    <span class="text-2xl leading-none">{{ $emoji }}</span>
                    <div>
                        <div class="text-sm font-bold text-slate-900">{{ $t }}</div>
                        <p class="text-xs text-slate-500 mt-1">{{ $d }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-3xl bg-gradient-to-br from-[#0F172A] to-[#134E4A] p-10 text-center">
            <h2 class="font-display text-2xl md:text-3xl font-bold text-white">Prêt à essayer ?</h2>
            <p class="text-sm text-slate-300 mt-2">Plus de 300 marques vous attendent — payables en FCFA.</p>
            <a href="{{ route('boutique') }}" class="inline-flex items-center gap-2 mt-6 px-7 py-3.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white font-bold text-sm transition">
                Explorer la boutique
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </section>
</div>

<script>
(function () {
    const section = document.getElementById('ccm-scrub');
    const video   = document.getElementById('ccm-video');
    const statics = document.getElementById('ccm-static');
    const labels  = [...document.querySelectorAll('.ccm-step-label')];
    const dots    = [...document.querySelectorAll('.ccm-dot')];

    // Fallback : reduced-motion ou vidéo indisponible → cartes statiques.
    const fallback = () => {
        section.style.display = 'none';
        statics.classList.remove('hidden');
    };
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { fallback(); return; }

    // Chargement en BLOB : garantit une vidéo entièrement seekable (le scrub
    // exige seekable sur toute la durée ; certains serveurs sans Range requests
    // produisent seekable=[0,0] en src direct). Fallback statique si échec.
    video.addEventListener('error', fallback);

    let duration = 0;
    const readDuration = () => { duration = video.duration || 15; };
    video.addEventListener('loadedmetadata', readDuration);
    // Vidéo en cache : loadedmetadata peut partir AVANT l'ajout du listener
    if (video.readyState >= 1) readDuration();

    // Master adapté à l'écran : 9:16 sur mobile (plus léger + cadrage natif),
    // 16:9 au-delà. Rebascule si l'orientation change vraiment de format.
    const mobileMQ = window.matchMedia('(max-width: 767px)');
    let loadedSrc = null, objectUrl = null;

    const loadVideo = () => {
        const src = (mobileMQ.matches && section.dataset.videoMobile)
            ? section.dataset.videoMobile
            : section.dataset.video;
        if (src === loadedSrc) return;
        loadedSrc = src;
        fetch(src)
            .then(r => { if (!r.ok) throw new Error('http ' + r.status); return r.blob(); })
            .then(blob => {
                if (objectUrl) URL.revokeObjectURL(objectUrl);   // pas de fuite mémoire
                objectUrl = URL.createObjectURL(blob);
                video.src = objectUrl;
                duration = 0;                                     // relu au loadedmetadata
            })
            .catch(fallback);
    };

    loadVideo();
    // addEventListener sur MediaQueryList : supporté partout depuis 2020
    mobileMQ.addEventListener('change', loadVideo);

    let target = 0, current = 0, raf = null;

    const setStep = (idx) => {
        labels.forEach((el, i) => {
            const active = i === idx;
            el.classList.toggle('opacity-0', !active);
            el.classList.toggle('translate-y-3', !active);
        });
        dots.forEach((el, i) => {
            el.classList.toggle('w-10', i === idx);
            el.classList.toggle('bg-[#4ECDC4]', i === idx);
            el.classList.toggle('w-5', i !== idx);
            el.classList.toggle('bg-white/35', i !== idx);
        });
    };

    const applyFrame = (p) => {
        if (duration && video.readyState >= 2) {
            try { video.currentTime = p * duration; } catch (e) {}
        }
        setStep(Math.min(2, Math.floor(p * 3)));
    };

    const tick = () => {
        // Lissage : la frame affichée rattrape la cible en douceur (scrub fluide)
        current += (target - current) * 0.18;
        applyFrame(current);
        if (Math.abs(target - current) > 0.0005) {
            raf = requestAnimationFrame(tick);
        } else {
            raf = null;
        }
    };

    const onScroll = () => {
        const rect = section.getBoundingClientRect();
        const scrollable = rect.height - window.innerHeight;
        if (scrollable <= 0) return; // viewport dégradé : ne jamais produire NaN
        target = Math.min(1, Math.max(0, -rect.top / scrollable));
        // rAF est suspendu quand l'onglet est caché : appliquer directement
        // (sans lissage) pour que la position reste juste au retour.
        if (document.hidden) {
            current = target;
            applyFrame(current);
            return;
        }
        if (!raf) raf = requestAnimationFrame(tick);
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
</script>
@endsection
