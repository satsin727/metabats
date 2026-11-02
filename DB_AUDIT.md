# BATS — Database Performance Audit (per file)

_Generated 2026-11-02 · read-only review, **no code changed**._

## Summary

Scanned 111 PHP files. Across the app: **1294 queries**, **204 separate `new PDO` connections**, **640 queries inside loops**, **1045 index-blocking date filters**, **1395 SQL strings with pasted variables**, **725 `SELECT *`**.

The single biggest cost: your database is reached over the network (`162.241.80.6`), so every query is a round-trip. Report and tracker pages run one query **per row per column**, so a 300-row report can easily make 5,000+ round-trips. Fixing that pattern alone typically makes those pages 10-50x faster.

## The 7 fixes (applied file by file below)

1. **One shared connection** — new `db.php` with a `db()` function (one connection per request, `utf8mb4`, exceptions on, real prepared statements; not persistent, because Bluehost limits connections per user). Replaces 200+ `new PDO(...)` calls.
2. **Shared auth helper** — `current_user()` runs the user lookup once per request instead of 2-3 times (page + menu.php).
3. **No queries inside loops** — fetch related rows with one JOIN, or one `WHERE id IN (...)`, before the loop.
4. **No one-column-at-a-time lookups** — e.g. `cfname`, `cmname`, `clname`, `skill` from `consultants` = 4 queries → 1 (or 0 with a JOIN).
5. **Index-friendly dates** — `MONTH(appdate)=11 AND YEAR(appdate)=2026` → `appdate >= '2026-11-01' AND appdate < '2026-12-01'`.
6. **Conditional aggregation for reports** — 12+ COUNT queries per consultant → 1 grouped query for all consultants.
7. **Prepared statements everywhere** — speed (statement reuse) + closes SQL injection.

Plus new composite indexes matching how the reports filter (Section at the end).

### Example: tracker row (today vs. after)

```php
// TODAY: ~20 queries per row
$cfname = $conn->query("select cfname from consultants where cid = $consultantid")->fetchColumn();
$clname = $conn->query("select clname from consultants where cid = $consultantid")->fetchColumn();
$skill  = $conn->query("SELECT skillname FROM skill WHERE sid = $skillid")->fetchColumn();
$job    = $conn->query("select job_title from req where reqid = $reqid")->fetchColumn();
...

// AFTER: 1 query for the whole report
SELECT a.app_id, a.appdate, a.ars_status, a.t1ip_name,
       c.cfname, c.cmname, c.clname, s.skillname,
       r.job_title, r.rlocation, r.ureq_id, r.datetime, r.rend_client,
       cl.companyname, cl.rname, cl.remail, cl.rphone, cl.rlocation, cl.tier
FROM app_data a
JOIN consultants c ON c.cid = a.consultant_id
LEFT JOIN skill s   ON s.sid = c.skill
LEFT JOIN req r     ON r.reqid = a.reqid
LEFT JOIN clients cl ON cl.cid = a.client_id
WHERE a.status = 1 AND a.appdate >= :start AND a.appdate < :end
ORDER BY a.appdate;
```

### Example: daily/weekly/monthly counts (today vs. after)

```php
// TODAY: 12 queries x every consultant, none can use an index
SELECT COUNT(*) FROM app_data WHERE consultant_id = $cid AND status = 1 AND DATE(appdate) = DATE('$curdate')
SELECT COUNT(*) FROM app_data WHERE consultant_id = $cid AND status = 1 AND WEEK(appdate) = WEEK('$curdate') ...

// AFTER: 1 query for all consultants
SELECT consultant_id,
  SUM(appdate >= :d0) AS d_app,  SUM(rcdone=1 AND rcdate >= :d0) AS d_rc,
  SUM(appdate >= :w0) AS w_app,  SUM(rcdone=1 AND rcdate >= :w0) AS w_rc,
  SUM(appdate >= :m0) AS m_app,  SUM(rcdone=1 AND rcdate >= :m0) AS m_rc,
  COUNT(*) AS y_app
FROM app_data
WHERE status = 1 AND appdate >= :y0 AND appdate < :next_day
GROUP BY consultant_id;
```


## Priority A — biggest speed gains (reports, trackers, downloads)

