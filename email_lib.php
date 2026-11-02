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
    $s = em_db()->prepare("SELECT r.reqid,r.uid,r.job_title,r.rlocation,r.emp_type,r.datetime,r.rend_client,c.companyname,c.remail,
            COALESCE(NULLIF(c.domain,''), SUBSTRING_INDEX(c.remail,'@',-1)) AS domain,
            (SELECT j.rdesc FROM jd j WHERE j.reqid=r.reqid ORDER BY j.jd_id DESC LIMIT 1) AS rdesc
        FROM req r LEFT JOIN clients c ON c.cid=r.cid WHERE r.reqid=? AND r.status=1 LIMIT 1");
    $s->execute([$id]);
    $row = $s->fetch() ?: null;
    if ($row && $user && (int)$user['level'] !== 1 && (int)$row['uid'] !== (int)$user['uid']) return null;
    return $row;
}
/** Convert stored HTML job descriptions to readable plain text for outgoing emails. */
function em_job_description_text(string $html): string {
    // Convert layout tags to line breaks before stripping HTML tags.
    $html = preg_replace('~<br\s*/?>~i', "\n", $html);
    $html = preg_replace('~</(?:p|div|li|tr|h[1-6])\s*>~i', "\n", $html);
    $html = preg_replace('~<li\b[^>]*>~i', '- ', $html);
    $html = preg_replace('~</t[dh]\s*>~i', '  ', $html);
    $text = strip_tags($html);

    // Decode &nbsp;, &amp;, &#160; and other HTML entities.
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Normalize Unicode non-breaking spaces to ordinary spaces.
    $text = str_replace("\xC2\xA0", ' ', $text);
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = explode("\n", $text);
    $lines = array_map(static function (string $line): string {
        $line = preg_replace('/[\t ]+/u', ' ', $line);
        return trim($line);
    }, $lines);
    $text = implode("\n", $lines);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}

