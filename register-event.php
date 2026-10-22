<?php
// ============================================================
// C Cube – Event Registration Handler (POST only)
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
session_boot();
require_participant();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'events.php'); exit;
}
csrf_verify();

$pdo    = db();
$user   = current_user();
$errors = [];

$event_id          = (int)($_POST['event_id'] ?? 0);
$participation_type= in_array($_POST['participation_type'] ?? '', ['individual','team']) ? $_POST['participation_type'] : 'individual';
$team_name         = trim($_POST['team_name'] ?? '');
$additional_info   = trim($_POST['additional_info'] ?? '');

// Fetch event
$stmt = $pdo->prepare("SELECT * FROM events WHERE id=? LIMIT 1");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    redirect_with_message(BASE_URL . 'events.php', 'error', 'Event not found.');
}
if (!is_registration_open($event)) {
    redirect_with_message(BASE_URL . 'event-details.php?slug=' . urlencode($event['slug']), 'error', 'Registration is not open for this event.');
}

// Duplicate check
$dup = $pdo->prepare("SELECT id FROM registrations WHERE user_id=? AND event_id=?");
$dup->execute([$user['id'], $event_id]);
if ($dup->fetch()) {
    redirect_with_message(BASE_URL . 'event-details.php?slug=' . urlencode($event['slug']), 'error', 'You are already registered for this event.');
}

// Capacity check
if ($event['capacity']) {
    $taken = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id=? AND registration_status NOT IN ('rejected','cancelled')");
    $taken->execute([$event_id]);
    if ((int)$taken->fetchColumn() >= $event['capacity']) {
        redirect_with_message(BASE_URL . 'event-details.php?slug=' . urlencode($event['slug']), 'error', 'Sorry, this event is at full capacity.');
    }
}

// Team validation
$member_names  = $_POST['member_name'] ?? [];
$member_emails = $_POST['member_email'] ?? [];
$member_insts  = $_POST['member_institution'] ?? [];

if ($participation_type === 'team') {
    if (!$team_name) $errors[] = 'Team name is required for team participation.';
    $total_members = 1 + count(array_filter($member_names, fn($n) => trim($n) !== ''));
    if ($total_members < $event['minimum_team_size']) {
        $errors[] = "Minimum team size is {$event['minimum_team_size']} members.";
    }
    if ($total_members > $event['maximum_team_size']) {
        $errors[] = "Maximum team size is {$event['maximum_team_size']} members.";
    }
}

if ($errors) {
    redirect_with_message(BASE_URL . 'event-details.php?slug=' . urlencode($event['slug']), 'error', implode(' ', $errors));
}

// DB transaction
try {
    $pdo->beginTransaction();

    $ref = generate_reference();

    // Insert registration
    $stmt = $pdo->prepare(
        "INSERT INTO registrations
         (registration_reference, user_id, event_id, participation_type, team_name, additional_info, registration_status)
         VALUES (?,?,?,?,?,?,'pending_payment')"
    );
    $stmt->execute([$ref, $user['id'], $event_id, $participation_type, $team_name ?: null, $additional_info ?: null]);
    $reg_id = (int)$pdo->lastInsertId();

    // Team members
    if ($participation_type === 'team') {
        $ins = $pdo->prepare(
            "INSERT INTO team_members (registration_id, member_name, member_email, member_institution)
             VALUES (?,?,?,?)"
        );
        foreach ($member_names as $i => $mn) {
            if (trim($mn) === '') continue;
            $ins->execute([$reg_id, trim($mn), trim($member_emails[$i] ?? ''), trim($member_insts[$i] ?? '')]);
        }
    }

    // Create payment record (not_submitted initial state)
    $pdo->prepare(
        "INSERT INTO payments (registration_id, amount, payment_status) VALUES (?,?,'not_submitted')"
    )->execute([$reg_id, $event['registration_fee']]);

    // Notify user
    notify_user($user['id'], 'Registration Confirmed',
        "Your registration for \"{$event['title']}\" (Ref: $ref) has been submitted. Please complete payment to proceed.");

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[CCube Register] ' . $e->getMessage());
    redirect_with_message(BASE_URL . 'event-details.php?slug=' . urlencode($event['slug']), 'error', 'Registration failed. Please try again.');
}

redirect_with_message(
    BASE_URL . 'participant/payment.php?ref=' . urlencode($ref),
    'success',
    "Registered successfully! Reference: {$ref}. Please submit your payment to complete registration."
);
