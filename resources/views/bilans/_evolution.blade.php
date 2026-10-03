{{-- Variation par rapport au mois précédent. Variables : $actuel, $avant, $inverse (true = une hausse est mauvaise) --}}
@php
    $inverse = $inverse ?? false;
    if ($avant == 0) {
        $texte = $actuel == 0 ? null : 'nouveau';
        $bon = null;
    } else {
        $pct = round(($actuel - $avant) * 100 / abs($avant));
        $texte = ($pct > 0 ? '+' : '') . $pct . ' %';
        $bon = $pct == 0 ? null : (($pct > 0) xor $inverse);
    }
@endphp
@if ($texte)
    <span @class(['text-xs font-medium', 'text-green-700' => $bon === true, 'text-red-600' => $bon === false, 'text-gray-500' => $bon === null])>
        {{ $texte }} <span class="font-normal text-gray-400">vs mois préc.</span>
    </span>
@endif
