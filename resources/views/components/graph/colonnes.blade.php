@props(['points', 'hauteur' => 220])
{{--
    Histogramme vertical (une seule série) en HTML/CSS.
    $points : [['label' => 'Oct 26', 'titre' => 'Octobre 2026', 'valeur' => 123000, 'tickets' => 45], ...]
--}}
@php
    use App\Support\Format;
    $max = max(1, collect($points)->max('valeur'));
    // Graduation « ronde » de l'axe (1, 2 ou 5 × 10^n)
    $puissance = 10 ** max(0, floor(log10($max)));
    $pas = collect([1, 2, 5, 10])->map(fn ($m) => $m * $puissance)->first(fn ($p) => $p * 4 >= $max) ?? $puissance * 10;
    $plafond = $pas * max(1, ceil($max / $pas));
    $graduations = range(0, $plafond, $pas);
    $nb = count($points);
    $saut = $nb > 16 ? (int) ceil($nb / 12) : 1; // une étiquette sur N si beaucoup de colonnes
    $court = fn ($v) => $v >= 1_000_000 ? rtrim(rtrim(number_format($v / 1_000_000, 1, ',', ' '), '0'), ',') . ' M' : ($v >= 1000 ? number_format($v / 1000, 0, ',', ' ') . ' k' : $v);
@endphp

<div class="flex gap-3">
    {{-- Axe vertical --}}
    <div class="relative w-12 shrink-0 text-right text-[11px] text-gray-400" style="height: {{ $hauteur }}px">
        @foreach ($graduations as $g)
            <span class="absolute right-0 -translate-y-1/2" style="bottom: {{ $g / $plafond * 100 }}%">{{ $court($g) }}</span>
        @endforeach
    </div>

    <div class="min-w-0 flex-1">
        <div class="relative" style="height: {{ $hauteur }}px">
            {{-- Grille (discrète) --}}
            @foreach ($graduations as $g)
                <div @class(['absolute inset-x-0 border-t', 'border-gray-300' => $g === 0, 'border-dashed border-gray-100' => $g !== 0])
                     style="bottom: {{ $g / $plafond * 100 }}%"></div>
            @endforeach

            {{-- Colonnes --}}
            <div class="absolute inset-0 flex items-end" style="gap: {{ $nb > 20 ? 2 : 6 }}px">
                @foreach ($points as $i => $point)
                    @php
                        // L'infobulle s'aligne sur le bord pour ne pas sortir du graphique
                        $position = $i < $nb * 0.25 ? 'left-0' : ($i >= $nb * 0.75 ? 'right-0' : 'left-1/2 -translate-x-1/2');
                    @endphp
                    <div class="group relative flex h-full flex-1 items-end justify-center">
                        {{-- Zone de survol plus large que la barre --}}
                        <div class="absolute inset-0 rounded-md group-hover:bg-gray-100/70"></div>
                        <div class="relative w-full max-w-[44px] rounded-t bg-primary-600 transition-colors group-hover:bg-primary-700"
                             style="height: {{ $point['valeur'] / $plafond * 100 }}%; {{ $point['valeur'] > 0 ? 'min-height: 2px;' : '' }}"></div>

                        {{-- Infobulle --}}
                        <div class="pointer-events-none absolute top-0 z-10 hidden w-max {{ $position }} rounded-lg bg-gray-900 px-3 py-2 text-xs text-white shadow-lg group-hover:block">
                            <p class="font-semibold">{{ $point['titre'] }}</p>
                            <p class="mt-0.5 tabular-nums">{{ Format::gnf($point['valeur']) }}</p>
                            <p class="text-gray-300 tabular-nums">{{ number_format($point['tickets'], 0, ',', ' ') }} tickets vendus</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Étiquettes de l'axe horizontal --}}
        <div class="mt-2 flex text-[11px] text-gray-500" style="gap: {{ $nb > 20 ? 2 : 6 }}px">
            @foreach ($points as $i => $point)
                <span class="flex-1 whitespace-nowrap text-center">{{ $i % $saut === 0 ? $point['label'] : '' }}</span>
            @endforeach
        </div>
    </div>
</div>
