{{--
    Arborescence de navigation admin — regroupée par domaine (vague « layered »).
    LES ROUTES SONT STRICTEMENT CELLES D'AVANT LA REFONTE : ce fichier ne change
    que la présentation. Les compteurs viennent des requêtes existantes, jamais
    en dur ; un compteur à zéro n'affiche aucun badge.
--}}
@php
    // Mémoïsés : l'arborescence est rendue deux fois par page (volet + tiroir).
    $badges = \App\Support\AdminBadges::make();

    $pendingDeliveryCount = $badges->pendingDeliveries();
    $proPending           = $badges->pendingProAccounts();
    $aVerser              = $badges->pendingSettlements()['count'];
@endphp

<div class="adm-nav-label">Vue d'ensemble</div>

<a href="{{ route('admin.dashboard') }}"
   class="adm-nav-item {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    <span class="adm-nav-txt">Tableau de bord</span>
</a>

<div class="adm-nav-label">Ventes</div>

<a href="{{ route('admin.orders.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.orders.index') || request()->routeIs('admin.orders.show') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
    <span class="adm-nav-txt">Commandes</span>
</a>

<a href="{{ route('admin.orders.pending-delivery') }}"
   class="adm-nav-item {{ request()->routeIs('admin.orders.pending-delivery') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span class="adm-nav-txt">Livraisons en attente</span>
    {{-- Orange : une livraison en souffrance est une action requise, pas une info. --}}
    @if($pendingDeliveryCount > 0)
        <span class="adm-count adm-count--warn">{{ $pendingDeliveryCount }}</span>
    @endif
</a>

<div class="adm-nav-label">Catalogue</div>

<a href="{{ route('admin.catalog.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.catalog.*') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
    <span class="adm-nav-txt">Catalogue</span>
</a>

<a href="{{ route('admin.cards.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.cards.*') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
    <span class="adm-nav-txt">Cartes</span>
</a>

<a href="{{ route('admin.merchant-cards.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.merchant-cards.*') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    <span class="adm-nav-txt">Cartes locales</span>
</a>

<a href="{{ route('admin.daywatch.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.daywatch.*') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
    <span class="adm-nav-txt">Daywatch</span>
</a>

<div class="adm-nav-label">Partenaires</div>

<a href="{{ route('admin.resellers.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.resellers.*') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    <span class="adm-nav-txt">Vendeurs</span>
</a>

<a href="{{ route('admin.card-owners.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.card-owners.*') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
    <span class="adm-nav-txt">Propriétaires</span>
</a>

<a href="{{ route('admin.proprietaires.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.proprietaires.*') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span class="adm-nav-txt">Comptes pro</span>
    {{-- Orange : des dossiers KYC attendent une décision humaine. --}}
    @if($proPending > 0)
        <span class="adm-count adm-count--warn">{{ $proPending }}</span>
    @endif
</a>

<div class="adm-nav-label">Finance</div>

<a href="{{ route('admin.payments.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.payments.*') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    <span class="adm-nav-txt">Paiements</span>
</a>

<a href="{{ route('admin.versements.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.versements.*') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span class="adm-nav-txt">Versements</span>
    @if($aVerser > 0)
        <span class="adm-count">{{ $aVerser }}</span>
    @endif
</a>

<div class="adm-nav-label">Marketing</div>

<a href="{{ route('admin.newsletter.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.newsletter.*') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
    <span class="adm-nav-txt">Newsletter</span>
</a>

<div class="adm-nav-label">Système</div>

<a href="{{ route('admin.users.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    <span class="adm-nav-txt">Utilisateurs</span>
</a>

<a href="{{ route('admin.settings.index') }}"
   class="adm-nav-item {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    <span class="adm-nav-txt">Paramètres</span>
</a>

@if(\App\Models\AppSetting::isMaintenanceMode())
    <div class="mx-2 mt-3 px-3 py-2 rounded-lg bg-rose-500/15 border border-rose-500/30 text-rose-300 text-[11px] font-bold uppercase tracking-wider flex items-center gap-2">
        <span class="relative flex h-2 w-2">
            <span class="absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75 animate-ping"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
        </span>
        Maintenance ON
    </div>
@endif
