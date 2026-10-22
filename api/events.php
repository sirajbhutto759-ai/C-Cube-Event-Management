<?php
// ============================================================
// C Cube – API: Events Search & Listing (JSON)
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/functions.php';
$pdo = db();

$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$limit    = min(50, max(1, (int)($_GET['limit'] ?? 12)));

$where = ["e.status = 'published'"];
$params = [];

if ($search !== '') {
    $where[] = "(e.title LIKE ? OR e.short_description LIKE ? OR e.venue LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category !== '') {
    $where[] = "c.slug = ?";
    $params[] = $category;
}

$where_sql = implode(' AND ', $where);

try {
    $sql = "
        SELECT e.id, e.title, e.slug, e.short_description, e.banner_path,
               e.event_date, e.registration_deadline, e.venue, e.registration_fee,
               e.capacity, e.minimum_team_size, e.maximum_team_size, e.status,
               c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
        FROM events e
        LEFT JOIN categories c ON c.id = e.category_id
        WHERE $where_sql
        ORDER BY e.event_date ASC
        LIMIT $limit
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format fields for frontend consumption
    $formatted = array_map(function ($ev) {
        return [
            'id'                    => (int)$ev['id'],
            'title'                 => $ev['title'],
            'slug'                  => $ev['slug'],
            'short_description'     => $ev['short_description'],
            'banner_url'            => !empty($ev['banner_path']) ? BASE_URL . $ev['banner_path'] : null,
            'event_date'            => $ev['event_date'],
            'event_date_formatted'  => fmt_date($ev['event_date'], 'M j, Y'),
            'deadline_formatted'    => fmt_date($ev['registration_deadline'], 'M j, Y'),
            'venue'                 => $ev['venue'],
            'fee'                   => (float)$ev['registration_fee'],
            'fee_formatted'         => fmt_money($ev['registration_fee']),
            'category_name'         => $ev['category_name'],
            'category_slug'         => $ev['category_slug'],
            'category_icon'         => $ev['category_icon'] ?? 'fa-trophy',
            'team_size_text'        => ($ev['maximum_team_size'] > 1) ? "{$ev['minimum_team_size']}–{$ev['maximum_team_size']} Members" : "Individual",
            'detail_url'            => BASE_URL . 'event-details.php?slug=' . urlencode($ev['slug'])
        ];
    }, $events);

    echo json_encode([
        'status' => 'success',
        'count'  => count($formatted),
        'events' => $formatted
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to query events.'
    ]);
}
