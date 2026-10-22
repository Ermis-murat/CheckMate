<?php
const AANTAL_TAFELS = 10; // tafel 1 t/m 10 (FE8)

function loadPartij(PDO $pdo, int $id, int $eventId): ?array
{
    $st = $pdo->prepare(
        'SELECT p.*, g.Event_id,
                a1.Naam AS naam1, a2.Naam AS naam2, ar.Naam AS arbiter
         FROM Partij p
         JOIN Groep g   ON g.Groep_id = p.Groep_id
         JOIN Account a1 ON a1.Account_id = p.Lid1_id
         JOIN Account a2 ON a2.Account_id = p.Lid2_id
         LEFT JOIN Account ar ON ar.Account_id = p.Arbiter_id -- arbiter mag leeg zijn
         WHERE p.Partij_id = ? AND g.Event_id = ?' // alleen partijen van het huidige event
    );
    $st->execute([$id, $eventId]);
    return $st->fetch() ?: null; // null als de partij niet bestaat
}

function backToPartij(int $id, string $msg = '', bool $isError = false): void
{
    if ($msg !== '') {
        $_SESSION['flash'] = $msg; // melding voor de volgende pagina
        $_SESSION['flash_error'] = $isError; // rood of groen
    }
    redirect('/pages/partij.php?id=' . $id); // terug naar de partij
}

function uitslagTekst(?string $winnaar): string
{
    return ['lid1' => '1-0', 'lid2' => '0-1', 'gelijk' => '½-½'][$winnaar ?? ''] ?? '-'; // database-waarde naar schaakuitslag
}

function applyResult(PDO $pdo, int $groepId, int $lid1, int $lid2, string $winnaar, int $sign): void
{ // $sign: +1 optellen, -1 terugdraaien
    $ins = $pdo->prepare('INSERT IGNORE INTO Stand (Account_id, Groep_id) VALUES (?, ?)'); // zorg dat beide stand-rijen bestaan
    $ins->execute([$lid1, $groepId]);
    $ins->execute([$lid2, $groepId]);

    $delta = function (string $uitkomst) use ($sign) { // wat verandert er voor één speler?
        return [
            'w' => $uitkomst === 'win' ? $sign : 0, // winst
            'g' => $uitkomst === 'gelijk' ? $sign : 0, // gelijkspel
            'v' => $uitkomst === 'verlies' ? $sign : 0, // verlies
        ];
    };

    $res1 = $winnaar === 'lid1' ? 'win' : ($winnaar === 'lid2' ? 'verlies' : 'gelijk'); // uitkomst voor speler 1
    $res2 = $winnaar === 'lid2' ? 'win' : ($winnaar === 'lid1' ? 'verlies' : 'gelijk'); // uitkomst voor speler 2

    $upd = $pdo->prepare(
        'UPDATE Stand
         SET Gespeeld = Gespeeld + ?, Winst = Winst + ?, Gelijkspel = Gelijkspel + ?,
             Verlies = Verlies + ?, Punten = Winst + 0.5 * Gelijkspel -- MySQL werkt links naar rechts, dus dit gebruikt de nieuwe waarden
         WHERE Account_id = ? AND Groep_id = ?'
    );
    foreach ([[$lid1, $res1], [$lid2, $res2]] as [$acc, $res]) { // beide spelers bijwerken
        $d = $delta($res);
        $upd->execute([$sign, $d['w'], $d['g'], $d['v'], $acc, $groepId]);
    }
}