function em_replacements(array $r): array {
    return [
        '{{req_id}}' => (string)$r['reqid'],
        '{{job_title}}' => (string)$r['job_title'],
        '{{location}}' => (string)$r['rlocation'],
        '{{employment_type}}' => (string)$r['emp_type'],
        '{{client}}' => (string)$r['companyname'],
        '{{end_client}}' => (string)($r['rend_client'] ?? ''),
        '{{domain}}' => (string)($r['domain'] ?? ''),
        '{{recruiter_email}}' => (string)($r['remail'] ?? ''),
        '{{date}}' => (new DateTimeImmutable('now', em_tz()))->format('m/d/Y'),
        '{{job_description}}' => em_job_description_text((string)($r['rdesc'] ?? '')),
    ];
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

/* ======================================================================
 * Added 2026-11: C2C sharing page, previews, AJAX queue, resend, reports,
 * Google Chat webhook.
 * ==================================================================== */

/** Adds the new columns once if they are missing (only ADD COLUMN / ADD INDEX, never drops). */
function em_schema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $db = em_db();
    $want = [
        'em_settings'  => ['gchat_enabled' => "TINYINT(1) NOT NULL DEFAULT 0", 'gchat_webhook_enc' => "TEXT NULL", 'gchat_template' => "TEXT NULL",
                           'chat_min_gap_seconds' => "INT NOT NULL DEFAULT 2", 'chat_hourly_limit' => "INT NOT NULL DEFAULT 60",
                           'gchat_summary_template' => "TEXT NULL", 'gchat_summary_skill_line' => "TEXT NULL", 'gchat_summary_job_line' => "TEXT NULL"],
        'em_campaigns' => ['post_to_chat' => "TINYINT(1) NOT NULL DEFAULT 0"],
        'em_messages'  => ['chat_status' => "VARCHAR(20) NULL", 'chat_error' => "TEXT NULL", 'resend_of' => "BIGINT NULL", 'channel' => "VARCHAR(10) NOT NULL DEFAULT 'email'"],
    ];
    foreach ($want as $table => $cols) {
        $have = [];
        foreach ($db->query("SHOW COLUMNS FROM `$table`") as $c) $have[strtolower($c['Field'])] = true;
        foreach ($cols as $col => $def) {
            if (!isset($have[strtolower($col)])) {
                try { $db->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def"); }
                catch (Throwable $e) { error_log("em_schema: $table.$col: " . $e->getMessage()); }
            }
        }
    }
}

function em_tz(): DateTimeZone {
    static $tz = null;
    if ($tz === null) {
        try { $name = (string)(em_settings()['timezone_name'] ?? 'America/Chicago'); $tz = new DateTimeZone($name); }
        catch (Throwable $e) { $tz = new DateTimeZone('America/Chicago'); }
    }
    return $tz;
}

/** UTC DB time -> local display time (settings timezone). */
function em_local(?string $utc, string $fmt = 'm/d/Y g:i A'): string {
    if ($utc === null || $utc === '') return '';
    try { return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(em_tz())->format($fmt); }
    catch (Throwable $e) { return (string)$utc; }
}

/**
 * Period -> [startUtc, endUtc, label, fromLocal, toLocal]. Periods: today, week (last 7 days), month (last 30 days), thismonth, custom.
 * Boundaries are local days (settings timezone) converted to UTC for the DB.
 */
function em_period(string $period, string $from = '', string $to = ''): array {
    $tz = em_tz();
    $today = new DateTimeImmutable('today', $tz);
    switch ($period) {
        case 'week':      $start = $today->modify('-6 days'); $end = $today->modify('+1 day'); break;   // last 7 days incl. today
        case 'month':     $start = $today->modify('-29 days'); $end = $today->modify('+1 day'); break;  // last 30 days incl. today
        case 'thismonth': $start = $today->modify('first day of this month'); $end = $today->modify('+1 day'); break;
        case 'custom':
            $f = DateTimeImmutable::createFromFormat('!Y-m-d', $from, $tz) ?: $today;
            $t = DateTimeImmutable::createFromFormat('!Y-m-d', $to, $tz) ?: $f;
            if ($t < $f) { [$f, $t] = [$t, $f]; }
            $start = $f; $end = $t->modify('+1 day'); break;
        default: $period = 'today'; $start = $today; $end = $today->modify('+1 day');
    }
    $utc = new DateTimeZone('UTC');
    $label = $period === 'today' ? 'Today, ' . $start->format('D m/d/Y')
        : ($start->format('m/d/Y') . ' - ' . $end->modify('-1 day')->format('m/d/Y'));
    return [$start->setTimezone($utc)->format('Y-m-d H:i:s'), $end->setTimezone($utc)->format('Y-m-d H:i:s'), $label,
            $start->format('Y-m-d'), $end->modify('-1 day')->format('Y-m-d'), $period];
}

/**
 * Builds (and optionally saves) a campaign: one email per requirement x recipient list.
 * $in uses the same field names as the compose form. Returns ['messages' => [...], 'campaign_id' => ?int].
 */
function em_build_campaign(array $u, array $in, bool $save): array {
    if (($in['channel'] ?? 'email') === 'chat') return em_build_chat_campaign($u, $in, $save);
    $db = em_db();
    $reqids = array_values(array_unique(array_filter(array_map('intval', (array)($in['reqids'] ?? [])))));
    $listids = array_values(array_unique(array_filter(array_map('intval', (array)($in['listids'] ?? [])))));
    if (!$reqids || !$listids || count($reqids) > 100 || count($listids) > 20) throw new RuntimeException('Select 1–100 requirements and 1–20 lists');
    $templateId = (int)($in['template_id'] ?? 0);
    $q = $db->prepare('SELECT * FROM em_templates WHERE id=? AND active=1');
    $q->execute([$templateId]);
    $tpl = $q->fetch();
    if (!$tpl) throw new RuntimeException('Select a valid template');
    $extra = ['to' => em_addresses((string)($in['extra_to'] ?? '')), 'cc' => em_addresses((string)($in['extra_cc'] ?? '')), 'bcc' => em_addresses((string)($in['extra_bcc'] ?? ''))];
    $excluded = array_fill_keys(array_map('strtolower', em_addresses((string)($in['exclude'] ?? ''))), true);
    $when = trim((string)($in['scheduled_at'] ?? ''));
    $tz = em_tz();
    $scheduled = $when !== '' ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $when, $tz) : new DateTimeImmutable('now', $tz);
    if (!$scheduled || $scheduled < (new DateTimeImmutable('now', $tz))->modify('-2 minutes')) throw new RuntimeException('Invalid or past schedule');
    $utc = $scheduled->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $lq = $db->prepare('SELECT id,name FROM em_lists WHERE id=? AND active=1');
    $m = $db->prepare("SELECT c.email,lm.recipient_type FROM em_list_members lm JOIN em_contacts c ON c.id=lm.contact_id AND c.active=1 WHERE lm.list_id=?");
    $payload = [];
    foreach ($listids as $lid) {
        $lq->execute([$lid]);
        $list = $lq->fetch();
        if (!$list) throw new RuntimeException('Invalid recipient list ' . $lid);
        $m->execute([$lid]);
        $groups = ['to' => $extra['to'], 'cc' => $extra['cc'], 'bcc' => $extra['bcc']];
        foreach (['to', 'cc', 'bcc'] as $kind) $groups[$kind] = array_merge($groups[$kind], em_addresses((string)($in['list_extra'][$lid][$kind] ?? '')));
        $listExcluded = $excluded + array_fill_keys(array_map('strtolower', em_addresses((string)($in['list_exclude'][$lid] ?? ''))), true);
        foreach ($m as $member) $groups[$member['recipient_type']][] = $member['email'];
        $rec = em_recipient_map($groups);
        foreach ($rec as $role => $addresses) $rec[$role] = array_values(array_filter($addresses, fn($v) => !isset($listExcluded[strtolower($v)])));
        if (!$rec['to']) throw new RuntimeException('List ' . $list['name'] . ' has no To recipient after exclusions');
        $total = array_sum(array_map('count', $rec));
        if ($total > 100) throw new RuntimeException('Recipient count exceeds 100 per message');
        $payload[] = ['list' => $list, 'recipients' => $rec, 'count' => $total];
    }
    $reqs = [];
    foreach ($reqids as $id) {
        $r = em_req($id, $u);
        if (!$r) throw new RuntimeException('Requirement ' . $id . ' is unavailable');
        $reqs[] = $r;
    }
    if (count($reqs) * count($payload) > 500) throw new RuntimeException('Campaign too large');
    $messages = [];
    foreach ($reqs as $r) {
        $rep = em_replacements($r);
        foreach ($payload as $p) {
            $messages[] = ['reqid' => (int)$r['reqid'], 'job_title' => (string)$r['job_title'], 'list_id' => (int)$p['list']['id'], 'list_name' => (string)$p['list']['name'],
                'subject' => em_mbcut(em_render($tpl['subject'], $rep), 255), 'body' => em_render($tpl['body'], $rep),
                'recipients' => $p['recipients'], 'count' => $p['count']];
        }
    }
    $chat = !empty($in['post_to_chat']) ? 1 : 0;
    if (!$save) return ['messages' => $messages, 'campaign_id' => null, 'scheduled_local' => $scheduled->format('m/d/Y g:i A')];
    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO em_campaigns(created_by,template_id,scheduled_at,post_to_chat) VALUES(?,?,?,?)')->execute([(int)$u['uid'], $templateId, $utc, $chat]);
        $camp = (int)$db->lastInsertId();
        $insert = $db->prepare('INSERT INTO em_messages(campaign_id,reqid,list_id,list_name,subject,body,recipients_json,recipient_count,available_at,chat_status) VALUES(?,?,?,?,?,?,?,?,?,?)');
        foreach ($messages as $msg) {
            $insert->execute([$camp, $msg['reqid'], $msg['list_id'], $msg['list_name'], $msg['subject'], $msg['body'],
                json_encode($msg['recipients'], JSON_THROW_ON_ERROR), $msg['count'], $utc, $chat ? 'pending' : null]);
        }
        em_log('campaign_created', 'Requirements ' . count($reqs) . ', lists ' . count($payload) . ', messages ' . count($messages), null, $camp, (int)$u['uid']);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    return ['messages' => $messages, 'campaign_id' => $camp, 'scheduled_local' => $scheduled->format('m/d/Y g:i A')];
}

