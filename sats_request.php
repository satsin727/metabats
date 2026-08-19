<?php
declare(strict_types=1);

/*
 * BATS endpoint for SATS application submissions.
 *
 * Flow:
 *   SATS application marked applied
 *      -> POST JSON here
 *      -> resolve BATS user by submitted_by_email
 *      -> resolve BATS client abc@sats.com
 *      -> create/update BATS req + jd
 *      -> create app_data row for the consultant
 *
 * This endpoint intentionally does NOT use a logged-in BATS session.
 * Authentication is HMAC based.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sats_api_config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function satsJsonResponse(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function satsRequireString(array $data, string $key, int $maxLength = 5000): string
{
    $value = trim((string)($data[$key] ?? ''));
    if ($value === '') {
        satsJsonResponse(422, [
            'success' => false,
            'error' => "Missing required field: {$key}"
        ]);
    }

    if (strlen($value) > $maxLength) {
        satsJsonResponse(422, [
            'success' => false,
            'error' => "Field too long: {$key}"
        ]);
    }

    return $value;
}

function satsRequirePositiveInt(array $data, string $key): int
{
    $value = filter_var($data[$key] ?? null, FILTER_VALIDATE_INT);

    if ($value === false || (int)$value <= 0) {
        satsJsonResponse(422, [
            'success' => false,
            'error' => "Invalid required field: {$key}"
        ]);
    }

    return (int)$value;
}


/*
 * Optional SATS application event timestamp.
 *
 * Normal real-time requests should send this value. Recovery requests MUST send
 * the historical SATS apply timestamp so BATS does not stamp repaired records
 * with the recovery-run date.
 *
 * Format: YYYY-MM-DD HH:MM:SS
 */
function satsOptionalDateTime(array $data, string $key): ?string
{
    $value = trim((string)($data[$key] ?? ''));

    if ($value === '') {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);

    if ($dt === false || $dt->format('Y-m-d H:i:s') !== $value) {
        satsJsonResponse(422, [
            'success' => false,
            'error' => "Invalid datetime field: {$key}. Expected YYYY-MM-DD HH:MM:SS"
        ]);
    }

    return $value;
}

function satsNormalizeEmployment(string $employmentType): array
{
    $normalized = strtolower(trim($employmentType));
    $compact = preg_replace('/[^a-z]+/', '', $normalized);

    if (
        $compact === 'fulltime' ||
        $compact === 'fulltimeemployee' ||
        str_contains($compact, 'fulltime')
    ) {
        return [
            'jobtype' => 4,
            'emp_type' => 'FTE'
        ];
    }

    if (str_contains($compact, 'contract')) {
        return [
            'jobtype' => 3,
            'emp_type' => 'W2'
        ];
    }

    satsJsonResponse(422, [
        'success' => false,
        'error' => 'Unsupported employment type. SATS must send Full-Time or Contract.',
        'employment_type_received' => $employmentType
    ]);
}

function satsBuildDescription(
    string $jobTitle,
    string $location,
    string $clientName,
    string $directJobLink
): string {
    $e = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $url = $directJobLink !== ''
        ? '<a href="' . $e($directJobLink) . '" target="_blank" rel="noopener noreferrer">' . $e($directJobLink) . '</a>'
        : 'N/A';

    return
        '<p><strong>Job Title:</strong> ' . $e($jobTitle) . '</p>' .
        '<p><strong>Location:</strong> ' . $e($location) . '</p>' .
        '<p><strong>Client Name:</strong> ' . $e($clientName) . '</p>' .
        '<p><strong>URL:</strong> ' . $url . '</p>';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    satsJsonResponse(405, [
        'success' => false,
        'error' => 'POST required'
    ]);
}

$rawBody = file_get_contents('php://input');

if ($rawBody === false || trim($rawBody) === '') {
    satsJsonResponse(400, [
        'success' => false,
        'error' => 'Empty request body'
    ]);
}

/*
 * HMAC authentication.
 * Signature = HMAC_SHA256(timestamp + "." + raw_json_body, shared_secret)
 */
$timestampHeader = trim((string)($_SERVER['HTTP_X_SATS_TIMESTAMP'] ?? ''));
$signatureHeader = strtolower(trim((string)($_SERVER['HTTP_X_SATS_SIGNATURE'] ?? '')));

