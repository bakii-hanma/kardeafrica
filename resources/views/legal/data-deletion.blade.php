@extends('layouts.app')

@section('title', 'Suppression des données utilisateurs — KardAfrica')

@section('content')
<div class="bg-[#FAFAF7] min-h-screen py-12 px-4">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-card overflow-hidden">
            <div class="bg-gradient-to-br from-[#1F2937] via-[#0F172A] to-[#1F2937] px-6 md:px-10 py-10">
                <h1 class="font-display text-3xl font-bold text-white tracking-tight">Suppression des données utilisateurs</h1>
                <p class="text-slate-300 text-sm mt-2">Dernière mise à jour : 25 juillet 2026</p>
            </div>

            <div class="px-6 md:px-10 py-8 text-[15px] leading-relaxed text-slate-700 space-y-6
                        [&_h2]:font-display [&_h2]:text-lg [&_h2]:font-bold [&_h2]:text-slate-900 [&_h2]:mt-8 [&_h2]:mb-2
                        [&_p]:mt-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:mt-2 [&_ul]:space-y-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:mt-2 [&_ol]:space-y-1
                        [&_a]:text-[#44A08D] [&_a]:font-semibold [&_a]:underline">

                <p>Chez <strong>KardAfrica</strong>, tu gardes le contrôle de tes données. Tu peux demander la suppression
                   de ton compte et des données personnelles associées à tout moment, gratuitement.</p>

                <h2>Comment demander la suppression</h2>
                <p>Choisis l'une des méthodes suivantes :</p>
                <ol>
                    <li><strong>Depuis l'application</strong> : <em>Profil</em> → <em>Paramètres du compte</em> → <em>Supprimer mon compte</em> (si l'option est disponible dans ta version).</li>
                    <li><strong>Par e-mail</strong> : envoie une demande à
                        <a href="mailto:hello@kardafrica.com?subject=Suppression%20de%20compte">hello@kardafrica.com</a>
                        avec pour objet <strong>« Suppression de compte »</strong>, depuis l'adresse e-mail associée à ton compte
                        (ou en indiquant ton adresse e-mail / numéro de téléphone de compte).</li>
                </ol>
                <p>Nous confirmons la réception et traitons ta demande sous <strong>30 jours</strong>.</p>

                <h2>Ce qui est supprimé</h2>
                <ul>
                    <li>Ton profil et tes identifiants (nom, e-mail, téléphone, mot de passe, photo).</li>
                    <li>Tes préférences et données de session.</li>
                    <li>Les données personnelles liées à ton compte qui ne sont pas soumises à une obligation légale de conservation.</li>
                </ul>

                <h2>Ce qui peut être conservé</h2>
                <p>Certaines données peuvent être conservées, sous forme minimale ou anonymisée, uniquement lorsque la loi
                   l'exige — par exemple les <strong>documents de facturation et l'historique des transactions</strong>,
                   conservés le temps requis par nos obligations comptables et légales. Ces données ne sont plus utilisées
                   à des fins commerciales.</p>

                <h2>Effet de la suppression</h2>
                <p>La suppression est <strong>définitive</strong> : ton accès au compte, tes cartes non encore consultées et
                   ton historique dans l'application seront perdus. Assure-toi d'avoir récupéré les codes/PIN de tes cartes
                   déjà achetées avant de demander la suppression.</p>

                <h2>Contact</h2>
                <p>Pour toute question sur la suppression de tes données :
                   <a href="mailto:hello@kardafrica.com">hello@kardafrica.com</a> · +241 06 87 13 09.<br>
                   Voir aussi notre <a href="{{ route('privacy') }}">Politique de confidentialité</a>.</p>
            </div>
        </div>
    </div>
</div>
@endsection
