<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$flash = $_SESSION['flash'] ?? ''; // melding, bijvoorbeeld "Wachtwoord onjuist"
unset($_SESSION['flash']);

$pageTitle = 'Profiel';
include __DIR__ . '/../includes/header.php';
?>
<main class="box">
    <h1>Mijn profiel</h1>
    <?php if ($flash): ?>
        <p class="error">
            <?= e($flash) ?>
        </p>
    <?php endif; ?>
    <p>Naam:
        <?= e($_SESSION['naam']) ?>
    </p>

    <h2>Account anonimiseren</h2>
    <p>Je naam wordt "Verwijderd lid", e-mail en wachtwoord worden gewist en inloggen is daarna niet meer mogelijk.
        Partijgeschiedenis en standen blijven bestaan.</p>
    <form method="post" action="<?= BASE_URL ?>/actions/anonimiseer.php"
        onsubmit="return confirm('Weet je het zeker? Dit kan niet ongedaan worden gemaakt.')">
        <?= csrfField() ?>
        <label>Bevestig met je wachtwoord <input type="password" name="wachtwoord" required></label>
        <button>Account anonimiseren</button>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>