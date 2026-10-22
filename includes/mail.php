<?php
function sendMail(string $to, string $subject, string $body): void
{
    if (APP_DEBUG) { // lokaal: niet echt versturen
        $dir = __DIR__ . '/../logs'; // logmap
        if (!is_dir($dir))
            mkdir($dir, 0775, true); // map aanmaken als die nog niet bestaat
        file_put_contents(
            $dir . '/mail.log',
            "[" . date('c') . "] TO: $to\nSUBJECT: $subject\n$body\n\n",
            FILE_APPEND // onderaan toevoegen
        );
        return;
    }
    $headers = "From: CheckMate <no-reply@checkmate.local>\r\nContent-Type: text/plain; charset=UTF-8"; // afzender
    mail($to, $subject, $body, $headers); // echte mail in productie
}

function createResetToken(PDO $pdo, int $accountId): string
{
    $token = bin2hex(random_bytes(32)); // willekeurig token voor de link
    $st = $pdo->prepare('UPDATE Account SET Reset_token = ?, Reset_vervalt = ? WHERE Account_id = ?');
    $st->execute([hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600), $accountId]); // alleen de hash opslaan, 1 uur geldig
    return $token; // ongehashte versie gaat in de mail
}

function sendResetMail(string $email, string $token, bool $blocked): void
{
    $link = 'http://localhost' . BASE_URL . '/pages/reset.php?token=' . $token; // resetlink
    $intro = $blocked
        ? "Er is verdachte inlogactiviteit op je account geweest. Je account is geblokkeerd.\n" // na 5 foute pogingen
        : "Je hebt een nieuw wachtwoord aangevraagd.\n"; // normale aanvraag
    sendMail(
        $email,
        'CheckMate: wachtwoord resetten',
        $intro . "Stel een nieuw wachtwoord in (1 uur geldig):\n$link\n"
    );
}