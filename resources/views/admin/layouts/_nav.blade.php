<a href="{{ route('admin.dashboard') }}"
   class="sidebar-link flex items-center px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap {{ request()->routeIs('admin.dashboard') ? 'active' : 'text-gray-300' }}">
    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    Tableau de bord
</a>

@php
    $pendingDeliveryCount = \App\Models\Order::where('payment_status', \App\Models\Order::PAYMENT_STATUS_COMPLETED)
        ->where('status', '!=', \App\Models\Order::STATUS_COMPLETED)
        ->whereDoesntHave('userCards')
        ->count();
@endphp

<a href="{{ route('admin.orders.index') }}"
   class="sidebar-link flex items-center px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap {{ request()->routeIs('admin.orders.index') || request()->routeIs('admin.orders.show') ? 'active' : 'text-gray-300' }}">
    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
    Commandes
</a>

<a href="{{ route('admin.orders.pending-delivery') }}"
   class="sidebar-link flex items-center px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap {{ request()->routeIs('admin.orders.pending-delivery') ? 'active' : 'text-gray-300' }}">
    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span class="flex-1">Livraisons en attente</span>
    @if($pendingDeliveryCount > 0)
        <span class="ml-2 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-amber-500 text-white text-[10px] font-bold tabular-nums">{{ $pendingDeliveryCount }}</span>
    @endif
</a>

<a href="{{ route('admin.users.index') }}"
   class="sidebar-link flex items-center px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap {{ request()->routeIs('admin.users.*') ? 'active' : 'text-gray-300' }}">
    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    Utilisateurs
</a>

<a href="{{ route('admin.payments.index') }}"
   class="sidebar-link flex items-center px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap {{ request()->routeIs('admin.payments.*') ? 'active' : 'text-gray-300' }}">
    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    Paiements
</a>

<a href="{{ route('admin.cards.index') }}"
   class="sidebar-link flex items-center px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap {{ request()->routeIs('admin.cards.*') ? 'active' : 'text-gray-300' }}">
    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
    Cartes
</a>

<a href="{{ route('admin.catalog.index') }}"
   class="sidebar-link flex items-center px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap {{ request()->routeIs('admin.catalog.*') ? 'active' : 'text-gray-300' }}">
    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
    Catalogue
</a>

<a href="{{ route('admin.daywatch.index') }}"
   class="sidebar-link flex items-center px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap {{ request()->routeIs('admin.daywatch.*') ? 'active' : 'text-gray-300' }}">
    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
    Daywatch
</a>

<a href="{{ route('admin.resellers.index') }}"
   class="sidebar-link flex items-center px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap {{ request()->routeIs('admin.resellers.*') ? 'active' : 'text-gray-300' }}">
    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    Vendeurs
</a>

<a href="{{ route('admin.merchant-cards.index') }}"
   class="sidebar-link flex items-center px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap {{ request()->routeIs('admin.merchant-cards.*') ? 'active' : 'text-gray-300' }}">
    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
    Cartes locales
</a>

<a href="{{ route('admin.newsletter.index') }}"
   class="sidebar-link flex items-center px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap {{ request()->routeIs('admin.newsletter.*') ? 'active' : 'text-gray-300' }}">
    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
    Newsletter
</a>

<a href="{{ route('admin.settings.index') }}"
   class="sidebar-link flex items-center px-3 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap {{ request()->routeIs('admin.settings.*') ? 'active' : 'text-gray-300' }}">
    <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    Paramètres
</a>
@if(\App\Models\AppSetting::isMaintenanceMode())
    <div class="mx-3 mt-2 px-3 py-2 rounded-lg bg-rose-500/15 border border-rose-500/30 text-rose-300 text-[11px] font-bold uppercase tracking-wider flex items-center gap-2">
        <span class="relative flex h-2 w-2">
            <span class="absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75 animate-ping"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
        </span>
        Maintenance ON
    </div>
@endif
