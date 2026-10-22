<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    redirect('/pages/events.php');
csrfCheck();

$eventId = (int) ($_POST['event_id'] ?? 0); // naar getal omzetten
try {
    if (!setEventRole($pdo, (int) $_SESSION['account_id'], $eventId)) { // rol bepalen voor dit event
        forbidden(); // geen rol in dit event: 403
    }
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    http_response_code(500);
    include __DIR__ . '/../pages/error.php';
    exit;
}
redirect('/pages/dashboard.php');