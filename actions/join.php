<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    redirect('/pages/events.php');
csrfCheck();

$code = strtoupper(trim($_POST['code'] ?? '')); // codes zijn hoofdletters
$me = (int) $_SESSION['account_id'];

try {
    $st = $pdo->prepare('SELECT Event_id, Lid_Code, Admin_Code FROM Event WHERE Lid_Code = ? OR Admin_Code = ?'); // past de code bij een event?
    $st->execute([$code, $code]);
    $ev = $st->fetch();

    if (!$ev || $code === '') { // onbekende of lege code
        $_SESSION['flash'] = 'Onbekende code.';
        redirect('/pages/events.php');
    }

    if (hash_equals($ev['Admin_Code'], $code)) { // admin-code
        $pdo->prepare('INSERT IGNORE INTO AdminGroep (Event_id, Account_id) VALUES (?, ?)')
            ->execute([$ev['Event_id'], $me]); // IGNORE voorkomt dubbele rijen
        setEventRole($pdo, $me, (int) $ev['Event_id']);
        redirect('/pages/dashboard.php');
    }

    $_SESSION['pending_join'] = (int) $ev['Event_id']; // lid-code: onthoud het event, kies eerst een groep
    redirect('/pages/join_groep.php');
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    http_response_code(500);
    include __DIR__ . '/../pages/error.php';
    exit;
}