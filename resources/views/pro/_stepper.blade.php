{{-- Indicateur d'étapes du parcours d'onboarding pro. Variable : $current (1..3) --}}
@php
    $steps = [1 => 'Inscription', 2 => 'Vérification', 3 => 'Dossier'];
@endphp
<div class="flex items-center justify-center gap-2 sm:gap-4">
    @foreach($steps as $n => $label)
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                    {{ $n < $current ? 'bg-[#44A08D] text-white' : ($n === $current ? 'bg-[#44A08D] text-white ring-4 ring-[#44A08D]/20' : 'bg-slate-200 text-slate-500') }}">
                    @if($n < $current)
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @else
                        {{ $n }}
                    @endif
                </span>
                <span class="hidden sm:inline text-xs font-semibold {{ $n === $current ? 'text-slate-900' : 'text-slate-400' }}">{{ $label }}</span>
            </div>
            @if(!$loop->last)
                <span class="w-6 sm:w-10 h-0.5 rounded {{ $n < $current ? 'bg-[#44A08D]' : 'bg-slate-200' }}"></span>
            @endif
        </div>
    @endforeach
</div>
