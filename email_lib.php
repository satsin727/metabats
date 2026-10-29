<?php
// Shared module utilities. Keep this file in the same directory as config.php.
require_once __DIR__ . '/config.php';
function em_db(): PDO {
    static $db;
    if (!$db) {
        $db = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        $db->exec("SET NAMES utf8mb4");
        $db->exec("SET time_zone = '+00:00'");
    }
    return $db;
}
function em_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
}
function em_user(): array {
    if (empty($_SESSION['id']) || empty($_SESSION['username'])) {
        http_response_code(403);
        exit('Login required');
    }
    $q = em_db()->prepare('SELECT uid,level,sess,status FROM users WHERE uid=? LIMIT 1');
    $q->execute([(int)$_SESSION['id']]);
    $u = $q->fetch();
    if (!$u || !in_array((int)$u['level'], [1, 2, 3], true) || (string)$u['sess'] !== (string)$_SESSION['username'] || (int)$u['status'] !== 1) {
        http_response_code(403);
        exit('Not authorized');
    }
    return $u;
}
function em_admin(array $u): void {
    if ((int)$u['level'] !== 1) {
        http_response_code(403);
        exit('Administrator permission required');
    }
}
function em_csrf(): string {
    if (empty($_SESSION['em_csrf']))$_SESSION['em_csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['em_csrf'];
}
function em_verify(): void {
    if (!hash_equals(em_csrf(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Invalid security token');
    }
}
function em_redirect(string $page, string $msg = ''): void {
    header('Location: admin.php?action=emaildistribution&page='.rawurlencode($page).($msg ? '&notice='.rawurlencode($msg): ''));
    exit;
}
function em_log(string $event, string $detail = '', ? int $msg = null, ? int $camp = null, ? int $uid = null): void {
    $s = em_db()->prepare('INSERT INTO em_logs(message_id,campaign_id,actor_uid,event,detail) VALUES(?,?,?,?,?)');
    $s->execute([$msg, $camp, $uid, $event, $detail]);
}
function em_key(): string {
    $key = getenv('EMAIL_SECRET_KEY');
    $bytes = $key ? base64_decode($key, true): false;
    if ($bytes === false || strlen($bytes) !== 32)throw new RuntimeException('EMAIL_SECRET_KEY must be a base64-encoded 32-byte key');
    return $bytes;
}
function em_encrypt(string $value): string {
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($value, 'aes-256-gcm', em_key(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false)throw new RuntimeException('Encryption failed');
    return base64_encode($iv.$tag.$cipher);
}
function em_decrypt(string $value): string {
    $raw = base64_decode($value, true);
    if ($raw === false || strlen($raw) < 29)throw new RuntimeException('Invalid stored secret');
    $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', em_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    if ($plain === false)throw new RuntimeException('Could not decrypt SMTP password');
    return $plain;
}
function em_settings(): array {
    return em_db()->query('SELECT * FROM em_settings WHERE id=1')->fetch();
}
function em_addresses(string $raw): array {
    $out = [];
    foreach (preg_split('/[,;\r\n]+/', $raw) as $v) {
        $v = trim($v);
        if (!$v)continue;
        if (!filter_var($v, FILTER_VALIDATE_EMAIL) || strlen($v) > 254)throw new InvalidArgumentException('Invalid email: '.$v);
        $out[strtolower($v)] = $v;
    }
    if (count($out) > 150)throw new InvalidArgumentException('Too many additional addresses');
    return array_values($out);
}
function em_render(string $template, array $data): string {
    return strtr($template, $data);
}
function em_req(int $id, ? array $user = null): ? array {
    $s = em_db()->prepare('SELECT r.reqid,r.job_title,r.rlocation,r.emp_type,r.datetime,c.companyname,(SELECT j.rdesc FROM jd j WHERE j.reqid=r.reqid ORDER BY j.jd_id DESC LIMIT 1) AS rdesc FROM req r LEFT JOIN clients c ON c.cid=r.cid WHERE r.reqid=? AND r.status=1 LIMIT 1');
    $s->execute([$id]);
    $row = $s->fetch() ?: null;
    if ($row && $user && (int)$user['level'] !== 1) {
        $check = em_db()->prepare('SELECT uid FROM req WHERE reqid=?');
        $check->execute([$id]);
        if ((int)$check->fetchColumn() !== (int)$user['uid']) return null;
    }
    return $row;
}
function em_replacements(array $r): array {
    return ['{{req_id}}' => (string)$r['reqid'], '{{job_title}}' => (string)$r['job_title'], '{{location}}' => (string)$r['rlocation'], '{{employment_type}}' => (string)$r['emp_type'], '{{client}}' => (string)$r['companyname'], '{{job_description}}' => trim(strip_tags((string)$r['rdesc']))];
}
function em_recipient_map(array $groups): array {
    $result = ['to' => [], 'cc' => [], 'bcc' => []];
    foreach (['to', 'cc', 'bcc'] as $role) {
        foreach ($groups[$role] ?? [] as $address) {
            $result[$role][strtolower($address)] = $address;
        }
    }
    // BCC wins if same address occurs in several fields
    foreach (['bcc', 'cc'] as $role) {
        foreach ($result[$role] as $key => $value) {
            if ($role === 'bcc') {
                unset($result['to'][$key], $result['cc'][$key]);
            }
            else unset($result['to'][$key]);
        }
    }
    foreach ($result as $role => $values)$result[$role] = array_values($values);
    return $result;
}
function em_require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('POST required');
    }
    em_verify();
}
function em_mbcut(string $value, int $length): string {
    return function_exists('mb_substr') ? mb_substr($value, 0, $length): substr($value, 0, $length);
}
