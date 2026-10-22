<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/partij_helpers.php';
requireLogin();

if (empty($_SESSION['event_id']))
    redirect('/pages/events.php');
$eventId = (int) $_SESSION['event_id'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0; // geen geldig getal wordt 0

$flash = $_SESSION['flash'] ?? ''; // melding van een vorige actie
$flashErr = !empty($_SESSION['flash_error']);
unset($_SESSION['flash'], $_SESSION['flash_error']); // maar één keer tonen

try {
    $p = $id ? loadPartij($pdo, $id, $eventId) : null; // null = niet gevonden

    $vrijeTafels = [];
    if ($p && isAdmin()) { // alleen de admin ziet de tafelkeuze
        $st = $pdo->prepare(
            "SELECT p.Tafel FROM Partij p JOIN Groep g ON g.Groep_id = p.Groep_id
             WHERE g.Event_id = ? AND p.Status IN ('Gepland','Bezig')
               AND p.Tafel IS NOT NULL AND p.Partij_id <> ?" // tafels die nu bezet zijn, behalve die van deze partij
        );
        $st->execute([$eventId, $id]);
        $bezet = $st->fetchAll(PDO::FETCH_COLUMN);
        for ($t = 1; $t <= AANTAL_TAFELS; $t++) {
            if (!in_array((string) $t, $bezet, true))
                $vrijeTafels[] = $t; // alleen vrije tafels in de lijst
        }
    }
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    include __DIR__ . '/error.php';
    exit;
}

$pageTitle = 'Partij';
include __DIR__ . '/../includes/header.php';
?>
<main class="wide">
    <?php if (!$p): ?>
        <h1>Partij niet gevonden</h1>
        <p><a href="dashboard.php">Terug naar dashboard</a></p>
    <?php else:
        $start = $p['Status'] === 'Gepland' ? $p['Geplande_Tijd'] : ($p['Werkelijke_Tijd'] ?? $p['Geplande_Tijd']); // geplande tijd bij Gepland, anders de werkelijke (FE11) ?>
        <?php if ($flash): ?>
            <p class="<?= $flashErr ? 'error' : 'info' ?>">
                <?= e($flash) ?>
            </p>
        <?php endif; ?>

        <div class="cols">
            <section class="col-main">
                <div class="players">
                    <span>
                        <?= e($p['naam1']) ?>
                    </span>
                    <span class="vs">vs</span>
                    <span>
                        <?= e($p['naam2']) ?>
                    </span>
                </div>
                <p>
                    <?= statusBadge($p['Status']) ?>
                </p>
                <p class="uitslag">Uitslag: <strong>
                        <?= e(uitslagTekst($p['Winnaar'])) ?>
                    </strong></p>

                <?php if (isAdmin()): ?> <!-- alleen de admin ziet de bedieningen -->
                    <h3>Status wijzigen</h3>
                    <form method="post" action="<?= BASE_URL ?>/actions/status.php" class="inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="partij_id" value="<?= (int) $p['Partij_id'] ?>">
                        <?php foreach (['Gepland', 'Bezig', 'Afgelopen'] as $s): ?>
                            <button name="status" value="<?= $s ?>" <?= $p['Status'] === $s ? 'disabled' : '' ?>>
                                <?= $s ?>
                            </button>
                        <?php endforeach; ?>
                    </form>

                    <?php if ($p['Status'] === 'Afgelopen'): ?> <!-- uitslagknoppen alleen bij status Afgelopen (FE7) -->
                        <h3>Uitslag invoeren</h3>
                        <form method="post" action="<?= BASE_URL ?>/actions/uitslag.php" class="inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="partij_id" value="<?= (int) $p['Partij_id'] ?>">
                            <button name="winnaar" value="lid1" onclick="return confirm(this.dataset.msg)" 
                                data-msg="<?= e('Weet je zeker dat ' . $p['naam1'] . ' wint? Dit sluit de partij.') ?>">
                                <?= e($p['naam1']) ?> wint
                            </button>
                            <button name="winnaar" value="gelijk" onclick="return confirm(this.dataset.msg)"
                                data-msg="Weet je zeker dat het gelijkspel is? Dit sluit de partij.">Gelijkspel</button>
                            <button name="winnaar" value="lid2" onclick="return confirm(this.dataset.msg)"
                                data-msg="<?= e('Weet je zeker dat ' . $p['naam2'] . ' wint? Dit sluit de partij.') ?>">
                                <?= e($p['naam2']) ?> wint
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <aside class="col-side infobox">
                <h3>Details</h3>
                <p><strong>Tafel</strong><br>
                    <?= e($p['Tafel'] ?? '-') ?>
                </p>
                <p><strong>Starttijd</strong><br>
                    <?= e(date('d-m-Y H:i', strtotime($start))) ?>
                </p>
                <p><strong>Arbiter</strong><br>
                    <?= e($p['arbiter'] ?? '-') ?>
                </p>

                <?php if (isAdmin()): ?>
                    <form method="post" action="<?= BASE_URL ?>/actions/tafel.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="partij_id" value="<?= (int) $p['Partij_id'] ?>">
                        <?php if (!$vrijeTafels): ?>
                            <p>Geen tafels beschikbaar</p>
                        <?php else: ?>
                            <label>Tafel toewijzen
                                <select name="tafel">
                                    <?php foreach ($vrijeTafels as $t): ?>
                                        <option value="<?= $t ?>">Tafel
                                            <?= $t ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button>Opslaan</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </aside>
        </div>
        <p><a href="dashboard.php">← Terug</a></p>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>