| File | Queries | Connections | In loops | Date filters | Pasted vars |
|---|---:|---:|---:|---:|---:|
| [trackersmdownload.php](#trackersmdownload-php) | 84 | 2 | 82 | 35 | 100 |
| [trackerdownload.php](#trackerdownload-php) | 80 | 2 | 78 | 39 | 95 |
| [showmtddata.php](#showmtddata-php) | 90 | 4 | 54 | 93 | 89 |
| [showdailyreks.php](#showdailyreks-php) | 90 | 4 | 54 | 90 | 89 |
| [smwisedata.php](#smwisedata-php) | 76 | 3 | 43 | 86 | 75 |
| [trackercmd.php](#trackercmd-php) | 23 | 1 | 13 | 128 | 105 |
| [subtracker.php](#subtracker-php) | 23 | 1 | 13 | 96 | 84 |
| [rctracker.php](#rctracker-php) | 23 | 1 | 13 | 96 | 84 |
| [apptracker.php](#apptracker-php) | 23 | 1 | 13 | 96 | 84 |
| [skillmtd.php](#skillmtd-php) | 38 | 4 | 20 | 60 | 31 |
| [consultanttracker.php](#consultanttracker-php) | 39 | 1 | 22 | 37 | 65 |

### trackersmdownload.php
_Tables: app_data, clients, comments, consultants, eci, jd, req, skill, users_

- Fetches 2 columns of `app_data` one query at a time (`ars_status, t1ip_name` by `app_id = $app_id`). Fix: one `SELECT ars_status, t1ip_name FROM app_data WHERE app_id = ?`, or better, JOIN `app_data` into the main query.
- Fetches 8 columns of `clients` one query at a time (`companyname, remail, rlocation, rname, rphone, rtimezon, tier, uid` by `cid = $cid`). Fix: one `SELECT companyname, remail, rlocation, rname, rphone, rtimezon, tier, uid FROM clients WHERE cid = ?`, or better, JOIN `clients` into the main query.
- Fetches 4 columns of `consultants` one query at a time (`cfname, clname, cmname, skill` by `cid = $consultantid`). Fix: one `SELECT cfname, clname, cmname, skill FROM consultants WHERE cid = ?`, or better, JOIN `consultants` into the main query.
- Fetches 4 columns of `req` one query at a time (`datetime, rend_client, rlocation, ureq_id` by `reqid = $reqid`). Fix: one `SELECT datetime, rend_client, rlocation, ureq_id FROM req WHERE reqid = ?`, or better, JOIN `req` into the main query.
- 82 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 35 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 100 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 25 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- Download/export: writes a CSV into `tmp/` then re-reads it. Fix: stream straight to the browser with `fopen('php://output','w')` (no leftover files with client data) and use an unbuffered query for large exports.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### trackerdownload.php
_Tables: app_data, clients, comments, consultants, eci, jd, req, skill, users_

- Fetches 2 columns of `app_data` one query at a time (`ars_status, t1ip_name` by `app_id = $app_id`). Fix: one `SELECT ars_status, t1ip_name FROM app_data WHERE app_id = ?`, or better, JOIN `app_data` into the main query.
- Fetches 8 columns of `clients` one query at a time (`companyname, remail, rlocation, rname, rphone, rtimezon, tier, uid` by `cid = $cid`). Fix: one `SELECT companyname, remail, rlocation, rname, rphone, rtimezon, tier, uid FROM clients WHERE cid = ?`, or better, JOIN `clients` into the main query.
- Fetches 4 columns of `consultants` one query at a time (`cfname, clname, cmname, skill` by `cid = $consultantid`). Fix: one `SELECT cfname, clname, cmname, skill FROM consultants WHERE cid = ?`, or better, JOIN `consultants` into the main query.
- Fetches 5 columns of `req` one query at a time (`datetime, job_title, rend_client, rlocation, ureq_id` by `reqid = $reqid`). Fix: one `SELECT datetime, job_title, rend_client, rlocation, ureq_id FROM req WHERE reqid = ?`, or better, JOIN `req` into the main query.
- 78 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 39 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 95 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 27 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- Download/export: writes a CSV into `tmp/` then re-reads it. Fix: stream straight to the browser with `fopen('php://output','w')` (no leftover files with client data) and use an unbuffered query for large exports.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### showmtddata.php
_Tables: app_data, assigned, consultants, eci, skill, users_

- 54 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 48 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 93 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 89 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **4 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 4 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### showdailyreks.php
_Tables: app_data, assigned, consultants, eci, skill, users_

- 54 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 48 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 90 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 89 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **4 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 4 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### smwisedata.php
_Tables: app_data, eci, users_

- 43 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 48 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 86 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 75 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **3 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 3 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### trackercmd.php
_Tables: app_data, clients, consultants, eci, req, skill, users_

- Fetches 2 columns of `app_data` one query at a time (`ars_status, t1ip_name` by `app_id = $app_id`). Fix: one `SELECT ars_status, t1ip_name FROM app_data WHERE app_id = ?`, or better, JOIN `app_data` into the main query.
- Fetches 6 columns of `consultants` one query at a time (`cfname, clname, cm_phonenumber, cmname, co_phonenumber, skill` by `cid = $consultantid`). Fix: one `SELECT cfname, clname, cm_phonenumber, cmname, co_phonenumber, skill FROM consultants WHERE cid = ?`, or better, JOIN `consultants` into the main query.
- 13 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 128 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 105 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 89 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### subtracker.php
_Tables: app_data, clients, consultants, eci, req, skill, users_

- Fetches 2 columns of `app_data` one query at a time (`ars_status, t1ip_name` by `app_id = $app_id`). Fix: one `SELECT ars_status, t1ip_name FROM app_data WHERE app_id = ?`, or better, JOIN `app_data` into the main query.
- Fetches 6 columns of `consultants` one query at a time (`cfname, clname, cm_phonenumber, cmname, co_phonenumber, skill` by `cid = $consultantid`). Fix: one `SELECT cfname, clname, cm_phonenumber, cmname, co_phonenumber, skill FROM consultants WHERE cid = ?`, or better, JOIN `consultants` into the main query.
- 13 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 96 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 84 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 68 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### rctracker.php
_Tables: app_data, clients, consultants, eci, req, skill, users_

- Fetches 2 columns of `app_data` one query at a time (`ars_status, t1ip_name` by `app_id = $app_id`). Fix: one `SELECT ars_status, t1ip_name FROM app_data WHERE app_id = ?`, or better, JOIN `app_data` into the main query.
- Fetches 6 columns of `consultants` one query at a time (`cfname, clname, cm_phonenumber, cmname, co_phonenumber, skill` by `cid = $consultantid`). Fix: one `SELECT cfname, clname, cm_phonenumber, cmname, co_phonenumber, skill FROM consultants WHERE cid = ?`, or better, JOIN `consultants` into the main query.
- 13 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 96 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 84 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 68 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### apptracker.php
_Tables: app_data, clients, consultants, eci, req, skill, users_

- Fetches 2 columns of `app_data` one query at a time (`ars_status, t1ip_name` by `app_id = $app_id`). Fix: one `SELECT ars_status, t1ip_name FROM app_data WHERE app_id = ?`, or better, JOIN `app_data` into the main query.
- Fetches 6 columns of `consultants` one query at a time (`cfname, clname, cm_phonenumber, cmname, co_phonenumber, skill` by `cid = $consultantid`). Fix: one `SELECT cfname, clname, cm_phonenumber, cmname, co_phonenumber, skill FROM consultants WHERE cid = ?`, or better, JOIN `consultants` into the main query.
- 13 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 96 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 84 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 68 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### skillmtd.php
_Tables: app_data, consultants, eci, req, skill, users_

- 20 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 18 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 60 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 31 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **4 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### consultanttracker.php
_Tables: app_data, clients, comments, consultants, eci, issues, req, skill, users_

- Fetches 2 columns of `app_data` one query at a time (`ars_status, t1ip_name` by `app_id = $app_id`). Fix: one `SELECT ars_status, t1ip_name FROM app_data WHERE app_id = ?`, or better, JOIN `app_data` into the main query.
- Fetches 6 columns of `consultants` one query at a time (`cfname, clname, cm_phonenumber, cmname, co_phonenumber, skill` by `cid = $consultantid`). Fix: one `SELECT cfname, clname, cm_phonenumber, cmname, co_phonenumber, skill FROM consultants WHERE cid = ?`, or better, JOIN `consultants` into the main query.
- 22 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 37 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 65 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 37 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

## Priority B — list/show pages

| File | Queries | Connections | In loops | Date filters | Pasted vars |
|---|---:|---:|---:|---:|---:|
| [showallrek.php](#showallrek-php) | 15 | 2 | 14 | 26 | 34 |
| [smreqsdownload.php](#smreqsdownload-php) | 17 | 2 | 15 | 12 | 19 |
| [fetchdata.php](#fetchdata-php) | 15 | 2 | 13 | 12 | 19 |
| [trackersmcmd.php](#trackersmcmd-php) | 16 | 1 | 8 | 24 | 35 |
| [showrek_teamreqs.php](#showrek-teamreqs-php) | 13 | 1 | 11 | 0 | 15 |
| [showrek.php](#showrek-php) | 13 | 1 | 11 | 0 | 14 |
| [showsub.php](#showsub-php) | 12 | 1 | 10 | 0 | 14 |
| [showeci.php](#showeci-php) | 12 | 1 | 10 | 0 | 11 |
| [showrc.php](#showrc-php) | 11 | 1 | 9 | 0 | 13 |
| [showapplication.php](#showapplication-php) | 11 | 1 | 9 | 0 | 13 |

### showallrek.php
_Tables: app_data, clients, consultants, req, skill, users_

- Fetches 2 columns of `req` one query at a time (`datetime, rend_client` by `reqid = $reqid`). Fix: one `SELECT datetime, rend_client FROM req WHERE reqid = ?`, or better, JOIN `req` into the main query.
- 14 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 4 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 26 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 34 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 20 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### smreqsdownload.php
_Tables: app_data, clients, comments, consultants, jd, req, skill, users_

- Fetches 2 columns of `clients` one query at a time (`remail, rphone` by `cid = $cid`). Fix: one `SELECT remail, rphone FROM clients WHERE cid = ?`, or better, JOIN `clients` into the main query.
- Fetches 2 columns of `req` one query at a time (`rend_client, rlocation` by `reqid = $reqid`). Fix: one `SELECT rend_client, rlocation FROM req WHERE reqid = ?`, or better, JOIN `req` into the main query.
- 15 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 3 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 12 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 19 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 8 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- Download/export: writes a CSV into `tmp/` then re-reads it. Fix: stream straight to the browser with `fopen('php://output','w')` (no leftover files with client data) and use an unbuffered query for large exports.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### fetchdata.php
_Tables: app_data, clients, consultants, req, skill, users_

- Fetches 2 columns of `app_data` one query at a time (`ars_status, t1ip_name` by `app_id = $app_id`). Fix: one `SELECT ars_status, t1ip_name FROM app_data WHERE app_id = ?`, or better, JOIN `app_data` into the main query.
- Fetches 6 columns of `consultants` one query at a time (`cfname, clname, cm_phonenumber, cmname, co_phonenumber, skill` by `cid = $consultantid`). Fix: one `SELECT cfname, clname, cm_phonenumber, cmname, co_phonenumber, skill FROM consultants WHERE cid = ?`, or better, JOIN `consultants` into the main query.
- 13 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 12 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 19 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 10 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### trackersmcmd.php
_Tables: app_data, clients, consultants, eci, req, skill, users_

- Fetches 2 columns of `app_data` one query at a time (`ars_status, t1ip_name` by `app_id = $app_id`). Fix: one `SELECT ars_status, t1ip_name FROM app_data WHERE app_id = ?`, or better, JOIN `app_data` into the main query.
- Fetches 3 columns of `consultants` one query at a time (`cfname, clname, skill` by `cid = $consultantid`). Fix: one `SELECT cfname, clname, skill FROM consultants WHERE cid = ?`, or better, JOIN `consultants` into the main query.
- 8 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 24 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 35 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 20 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### showrek_teamreqs.php
_Tables: app_data, clients, consultants, req, skill, users_

- 11 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 4 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 15 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 8 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### showrek.php
_Tables: app_data, clients, consultants, req, skill, users_

- 11 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 4 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 14 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 7 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### showsub.php
_Tables: app_data, clients, consultants, req, skill, users_

- Fetches 2 columns of `app_data` one query at a time (`ars_status, subto` by `app_id = $app_id`). Fix: one `SELECT ars_status, subto FROM app_data WHERE app_id = ?`, or better, JOIN `app_data` into the main query.
- 10 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 4 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 14 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 9 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### showeci.php
_Tables: app_data, clients, consultants, eci, req, skill, users_

- 10 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 4 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 11 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 8 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### showrc.php
_Tables: app_data, clients, consultants, req, skill, users_

- 9 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 4 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 13 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 9 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### showapplication.php
_Tables: app_data, clients, consultants, req, skill, users_

- 9 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 4 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 13 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 9 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

## Priority C — add/edit/command pages (CRUD)

| File | Queries | Connections | In loops | Date filters | Pasted vars |
|---|---:|---:|---:|---:|---:|
| [consultantdata.php](#consultantdata-php) | 22 | 1 | 0 | 24 | 21 |
| [updatecalllist.php](#updatecalllist-php) | 8 | 1 | 7 | 0 | 7 |
| [leads/view.php](#leads-view-php) | 14 | 3 | 5 | 0 | 10 |
| [assignconsultant.php](#assignconsultant-php) | 10 | 6 | 4 | 0 | 5 |
| [listupload.php](#listupload-php) | 6 | 3 | 5 | 0 | 4 |
| [reqcmd.php](#reqcmd-php) | 14 | 11 | 0 | 0 | 6 |
| [listcmd.php](#listcmd-php) | 12 | 9 | 0 | 0 | 8 |
| [issues.php](#issues-php) | 5 | 2 | 4 | 0 | 5 |
| [assigned.php](#assigned-php) | 5 | 2 | 4 | 0 | 4 |
| [listall.php](#listall-php) | 5 | 2 | 4 | 0 | 2 |
| [assign.php](#assign-php) | 5 | 2 | 4 | 0 | 3 |
| [reqsdownload.php](#reqsdownload-php) | 6 | 1 | 4 | 0 | 0 |
| [consultantdetails.php](#consultantdetails-php) | 10 | 7 | 0 | 0 | 3 |
| [usercmd.php](#usercmd-php) | 9 | 5 | 0 | 0 | 6 |
| [assigncmd.php](#assigncmd-php) | 6 | 6 | 0 | 0 | 3 |
| [list_consultants.php](#list-consultants-php) | 3 | 2 | 2 | 0 | 1 |
| [issuecmd.php](#issuecmd-php) | 7 | 5 | 0 | 0 | 5 |
| [email_worker_core.php](#email-worker-core-php) | 12 | 0 | 3 | 0 | 1 |
| [comments.php](#comments-php) | 5 | 3 | 1 | 0 | 4 |
| [leads/index.php](#leads-index-php) | 10 | 1 | 2 | 0 | 2 |

### consultantdata.php
_Tables: app_data, consultants, eci, users_

- 15 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 24 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 21 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### updatecalllist.php
_Tables: clients, users_

- 7 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 7 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### leads/view.php
_Tables: app_data, clients, comments, consultants, jd, req, skill, users_

- Fetches 2 columns of `clients` one query at a time (`remail, rphone` by `cid = $cid`). Fix: one `SELECT remail, rphone FROM clients WHERE cid = ?`, or better, JOIN `clients` into the main query.
- Fetches 2 columns of `consultants` one query at a time (`cfname, clname` by `cid = $consultant_id`). Fix: one `SELECT cfname, clname FROM consultants WHERE cid = ?`, or better, JOIN `consultants` into the main query.
- 5 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 10 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **3 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 7 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### assignconsultant.php
_Tables: assigned, consultants, skill, users_

- 4 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 5 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **6 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 8 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### listupload.php
_Tables: clients, lists, users_

- 5 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 4 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **3 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### reqcmd.php
_Tables: app_data, clients, jd, req, skill, users_

- 6 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **11 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 7 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### listcmd.php
_Tables: clients, lists, users_

- 8 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **9 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 9 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- Download/export: writes a CSV into `tmp/` then re-reads it. Fix: stream straight to the browser with `fopen('php://output','w')` (no leftover files with client data) and use an unbuffered query for large exports.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### issues.php
_Tables: consultants, issues, skill, users_

- 4 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 5 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 6 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### assigned.php
_Tables: assigned, consultants, skill, users_

- 4 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 4 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 6 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### listall.php
_Tables: clients, lists, users_

- 4 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 2 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 4 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### assign.php
_Tables: assigned, consultants, skill, users_

- 4 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 3 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 5 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### reqsdownload.php
_Tables: app_data, clients, comments, consultants, jd, req, skill, users_

- 4 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Download/export: writes a CSV into `tmp/` then re-reads it. Fix: stream straight to the browser with `fopen('php://output','w')` (no leftover files with client data) and use an unbuffered query for large exports.

### consultantdetails.php
_Tables: consultants, users_

- 3 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **7 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 7 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### usercmd.php
_Tables: users_

- 6 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **5 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 3 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### assigncmd.php
_Tables: assigned, consultants, users_

- 3 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **6 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 4 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### list_consultants.php
_Tables: consultants, skill, users_

- 2 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 1 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 3 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### issuecmd.php
_Tables: consultants, issues, users_

- 5 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **5 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 4 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### email_worker_core.php
_Tables: em_messages_

- 3 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 1 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### comments.php
_Tables: app_data, comments, users_

- 1 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 4 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **3 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 5 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### leads/index.php
_Tables: req, skill, users_

- 2 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 2 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- `SELECT *` used 3 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

## Priority D — minor clean-up

| File | Queries | Connections | In loops | Date filters | Pasted vars |
|---|---:|---:|---:|---:|---:|
| [updatedhotlist.php](#updatedhotlist-php) | 3 | 2 | 1 | 0 | 2 |
| [listupdate.php](#listupdate-php) | 5 | 4 | 0 | 0 | 2 |
| [admin.php](#admin-php) | 6 | 4 | 0 | 0 | 2 |
| [reqnoedit.php](#reqnoedit-php) | 6 | 3 | 0 | 0 | 2 |
| [listadd.php](#listadd-php) | 5 | 3 | 0 | 0 | 3 |
| [ecidetails.php](#ecidetails-php) | 10 | 1 | 0 | 0 | 10 |
| [add_user.php](#add-user-php) | 5 | 3 | 0 | 0 | 3 |
| [add_consultant.php](#add-consultant-php) | 4 | 3 | 0 | 0 | 0 |
| [assigneddetails.php](#assigneddetails-php) | 3 | 2 | 0 | 0 | 2 |
| [addeci.php](#addeci-php) | 6 | 1 | 0 | 0 | 7 |
| [addapp.php](#addapp-php) | 7 | 1 | 0 | 0 | 7 |
| [viewcombined.php](#viewcombined-php) | 2 | 2 | 0 | 0 | 1 |
| [smcallreports.php](#smcallreports-php) | 6 | 1 | 0 | 0 | 4 |
| [email_diagnose.php](#email-diagnose-php) | 1 | 0 | 1 | 0 | 0 |
| [download.php](#download-php) | 2 | 2 | 0 | 0 | 0 |
| [call_reports.php](#call-reports-php) | 6 | 1 | 0 | 0 | 4 |
| [appdata_cmd.php](#appdata-cmd-php) | 6 | 1 | 0 | 0 | 5 |
| [addstatus.php](#addstatus-php) | 5 | 1 | 0 | 0 | 5 |
| [addrc.php](#addrc-php) | 5 | 1 | 0 | 0 | 4 |
| [addecifeedback.php](#addecifeedback-php) | 5 | 1 | 0 | 0 | 4 |
| [add_skill.php](#add-skill-php) | 2 | 2 | 0 | 0 | 1 |
| [add_recruiter.php](#add-recruiter-php) | 3 | 2 | 0 | 0 | 1 |
| [add_list.php](#add-list-php) | 2 | 2 | 0 | 0 | 1 |
| [ecicmd.php](#ecicmd-php) | 4 | 1 | 0 | 0 | 2 |
| [clientcallcmd.php](#clientcallcmd-php) | 14 | 1 | 0 | 0 | 3 |
| [addsubstatus.php](#addsubstatus-php) | 3 | 1 | 0 | 0 | 3 |
| [addcomment.php](#addcomment-php) | 3 | 1 | 0 | 0 | 2 |
| [sats_request.php](#sats-request-php) | 13 | 1 | 0 | 0 | 0 |
| [reportdashboard.php](#reportdashboard-php) | 1 | 1 | 0 | 0 | 0 |
| [rateconfirmation.php](#rateconfirmation-php) | 1 | 1 | 0 | 0 | 0 |
| [postreq_cp.php](#postreq-cp-php) | 8 | 1 | 0 | 0 | 0 |
| [postrek.php](#postrek-php) | 8 | 1 | 0 | 0 | 0 |
| [lout.php](#lout-php) | 1 | 1 | 0 | 0 | 0 |
| [list_users.php](#list-users-php) | 2 | 1 | 0 | 0 | 0 |
| [includes/menu.php](#includes-menu-php) | 1 | 1 | 0 | 0 | 0 |
| [fetchclient.php](#fetchclient-php) | 2 | 1 | 0 | 0 | 0 |
| [export_reports.php](#export-reports-php) | 4 | 1 | 0 | 0 | 1 |
| [dashboard.php](#dashboard-php) | 1 | 1 | 0 | 0 | 0 |
| [consultantcmd.php](#consultantcmd-php) | 6 | 1 | 0 | 0 | 1 |
| [clientsdata.php](#clientsdata-php) | 2 | 1 | 0 | 0 | 0 |
| [client_todays_calls.php](#client-todays-calls-php) | 3 | 1 | 0 | 0 | 0 |
| [client_today_client_ajax.php](#client-today-client-ajax-php) | 13 | 1 | 0 | 0 | 0 |
| [client_call_history.php](#client-call-history-php) | 9 | 1 | 0 | 0 | 0 |
| [client_call_assignment.php](#client-call-assignment-php) | 2 | 1 | 0 | 0 | 0 |
| [backendsearch.php](#backendsearch-php) | 1 | 1 | 0 | 0 | 0 |
| [addreqstatus.php](#addreqstatus-php) | 3 | 1 | 0 | 0 | 1 |
| [addissues.php](#addissues-php) | 3 | 1 | 0 | 0 | 1 |
| [email_distribution.php](#email-distribution-php) | 30 | 0 | 0 | 0 | 3 |
| [email_export.php](#email-export-php) | 1 | 0 | 0 | 0 | 0 |

### updatedhotlist.php
_Tables: assigned, consultants, skill, users_

- 1 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 2 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 4 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### listupdate.php
_Tables: clients, lists, users_

- 2 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **4 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 3 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### admin.php
_Tables: clients, users_

- 2 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **4 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- Uses `DISTINCT` together with `GROUP BY` (redundant; extra sort). Fix: keep only `GROUP BY`.
- Download/export: writes a CSV into `tmp/` then re-reads it. Fix: stream straight to the browser with `fopen('php://output','w')` (no leftover files with client data) and use an unbuffered query for large exports.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### reqnoedit.php
_Tables: jd, req, skill, users_

- 2 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **3 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 5 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### listadd.php
_Tables: clients, lists, users_

- 3 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **3 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### ecidetails.php
_Tables: app_data, consultants, eci, req, skill, users_

- Fetches 3 columns of `app_data` one query at a time (`consultant_id, reqid, t1ip_name` by `app_id = $eciid`). Fix: one `SELECT consultant_id, reqid, t1ip_name FROM app_data WHERE app_id = ?`, or better, JOIN `app_data` into the main query.
- Fetches 3 columns of `consultants` one query at a time (`cfname, clname, skill` by `cid = $consultantid`). Fix: one `SELECT cfname, clname, skill FROM consultants WHERE cid = ?`, or better, JOIN `consultants` into the main query.
- 10 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 3 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### add_user.php
_Tables: lists, users_

- 3 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **3 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### add_consultant.php
_Tables: consultants, skill, users_

- Opens **3 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).

### assigneddetails.php
_Tables: assigned, consultants, users_

- 2 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 3 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### addeci.php
_Tables: app_data, consultants, eci, users_

- 7 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 3 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### addapp.php
_Tables: app_data, assigned, consultants, req, users_

- 7 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 5 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### viewcombined.php
_Tables: clients, users_

- 1 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- Uses `DISTINCT` together with `GROUP BY` (redundant; extra sort). Fix: keep only `GROUP BY`.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### smcallreports.php
_Tables: client_call_schedule, client_response_type, clients, users_

- 4 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### email_diagnose.php
_Tables: —_

- 1 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### download.php
_Tables: consultants, users_

- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- Download/export: writes a CSV into `tmp/` then re-reads it. Fix: stream straight to the browser with `fopen('php://output','w')` (no leftover files with client data) and use an unbuffered query for large exports.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### call_reports.php
_Tables: client_call_schedule, client_response_type, clients, users_

- 4 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### appdata_cmd.php
_Tables: app_data, eci, users_

- 5 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### addstatus.php
_Tables: app_data, users_

- 5 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### addrc.php
_Tables: app_data, assigned, consultants, users_

- 4 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 4 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### addecifeedback.php
_Tables: app_data, eci, users_

- 4 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### add_skill.php
_Tables: skill, users_

- 1 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### add_recruiter.php
_Tables: users_

- 1 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### add_list.php
_Tables: lists, users_

- 1 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### ecicmd.php
_Tables: eci, users_

- 2 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### clientcallcmd.php
_Tables: client_call_history, client_call_schedule, clients, users_

- 3 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).

### addsubstatus.php
_Tables: app_data, users_

- 3 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### addcomment.php
_Tables: comments, consultants, users_

- 2 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### sats_request.php
_Tables: app_data, clients, consultants, jd, req, skill, users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).

### reportdashboard.php
_Tables: users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### rateconfirmation.php
_Tables: users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### postreq_cp.php
_Tables: clients, jd, req, skill, users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).

### postrek.php
_Tables: clients, jd, req, skill, users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 3 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).

### lout.php
_Tables: users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### list_users.php
_Tables: users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### includes/menu.php
_Tables: users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### fetchclient.php
_Tables: clients_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).

### export_reports.php
_Tables: client_call_history, client_call_schedule, client_response_type, clients, users_

- 1 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Download/export: writes a CSV into `tmp/` then re-reads it. Fix: stream straight to the browser with `fopen('php://output','w')` (no leftover files with client data) and use an unbuffered query for large exports.

### dashboard.php
_Tables: users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### consultantcmd.php
_Tables: consultants, skill, users_

- 1 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).

### clientsdata.php
_Tables: clients, users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- Uses `DISTINCT` together with `GROUP BY` (redundant; extra sort). Fix: keep only `GROUP BY`.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### client_todays_calls.php
_Tables: client_call_schedule, client_response_type, clients, users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).

### client_today_client_ajax.php
_Tables: client_call_history, client_call_schedule, clients, users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).

### client_call_history.php
_Tables: client_call_history, client_call_schedule, client_response_type, clients, users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).

### client_call_assignment.php
_Tables: client_call_history, client_call_schedule, clients, users_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).

### backendsearch.php
_Tables: clients_

- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).

### addreqstatus.php
_Tables: req, users_

- 1 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### addissues.php
_Tables: consultants, issues, users_

- 1 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### email_distribution.php
_Tables: clients, em_campaigns, em_contacts, em_list_members, em_lists, em_logs, em_messages, em_settings, em_templates, req, skill, users_

- 3 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- `SELECT *` used 4 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### email_export.php
_Tables: em_messages, req_

- Download/export: writes a CSV into `tmp/` then re-reads it. Fix: stream straight to the browser with `fopen('php://output','w')` (no leftover files with client data) and use an unbuffered query for large exports.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

## Legacy / unused files — remove rather than fix

| File | Queries | Connections | In loops | Date filters | Pasted vars |
|---|---:|---:|---:|---:|---:|
| [weeklydata.php](#weeklydata-php) | 74 | 4 | 42 | 50 | 58 |
| [reqsdownload_old.php](#reqsdownload-old-php) | 17 | 2 | 15 | 33 | 29 |
| [showuniquereqs.php](#showuniquereqs-php) | 15 | 1 | 13 | 8 | 21 |
| [list_consultants-o.php](#list-consultants-o-php) | 3 | 2 | 2 | 0 | 2 |
| [consultantcmd-o.php](#consultantcmd-o-php) | 6 | 6 | 0 | 0 | 3 |
| [showrek_old.php](#showrek-old-php) | 4 | 1 | 2 | 0 | 3 |
| [list cmd modified.php](#list-cmd-modified-php) | 6 | 4 | 0 | 0 | 2 |
| [admin-o.php](#admin-o-php) | 6 | 4 | 0 | 0 | 2 |
| [smclientsdata.php](#smclientsdata-php) | 2 | 1 | 0 | 0 | 0 |
| [postreq_cp-o.php](#postreq-cp-o-php) | 8 | 1 | 0 | 0 | 0 |
| [includes/menu-o.php](#includes-menu-o-php) | 1 | 1 | 0 | 0 | 0 |
| [leads/ajaxfile.php](#leads-ajaxfile-php) | 3 | 0 | 0 | 0 | 0 |

### weeklydata.php
_Tables: app_data, assigned, consultants, eci, skill, users_

- **Not linked from anywhere in the app.** Recommend: confirm it's unused, then remove it instead of optimising it.
- 42 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 36 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 50 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 58 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **4 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 4 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### reqsdownload_old.php
_Tables: app_data, clients, comments, consultants, jd, req, skill, users_

- **Not linked from anywhere in the app.** Recommend: confirm it's unused, then remove it instead of optimising it.
- Fetches 2 columns of `clients` one query at a time (`remail, rphone` by `cid = $cid`). Fix: one `SELECT remail, rphone FROM clients WHERE cid = ?`, or better, JOIN `clients` into the main query.
- Fetches 2 columns of `req` one query at a time (`rend_client, rlocation` by `reqid = $reqid`). Fix: one `SELECT rend_client, rlocation FROM req WHERE reqid = ?`, or better, JOIN `req` into the main query.
- 15 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 4 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 33 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 29 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 17 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- Download/export: writes a CSV into `tmp/` then re-reads it. Fix: stream straight to the browser with `fopen('php://output','w')` (no leftover files with client data) and use an unbuffered query for large exports.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### showuniquereqs.php
_Tables: app_data, clients, consultants, req, skill, users_

- **Not linked from anywhere in the app.** Recommend: confirm it's unused, then remove it instead of optimising it.
- Fetches 2 columns of `req` one query at a time (`datetime, rend_client` by `reqid = $reqid`). Fix: one `SELECT datetime, rend_client FROM req WHERE reqid = ?`, or better, JOIN `req` into the main query.
- 13 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 4 separate `COUNT(*)` queries on `app_data` (daily/weekly/monthly/yearly per consultant). Fix: one query with conditional sums, e.g. `SUM(appdate >= :day_start) AS d_app, SUM(appdate >= :week_start) AS w_app ... GROUP BY consultant_id`.
- 8 date filters wrap the column in `DATE()/WEEK()/MONTH()/YEAR()`, so MySQL can't use the index and scans the whole table. Fix: compute the range in PHP and use `col >= :start AND col < :end`.
- 21 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 13 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### list_consultants-o.php
_Tables: consultants, skill, users_

- **Not linked from anywhere in the app.** Recommend: confirm it's unused, then remove it instead of optimising it.
- 2 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 2 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **2 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 3 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### consultantcmd-o.php
_Tables: consultants, skill, users_

- **Not linked from anywhere in the app.** Recommend: confirm it's unused, then remove it instead of optimising it.
- 3 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **6 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 4 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### showrek_old.php
_Tables: clients, req, skill, users_

- **Not linked from anywhere in the app.** Recommend: confirm it's unused, then remove it instead of optimising it.
- 2 queries run **inside a loop** (one DB round-trip per row, the 'N+1' problem). Fix: fetch everything with one JOIN / `WHERE id IN (...)` query before the loop and look rows up from a PHP array.
- 3 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 5 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### list cmd modified.php
_Tables: lists, users_

- **Not linked from anywhere in the app.** Recommend: confirm it's unused, then remove it instead of optimising it.
- 2 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **4 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- `SELECT *` used 4 times. Fix: list only the columns the page shows (avoids pulling `longtext`/`mediumtext` columns like `jd.rdesc`, `feedback`, `filetarget`).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### admin-o.php
_Tables: clients, users_

- **Not linked from anywhere in the app.** Recommend: confirm it's unused, then remove it instead of optimising it.
- 2 SQL statements built by pasting PHP variables into the string. Slower (no statement reuse) and open to SQL injection. Fix: `?`/`:name` placeholders with `execute([...])`.
- Opens **4 database connections** in one page (`new PDO` each time; the DB is remote, so each one costs a network handshake). Fix: one shared connection from a `db()` helper.
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- Uses `DISTINCT` together with `GROUP BY` (redundant; extra sort). Fix: keep only `GROUP BY`.
- Download/export: writes a CSV into `tmp/` then re-reads it. Fix: stream straight to the browser with `fopen('php://output','w')` (no leftover files with client data) and use an unbuffered query for large exports.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### smclientsdata.php
_Tables: clients, users_

- **Not linked from anywhere in the app.** Recommend: confirm it's unused, then remove it instead of optimising it.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- Repeats the logged-in-user lookup (`select * from users where uid = :u`) at the top of the page. Fix: shared `current_user()` helper that runs it once per request (menu.php runs it again).
- Uses `DISTINCT` together with `GROUP BY` (redundant; extra sort). Fix: keep only `GROUP BY`.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### postreq_cp-o.php
_Tables: clients, jd, req, skill, users_

- **Not linked from anywhere in the app.** Recommend: confirm it's unused, then remove it instead of optimising it.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).

### includes/menu-o.php
_Tables: users_

- **Not linked from anywhere in the app.** Recommend: confirm it's unused, then remove it instead of optimising it.
- Creates its own connection; switch to the shared `db()` helper (same speed, but one place for settings/charset/errors).
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.

### leads/ajaxfile.php
_Tables: —_

- **Not linked from anywhere in the app.** Recommend: confirm it's unused, then remove it instead of optimising it.
- No `PDO::ERRMODE_EXCEPTION`, so failed queries fail silently (blank tables). The shared helper turns this on.


## Indexes to add (match the report filters)

These go in a second SQL file, after the code changes, so they line up with the new range-based queries:

```sql
ALTER TABLE app_data ADD INDEX idx_cons_status_appdate (consultant_id, status, appdate),
                     ADD INDEX idx_cons_status_rcdate  (consultant_id, status, rcdone, subdone, rcdate),
                     ADD INDEX idx_uid_status_appdate  (uid, status, appdate);
ALTER TABLE eci      ADD INDEX idx_app_round (app_id, eci_happened, eci_round),
                     ADD INDEX idx_sm_date   (sm_id, eci_date);
ALTER TABLE comments ADD INDEX idx_post_flags (com_postid, appcom_id, rccom_id, subcom_id);
ALTER TABLE req      ADD INDEX idx_uid_status_dt (uid, status, datetime);
ALTER TABLE assigned ADD INDEX idx_uid_cid (uid, cid);
```

## Proposed order of work (each step testable on its own)

1. Add `db.php` (shared connection + `current_user()`), with no page changes yet. Zero risk.
2. Priority A files one at a time, comparing each report's numbers before/after on the same date.
3. Add the indexes above.
4. Priority B, then C (CRUD forms: placeholders, one connection; behaviour unchanged).
5. Remove the legacy files after you confirm they're unused.
