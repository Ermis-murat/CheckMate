<?php
require_once __DIR__ . '/../includes/auth.php';

$token = $_GET['token'] ?? $_POST['token'] ?? ''; // token uit de link of het formulier
$hash = hash('sha256', $token); // in de database staat alleen de hash
$errors = [];
$user = false;

try {
    $st = $pdo->prepare('SELECT Account_id FROM Account WHERE Reset_token = ? AND Reset_vervalt > ?'); // token moet kloppen en nog geldig zijn
    $st->execute([$hash, date('Y-m-d H:i:s')]);
    $user = $st->fetch(); // false = ongeldig of verlopen

    if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
        csrfCheck();
        $pw = $_POST['wachtwoord'] ?? '';
        $pw2 = $_POST['wachtwoord2'] ?? '';
        if (strlen($pw) < 8)
            $errors[] = 'Wachtwoord moet minimaal 8 tekens zijn.';
        if ($pw !== $pw2)
            $errors[] = 'Wachtwoorden komen niet overeen.';

        if (!$errors) {
            $pdo->prepare(
                'UPDATE Account
                 SET Wachtwoord = ?, Geblokkeerd = 0, Login_poging = 0,
                     Reset_token = NULL, Reset_vervalt = NULL
                 WHERE Account_id = ?' // nieuw wachtwoord, deblokkeren, token wissen
            )->execute([password_hash($pw, PASSWORD_DEFAULT), $user['Account_id']]); // link werkt daarna niet meer
            redirect('/pages/login.php?reset=1');
        }
    }
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    include __DIR__ . '/error.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="utf-8">
    <title>Nieuw wachtwoord</title>
    <link rel="stylesheet" href="/checkmate/css/style.css">
</head>

<body>
    <main class="box">
        <h1>Nieuw wachtwoord</h1>
        <?php if (!$user): ?>
            <p class="error">Deze link is verlopen of al gebruikt.</p>
            <p><a href="forgot.php">Vraag een nieuwe link aan</a></p>
        <?php else: ?>
            <?php foreach ($errors as $err): ?>
                <p class="error">
                    <?= e($err) ?>
                </p>
            <?php endforeach; ?>
            <form method="post">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <label>Nieuw wachtwoord <input type="password" name="wachtwoord" required></label>
                <label>Herhaal wachtwoord <input type="password" name="wachtwoord2" required></label>
                <button>Opslaan</button>
            </form>
        <?php endif; ?>
    </main>
</body>

</html>