<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    forbidden();
csrfCheck();

$me = (int) $_SESSION['account_id'];

try {
    $st = $pdo->prepare('SELECT Wachtwoord FROM Account WHERE Account_id = ?');
    $st->execute([$me]);
    $hash = $st->fetchColumn();

    if (!$hash || !password_verify($_POST['wachtwoord'] ?? '', $hash)) { // bevestigen met wachtwoord
        $_SESSION['flash'] = 'Wachtwoord onjuist.';
        redirect('/pages/profiel.php');
    }

    $pdo->prepare(
        'UPDATE Account
         SET Naam = ?, Email = ?, Wachtwoord = ?, Geblokkeerd = 1,
             Login_poging = 0, Reset_token = NULL, Reset_vervalt = NULL
         WHERE Account_id = ?' // persoonsgegevens vervangen, account blokkeren
    )->execute([
                'Verwijderd lid', // naam wordt anoniem
                'verwijderd-' . $me . '@invalid.local', // uniek placeholder-adres (Email is UNIQUE)
                password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), // onbekend wachtwoord, inloggen kan niet meer
                $me,
            ]);

    $_SESSION = []; // direct uitloggen
    session_destroy();
    redirect('/pages/login.php');
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    http_response_code(500);
    include __DIR__ . '/../pages/error.php';
    exit;
}