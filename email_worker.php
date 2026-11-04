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

// Open Tasks notifications (email + Google Chat), if that module is installed
if (is_file(__DIR__ . '/tasks_notify.php')) {
    try { require_once __DIR__ . '/tasks_notify.php'; echo tk_notify_run(20, 40) . PHP_EOL; }
    catch (Throwable $e) { echo 'Task notifications: ' . $e->getMessage() . PHP_EOL; }
}
