{{--
    Pills dans un rail inset ; l'onglet actif = fond navy, texte blanc.
    `tabs` = [['label' => ..., 'url' => ..., 'active' => bool], ...]
--}}
@props(['tabs' => []])
<nav {{ $attributes->merge(['class' => 'ui-tabs']) }}>
    @foreach ($tabs as $tab)
        <a href="{{ $tab['url'] }}"
           class="ui-tab {{ !empty($tab['active']) ? 'is-active' : '' }}"
           @if(!empty($tab['active'])) aria-current="page" @endif>
            {{ $tab['label'] }}
        </a>
    @endforeach
    {{ $slot }}
</nav>
