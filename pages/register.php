<?php
require_once __DIR__ . '/../includes/auth.php'; // sessie, CSRF, database

$errors = []; // verzamelde foutmeldingen
$naam = $email = ''; // waarden onthouden bij een fout

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // formulier verstuurd
    csrfCheck(); // CSRF-token controleren
    $naam = trim($_POST['naam'] ?? ''); // spaties weghalen
    $email = trim($_POST['email'] ?? '');
    $pw = $_POST['wachtwoord'] ?? '';
    $pw2 = $_POST['wachtwoord2'] ?? '';

    if ($naam === '' || mb_strlen($naam) > 100)
        $errors[] = 'Vul een geldige naam in.'; // naam verplicht, max 100
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Vul een geldig e-mailadres in.'; // e-mailformaat
    if (strlen($pw) < 8)
        $errors[] = 'Wachtwoord moet minimaal 8 tekens zijn.'; // minimale lengte
    if ($pw !== $pw2)
        $errors[] = 'Wachtwoorden komen niet overeen.'; // twee keer hetzelfde

    if (!$errors) { // invoer is goed
        try {
            $st = $pdo->prepare('SELECT 1 FROM Account WHERE Email = ?'); // bestaat het e-mailadres al?
            $st->execute([$email]);
            if ($st->fetchColumn()) {
                $errors[] = 'Dit e-mailadres is al geregistreerd.';
            } else {
                $st = $pdo->prepare('INSERT INTO Account (Naam, Email, Wachtwoord) VALUES (?, ?, ?)');
                $st->execute([$naam, $email, password_hash($pw, PASSWORD_DEFAULT)]); // wachtwoord gehasht opslaan (bcrypt)
                redirect('/pages/login.php?registered=1'); // naar inloggen met bevestiging
            }
        } catch (PDOException $ex) {
            error_log($ex->getMessage()); // alleen loggen
            include __DIR__ . '/error.php'; // neutrale foutpagina
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="utf-8">
    <title>Registreren</title>
    <link rel="stylesheet" href="/checkmate/css/style.css">
</head>

<body>
    <main class="box">
        <h1>Registreren</h1>
        <?php foreach ($errors as $err): ?>
            <p class="error">
                <?= e($err) ?>
            </p>
        <?php endforeach; ?>
        <form method="post">
            <?= csrfField() ?>
            <label>Naam <input name="naam" value="<?= e($naam) ?>" required></label>
            <label>E-mail <input type="email" name="email" value="<?= e($email) ?>" required></label>
            <label>Wachtwoord <input type="password" name="wachtwoord" required></label>
            <label>Herhaal wachtwoord <input type="password" name="wachtwoord2" required></label>
            <button>Registreren</button>
        </form>
        <p><a href="login.php">Al een account? Inloggen</a></p>
    </main>
</body>

</html>