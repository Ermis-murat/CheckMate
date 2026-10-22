<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if (empty($_SESSION['event_id']) || empty($_SESSION['rol'])) { // nog geen event gekozen
    redirect('/pages/events.php');
}

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<main class="wide">
    <?php
    try {
        if (isAdmin()) {
            include __DIR__ . '/../includes/dashboard_admin.php'; // admin-blok (FE6)
        } else {
            include __DIR__ . '/../includes/dashboard_lid.php'; // lid-blok (FE3, FE4)
        }
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo '<p class="error">Er is een fout opgetreden. Probeer het later opnieuw.</p>'; // neutrale melding
    }
    ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>