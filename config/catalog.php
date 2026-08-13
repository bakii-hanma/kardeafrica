<?php

/*
|--------------------------------------------------------------------------
| Classification du catalogue KardAfrica
|--------------------------------------------------------------------------
|
| L'API fournisseur (Bamboo) ne renvoie AUCUNE catégorie : le payload produit
| ne contient que name / brand / countryCode / currencyCode. La catégorisation
| est donc 100 % locale et doit être déterministe, testable et versionnée.
|
| Deux axes indépendants sont calculés ici :
|
|   1. category_id   -> dans quel rayon la carte est rangée
|   2. redeem_model  -> comment la carte se rachète, ce qui pilote la visibilité
|
|        global          aucune contrainte de pays/devise (voucher au porteur,
|                        clé de jeu, crypto). Se rachète depuis le Gabon.
|        account_region  se rachète, mais uniquement sur un compte enregistré
|                        dans le pays de la carte (PSN, Apple, Netflix...).
|        physical        rachat en magasin ou livraison dans le pays d'émission.
|                        Inutilisable depuis le Gabon -> masqué.
|
| Les règles sont ordonnées : la PREMIÈRE qui matche gagne. L'ordre est donc
| significatif — ne pas trier alphabétiquement.
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Drapeau de bascule
    |----------------------------------------------------------------------
    | false = catégorisation héritée (mots-clés cumulatifs, tout visible).
    | true  = CatalogClassifier (un rayon par carte, visibilité rachat+devise).
    | La bascule invalide d'elle-même les caches (clés distinctes v8/v9).
    */
    'use_classifier' => env('CATALOG_CLASSIFIER', false),


    /*
    |----------------------------------------------------------------------
    | Rayons
    |----------------------------------------------------------------------
    | 1-8 préexistent. TROIS écarts au patch d'origine, décidés le 12 août :
    |
    |   5  Daywatch est CONSERVÉ (décision client), au lieu d'être recyclé en
    |      « Mobile & Recharges ». Il ne porte que les produits Daywatch locaux.
    |   11 « Mobile & Recharges » reçoit donc son propre id : ses 114 références
    |      (PayPal, Neosurf, recharges Airtel/Moov, cartes prépayées) sont trop
    |      pertinentes pour le Gabon pour retomber dans Shopping — rayon
    |      `physical`, donc invisible.
    | `featured` : Musique et Logiciels sont EN ACCUEIL (arbitrage client du
    | 12 août, après un passage en secondaires). Réserve mesurée : Musique ne
    | porte que ~16 références et Logiciels ~6 — la règle 9 « App Store » capte
    | Apple/iTunes en amont, ce qui est voulu mais vide Musique. Deux rayons
    | maigres en page d'accueil.
    |
    |   10 « Logiciels » est ajouté (décision client) : Windows, Office, Norton,
    |      BitDefender, NordVPN, Surfshark n'avaient aucun rayon. C'était le
    |      trou principal de la taxonomie (mesuré : NordVPN 0/3 catégorisés).
    */
    'categories' => [
        1 => ['name' => 'Divertissement',            'emoji' => '🎬', 'icon' => 'film',          'featured' => true],
        2 => ['name' => 'Jeux Vidéo',                'emoji' => '🎮', 'icon' => 'gamepad-2',     'featured' => true],
        3 => ['name' => 'Musique',                   'emoji' => '🎵', 'icon' => 'music',         'featured' => true],
        4 => ['name' => 'Shopping international',    'emoji' => '🛍️', 'icon' => 'shopping-cart', 'featured' => false],
        5 => ['name' => 'Daywatch',                  'emoji' => '📺', 'icon' => 'tv',            'featured' => true],
        6 => ['name' => 'Voyage',                    'emoji' => '✈️', 'icon' => 'map',           'featured' => false],
        7 => ['name' => 'Intelligence Artificielle', 'emoji' => '🤖', 'icon' => 'sparkles',      'featured' => true],
        8 => ['name' => 'Crypto',                    'emoji' => '₿',  'icon' => 'bitcoin',       'featured' => true],
        9 => ['name' => 'App Store & Google Play',   'emoji' => '📲', 'icon' => 'grid',          'featured' => true],
        10 => ['name' => 'Logiciels',                'emoji' => '💻', 'icon' => 'monitor',       'featured' => true],
        11 => ['name' => 'Mobile & Recharges',       'emoji' => '📱', 'icon' => 'smartphone',    'featured' => true],
    ],

    /*
    |----------------------------------------------------------------------
    | Ordre d'AFFICHAGE des rayons (filtres, navigation, accueil)
    |----------------------------------------------------------------------
    | Décidé par le client le 12 août. À ne pas confondre avec l'ordre des
    | RÈGLES ci-dessous, qui est un ordre de résolution (premier match gagne) :
    | les deux sont indépendants et n'ont aucune raison de coïncider.
    |
    | Les identifiants ne bougent pas — un rayon garde son id, seule sa place
    | dans la liste change. Un rayon absent de cette liste se range après, dans
    | l'ordre de sa déclaration.
    */
    'display_order' => [
        7,   // Intelligence Artificielle — remontée en tête le 12 août
        9,   // App Store & Google Play — les deux marques les plus demandées
        1,   // Divertissement
        5,   // Daywatch
        2,   // Jeux Vidéo
        3,   // Musique
        6,   // Voyage
        4,   // Shopping international
        11,  // Mobile & Recharges
        8,   // Crypto
        10,  // Logiciels
    ],

    /*
    |----------------------------------------------------------------------
    | Règles de catégorie — ordre significatif, première correspondance gagne
    |----------------------------------------------------------------------
    | Testées sur les 2 400 marques réellement présentes au catalogue :
    | 0 orphelin. Le rayon 4 est le filet de sécurité (retail), volontairement
    | placé en défaut plutôt qu'en règle.
    */
    'category_rules' => [

        // -- Crypto ------------------------------------------------------
        [8, '/binance|crypto|bitnovo|bitcoin|ethereum|usdt|usdc|\bbtc\b|\beth\b|\bxrp\b|solana|coinbase|kraken|bitpanda|bitsa|litecoin|dogecoin|blockchain/i'],

        // -- Intelligence artificielle -----------------------------------
        [7, '/chatgpt|openai|anthropic|claude ai|midjourney|perplexity|copilot|artificial intelligen|elevenlabs|runway ?ml|jasper ai|\bai gift/i'],

        // -- App stores (avant Musique : Apple/iTunes doit tomber ici) ----
        [9, '/app store|itunes|google play|microsoft store|huawei|appgallery|galaxy store|amazon appstore|^apple\b|apple gift|apple store/i'],

        // -- Jeux vidéo --------------------------------------------------
        [2, '/playstation|\bpsn\b|xbox|nintendo|steam|roblox|robux|epic games|fortnite|riot|valorant|league of legends|wild rift|runeterra|teamfight|garena|free fire|pubg|mobile legends|genshin|honkai|call of duty|razer gold|razer|blizzard|battle.?net|\bea play\b|ea sports|origin|ubisoft|rockstar|minecraft|game ?pass|gamivo|\bg2a\b|eneba|kinguin|nexon|maplestory|runescape|world of warcraft|final fantasy|dofus|ankama|wargaming|world of tanks|war thunder|smite|paladins|rainbow six|forza|halo|just dance|\bfifa\b|nba 2k|madden|the sims|\bgta\b|red dead|elder scrolls|fallout|witcher|cyberpunk|assassin|far cry|battlefield|apex legends|overwatch|hearthstone|diablo|starcraft|counter.?strike|dota|valve|\bgog\b|humble bundle|itch\.io|unipin|codashop|meta quest|oculus|gaming|gamer|\bgames?\b|jeux? ?vid|zynga|supercell|brawl stars|clash of|candy crush|king\.com|gravity/i'],

        // -- Divertissement (vidéo, TV, ciné, billetterie, lecture) -------
        [1, '/netflix|disney|\bhbo\b|hbo max|prime video|hulu|paramount|peacock|showtime|starz|crunchyroll|funimation|dazn|eurosport|sky ?(tv|sport|showtime)|canal ?\+|now ?tv|viaplay|rakuten tv|\bmubi\b|discovery|britbox|shudder|apple tv|youtube premium|twitch|patreon|cinema|cineworld|\bvue\b|odeon|path[ée]|\bugc\b|kinepolis|\bamc\b|regal|fandango|ticketmaster|eventim|live ?nation|see tickets|dice\.fm|theatre|theater|concert|imax|showcase|cineplex|kobo|kindle|storytel|bookbeat|scribd|readly|magazine|streaming/i'],

        // -- Musique -----------------------------------------------------
        [3, '/spotify|deezer|apple music|tidal|napster|soundcloud|audible|audiobook|qobuz|pandora|amazon music|youtube music|beatport|bandcamp|\bhmv\b|\bjoox\b|music/i'],

        // -- Voyage ------------------------------------------------------
        [6, '/airbnb|booking\.com|hotels?\.com|hotelsgift|hotel|expedia|trivago|agoda|marriott|hilton|accor|\bibis\b|novotel|radisson|best western|premier inn|travelodge|\btui\b|thomas cook|lastminute|opodo|edreams|kayak|skyscanner|flixbus|trainline|\bsncf\b|eurostar|renfe|deutsche bahn|omio|blablacar|\buber\b|\blyft\b|\bsixt\b|hertz|\bavis\b|europcar|enterprise rent|rentalcars|ryanair|easyjet|lufthansa|air ?france|\bklm\b|british airways|emirates|qatar airways|turkish airlines|\bdelta\b|united air|american airlines|jetblue|norwegian|wizz ?air|vueling|transavia|iberia|ita airways|aer lingus|cruise|croisi|club med|center parcs|pierre et vacances|belambra|camping|globalhotel|global hotel|tripgift|smartbox|wonderbox|dakotabox|tripadvisor|getyourguide|viator|klook|travel|voyage|flight|airline|airport|rail|bucketlist|adrenalin|activity gift/i'],

        // -- Daywatch (produits locaux uniquement) -----------------------
        [5, '/daywatch|day ?watch/i'],

        // -- Logiciels : licences, antivirus, VPN ------------------------
        // Avant Mobile (« Microsoft 365 » ne doit pas tomber sur /telekom/…)
        // et avant Jeux Vidéo (« Microsoft Store » reste rayon 9).
        [10, '/windows ?(10|11|server)?\\b|office ?(20\\d\\d|365)|microsoft ?365|\\bnorton\\b|bitdefender|kaspersky|\\bmcafee\\b|\\beset\\b|avast|\\bavg\\b|malwarebytes|trend micro|panda security|f.secure|nord ?vpn|nordpass|nordlocker|surfshark|express ?vpn|cyberghost|private internet access|\\bpia vpn\\b|proton ?(vpn|mail)|ipvanish|hide ?my ?ass|\\bhma\\b|tunnelbear|mullvad|adobe|photoshop|lightroom|autocad|corel|parallels|\\bccleaner\\b|driver ?booster|iobit|wondershare|movavi|nero|winrar|\\bvpn\\b|antivirus|licen[cs]e|software|logiciel/i'],

        // -- Mobile, recharges, paiement prépayé -------------------------
        [11, '/paypal|paysafe|neosurf|flexepin|cashlib|transcash|toneo|\brecharge\b|top ?up|topup|airtime|vodafone|lycamobile|lebara|telekom|telefonica|movistar|wind ?tre|proximus|\bkpn\b|telia|telenor|swisscom|t.?mobile|verizon|at&t|mint mobile|simyo|\bmtn\b|airtel|moov|tigo|safaricom|skype|discord|nitro|prepaid|pr[ée]pay|sim ?card|mobile ?credit|\bsim\b|revolut|\bwise\b|neteller|skrill|payoneer|western union|mastercard|\bvisa\b|virtual card|cashtocode|mifinity|icash|advcash|astropay|jeton|\bepay\b|dundle|recharge\.com/i'],
    ],

    // Rayon utilisé quand aucune règle ne matche (retail physique)
    'category_fallback' => 4,

    /*
    |----------------------------------------------------------------------
    | Modèle de rachat
    |----------------------------------------------------------------------
    */
    'redeem' => [

        // Codes pays du fournisseur qui signifient "sans frontière"
        'global_country_codes' => ['GLC', 'WW'],

        // Un nom qui contient ça est global, quel que soit le pays annoncé
        'global_name'  => '/\b(global|globally|worldwide|international)\b/i',

        // Marques structurellement sans contrainte de région
        'global_brand' => '/razer gold|unipin|codashop|garena|free fire|pubg|mobile legends|genshin|honkai|paysafecard|neosurf|flexepin|cashlib|transcash|toneo|crypto|binance|bitnovo|rewarble|gift me crypto/i',

        // Rayons dont le rachat dépend du pays du compte
        'account_region_categories' => [1, 2, 3, 5, 9, 11],

        // Rayons toujours globaux — une licence Windows ou un VPN s'active
        // depuis n'importe où : aucune contrainte de pays de compte.
        'global_categories' => [7, 8, 10],

        // Rayons où le mot "global" dans le nom ne suffit PAS à rendre la
        // carte utilisable (ex. "Global Experience Card" = expérience physique)
        'physical_categories' => [4],

        // Pays dont le retail reste utilisable malgré le rayon physique.
        // Décision client du 12 août : « garde tout le shopping FR ». Le lien
        // Gabon–France est tel que ces cartes servent réellement — commande en
        // ligne, livraison à un proche, diaspora. Les masquer aurait retiré de
        // la vitrine Fnac, Darty, Cdiscount, Decathlon et consorts.
        'physical_visible_countries' => ['FR'],

        // Voyage : ces enseignes se réservent en ligne, donc utilisables
        'online_travel' => '/airbnb|booking\.com|hotels?\.com|hotelsgift|expedia|agoda|global hotel|globalhotel|tripgift|klook|getyourguide|viator|skyscanner|trivago|lastminute|opodo|edreams|kayak/i',
    ],

    /*
    |----------------------------------------------------------------------
    | Visibilité boutique
    |----------------------------------------------------------------------
    | Un modèle de rachat "physical" n'est jamais mis en vitrine : le client
    | gabonais ne peut rien en faire. Il reste en base (SEO, recherche,
    | diaspora) mais sort des rayons.
    */
    'visible_redeem_models' => ['global', 'account_region'],

    /*
    |----------------------------------------------------------------------
    | Devises de marché — filtre de PERTINENCE, pas de conversion
    |----------------------------------------------------------------------
    | Décision client du 12 août. La devise d'une carte dit sur quel compte
    | elle se rachète. Une Google Play PL s'active parfaitement… sur un compte
    | polonais — qu'aucun client gabonais n'ouvrira.
    |
    | On ne garde donc en vitrine que les devises correspondant à un compte
    | réellement accessible depuis Libreville. Ce n'est pas un contournement du
    | taux de change manquant : c'est le constat que renseigner 27 taux
    | reviendrait à afficher correctement le prix de cartes que personne
    | n'achètera.
    |
    | Effet de bord voulu : plus aucun produit sans taux n'atteint l'affichage,
    | donc plus aucun prix calculé au taux 1 (le défaut mesuré : 20, 50 et
    | 75 PLN affichaient tous « 100 FCFA »).
    |
    | Les produits écartés restent au catalogue et dans la recherche.
    */
    'market_currencies' => ['EUR', 'USD', 'GBP', 'XAF', 'XOF', 'CAD', 'AED'],

    /*
    |----------------------------------------------------------------------
    | Priorité — ce que le client voit EN PREMIER
    |----------------------------------------------------------------------
    | Règle métier énoncée par le client : « une carte France ou Global nous
    | intéresse ; une carte Pologne ou Danemark, non ».
    |
    | Le barème n'est PAS purement géographique, et c'est le cas Decathlon qui
    | l'impose : une carte française de retail doit rester en vitrine (décision
    | du 12 août) sans jamais remonter en tête. La géographie ne suffit donc
    | pas — le rayon et le modèle de rachat pèsent aussi.
    |
    | Quatre axes additionnés :
    |   1. d'où vient la carte      (pays / zone)
    |   2. comment elle se rachète  (global > compte régional > physique)
    |   3. dans quel rayon          (rayon d'accueil vs fourre-tout retail)
    |   4. dans quelle devise       (de marché ou non)
    |
    | `is_popular` = score >= seuil. Le seuil est volontairement au-dessus de
    | « France + retail » (30) et en dessous de « France + rayon d'accueil » (70).
    */
    'priority' => [

        // 1. Origine. GLC/WW = sans frontière, le meilleur cas pour Libreville.
        'geo' => [
            'GLC' => 40, 'WW' => 40,
            'FR'  => 35,
            'EU'  => 25,
            'US'  => 10, 'GB' => 10, 'CA' => 10,
        ],
        'geo_default' => 0,

        // 2. Rachat. Le physique est éliminatoire : il ne sert à rien ici.
        'redeem' => [
            'global'         => 25,
            'account_region' => 10,
            'physical'       => -50,
        ],

        // 3. Rayon. Les rayons d'accueil (`featured`) sont ceux qu'on pousse ;
        //    « Shopping international » est le fourre-tout du fallback, il ne
        //    doit jamais remonter — c'est ce qui écarte Decathlon France.
        // Daywatch : produit maison, vendu en FCFA, activable au Gabon sans
        // compte étranger. Au-dessus de tout barème calculé (plafond observé
        // ~130) pour qu'il ouvre systématiquement son rayon et les listings.
        'daywatch_score' => 150,

        'category_featured' => 15,
        'category_penalty'  => [4 => -25],

        // 4. Devise. Hors marché, la carte n'est de toute façon pas en vitrine :
        //    la pénalité garantit qu'elle ne remonte jamais par un autre biais.
        'currency_in_market'  => 10,
        'currency_off_market' => -60,

        // 5. Notoriété de marque.
        //
        // Sans elle, le tri « populaire » remontait des micro-recharges de jeu :
        // « Mobile Legends 11 Diamonds » (0,20 USD) marquait 90 comme
        // « Netflix EU », et le départage au prix la plaçait devant. Ce sont de
        // vraies cartes, mais ce n'est pas ce qu'on met en page d'accueil.
        //
        // Deux paliers, appliqués sur le nom de marque ou de produit :
        //   fort   — la marque justifie à elle seule une place en vitrine
        //   moyen  — connue, mais ne prime pas sur une grande marque
        'notoriety' => [
            40 => '/netflix|spotify|playstation|\bpsn\b|xbox|steam|nintendo|apple|itunes|app store|google play|amazon|disney|crunchyroll|chatgpt|openai|binance/i',
            20 => '/deezer|roblox|epic games|fortnite|razer gold|paypal|nord ?vpn|canal ?\+|dazn|twitch|prime video|hbo|paramount|uber|airbnb|booking|microsoft|windows|office|norton|bitdefender|tidal|audible|youtube/i',
        ],

        // Plafond de cartes par marque dans les blocs POPULAIRES.
        //
        // Sans lui, Binance occupait dix places d'affilée (un ticker = une
        // carte : USDT, BTC, ETH, XRP, BNB…) et les jeux Steam héritaient de la
        // notoriété de Steam. Une page d'accueil qui répète la même marque
        // n'informe plus : elle donne l'impression d'un catalogue étroit.
        //
        // S'applique UNIQUEMENT au filtre « populaires ». Un listing trié par
        // pertinence continue de tout montrer — plafonner une recherche
        // masquerait des produits que le client cherche explicitement.
        'max_per_brand_in_popular' => 2,

        // Familles de marque, pour le plafonnement ci-dessus.
        //
        // `brandKey()` traite chaque TITRE comme une marque : « Halo Infinite
        // Credits XBOX », « NBA 2K25 XBOX GAMES », « Sea of Thieves XBOX » sont
        // trois clés distinctes, et le plafond ne les voyait pas. Elles
        // appartiennent pourtant à la même famille pour un œil de client.
        //
        // Premier motif qui matche gagne ; sans correspondance, on retombe sur
        // la clé de marque — le plafond reste donc actif partout.
        'brand_families' => [
            'xbox'        => '/xbox|game ?pass/i',
            'playstation' => '/playstation|\bpsn\b/i',
            'steam'       => '/\bsteam\b/i',
            'binance'     => '/binance/i',
            'nintendo'    => '/nintendo/i',
            'roblox'      => '/roblox|robux/i',
            'apple'       => '/apple|itunes|app store/i',
            'google'      => '/google play/i',
            'razer'       => '/razer/i',
            'netflix'     => '/netflix/i',
            'amazon'      => '/amazon/i',
        ],

        // Seuil de mise en avant : 100.
        //
        // Calibré pour qu'une carte doive porter une NOTORIÉTÉ, pas seulement
        // une bonne origine. Une micro-recharge de jeu sans marque forte plafonne
        // à 90 (Global + global + rayon d'accueil + devise de marché) et reste
        // donc hors accueil, tandis que « Netflix EU » atteint exactement 100.
        'popular_threshold' => 100,
    ],

    /*
    |----------------------------------------------------------------------
    | Exceptions manuelles — priorité absolue sur les règles
    |----------------------------------------------------------------------
    | Clé = id de marque fournisseur. À utiliser pour les cas que la règle
    | ne peut pas deviner, pas pour compenser une règle trop faible.
    */
    'overrides' => [
        // 1146 => ['category_id' => 2, 'redeem_model' => 'global'], // Steam USD Global
    ],
];
