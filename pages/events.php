<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin(); // alleen voor ingelogde gebruikers

$me = (int) $_SESSION['account_id']; // ingelogde gebruiker
$events = [];
try {
    $st = $pdo->prepare(
        "SELECT e.Event_id, e.Event_name, 'admin' AS rol FROM Event e WHERE e.Organizer = ?
         UNION
         SELECT e.Event_id, e.Event_name, 'admin' FROM Event e
           JOIN AdminGroep ag ON ag.Event_id = e.Event_id WHERE ag.Account_id = ?
         UNION
         SELECT e.Event_id, e.Event_name, 'lid' FROM Event e
           JOIN Groep g ON g.Event_id = e.Event_id
           JOIN LidGroep lg ON lg.Groep_id = g.Groep_id WHERE lg.Account_id = ?
         ORDER BY Event_name" // events waar ik organisator, admin of lid ben
    );
    $st->execute([$me, $me, $me]); // één waarde per vraagteken
    $events = $st->fetchAll();
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    include __DIR__ . '/error.php';
    exit;
}

$msg = $_SESSION['flash'] ?? ''; // eenmalige melding
unset($_SESSION['flash']); // na tonen verwijderen

$pageTitle = 'Events';
include __DIR__ . '/../includes/header.php'; // navigatiebalk
?>
<main class="wide">
    <?php if ($msg): ?>
        <p class="info">
            <?= e($msg) ?>
        </p>
    <?php endif; ?>

    <h1>Mijn events</h1>
    <?php if (!$events): ?>
        <p>Je zit nog in geen enkel event.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Event</th>
                <th>Rol</th>
                <th></th>
            </tr>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td>
                        <?= e($ev['Event_name']) ?>
                    </td>
                    <td>
                        <?= e($ev['rol']) ?>
                    </td>
                    <td>
                        <form method="post" action="<?= BASE_URL ?>/actions/select_event.php">
                            <?= csrfField() ?>
                            <input type="hidden" name="event_id" value="<?= (int) $ev['Event_id'] ?>">
                            <button>Openen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h2>Meedoen met een code</h2>
    <form method="post" action="<?= BASE_URL ?>/actions/join.php">
        <?= csrfField() ?>
        <label>Code <input name="code" maxlength="20" required></label>
        <button>Meedoen</button>
    </form>

    <h2>Nieuw event aanmaken</h2>
    <form method="post" action="<?= BASE_URL ?>/actions/event_create.php">
        <?= csrfField() ?>
        <label>Naam <input name="naam" maxlength="100" required></label>
        <label>Groepen (één per regel)
            <textarea name="groepen" rows="4" required></textarea></label>
        <button>Aanmaken</button>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>