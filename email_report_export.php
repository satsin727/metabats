<?php
/* CSV download of the Email Distribution report: today / this week / this month / custom range. */
require_once __DIR__ . '/email_lib.php';
$u = em_user();
em_schema();
[$start, $end, $label, $fromLocal, $toLocal, $period] = em_period((string)($_GET['period'] ?? 'today'), (string)($_GET['from'] ?? ''), (string)($_GET['to'] ?? ''));
$rows = em_report_rows($u, $start, $end, true);
$names = ['today' => 'todays', 'week' => 'weekly', 'month' => 'monthly', 'thismonth' => 'this-month', 'custom' => 'custom'];
$name = $period === 'today' ? 'todays-shared-reqs-' . $fromLocal : ($names[$period] . '-shared-reqs-' . $fromLocal . '_to_' . $toLocal);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $name . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows special characters correctly
$safe = function ($v) { $v = (string)$v; return preg_match('/^[\s]*[=+@\-]/u', $v) ? "'" . $v : $v; };
fputcsv($out, ['Report', $label]);
fputcsv($out, ['Date Shared', 'Req ID', 'Job Title', 'Location', 'Client', 'Client Domain', 'Recruiter Email (remail)', 'End Client', 'Sent To Group', 'Shared By', 'Job Description']);
foreach ($rows as $r) {
    fputcsv($out, array_map($safe, [em_local($r['sent_at']), $r['reqid'], $r['job_title'], $r['rlocation'], $r['companyname'], $r['domain'], $r['remail'],
        $r['rend_client'], $r['list_name'], $r['shared_by'], em_job_description_text((string)$r['rdesc'])]));
}
fclose($out);
