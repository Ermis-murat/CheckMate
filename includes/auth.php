<?php
require_once __DIR__ . '/../config/db.php'; // databaseverbinding laden

if (session_status() === PHP_SESSION_NONE) { // alleen starten als er nog geen sessie is
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']); // JavaScript kan cookie niet lezen, beperkt cross-site gebruik
    session_start(); // sessie starten
}

const SESSION_TIMEOUT = 1800; // 30 minuten inactiviteit

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); // tekens onschadelijk maken (XSS-preventie)
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path); // doorsturen binnen het project
    exit; // script stoppen na redirect
}

/* ---------- CSRF ---------- */
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) { // nog geen token in deze sessie
        $_SESSION['csrf'] = bin2hex(random_bytes(32)); // willekeurig, onvoorspelbaar token
    }
    return $_SESSION['csrf'];
}
function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">'; // verborgen veld voor in elk formulier
}
function csrfCheck(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { // vergelijking in constante tijd
        forbidden(); // fout of ontbrekend token: 403
    }
}

/* ---------- Login / rollen ---------- */
function requireLogin(): void
{
    if (empty($_SESSION['account_id'])) { // niet ingelogd
        redirect('/pages/login.php'); // naar het inlogscherm
    }
    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) { // te lang inactief
        session_unset(); // sessiegegevens wissen
        session_destroy(); // sessie beëindigen
        redirect('/pages/login.php?expired=1'); // melding "sessie verlopen" (FE12)
    }
    $_SESSION['last_activity'] = time(); // activiteit bijwerken
}

function forbidden(): void
{
    http_response_code(403); // statuscode 403
    include __DIR__ . '/../pages/403.php'; // foutpagina "Geen toegang" (FE2)
    exit; // actie wordt niet uitgevoerd
}

function isAdmin(): bool
{
    return ($_SESSION['rol'] ?? '') === 'admin'; // rol komt uit de sessie
}

function requireAdmin(): void
{
    requireLogin(); // eerst ingelogd zijn
    if (!isAdmin()) { // geen admin
        forbidden(); // 403 (FE2, FE13)
    }
}

function setEventRole(PDO $pdo, int $accountId, int $eventId): bool
{
    $st = $pdo->prepare('SELECT 1 FROM Event WHERE Event_id = ? AND Organizer = ?'); // is de gebruiker de organisator?
    $st->execute([$eventId, $accountId]);
    $isOrganizer = (bool) $st->fetchColumn();

    $st = $pdo->prepare('SELECT 1 FROM AdminGroep WHERE Event_id = ? AND Account_id = ?'); // staat de gebruiker in AdminGroep?
    $st->execute([$eventId, $accountId]);
    $isAdmin = (bool) $st->fetchColumn();

    if ($isOrganizer || $isAdmin) { // organisator of admin
        $_SESSION['rol'] = 'admin'; // rol voor dit event
        $_SESSION['event_id'] = $eventId; // actief event onthouden
        return true;
    }

    $st = $pdo->prepare(
        'SELECT lg.Groep_id FROM LidGroep lg
         JOIN Groep g ON g.Groep_id = lg.Groep_id
         WHERE g.Event_id = ? AND lg.Account_id = ? LIMIT 1' // zit de gebruiker in een groep van dit event?
    );
    $st->execute([$eventId, $accountId]);
    $groep = $st->fetchColumn();
    if ($groep !== false) { // gebruiker is lid
        $_SESSION['rol'] = 'lid';
        $_SESSION['event_id'] = $eventId;
        $_SESSION['groep_id'] = (int) $groep; // eigen groep, nodig voor de stand (FE4)
        return true;
    }
    return false; // geen rol in dit event
}