/** Queue a corrected copy of an earlier message (History > Edit & Resend). */
function em_resend(array $u, int $id, string $subject, string $body, string $to, string $cc, string $bcc, bool $chat): int {
    $db = em_db();
    $q = $db->prepare('SELECT m.*, c.created_by, c.template_id FROM em_messages m JOIN em_campaigns c ON c.id=m.campaign_id WHERE m.id=?');
    $q->execute([$id]);
    $orig = $q->fetch();
    if (!$orig) throw new RuntimeException('Message not found');
    if ((int)$u['level'] !== 1 && (int)$orig['created_by'] !== (int)$u['uid']) throw new RuntimeException('Not authorized');
    if (in_array($orig['status'], ['scheduled', 'processing'], true)) throw new RuntimeException('This message is still in the queue');
    $subject = trim($subject); $body = trim($body);
    if ($subject === '' || $body === '') throw new RuntimeException('Subject and body are required');
    $isChat = ($orig['channel'] ?? 'email') === 'chat';
    $rec = $isChat ? ['to' => [], 'cc' => [], 'bcc' => []] : em_recipient_map(['to' => em_addresses($to), 'cc' => em_addresses($cc), 'bcc' => em_addresses($bcc)]);
    if (!$isChat && !$rec['to']) throw new RuntimeException('At least one To recipient is required');
    $count = array_sum(array_map('count', $rec));
    if ($count > 100) throw new RuntimeException('Recipient count exceeds 100 per message');
    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO em_campaigns(created_by,template_id,scheduled_at,post_to_chat) VALUES(?,?,UTC_TIMESTAMP(),?)')->execute([(int)$u['uid'], (int)$orig['template_id'], $chat ? 1 : 0]);
        $camp = (int)$db->lastInsertId();
        $db->prepare('INSERT INTO em_messages(campaign_id,reqid,list_id,list_name,subject,body,recipients_json,recipient_count,available_at,chat_status,resend_of,channel) VALUES(?,?,?,?,?,?,?,?,UTC_TIMESTAMP() - INTERVAL 1 SECOND,?,?,?)')
           ->execute([$camp, $orig['reqid'], $orig['list_id'], $orig['list_name'], em_mbcut($subject, 255), $body, json_encode($rec, JSON_THROW_ON_ERROR), $count,
                      ($chat || $isChat) ? 'pending' : null, $id, $isChat ? 'chat' : 'email']);
        $newId = (int)$db->lastInsertId();
        em_log('message_resent', 'Corrected copy of message #' . $id . ' queued as #' . $newId, $newId, $camp, (int)$u['uid']);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    return $newId;
}

