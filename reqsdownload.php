<?php
require("config.php");


if (!isset($_SESSION['id']) || !$_SESSION['id']) {
    header("Location: admin.php");
    exit;
}

$sessid = (int) $_SESSION['id'];

try {
    $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));

    $ins = $conn->prepare("SELECT uid, sess, level FROM users WHERE uid = :uid LIMIT 1");
    $ins->execute(array(':uid' => $sessid));
    $dta = $ins->fetch();

    if (!$dta || !isset($_SESSION['username']) || $dta['sess'] !== $_SESSION['username']) {
        throw new RuntimeException('Not Authorised to download requirements. Please log in again.');
    }

    $download = isset($_GET['download']) ? $_GET['download'] : '';
    if ($download !== 'allreqs') {
        header("Location: admin.php?action=showallreqs");
        exit;
    }

    // Date used by the existing screen/download links.
    $curdate = date('Y-m-d');
    if (!empty($_GET['date'])) {
        $candidateDate = DateTime::createFromFormat('Y-m-d', $_GET['date']);
        if ($candidateDate && $candidateDate->format('Y-m-d') === $_GET['date']) {
            $curdate = $_GET['date'];
        }
    }

    $showWeekly  = isset($_GET['showweekly'])  && (int)$_GET['showweekly']  === 1;
    $showMonthly = isset($_GET['showmonthly']) && (int)$_GET['showmonthly'] === 1;
    $showYearly  = isset($_GET['showyearly'])  && (int)$_GET['showyearly']  === 1;
    $showUnique  = isset($_GET['showunique'])  && (int)$_GET['showunique']  === 1;
    $sid         = (isset($_GET['sid']) && $_GET['sid'] !== '') ? (int)$_GET['sid'] : null;

    // Build an index-friendly date range instead of DATE()/WEEK()/MONTH() on the DB column.
    $anchor = new DateTime($curdate . ' 00:00:00');

    if ($showWeekly) {
        // Existing SQL used WEEK(date) + YEAR(date). MySQL's default WEEK mode
        // is Sunday-based, so preserve that behavior and clamp at year boundaries.
        $periodStart = clone $anchor;
        $daysSinceSunday = (int)$periodStart->format('w');
        if ($daysSinceSunday > 0) {
            $periodStart->modify('-' . $daysSinceSunday . ' days');
        }
        $periodEnd = clone $periodStart;
        $periodEnd->modify('+7 days');

        $yearStart = new DateTime($anchor->format('Y-01-01') . ' 00:00:00');
        $nextYearStart = new DateTime(((int)$anchor->format('Y') + 1) . '-01-01 00:00:00');
        if ($periodStart < $yearStart) {
            $periodStart = $yearStart;
        }
        if ($periodEnd > $nextYearStart) {
            $periodEnd = $nextYearStart;
        }
    } elseif ($showMonthly) {
        $periodStart = new DateTime($anchor->format('Y-m-01') . ' 00:00:00');
        $periodEnd = clone $periodStart;
        $periodEnd->modify('+1 month');
    } elseif ($showYearly) {
        $periodStart = new DateTime($anchor->format('Y-01-01') . ' 00:00:00');
        $periodEnd = clone $periodStart;
        $periodEnd->modify('+1 year');
    } else {
        $periodStart = clone $anchor;
        $periodEnd = clone $periodStart;
        $periodEnd->modify('+1 day');
    }

    $startSql = $periodStart->format('Y-m-d H:i:s');
    $endSql   = $periodEnd->format('Y-m-d H:i:s');

    // One row per req normally. For "Unique" downloads, retain one deterministic
    // representative row per ureq_id. Blank/default PP values stay independent.
    if ($showUnique) {
        $uniqueKey = "CASE
            WHEN rx.ureq_id IS NULL OR TRIM(rx.ureq_id) = '' OR rx.ureq_id = 'PP'
                THEN CONCAT('__REQ__', rx.reqid)
            ELSE rx.ureq_id
        END";

        $innerWhere = "rx.status = 1 AND rx.datetime >= :start_date AND rx.datetime < :end_date";
        if ($sid !== null && $sid > 0) {
            $innerWhere .= " AND rx.skillid = :sid";
        }

        $query = "
            SELECT
                r.*,
                s.skillname,
                c.remail AS bp_email,
                c.rphone AS bp_phone,
                u.name AS sm_name,
                (
                    SELECT j.rdesc
                    FROM jd j
                    WHERE j.reqid = r.reqid
                    ORDER BY j.jd_id DESC
                    LIMIT 1
                ) AS rdesc
            FROM req r
            INNER JOIN (
                SELECT MIN(rx.reqid) AS min_reqid
                FROM req rx
                WHERE $innerWhere
                GROUP BY $uniqueKey
            ) uq ON uq.min_reqid = r.reqid
            LEFT JOIN skill s ON s.sid = r.skillid
            LEFT JOIN clients c ON c.cid = r.cid
            LEFT JOIN users u ON u.uid = r.uid
            ORDER BY r.datetime ASC, r.reqid ASC
        ";
    } else {
        $where = "r.status = 1 AND r.datetime >= :start_date AND r.datetime < :end_date";
        if ($sid !== null && $sid > 0) {
            $where .= " AND r.skillid = :sid";
        }

        $query = "
            SELECT
                r.*,
                s.skillname,
                c.remail AS bp_email,
                c.rphone AS bp_phone,
                u.name AS sm_name,
                (
                    SELECT j.rdesc
                    FROM jd j
                    WHERE j.reqid = r.reqid
                    ORDER BY j.jd_id DESC
                    LIMIT 1
                ) AS rdesc
            FROM req r
            LEFT JOIN skill s ON s.sid = r.skillid
            LEFT JOIN clients c ON c.cid = r.cid
            LEFT JOIN users u ON u.uid = r.uid
            WHERE $where
            ORDER BY r.datetime ASC, r.reqid ASC
        ";
    }

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':start_date', $startSql, PDO::PARAM_STR);
    $stmt->bindValue(':end_date', $endSql, PDO::PARAM_STR);
    if ($sid !== null && $sid > 0) {
        $stmt->bindValue(':sid', $sid, PDO::PARAM_INT);
    }
    $stmt->execute();
    $data = $stmt->fetchAll();

    $reqSources = array(
        1  => 'Inbox',
        2  => 'Posting',
        3  => 'Cold Calls',
        4  => 'AMC',
        5  => 'Prohires',
        6  => 'Google Groups',
        7  => 'LinkedIn',
        8  => 'Job Portal - Dice',
        9  => 'Job Portal - Techfetch',
        10 => 'Job Portal - SimplyHired',
        11 => 'Job Portal - Careerbuilder',
        12 => 'Job Portal - Ziprecruiter',
        13 => 'Job Portal - Monster',
        14 => 'Job Portal - other',
        15 => 'Company Websites',
        16 => 'I-Labor',
        17 => 'Other'
    );

    function reqStatusText($status)
    {
        switch ((int)$status) {
            case 1: return 'Rejected';
            case 2: return 'Closed';
            case 3: return 'Not Connected';
            case 4: return 'Open';
            case 5: return 'In process';
            case 6: return 'No Number';
            case 7: return 'No status';
            default: return 'No status';
        }
    }

    function reqStatusPriority($status)
    {
        // Matches the precedence used by the old reqsdownload.php.
        switch ((int)$status) {
            case 6: return 6; // No Number
            case 5: return 5; // In process
            case 4: return 4; // Open
            case 2: return 3; // Closed
            case 1: return 2; // Rejected
            case 3: return 1; // Not Connected
            default: return 0;
        }
    }

    function uniqueNonEmpty($values)
    {
        $out = array();
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value !== '' && !in_array($value, $out, true)) {
                $out[] = $value;
            }
        }
        return $out;
    }

    function cleanJobDescription($html)
    {
        $text = strip_tags((string)$html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xC2\xA0", ' ', $text);
        return trim($text);
    }

    function placeholders($count)
    {
        return implode(',', array_fill(0, $count, '?'));
    }

    // Clear any buffered output from config/includes so CSV headers remain valid.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $filename = 'allreqs_' . $sessid . '-' . date('m-d-Y') . '.csv';
    header('Content-Description: File Transfer');
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    $fp = fopen('php://output', 'w');
    if ($fp === false) {
        throw new RuntimeException('Unable to open CSV output stream.');
    }

    // UTF-8 BOM helps Excel display names/descriptions correctly.
    fwrite($fp, "\xEF\xBB\xBF");

    fputcsv($fp, array(
        'S.no', 'Date', 'Req_ID', 'Req Source', 'Skill', 'Location',
        'Job Description', 'App Data', 'SM', 'BP Email', 'BP contact',
        'IP/Tier1', 'End Client', 'Utilization Status', 'Total RC',
        'Qualified', 'Req Status', 'Comment'
    ));

    $serial = 0;

    foreach ($data as $row) {
        $serial++;
        $reqid = (int)$row['reqid'];
        $rowTime = strtotime($row['datetime']);
        $displayDate = date('m/d/y', $rowTime);
        $curDatePart = date('dmy', $rowTime);
        $curWeek = date('W', $rowTime);
        $displayReqId = 'W' . $curWeek . $curDatePart . '-' . $row['ureq_id'];

        // Determine which req rows belong to this CSV record.
        $relatedReqs = array();

        if ($showUnique && trim((string)$row['ureq_id']) !== '' && $row['ureq_id'] !== 'PP') {
            $relSql = "
                SELECT
                    r.reqid, r.uid, r.cid, r.reqstatus, r.qualified, r.rend_client,
                    u.name AS sm_name, c.remail AS bp_email, c.rphone AS bp_phone
                FROM req r
                LEFT JOIN users u ON u.uid = r.uid
                LEFT JOIN clients c ON c.cid = r.cid
                WHERE r.status = 1
                  AND r.ureq_id = :ureq_id
                  AND r.datetime >= :start_date
                  AND r.datetime < :end_date
            ";
            if ($sid !== null && $sid > 0) {
                $relSql .= " AND r.skillid = :sid";
            }
            $relSql .= " ORDER BY r.datetime ASC, r.reqid ASC";

            $relStmt = $conn->prepare($relSql);
            $relStmt->bindValue(':ureq_id', $row['ureq_id'], PDO::PARAM_STR);
            $relStmt->bindValue(':start_date', $startSql, PDO::PARAM_STR);
            $relStmt->bindValue(':end_date', $endSql, PDO::PARAM_STR);
            if ($sid !== null && $sid > 0) {
                $relStmt->bindValue(':sid', $sid, PDO::PARAM_INT);
            }
            $relStmt->execute();
            $relatedReqs = $relStmt->fetchAll();
        } else {
            $relatedReqs[] = array(
                'reqid'       => $reqid,
                'uid'         => $row['uid'],
                'cid'         => $row['cid'],
                'reqstatus'   => $row['reqstatus'],
                'qualified'   => $row['qualified'],
                'rend_client' => $row['rend_client'],
                'sm_name'     => isset($row['sm_name']) ? $row['sm_name'] : '',
                'bp_email'    => isset($row['bp_email']) ? $row['bp_email'] : '',
                'bp_phone'    => isset($row['bp_phone']) ? $row['bp_phone'] : ''
            );
        }

        if (!$relatedReqs) {
            // Safety fallback; should not happen because the representative req exists.
            $relatedReqs[] = array(
                'reqid'       => $reqid,
                'uid'         => $row['uid'],
                'cid'         => $row['cid'],
                'reqstatus'   => $row['reqstatus'],
                'qualified'   => $row['qualified'],
                'rend_client' => $row['rend_client'],
                'sm_name'     => isset($row['sm_name']) ? $row['sm_name'] : '',
                'bp_email'    => isset($row['bp_email']) ? $row['bp_email'] : '',
                'bp_phone'    => isset($row['bp_phone']) ? $row['bp_phone'] : ''
            );
        }

        $reqIds = array();
        $reqById = array();
        $smNames = array();
        $bpEmails = array();
        $bpPhones = array();
        $endClients = array();
        $qualified = false;
        $bestStatus = 7;
        $bestStatusPriority = -1;

        foreach ($relatedReqs as $rel) {
            $rid = (int)$rel['reqid'];
            $reqIds[] = $rid;
            $reqById[$rid] = $rel;
            $smNames[] = isset($rel['sm_name']) ? $rel['sm_name'] : '';
            $bpEmails[] = isset($rel['bp_email']) ? $rel['bp_email'] : '';
            $bpPhones[] = isset($rel['bp_phone']) ? $rel['bp_phone'] : '';
            $endClients[] = isset($rel['rend_client']) ? $rel['rend_client'] : '';

            if ((int)$rel['qualified'] === 1) {
                $qualified = true;
            }

            $priority = reqStatusPriority($rel['reqstatus']);
            if ($priority > $bestStatusPriority) {
                $bestStatusPriority = $priority;
                $bestStatus = (int)$rel['reqstatus'];
            }
        }

        $reqIds = array_values(array_unique(array_map('intval', $reqIds)));

        // Applications for this req or unique req group.
        $applications = array();
        if (count($reqIds) > 0) {
            $appSql = "
                SELECT
                    a.app_id, a.uid, a.reqid, a.consultant_id,
                    a.rcdone, a.subdone, a.t1ip_name,
                    u.name AS app_user_name,
                    c.cfname, c.clname
                FROM app_data a
                LEFT JOIN users u ON u.uid = a.uid
                LEFT JOIN consultants c ON c.cid = a.consultant_id
                WHERE a.status = 1
                  AND a.reqid IN (" . placeholders(count($reqIds)) . ")
                ORDER BY a.uid ASC, a.app_id ASC
            ";
            $appStmt = $conn->prepare($appSql);
            $appStmt->execute($reqIds);
            $applications = $appStmt->fetchAll();
        }

        $appLines = array();
        $appIds = array();
        $t1ipNames = array();
        $totalRc = 0;

        foreach ($applications as $app) {
            $appIds[] = (int)$app['app_id'];
            if ((int)$app['rcdone'] === 1) {
                $totalRc++;
            }
            if (!empty($app['t1ip_name'])) {
                $t1ipNames[] = $app['t1ip_name'];
            }

            $appUser = trim((string)$app['app_user_name']);
            if ($appUser === '') {
                $rid = (int)$app['reqid'];
                $appUser = isset($reqById[$rid]['sm_name']) ? $reqById[$rid]['sm_name'] : '';
            }

            $consultantName = trim(
                trim((string)$app['cfname']) . ' ' . trim((string)$app['clname'])
            );

            $rid = (int)$app['reqid'];
            $throughEmail = isset($reqById[$rid]['bp_email']) ? trim((string)$reqById[$rid]['bp_email']) : '';

            $appLines[] = trim($appUser) . ' has applied ' . $consultantName .
                ' through ' . $throughEmail . ' and did ' . (int)$app['rcdone'] .
                ' RC, ' . (int)$app['subdone'] . ' Sub.';
        }

        // Requirement comments + application/RC/submission/interview comments.
        $commentRows = array();
        $seenCommentIds = array();

        if (count($reqIds) > 0) {
            $comReqSql = "
                SELECT cm.com_id, cm.comment, cm.datetime, u.name AS user_name
                FROM comments cm
                LEFT JOIN users u ON u.uid = cm.uid
                WHERE cm.reqcom_id = 1
                  AND cm.com_postid IN (" . placeholders(count($reqIds)) . ")
                ORDER BY cm.datetime ASC, cm.com_id ASC
            ";
            $comReqStmt = $conn->prepare($comReqSql);
            $comReqStmt->execute($reqIds);
            foreach ($comReqStmt->fetchAll() as $comment) {
                $cid = (int)$comment['com_id'];
                if (!isset($seenCommentIds[$cid])) {
                    $seenCommentIds[$cid] = true;
                    $commentRows[] = $comment;
                }
            }
        }

        if (count($appIds) > 0) {
            $comAppSql = "
                SELECT cm.com_id, cm.comment, cm.datetime, u.name AS user_name
                FROM comments cm
                LEFT JOIN users u ON u.uid = cm.uid
                WHERE cm.com_postid IN (" . placeholders(count($appIds)) . ")
                  AND (cm.appcom_id = 1 OR cm.rccom_id = 1 OR cm.subcom_id = 1 OR cm.ecicom_id = 1)
                ORDER BY cm.datetime ASC, cm.com_id ASC
            ";
            $comAppStmt = $conn->prepare($comAppSql);
            $comAppStmt->execute($appIds);
            foreach ($comAppStmt->fetchAll() as $comment) {
                $cid = (int)$comment['com_id'];
                if (!isset($seenCommentIds[$cid])) {
                    $seenCommentIds[$cid] = true;
                    $commentRows[] = $comment;
                }
            }
        }

        usort($commentRows, function ($a, $b) {
            if ($a['datetime'] === $b['datetime']) {
                return ((int)$a['com_id']) <=> ((int)$b['com_id']);
            }
            return strcmp($a['datetime'], $b['datetime']);
        });

        $commentLines = array();
        foreach ($commentRows as $comment) {
            $commentLines[] = trim((string)$comment['user_name']) . ': ' .
                trim((string)$comment['comment']) . ' at ' . $comment['datetime'];
        }

        $sourceId = isset($row['req_source']) ? (int)$row['req_source'] : 17;
        $reqSource = isset($reqSources[$sourceId]) ? $reqSources[$sourceId] : 'Other';

        $smNames = uniqueNonEmpty($smNames);
        $bpEmails = uniqueNonEmpty($bpEmails);
        $bpPhones = uniqueNonEmpty($bpPhones);
        $endClients = uniqueNonEmpty($endClients);
        $t1ipNames = uniqueNonEmpty($t1ipNames);

        $lineData = array(
            $serial,
            $displayDate,
            $displayReqId,
            $reqSource,
            isset($row['skillname']) ? $row['skillname'] : '',
            isset($row['rlocation']) ? $row['rlocation'] : '',
            cleanJobDescription(isset($row['rdesc']) ? $row['rdesc'] : ''),
            implode("\n", $appLines),
            implode("\n", $smNames),
            implode("\n", $bpEmails),
            implode("\n", $bpPhones),
            implode("\n", $t1ipNames),
            implode("\n", $endClients),
            $totalRc > 0 ? 'Utilized' : 'Unutilized',
            $totalRc,
            $qualified ? 'Qualified' : 'Not Qualified',
            reqStatusText($bestStatus),
            implode("\n", $commentLines)
        );

        fputcsv($fp, $lineData);
    }

    fclose($fp);
    exit;

} catch (Throwable $e) {
    // Do not expose SQL/credentials in the browser; put details in PHP error log.
    error_log('reqsdownload.php: ' . $e->getMessage());

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }

    echo "Unable to download requirements. Please try again or check the PHP error log.\n";
    exit;
}
?>