if ($timestampHeader === '' || !ctype_digit($timestampHeader) || $signatureHeader === '') {
    satsJsonResponse(401, [
        'success' => false,
        'error' => 'Missing SATS authentication headers'
    ]);
}

$timestamp = (int)$timestampHeader;

if (abs(time() - $timestamp) > SATS_API_MAX_CLOCK_SKEW) {
    satsJsonResponse(401, [
        'success' => false,
        'error' => 'Expired SATS request'
    ]);
}

$expectedSignature = hash_hmac(
    'sha256',
    $timestampHeader . '.' . $rawBody,
    SATS_API_SHARED_SECRET
);

if (!hash_equals($expectedSignature, $signatureHeader)) {
    satsJsonResponse(401, [
        'success' => false,
        'error' => 'Invalid SATS signature'
    ]);
}

$data = json_decode($rawBody, true);

if (!is_array($data)) {
    satsJsonResponse(400, [
        'success' => false,
        'error' => 'Invalid JSON request'
    ]);
}

$satsJobId = satsRequirePositiveInt($data, 'sats_job_id');
$satsApplicationId = satsRequirePositiveInt($data, 'sats_application_id');
$consultantId = satsRequirePositiveInt($data, 'consultant_id');
$payloadSkillId = satsRequirePositiveInt($data, 'skill_id');

$submittedByEmail = strtolower(satsRequireString($data, 'submitted_by_email', 150));
$jobTitle = satsRequireString($data, 'job_title', 255);
$location = satsRequireString($data, 'location', 255);
$employmentType = satsRequireString($data, 'employment_type', 100);
$company = satsRequireString($data, 'company', 255);
$directJobLink = trim((string)($data['direct_job_link'] ?? ''));

/*
 * Historical event time supplied by SATS.
 *
 * This is optional for backward compatibility with older SATS callers.
 * When absent, the BATS database's current NOW() value is used.
 */
$applicationDateTime = satsOptionalDateTime($data, 'application_datetime');

if (!filter_var($submittedByEmail, FILTER_VALIDATE_EMAIL)) {
    satsJsonResponse(422, [
        'success' => false,
        'error' => 'Invalid submitted_by_email'
    ]);
}

$employment = satsNormalizeEmployment($employmentType);
$jobType = (int)$employment['jobtype'];
$empType = (string)$employment['emp_type'];

/*
 * User requested SATS-originated requirements to always have Duration = FTE.
 */
$duration = 'FTE';
$reqSource = 18;   // SATS
$tierType = 1;     // Tier 1
$nationality = 1;  // American