/** Sharing status of requirements, for the live-refresh of the Sharing column. */
function em_share_status(array $reqids): array {
    $reqids = array_values(array_unique(array_filter(array_map('intval', $reqids))));
    $out = [];
    if (!$reqids) return $out;
    $in = implode(',', array_fill(0, count($reqids), '?'));
    $s = em_db()->prepare("SELECT reqid,
            SUM(status='sent') AS sent_count, SUM(status IN ('scheduled','processing')) AS queued_count, SUM(status='failed') AS failed_count,
            GROUP_CONCAT(DISTINCT CASE WHEN status='sent' THEN list_name END ORDER BY list_name SEPARATOR ', ') AS sent_lists,
            MAX(CASE WHEN status='sent' THEN sent_at END) AS last_sent
        FROM em_messages WHERE reqid IN ($in) GROUP BY reqid");
    $s->execute($reqids);
    foreach ($reqids as $id) $out[$id] = ['sent_count' => 0, 'queued_count' => 0, 'failed_count' => 0, 'sent_lists' => '', 'last_sent' => ''];
    foreach ($s as $r) {
        $out[(int)$r['reqid']] = ['sent_count' => (int)$r['sent_count'], 'queued_count' => (int)$r['queued_count'], 'failed_count' => (int)$r['failed_count'],
            'sent_lists' => (string)$r['sent_lists'], 'last_sent' => em_local($r['last_sent'])];
    }
    foreach ($out as $id => $st) $out[$id]['html'] = em_share_html($st);
    return $out;
}
function em_share_html(array $st): string {
    $parts = [];
    if ($st['sent_count']) $parts[] = '<span class="label label-success">Sent: ' . (int)$st['sent_count'] . '</span>';
    if ($st['queued_count']) $parts[] = '<span class="label label-warning">Queued: ' . (int)$st['queued_count'] . '</span>';
    if ($st['failed_count']) $parts[] = '<span class="label label-danger">Failed: ' . (int)$st['failed_count'] . '</span>';
    if (!$parts) $parts[] = '<span class="muted">Not shared</span>';
    $html = implode(' ', $parts);
    if ($st['sent_lists'] !== '') $html .= '<div class="muted">' . em_h($st['sent_lists']) . ($st['last_sent'] ? ' · ' . em_h($st['last_sent']) : '') . '</div>';
    return $html;
}

/* ---------------- Google Chat ---------------- */
const EM_GCHAT_DEFAULT_TEMPLATE = "*New C2C Requirement* (Req #{{req_id}})\n*{{job_title}}*\nLocation: {{location}}\nEnd client: {{end_client}}\nShared with: {{list_name}}\nDate: {{date}}";

function em_gchat_valid_url(string $url): bool {
    return (bool)preg_match('~^https://chat\.googleapis\.com/v1/spaces/[^/\s]+/messages\?\S+$~', $url);
}
function em_gchat_post(string $url, string $text): void {
    if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension is not available');
    $text = em_mbcut($text, 4000);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=UTF-8'], CURLOPT_POSTFIELDS => json_encode(['text' => $text])]);
    $res = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($res === false || $code < 200 || $code >= 300) {
        throw new RuntimeException('Google Chat webhook failed (HTTP ' . $code . ') ' . em_mbcut($err ?: (string)$res, 300));
    }
}
function em_gchat_text(string $template, array $req, string $listName): string {
    $rep = $req ? em_replacements($req) : [];
    $rep['{{list_name}}'] = $listName;
    return em_render($template !== '' ? $template : EM_GCHAT_DEFAULT_TEMPLATE, $rep);
}
/** Called by the worker after an email is accepted by SMTP. Posts once per requirement per campaign. */
function em_gchat_after_send(array $item): void {
    try {
        if (($item['chat_status'] ?? null) !== 'pending') return;
        $db = em_db();
        $settings = em_settings();
        if (empty($settings['gchat_enabled']) || empty($settings['gchat_webhook_enc'])) {
            $db->prepare("UPDATE em_messages SET chat_status='skipped', chat_error='Google Chat disabled' WHERE id=?")->execute([$item['id']]);
            return;
        }
        $dup = $db->prepare("SELECT COUNT(*) FROM em_messages WHERE campaign_id=? AND reqid=? AND chat_status='posted'");
        $dup->execute([$item['campaign_id'], $item['reqid']]);
        if ((int)$dup->fetchColumn() > 0) {
            $db->prepare("UPDATE em_messages SET chat_status='skipped', chat_error='Already posted for this requirement' WHERE id=?")->execute([$item['id']]);
            return;
        }
        $lists = $db->prepare("SELECT GROUP_CONCAT(DISTINCT list_name ORDER BY list_name SEPARATOR ', ') FROM em_messages WHERE campaign_id=? AND reqid=?");
        $lists->execute([$item['campaign_id'], $item['reqid']]);
        $req = em_req((int)$item['reqid']) ?: ['reqid' => $item['reqid'], 'job_title' => $item['subject'], 'rlocation' => '', 'emp_type' => '', 'companyname' => '', 'rdesc' => ''];
        em_gchat_post(em_decrypt($settings['gchat_webhook_enc']), em_gchat_text((string)$settings['gchat_template'], $req, (string)$lists->fetchColumn()));
        $db->prepare("UPDATE em_messages SET chat_status='posted', chat_error=NULL WHERE id=?")->execute([$item['id']]);
        em_log('gchat_posted', 'Google Chat message posted for requirement ' . $item['reqid'], (int)$item['id'], (int)$item['campaign_id']);
    } catch (Throwable $e) {
        try {
            em_db()->prepare("UPDATE em_messages SET chat_status='failed', chat_error=? WHERE id=?")->execute([em_mbcut($e->getMessage(), 800), $item['id']]);
            em_log('gchat_failed', em_mbcut($e->getMessage(), 800), (int)$item['id'], (int)$item['campaign_id']);
        } catch (Throwable $ignore) {}
    }
}

/* ---------------- Reports ---------------- */
/** Sent requirement emails in [startUtc, endUtc). Level 3 users see only campaigns they created. */
function em_report_rows(array $u, string $startUtc, string $endUtc, bool $withJd): array {
    $sql = "SELECT m.id, m.reqid, m.list_name, m.sent_at, m.recipient_count, r.job_title, r.rlocation, r.emp_type, r.rend_client,
                   c.companyname, c.remail, COALESCE(NULLIF(c.domain,''), SUBSTRING_INDEX(c.remail,'@',-1)) AS domain, uu.name AS shared_by"
         . ($withJd ? ", (SELECT j.rdesc FROM jd j WHERE j.reqid=m.reqid ORDER BY j.jd_id DESC LIMIT 1) AS rdesc" : "")
         . " FROM em_messages m JOIN em_campaigns cp ON cp.id=m.campaign_id LEFT JOIN users uu ON uu.uid=cp.created_by
             LEFT JOIN req r ON r.reqid=m.reqid LEFT JOIN clients c ON c.cid=r.cid
             WHERE m.status='sent' AND m.sent_at >= ? AND m.sent_at < ?" . ((int)$u['level'] === 3 ? " AND cp.created_by = ?" : "")
         . " ORDER BY m.sent_at DESC, m.id DESC LIMIT 20000";
    $p = [$startUtc, $endUtc];
    if ((int)$u['level'] === 3) $p[] = (int)$u['uid'];
    $s = em_db()->prepare($sql);
    $s->execute($p);
    return $s->fetchAll();
}

/**
 * Google Chat ONLY campaign: one chat message per requirement, posted through the same queue
 * (same pause switch, sending window, minimum gap and hourly limit). No emails are sent.
 */
function em_build_chat_campaign(array $u, array $in, bool $save): array {
    $db = em_db();
    $settings = em_settings();
    if (empty($settings['gchat_enabled']) || empty($settings['gchat_webhook_enc'])) throw new RuntimeException('Google Chat is not set up. An admin can add the webhook under SMTP & Google Chat.');
    $reqids = array_values(array_unique(array_filter(array_map('intval', (array)($in['reqids'] ?? [])))));
    if (!$reqids || count($reqids) > 100) throw new RuntimeException('Select 1–100 requirements');
    $when = trim((string)($in['scheduled_at'] ?? ''));
    $tz = em_tz();
    $scheduled = $when !== '' ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $when, $tz) : new DateTimeImmutable('now', $tz);
    if (!$scheduled || $scheduled < (new DateTimeImmutable('now', $tz))->modify('-2 minutes')) throw new RuntimeException('Invalid or past schedule');
    $utc = $scheduled->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $template = (string)($settings['gchat_template'] ?? '');
    $messages = [];
    foreach ($reqids as $id) {
        $r = em_req($id, $u);
        if (!$r) throw new RuntimeException('Requirement ' . $id . ' is unavailable');
        $text = em_mbcut(em_gchat_text($template, $r, 'Google Chat'), 4000);
        $messages[] = ['reqid' => (int)$r['reqid'], 'job_title' => (string)$r['job_title'], 'list_id' => 0, 'list_name' => 'Google Chat', 'channel' => 'chat',
            'subject' => em_mbcut('Google Chat: ' . $r['job_title'], 255), 'body' => $text, 'recipients' => ['to' => [], 'cc' => [], 'bcc' => []], 'count' => 0];
    }
    if (!$save) return ['messages' => $messages, 'campaign_id' => null, 'scheduled_local' => $scheduled->format('m/d/Y g:i A'), 'channel' => 'chat'];
    $tpl = (int)$db->query('SELECT id FROM em_templates ORDER BY id LIMIT 1')->fetchColumn();
    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO em_campaigns(created_by,template_id,scheduled_at,post_to_chat) VALUES(?,?,?,1)')->execute([(int)$u['uid'], $tpl, $utc]);
        $camp = (int)$db->lastInsertId();
        $ins = $db->prepare("INSERT INTO em_messages(campaign_id,reqid,list_id,list_name,subject,body,recipients_json,recipient_count,available_at,chat_status,channel) VALUES(?,?,0,'Google Chat',?,?,?,0,?,'pending','chat')");
        foreach ($messages as $m) $ins->execute([$camp, $m['reqid'], $m['subject'], $m['body'], json_encode($m['recipients']), $utc]);
        em_log('chat_campaign_created', 'Google Chat only: ' . count($messages) . ' requirement(s)', null, $camp, (int)$u['uid']);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    return ['messages' => $messages, 'campaign_id' => $camp, 'scheduled_local' => $scheduled->format('m/d/Y g:i A'), 'channel' => 'chat'];
}

