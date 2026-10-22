<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mail.php'; // voor de resetmail

const MAX_POGINGEN = 5; // aantal foute pogingen voor blokkade (FE1)
$error = $info = ''; // foutmelding en infomelding

if (isset($_GET['expired']))
    $info = 'Je sessie is verlopen, log opnieuw in.'; // na sessie-timeout (FE12)
if (isset($_GET['registered']))
    $info = 'Account aangemaakt. Je kunt nu inloggen.';
if (isset($_GET['reset']))
    $info = 'Wachtwoord gewijzigd. Je kunt nu inloggen.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // formulier verstuurd
    csrfCheck();
    $email = trim($_POST['email'] ?? '');
    $pw = $_POST['wachtwoord'] ?? '';

    try {
        $st = $pdo->prepare('SELECT * FROM Account WHERE Email = ?'); // gebruiker zoeken via prepared statement
        $st->execute([$email]);
        $user = $st->fetch();

        if (!$user) {
            $error = 'E-mail of wachtwoord onjuist.'; // zelfde melding als bij fout wachtwoord
        } elseif ($user['Geblokkeerd']) {
            $error = 'Dit account is geblokkeerd. Gebruik de resetlink in je e-mail.'; // geblokkeerd account
        } elseif (!password_verify($pw, $user['Wachtwoord'])) { // wachtwoord klopt niet
            $pdo->prepare('UPDATE Account SET Login_poging = Login_poging + 1 WHERE Account_id = ?')
                ->execute([$user['Account_id']]); // teller omhoog
            $st = $pdo->prepare('SELECT Login_poging FROM Account WHERE Account_id = ?');
            $st->execute([$user['Account_id']]);
            $pogingen = (int) $st->fetchColumn(); // nieuwe stand van de teller

            if ($pogingen >= MAX_POGINGEN) { // 5e foute poging
                $pdo->prepare('UPDATE Account SET Geblokkeerd = 1 WHERE Account_id = ?')
                    ->execute([$user['Account_id']]); // account blokkeren
                $token = createResetToken($pdo, (int) $user['Account_id']); // resettoken maken
                sendResetMail($user['Email'], $token, true); // melding en resetlink mailen
                $error = 'Dit account is geblokkeerd. Er is een resetlink naar je e-mail gestuurd.';
            } else {
                $error = 'E-mail of wachtwoord onjuist.';
            }
        } else { // inloggen gelukt
            $pdo->prepare('UPDATE Account SET Login_poging = 0 WHERE Account_id = ?')
                ->execute([$user['Account_id']]); // teller terug naar 0
            session_regenerate_id(true); // nieuw sessie-ID (tegen session fixation)
            $_SESSION['account_id'] = (int) $user['Account_id']; // wie is ingelogd
            $_SESSION['naam'] = $user['Naam']; // voor de navigatiebalk
            $_SESSION['last_activity'] = time(); // start van de timeout-teller
            unset($_SESSION['rol'], $_SESSION['event_id'], $_SESSION['groep_id']); // rol wordt per event bepaald
            redirect('/pages/dashboard.php');
        }
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        include __DIR__ . '/error.php'; // neutrale foutpagina
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="utf-8">
    <title>Inloggen</title>
    <link rel="stylesheet" href="/checkmate/css/style.css">
</head>

<body>
    <main class="box">
        <h1>Inloggen</h1>
        <?php if ($info): ?>
            <p class="info">
                <?= e($info) ?>
            </p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error">
                <?= e($error) ?>
            </p>
        <?php endif; ?>
        <form method="post">
            <?= csrfField() ?>
            <label>E-mail <input type="email" name="email" required></label>
            <label>Wachtwoord <input type="password" name="wachtwoord" required></label>
            <button>Inloggen</button>
        </form>
        <p><a href="forgot.php">Wachtwoord vergeten?</a> · <a href="register.php">Registreren</a></p>
    </main>
</body>

</html>