try {
    $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    /*
     * Older real-time callers may not yet send application_datetime.
     * Use the BATS database clock only for those callers.
     */
    if ($applicationDateTime === null) {
        $applicationDateTime = (string)$conn->query('SELECT NOW()')->fetchColumn();
    }

    $conn->beginTransaction();

    /*
     * Resolve BATS user by the SATS submitter's email.
     * BATS users.email is the bridge key.
     */
    $userStmt = $conn->prepare("
        SELECT uid, email, status
        FROM users
        WHERE LOWER(email) = LOWER(:email)
        LIMIT 1
    ");
    $userStmt->execute([':email' => $submittedByEmail]);
    $batsUser = $userStmt->fetch();

    if (!$batsUser) {
        throw new RuntimeException(
            'No BATS user found for SATS submitter email: ' . $submittedByEmail
        );
    }

    if ((int)$batsUser['status'] !== 1) {
        throw new RuntimeException(
            'BATS user is disabled/inactive for email: ' . $submittedByEmail
        );
    }

    $batsUid = (int)$batsUser['uid'];

    /*
     * Resolve the fixed SATS client in BATS.
     */
    $clientStmt = $conn->prepare("
        SELECT cid
        FROM clients
        WHERE LOWER(remail) = LOWER(:remail)
        ORDER BY status DESC, cid ASC
        LIMIT 1
    ");
    $clientStmt->execute([':remail' => SATS_BATS_CLIENT_EMAIL]);
    $client = $clientStmt->fetch();

    if (!$client) {
        throw new RuntimeException(
            'BATS client not found for ' . SATS_BATS_CLIENT_EMAIL
        );
    }

    $clientId = (int)$client['cid'];

    /*
     * Consultant IDs are intentionally identical in BATS and SATS.
     * Use BATS consultant.skill as the authoritative skill mapping.
     */
    $consultantStmt = $conn->prepare("
        SELECT cid, skill, status
        FROM consultants
        WHERE cid = :cid
        LIMIT 1
    ");
    $consultantStmt->execute([':cid' => $consultantId]);
    $consultant = $consultantStmt->fetch();

    if (!$consultant) {
        throw new RuntimeException(
            'Consultant ID ' . $consultantId . ' does not exist in BATS'
        );
    }

    $skillId = (int)$consultant['skill'];

    if ($skillId <= 0) {
        throw new RuntimeException(
            'Consultant ID ' . $consultantId . ' has no valid BATS skill mapping'
        );
    }

    /*
     * Detect a sync mismatch early rather than silently posting to the wrong skill.
     */
    if ($payloadSkillId !== $skillId) {
        throw new RuntimeException(
            'Skill mismatch for consultant ' . $consultantId .
            '. SATS sent ' . $payloadSkillId .
            ' but BATS consultant is mapped to ' . $skillId
        );
    }

    $skillStmt = $conn->prepare("
        SELECT sid
        FROM skill
        WHERE sid = :sid
        LIMIT 1
    ");
    $skillStmt->execute([':sid' => $skillId]);

    if (!$skillStmt->fetch()) {
        throw new RuntimeException(
            'Skill ID ' . $skillId . ' does not exist in BATS skill table'
        );
    }

    /*
     * One BATS requirement per SATS Job + submitting BATS user.
     *
     * This preserves the BATS req.uid (SM) correctly while preventing the
     * same manager from creating duplicate requirements for multiple
     * consultants on the same SATS job.
     *
     * Example: SATS job 6303, BATS uid 17 -> S6303U17
     */
    $externalReqKey = 'S' . $satsJobId . 'U' . $batsUid;

    if (strlen($externalReqKey) > 25) {
        $externalReqKey = 'S' . substr(hash('sha256', $externalReqKey), 0, 24);
    }

    $reqFindStmt = $conn->prepare("
        SELECT reqid
        FROM req
        WHERE ureq_id = :ureq_id
          AND uid = :uid
        ORDER BY reqid ASC
        LIMIT 1
        FOR UPDATE
    ");
    $reqFindStmt->execute([
        ':ureq_id' => $externalReqKey,
        ':uid' => $batsUid
    ]);

    $existingReq = $reqFindStmt->fetch();
    $requirementCreated = false;

    if ($existingReq) {
        $reqId = (int)$existingReq['reqid'];

        /*
         * Keep the BATS requirement current with the latest SATS job data.
         */
        $reqUpdateStmt = $conn->prepare("
            UPDATE req
            SET
                cid = :cid,
                emp_type = :emp_type,
                jobtype = :jobtype,
                rlocation = :rlocation,
                rduration = :rduration,
                rrate = NULL,
                rend_client = :rend_client,
                skillid = :skillid,
                req_source = :req_source,
                ttype = :ttype,
                nationality = :nationality
            WHERE reqid = :reqid
        ");

        $reqUpdateStmt->execute([
            ':cid' => $clientId,
            ':emp_type' => $empType,
            ':jobtype' => $jobType,
            ':rlocation' => substr($location, 0, 60),
            ':rduration' => $duration,
            ':rend_client' => substr($company, 0, 30),
            ':skillid' => $skillId,
            ':req_source' => $reqSource,
            ':ttype' => $tierType,
            ':nationality' => $nationality,
            ':reqid' => $reqId
        ]);
    } else {
        $reqInsertStmt = $conn->prepare("
            INSERT INTO req
            (
                ureq_id,
                uid,
                cid,
                emp_type,
                jobtype,
                rlocation,
                rduration,
                rrate,
                rend_client,
                skillid,
                req_source,
                ttype,
                nationality,
                datetime
            )
            VALUES
            (
                :ureq_id,
                :uid,
                :cid,
                :emp_type,
                :jobtype,
                :rlocation,
                :rduration,
                NULL,
                :rend_client,
                :skillid,
                :req_source,
                :ttype,
                :nationality,
                :application_datetime
            )
        ");

        $reqInsertStmt->execute([
            ':ureq_id' => $externalReqKey,
            ':uid' => $batsUid,
            ':cid' => $clientId,
            ':emp_type' => $empType,
            ':jobtype' => $jobType,
            ':rlocation' => substr($location, 0, 60),
            ':rduration' => $duration,
            ':rend_client' => substr($company, 0, 30),
            ':skillid' => $skillId,
            ':req_source' => $reqSource,
            ':ttype' => $tierType,
            ':nationality' => $nationality,
            ':application_datetime' => $applicationDateTime
        ]);

        $reqId = (int)$conn->lastInsertId();
        $requirementCreated = true;
    }

    /*
     * Create/update BATS JD.
     */
    $jobDescription = satsBuildDescription(
        $jobTitle,
        $location,
        $company,
        $directJobLink
    );

    $jdFindStmt = $conn->prepare("
        SELECT jd_id
        FROM jd
        WHERE reqid = :reqid
        ORDER BY jd_id ASC
        LIMIT 1
        FOR UPDATE
    ");
    $jdFindStmt->execute([':reqid' => $reqId]);
    $jd = $jdFindStmt->fetch();

    if ($jd) {
        $jdUpdateStmt = $conn->prepare("
            UPDATE jd
            SET rdesc = :rdesc
            WHERE jd_id = :jd_id
        ");
        $jdUpdateStmt->execute([
            ':rdesc' => $jobDescription,
            ':jd_id' => (int)$jd['jd_id']
        ]);
    } else {
        $jdInsertStmt = $conn->prepare("
            INSERT INTO jd (reqid, rdesc)
            VALUES (:reqid, :rdesc)
        ");
        $jdInsertStmt->execute([
            ':reqid' => $reqId,
            ':rdesc' => $jobDescription
        ]);
    }

    /*
     * Idempotent application creation.
     * A retry must NOT reset an already-progressed BATS application.
     */
    $appFindStmt = $conn->prepare("
        SELECT app_id
        FROM app_data
        WHERE reqid = :reqid
          AND uid = :uid
          AND consultant_id = :consultant_id
        ORDER BY app_id ASC
        LIMIT 1
        FOR UPDATE
    ");
    $appFindStmt->execute([
        ':reqid' => $reqId,
        ':uid' => $batsUid,
        ':consultant_id' => $consultantId
    ]);

    $existingApp = $appFindStmt->fetch();
    $applicationCreated = false;

    if ($existingApp) {
        $appId = (int)$existingApp['app_id'];
    } else {
        $appInsertStmt = $conn->prepare("
            INSERT INTO app_data
            (
                uid,
                reqid,
                client_id,
                consultant_id,
                rcdone,
                subto,
                rateperhour,
                rcdate,
                t1ip_name,
                subdone,
                t1ip_id,
                hasinterview,
                ars_status,
                status,
                followup,
                appdate,
                subdate
            )
            VALUES
            (
                :uid,
                :reqid,
                :client_id,
                :consultant_id,
                1,
                4,
                NULL,
                :application_datetime,
                NULL,
                1,
                NULL,
                0,
                10,
                1,
                1,
                :application_datetime,
                :application_datetime
            )
        ");

        $appInsertStmt->execute([
            ':uid' => $batsUid,
            ':reqid' => $reqId,
            ':client_id' => $clientId,
            ':consultant_id' => $consultantId,
            ':application_datetime' => $applicationDateTime
        ]);

        $appId = (int)$conn->lastInsertId();
        $applicationCreated = true;
    }

    $conn->commit();

    satsJsonResponse(200, [
        'success' => true,
        'sats_job_id' => $satsJobId,
        'sats_application_id' => $satsApplicationId,
        'bats_reqid' => $reqId,
        'bats_app_id' => $appId,
        'bats_uid' => $batsUid,
        'client_id' => $clientId,
        'consultant_id' => $consultantId,
        'skill_id' => $skillId,
        'application_datetime' => $applicationDateTime,
        'requirement_created' => $requirementCreated,
        'application_created' => $applicationCreated,
        'duplicate_application' => !$applicationCreated
    ]);

} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        '[SATS->BATS] ' . date('Y-m-d H:i:s') . ' ' .
        $e->getMessage()
    );

    satsJsonResponse(500, [
        'success' => false,
        'error' => $e->getMessage()
    ]);
}