{{-- ================================================================
     Bandeau « carte populaire » — accueil + tête de boutique.
     La STRUCTURE est fixe ; la carte tirée change à chaque rafraîchissement
     et apporte son propre titre, sa description, sa couleur, son logo et son
     bouton (App\Support\PopularHighlights). Rien n'est affiché si le catalogue
     n'a rien pu résoudre : jamais de lien mort ni de prix inventé.
     ================================================================ --}}
@php
    $hl = \App\Support\PopularHighlights::pick();
@endphp
@if($hl)
    @php
        [$titleBefore, $titleBrand, $titleAfter] = $hl['title'];
        $color   = $hl['color'];
        $logoUrl = asset('logos/brands/' . $hl['logo'] . '.svg');
        // Logo monochrome recoloré à la teinte de la marque (masque CSS) —
        // même technique que les pills de la boutique.
        $logoMask = "background-color: {$color}; -webkit-mask: url('{$logoUrl}') left center / contain no-repeat; mask: url('{$logoUrl}') left center / contain no-repeat;";
        [$flag, $regionLabel] = \App\Support\BrandStyle::region($hl['country_code'] ?? null);

        // Fond vidéo animé de la marque, s'il a été livré. Absent = dégradé seul :
        // on vérifie le fichier à chaque rendu (pas de cache) pour qu'un clip
        // déposé apparaisse immédiatement.
        $videoPath = 'assets/videos/highlights/' . $hl['key'] . '.mp4';
        $hasVideo  = is_file(public_path($videoPath));
        // Un clip clair (Apple, Google Play) sous un voile noir virerait au gris
        // sale : le voile et la couleur du sous-titre suivent la tonalité du clip.
        $darkVideo = $hasVideo && ($hl['video_tone'] ?? 'dark') === 'dark';
    @endphp
    <div class="rounded-3xl bg-white border border-slate-100 shadow-card overflow-hidden"
         data-highlight="{{ $hl['key'] }}">
        <div class="grid md:grid-cols-2 items-stretch">

            {{-- Colonne gauche : discours propre à la carte tirée --}}
            <div class="p-6 sm:p-8 lg:p-10 flex flex-col justify-center">
                <span class="inline-flex items-center gap-1.5 self-start px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase mb-3"
                      style="background-color: {{ $color }}1A; border: 1px solid {{ $color }}4D; color: {{ $color }};">
                    <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background-color: {{ $color }};"></span>
                    {{ $hl['badge'] }}
                </span>

                <h3 class="font-display text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight leading-tight">
                    {{ $titleBefore }} <span style="color: {{ $color }};">{{ $titleBrand }}</span><br class="hidden sm:block">
                    {{ $titleAfter }}
                </h3>
                <p class="text-sm sm:text-base text-slate-500 mt-3 max-w-md">
                    {{ $hl['tagline'] }}
                </p>

                <a href="{{ route('card-type.show', $hl['card_type_id']) }}"
                   class="mt-6 self-start inline-flex items-center gap-2 px-5 py-3 rounded-xl text-white font-semibold shadow-lg transition-all active:scale-95 hover:brightness-110"
                   style="background-color: {{ $color }}; box-shadow: 0 10px 15px -3px {{ $color }}4D;">
                    {{ $hl['cta'] }}
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>

            {{-- Colonne droite : aperçu carte (fond clair → logo lisible) --}}
            <div class="relative overflow-hidden p-6 sm:p-8 lg:p-10 flex items-center justify-center border-t md:border-t-0 md:border-l {{ $darkVideo ? 'border-slate-800' : 'border-slate-100' }}"
                 style="background-image: {{ $darkVideo
                    ? 'linear-gradient(160deg, #12141A 0%, #0B0D12 55%, ' . $color . '2E 100%)'
                    : 'linear-gradient(to bottom right, #F8FAFC, ' . $color . '0D)' }};">

                @if($hasVideo)
                    {{-- Fond animé de la marque tirée. Sans src au rendu : le JS ne
                         la charge que si l'écran la voit ET que la connexion le
                         permet (données chères au Gabon). Sinon le dégradé sombre
                         ci-dessus reste seul — le rendu ne dépend jamais du clip. --}}
                    <video class="ka-hl-video absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-700"
                           data-src="{{ asset($videoPath) }}"
                           autoplay muted loop playsinline preload="none"
                           aria-hidden="true" tabindex="-1"></video>
                    {{-- Voile : garantit le contraste de la carte, dans la tonalité du clip --}}
                    <div class="absolute inset-0 bg-gradient-to-t {{ $darkVideo
                        ? 'from-black/55 via-black/25 to-black/45'
                        : 'from-white/60 via-white/25 to-white/50' }}" aria-hidden="true"></div>
                @endif

                <div class="relative z-10 w-full max-w-xs">
                    <div class="rounded-2xl bg-white border border-slate-200 shadow-xl p-5">
                        <div class="flex items-center justify-between">
                            <span class="h-7 w-24 block" role="img" aria-label="{{ $hl['brand'] }}" style="{{ $logoMask }}"></span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $regionLabel }}</span>
                        </div>
                        <div class="mt-8">
                            <div class="font-display text-lg font-bold text-slate-900">{{ $hl['brand'] }}</div>
                            <div class="mt-3 pt-3 border-t border-slate-100 flex items-end justify-between">
                                <span class="text-[10px] text-slate-400 font-medium">À partir de</span>
                                <span class="font-display text-xl font-black text-slate-900 tabular-nums">
                                    {{ number_format($hl['price_fcfa'], 0, ',', ' ') }} <span class="text-[10px] text-slate-500 font-bold">FCFA</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <p class="text-center text-[11px] mt-3 {{ $darkVideo ? 'text-white font-medium' : ($hasVideo ? 'text-slate-600 font-medium' : 'text-slate-400') }}"
                       @if($hasVideo) style="text-shadow: 0 1px 4px {{ $darkVideo ? 'rgba(0,0,0,.85)' : 'rgba(255,255,255,.9)' }};" @endif>
                        Livraison instantanée du code
                    </p>
                </div>
            </div>

        </div>
    </div>

    @if($hasVideo)
        @once
            <script>
            // Chargement du fond animé, sous conditions — la page est complète
            // sans lui (dégradé sombre) : aucun octet n'est dépensé si l'écran ne
            // le voit pas, si l'utilisateur économise ses données, ou s'il a
            // demandé moins d'animations.
            (function () {
                var videos = document.querySelectorAll('.ka-hl-video');
                if (!videos.length) return;

                var conn = navigator.connection || {};
                var frugal = conn.saveData === true
                    || ['slow-2g', '2g'].indexOf(conn.effectiveType) !== -1;
                var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (frugal || calm) return;

                var start = function (video) {
                    if (video.src) return;
                    video.src = video.dataset.src;
                    video.muted = true;                       // requis pour l'autoplay
                    var played = video.play();
                    var reveal = function () { video.classList.remove('opacity-0'); };
                    played && played.then ? played.then(reveal).catch(function () {}) : reveal();
                    video.addEventListener('playing', reveal, { once: true });
                };

                if (!('IntersectionObserver' in window)) {
                    videos.forEach(start);
                    return;
                }
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) return;
                        start(entry.target);
                        observer.unobserve(entry.target);
                    });
                }, { rootMargin: '200px' });
                videos.forEach(function (v) { observer.observe(v); });
            })();
            </script>
        @endonce
    @endif
@endif