/* ---------------- Google Chat: daily summary (one message) ---------------- */
const EM_SUMMARY_TEMPLATE = "*C2C Requirements — {{date}}*\nTotal open C2C requirements: *{{total}}*\n\n*By skill*\n{{skill_summary}}\n\n*Jobs*\n{{jobs_by_skill}}";
const EM_SUMMARY_SKILL_LINE = "• {{skill}}: {{count}}";
const EM_SUMMARY_JOB_LINE = "{{n}}. *{{job_title}}* — {{location}} | End client: {{end_client}} | Req #{{req_id}}";

/**
 * Builds the summary text for the open C2C requirements posted on $day (Y-m-d, local).
 * Main template placeholders: {{date}} {{total}} {{skill_count}} {{skill_summary}} {{jobs}} {{jobs_by_skill}}
 * Skill line (repeated per skill): {{skill}} {{count}}
 * Job line (repeated per requirement): {{n}} {{req_id}} {{job_title}} {{location}} {{end_client}} {{client}} {{domain}}
 *                                      {{recruiter_email}} {{skill}} {{employment_type}} {{posted}}
 * Returns [parts (array of message texts, each <= 4000 chars), total, skillCounts].
 */
function em_build_summary(string $day, ?array $settings = null): array {
    $settings = $settings ?? em_settings();
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) $day = (new DateTimeImmutable('now', em_tz()))->format('Y-m-d');
    $s = em_db()->prepare("SELECT r.reqid, r.job_title, r.rlocation, r.emp_type, r.datetime, r.rend_client, c.companyname, c.remail,
            COALESCE(NULLIF(c.domain,''), SUBSTRING_INDEX(c.remail,'@',-1)) AS domain, COALESCE(sk.skillname,'Other') AS skillname
        FROM req r LEFT JOIN clients c ON c.cid=r.cid LEFT JOIN skill sk ON sk.sid=r.skillid
        WHERE r.status=1 AND r.emp_type='C2C' AND r.datetime >= ? AND r.datetime < ?
        ORDER BY skillname, r.datetime, r.reqid");
    $s->execute([$day . ' 00:00:00', date('Y-m-d', strtotime($day . ' +1 day')) . ' 00:00:00']);
    $rows = $s->fetchAll();
    $tMain = trim((string)($settings['gchat_summary_template'] ?? '')) !== '' ? (string)$settings['gchat_summary_template'] : EM_SUMMARY_TEMPLATE;
    $tSkill = trim((string)($settings['gchat_summary_skill_line'] ?? '')) !== '' ? (string)$settings['gchat_summary_skill_line'] : EM_SUMMARY_SKILL_LINE;
    $tJob = trim((string)($settings['gchat_summary_job_line'] ?? '')) !== '' ? (string)$settings['gchat_summary_job_line'] : EM_SUMMARY_JOB_LINE;
    $bySkill = [];
    foreach ($rows as $r) $bySkill[$r['skillname']][] = $r;
    $skillLines = []; $jobLines = []; $groupBlocks = []; $n = 0;
    foreach ($bySkill as $skill => $list) {
        $skillLines[] = strtr($tSkill, ['{{skill}}' => $skill, '{{count}}' => (string)count($list)]);
        $block = ['*' . $skill . ' (' . count($list) . ')*'];
        foreach ($list as $r) {
            $n++;
            $line = strtr($tJob, ['{{n}}' => (string)$n, '{{req_id}}' => (string)$r['reqid'], '{{job_title}}' => (string)$r['job_title'],
                '{{location}}' => (string)$r['rlocation'], '{{end_client}}' => (string)($r['rend_client'] ?: '—'), '{{client}}' => (string)$r['companyname'],
                '{{domain}}' => (string)$r['domain'], '{{recruiter_email}}' => (string)$r['remail'], '{{skill}}' => (string)$skill,
                '{{employment_type}}' => (string)$r['emp_type'], '{{posted}}' => $r['datetime'] ? date('g:i A', strtotime($r['datetime'])) : '']);
            $jobLines[] = $line; $block[] = $line;
        }
        $groupBlocks[] = implode("\n", $block);
    }
    $vars = ['{{date}}' => date('D m/d/Y', strtotime($day)), '{{total}}' => (string)count($rows), '{{skill_count}}' => (string)count($bySkill)];
    $skillTxt = $skillLines ? implode("\n", $skillLines) : 'No open C2C requirements were posted on this day.';
    $text = strtr($tMain, $vars + ['{{skill_summary}}' => $skillTxt, '{{jobs}}' => implode("\n", $jobLines), '{{jobs_by_skill}}' => implode("\n\n", $groupBlocks)]);
    // Google Chat accepts up to 4,096 characters per message: split long summaries at line breaks.
    $parts = []; $cur = '';
    foreach (explode("\n", $text) as $line) {
        $line = em_mbcut($line, 3900);
        if ($cur !== '' && (function_exists("mb_strlen") ? mb_strlen($cur . "\n" . $line) : strlen($cur . "\n" . $line)) > 3900) { $parts[] = $cur; $cur = $line; }
        else $cur = $cur === '' ? $line : $cur . "\n" . $line;
    }
    if ($cur !== '') $parts[] = $cur;
    if (count($parts) > 1) foreach ($parts as $i => $p) $parts[$i] = $p . "\n_(part " . ($i + 1) . ' of ' . count($parts) . ')_';
    $counts = array_map('count', $bySkill);
    return [$parts, count($rows), $counts];
}

