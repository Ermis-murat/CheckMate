<?php
function statusBadge(string $status): string
{
    $map = ['Gepland' => 'gepland', 'Bezig' => 'bezig', 'Afgelopen' => 'afgelopen']; // status naar CSS-klasse
    $cls = $map[$status] ?? 'gepland'; // onbekende status: grijs
    return '<span class="badge badge-' . $cls . '">' . e($status) . '</span>'; // kleurlabel (FE5)
}

function generateCode(): string
{
    return strtoupper(bin2hex(random_bytes(4))); // willekeurige code van 8 tekens
}