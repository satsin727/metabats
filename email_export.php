<?php
/* CSV export of Email Distribution > History (same filters as the History page). */
require_once __DIR__.'/email_lib.php';
$u = em_user();
$db = em_db();
em_schema();
$w = ['1=1'];
$p = [];
$status = (string)($_GET['status'] ?? '');
if (in_array($status, ['scheduled', 'processing', 'sent', 'failed', 'cancelled'], true)) {
    $w[] = 'm.status=?';
    $p[] = $status;
}
$lid = (int)($_GET['list_id'] ?? 0);
if ($lid) {
    $w[] = 'm.list_id=?';
    $p[] = $lid;
}
$req = (int)($_GET['reqid'] ?? 0);
if ($req) {
    $w[] = 'm.reqid=?';
    $p[] = $req;
}
// Dates are local days (settings timezone), the same as on the History page.
$from = (string)($_GET['from'] ?? '');
$to = (string)($_GET['to'] ?? '');
$okFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from);
$okTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to);
if ($okFrom || $okTo) {
    [$start, $end] = em_period('custom', $okFrom ? $from : '2000-01-01', $okTo ? $to : (new DateTimeImmutable('now', em_tz()))->format('Y-m-d'));
    $w[] = 'm.created_at>=? AND m.created_at<?';
    $p[] = $start;
    $p[] = $end;
}
$sql = "SELECT m.id,m.campaign_id,m.reqid,r.job_title,r.rlocation,r.rend_client,c.companyname,
               COALESCE(NULLIF(c.domain,''), SUBSTRING_INDEX(c.remail,'@',-1)) AS domain,
               m.list_name,m.subject,m.recipients_json,m.status,m.attempts,m.available_at,m.sent_at,m.last_error,m.created_at
        FROM em_messages m LEFT JOIN req r ON r.reqid=m.reqid LEFT JOIN clients c ON c.cid=r.cid
        WHERE ".implode(' AND ', $w).' ORDER BY m.id DESC LIMIT 20000';
$s = $db->prepare($sql);
$s->execute($p);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="email-sharing-history-'.gmdate('Ymd-His').'.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
fputcsv($out, ['Message ID', 'Campaign ID', 'Requirement ID', 'Job Title', 'Location', 'Client', 'Client Domain', 'End Client', 'Recipient List', 'Subject', 'To', 'CC', 'BCC', 'Status', 'Attempts', 'Scheduled', 'Sent', 'Error', 'Queued']);
function em_csv_safe($v): string {
    $v = (string)$v;
    return preg_match('/^[\s]*[=+@\-]/u', $v) ? "'".$v: $v;
}
while ($r = $s->fetch()) {
    $rc = json_decode($r['recipients_json'], true) ?: [];
    $line = [$r['id'], $r['campaign_id'], $r['reqid'], $r['job_title'], $r['rlocation'], $r['companyname'], $r['domain'], $r['rend_client'], $r['list_name'], $r['subject'],
        implode('; ', $rc['to'] ?? []), implode('; ', $rc['cc'] ?? []), implode('; ', $rc['bcc'] ?? []), $r['status'], $r['attempts'],
        em_local($r['available_at']), em_local($r['sent_at']), $r['last_error'], em_local($r['created_at'])];
    fputcsv($out, array_map('em_csv_safe', $line));
}
fclose($out);