/** Queue the summary as Google Chat message(s) (same queue and chat limits). */
function em_queue_summary(array $u, string $day): array {
    $settings = em_settings();
    if (empty($settings['gchat_enabled']) || empty($settings['gchat_webhook_enc'])) throw new RuntimeException('Google Chat is not set up (SMTP & Google Chat settings).');
    [$parts, $total] = em_build_summary($day, $settings);
    $db = em_db();
    $tpl = (int)$db->query('SELECT id FROM em_templates ORDER BY id LIMIT 1')->fetchColumn();
    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO em_campaigns(created_by,template_id,scheduled_at,post_to_chat) VALUES(?,?,UTC_TIMESTAMP(),1)')->execute([(int)$u['uid'], $tpl]);
        $camp = (int)$db->lastInsertId();
        $ins = $db->prepare("INSERT INTO em_messages(campaign_id,reqid,list_id,list_name,subject,body,recipients_json,recipient_count,available_at,chat_status,channel) VALUES(?,0,?,'Daily summary',?,?,'{}',0,UTC_TIMESTAMP() - INTERVAL 1 SECOND,'pending','chat')");
        foreach ($parts as $i => $p) $ins->execute([$camp, $i, em_mbcut('Google Chat summary ' . $day . (count($parts) > 1 ? ' (' . ($i + 1) . '/' . count($parts) . ')' : ''), 255), $p]);
        em_log('chat_summary_queued', 'Summary for ' . $day . ': ' . $total . ' requirement(s), ' . count($parts) . ' message(s)', null, $camp, (int)$u['uid']);
        $db->commit();
    } catch (Throwable $e) { $db->rollBack(); throw $e; }
    return ['campaign_id' => $camp, 'parts' => count($parts), 'total' => $total];
}
