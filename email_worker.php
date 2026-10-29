<?php
/** CLI-only cron entry point. Never run this file via a public URL. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require_once __DIR__ . '/email_worker_core.php';
echo em_process_one_due_message() . PHP_EOL;
