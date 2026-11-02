<?php
/** CLI-only cron entry point. Never run this file via a public URL. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require_once __DIR__ . '/email_worker_core.php';
// One email per run (Gmail limits), then any due Google Chat messages (chat limits).
echo 'Email: ' . em_process_one_due_message('email') . PHP_EOL;
echo 'Google Chat: ' . em_process_chat_burst(10, 40) . PHP_EOL;
