<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mail.php';

$sent = false; // is het formulier verwerkt?

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $email = trim($_POST['email'] ?? '');
    try {
        $st = $pdo->prepare('SELECT Account_id, Email FROM Account WHERE Email = ?');
        $st->execute([$email]);
        if ($user = $st->fetch()) { // alleen mailen als het account bestaat
            $token = createResetToken($pdo, (int) $user['Account_id']);
            sendResetMail($user['Email'], $token, false);
        }
        $sent = true; // altijd dezelfde melding, zo lekt niet welke adressen bestaan
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        include __DIR__ . '/error.php';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="utf-8">
    <title>Wachtwoord vergeten</title>
    <link rel="stylesheet" href="/checkmate/css/style.css">
</head>

<body>
    <main class="box">
        <h1>Wachtwoord vergeten</h1>
        <?php if ($sent): ?>
            <p class="info">Als dit e-mailadres bij ons bekend is, is er een resetlink verstuurd.</p>
        <?php else: ?>
            <form method="post">
                <?= csrfField() ?>
                <label>E-mail <input type="email" name="email" required></label>
                <button>Verstuur resetlink</button>
            </form>
        <?php endif; ?>
        <p><a href="login.php">Terug naar inloggen</a></p>
    </main>
</body>

</html>