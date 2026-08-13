<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bandeau « carte populaire » (accueil + tête de boutique)
    |--------------------------------------------------------------------------
    | La liste des cartes en rotation, leur discours et leur bouton vivent dans
    | App\Support\PopularHighlights : chaque entrée y est résolue contre le
    | catalogue afrikard (id + prix réels), donc rien à maintenir ici.
    | Pour ajouter/retirer une carte de la rotation, éditer la constante CARDS.
    */

    /*
    |--------------------------------------------------------------------------
    | Témoignages clients (preuve sociale — home)
    |--------------------------------------------------------------------------
    | ⚠️ EXEMPLES À REMPLACER par de VRAIS avis clients avant la mise en prod.
    | Ne pas laisser de faux témoignages en ligne (trompeur + risque juridique).
    | À terme : brancher sur une vraie table `testimonials` alimentée par les
    | avis collectés après achat.
    |
    | Chaque entrée : name, city, quote, rating (1-5). `avatar` optionnel
    | (chemin asset) — sinon l'initiale est affichée.
    */
    'testimonials' => [
        [
            'name'   => 'Aïssa M.',
            'city'   => 'Libreville',
            'quote'  => 'Code Netflix reçu en quelques secondes après paiement Airtel Money. Simple et rapide, je recommande.',
            'rating' => 5,
        ],
        [
            'name'   => 'Yannick O.',
            'city'   => 'Port-Gentil',
            'quote'  => 'J\'ai rechargé mon compte Steam sans carte bancaire, juste avec Moov Money. Le code marche direct.',
            'rating' => 5,
        ],
        [
            'name'   => 'Nadia B.',
            'city'   => 'Owendo',
            'quote'  => 'Un souci sur un code, le support a réglé ça dans la journée. Sérieux et réactif.',
            'rating' => 4,
        ],
    ],

];
