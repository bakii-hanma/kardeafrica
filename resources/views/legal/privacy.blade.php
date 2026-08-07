@extends('layouts.app')

@section('title', 'Politique de confidentialité — KardAfrica')

@section('content')
<div class="bg-[#FAFAF7] min-h-screen py-12 px-4">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-card overflow-hidden">
            <div class="bg-gradient-to-br from-[#1F2937] via-[#0F172A] to-[#1F2937] px-6 md:px-10 py-10">
                <h1 class="font-display text-3xl font-bold text-white tracking-tight">Politique de confidentialité</h1>
                <p class="text-slate-300 text-sm mt-2">Dernière mise à jour : 25 juillet 2026</p>
            </div>

            <div class="px-6 md:px-10 py-8 text-[15px] leading-relaxed text-slate-700 space-y-6
                        [&_h2]:font-display [&_h2]:text-lg [&_h2]:font-bold [&_h2]:text-slate-900 [&_h2]:mt-8 [&_h2]:mb-2
                        [&_p]:mt-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:mt-2 [&_ul]:space-y-1 [&_a]:text-[#44A08D] [&_a]:font-semibold [&_a]:underline">

                <p>La présente politique décrit comment <strong>KardAfrica</strong> (« nous ») collecte, utilise et protège
                   les données personnelles des utilisateurs de son site <strong>kardafrica.com</strong> et de son
                   application mobile Android (« les Services »). KardAfrica est une marketplace de cartes cadeaux
                   numériques opérant depuis le Gabon.</p>

                <h2>1. Responsable du traitement</h2>
                <p>KardAfrica — Libreville, Gabon.<br>
                   Contact : <a href="mailto:hello@kardafrica.com">hello@kardafrica.com</a> · +241 06 87 13 09</p>

                <h2>2. Données que nous collectons</h2>
                <ul>
                    <li><strong>Identité et compte</strong> : nom, adresse e-mail, numéro de téléphone, mot de passe (stocké chiffré/hashé), photo de profil (facultative).</li>
                    <li><strong>Commandes et cartes</strong> : historique d'achats, cartes cadeaux achetées, codes/PIN associés à ton compte.</li>
                    <li><strong>Paiement</strong> : montant, référence de transaction et opérateur Mobile Money (Airtel, Moov). Le paiement est traité par notre prestataire E-Billing ; <strong>nous ne stockons pas</strong> tes identifiants Mobile Money ni de données bancaires complètes.</li>
                    <li><strong>Données techniques</strong> : adresse IP, type d'appareil, identifiants de session, pages consultées, cookies et identifiants de mesure publicitaire (voir section Cookies & Pixel).</li>
                </ul>

                <h2>3. Finalités et bases légales</h2>
                <ul>
                    <li>Créer et gérer ton compte (exécution du contrat).</li>
                    <li>Traiter tes commandes, tes paiements et te livrer tes cartes (exécution du contrat).</li>
                    <li>Assurer le support client et la sécurité (intérêt légitime).</li>
                    <li>Mesurer l'audience et diffuser des publicités pertinentes via le Pixel Meta/Facebook (consentement).</li>
                    <li>Respecter nos obligations comptables et légales (obligation légale).</li>
                </ul>

                <h2>4. Destinataires et sous-traitants</h2>
                <p>Tes données peuvent être transmises, dans la stricte mesure nécessaire, à :</p>
                <ul>
                    <li><strong>E-Billing</strong> et les opérateurs Mobile Money (Airtel, Moov) — traitement des paiements.</li>
                    <li>Notre <strong>fournisseur de cartes cadeaux</strong> — approvisionnement et livraison des codes.</li>
                    <li><strong>Meta Platforms (Facebook)</strong> — mesure d'audience et publicité (Pixel / App Events).</li>
                    <li>Nos prestataires d'<strong>hébergement</strong> et d'infrastructure applicative.</li>
                </ul>
                <p>Nous ne vendons jamais tes données personnelles.</p>

                <h2>5. Durée de conservation</h2>
                <p>Les données de compte sont conservées tant que ton compte est actif. Les données de commande et de
                   facturation sont conservées le temps requis par nos obligations légales et comptables. Passé ces délais,
                   les données sont supprimées ou anonymisées.</p>

                <h2>6. Tes droits</h2>
                <p>Tu disposes des droits d'accès, de rectification, d'effacement, d'opposition, de limitation et de
                   portabilité de tes données. Pour les exercer, écris-nous à
                   <a href="mailto:hello@kardafrica.com">hello@kardafrica.com</a>.</p>

                <h2>7. Suppression de ton compte et de tes données</h2>
                <p>Tu peux demander la suppression de ton compte et de tes données personnelles à tout moment.
                   La procédure est détaillée sur notre page dédiée :
                   <a href="{{ route('data-deletion') }}">Suppression des données utilisateurs</a>.</p>

                <h2>8. Cookies et Pixel</h2>
                <p>Nous utilisons des cookies techniques (nécessaires au fonctionnement, ex. session et panier) et des
                   outils de mesure/publicité, notamment le <strong>Pixel Meta</strong>, qui enregistrent des événements
                   (pages vues, ajout au panier, achat) afin d'améliorer nos services et nos campagnes. Tu peux gérer les
                   cookies via les réglages de ton navigateur.</p>

                <h2>9. Sécurité</h2>
                <p>Nous mettons en œuvre des mesures techniques et organisationnelles raisonnables pour protéger tes
                   données (chiffrement des mots de passe, connexions sécurisées HTTPS, accès restreint).</p>

                <h2>10. Contact</h2>
                <p>Pour toute question relative à cette politique ou à tes données :
                   <a href="mailto:hello@kardafrica.com">hello@kardafrica.com</a>.</p>
            </div>
        </div>
    </div>
</div>
@endsection
