<?php
require( "config.php" );

header("Content-Type: text/html; charset=UTF-8");

// The autocomplete endpoint should only be available to logged-in users.
if (empty($_SESSION['id'])) {
    exit;
}

$term = trim($_GET['term'] ?? '');
if ($term === '') {
    exit;
}

try {
    $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Do not use PDO::rowCount() for SELECT queries because its behavior
    // is driver-dependent. Fetch the matching rows directly instead.
    $sql = "SELECT
                remail,
                MAX(rfname) AS rfname,
                MAX(rname) AS rname,
                MAX(companyname) AS companyname
            FROM clients
            WHERE remail IS NOT NULL
              AND remail <> ''
              AND remail LIKE :term
            GROUP BY remail
            ORDER BY remail ASC
            LIMIT 10";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':term' => $term . '%']);

    while ($row = $stmt->fetch()) {
        $email = strtolower(trim((string)$row['remail']));
        if ($email === '') {
            continue;
        }

        $recruiterName = trim((string)($row['rname'] ?? ''));
        $firstName     = trim((string)($row['rfname'] ?? ''));
        // Prefer rname because older BATS records may already store the full client name there.
        $fullName      = $recruiterName !== '' ? $recruiterName : $firstName;
        $company   = trim((string)($row['companyname'] ?? ''));

        $labelParts = [];
        if ($fullName !== '') {
            $labelParts[] = $fullName;
        }
        if ($company !== '' && strcasecmp($company, $fullName) !== 0) {
            $labelParts[] = $company;
        }

        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safeLabel = htmlspecialchars(implode(' - ', $labelParts), ENT_QUOTES, 'UTF-8');

        echo '<p class="client-search-item" tabindex="0" role="option" data-email="' . $safeEmail . '">';
        echo '<span>' . $safeEmail . '</span>';
        echo '</p>';
    }
} catch (PDOException $e) {
    // Do not expose database credentials or SQL details to the browser.
    error_log('backend-search.php database error: ' . $e->getMessage());
    http_response_code(500);
}
