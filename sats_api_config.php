<?php
declare(strict_types=1);

/*
 * Shared secret used only to authenticate SATS -> BATS requests.
 * Keep this value identical in SATS/config/bats_api.php.
 * Do not expose this file publicly.
 */
define('SATS_API_SHARED_SECRET', 'd2bd5c88ac395007518c160475f579aa66794676c2e9349aa08c7ba3b778d622');

/*
 * Reject requests older/newer than this many seconds.
 * Helps prevent captured requests from being replayed later.
 */
define('SATS_API_MAX_CLOCK_SKEW', 300);

/*
 * Fixed BATS client used for requirements coming from SATS.
 */
define('SATS_BATS_CLIENT_EMAIL', 'abc@sats.com');
