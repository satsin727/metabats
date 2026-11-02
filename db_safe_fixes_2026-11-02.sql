-- =====================================================================
-- BATS database: SAFE, non-breaking fixes (2026-11-02)
-- Database: oejwxwmy_newdb
--
-- BEFORE RUNNING: take a backup in phpMyAdmin (Export > Quick > SQL).
-- Run in phpMyAdmin > SQL tab. Each statement is independent; if one
-- fails (e.g. an index was already changed), the rest still apply.
--
-- What this does NOT do (on purpose, to avoid breaking anything):
--   * no foreign keys (old orphan rows would make them fail)
--   * no data changes, no column removals, no type narrowing
--   * no changes to end_date / lastssn / password columns
-- =====================================================================

-- 1) Proper primary keys (currently only a UNIQUE key exists)
ALTER TABLE `app_data` ADD PRIMARY KEY (`app_id`);
ALTER TABLE `app_data` DROP INDEX `app_id`;

ALTER TABLE `ulevel` ADD PRIMARY KEY (`id`);
ALTER TABLE `ulevel` DROP INDEX `id`;

-- 2) Remove duplicate indexes (same column indexed twice = slower writes)
ALTER TABLE `assigned` DROP INDEX `id`;      -- duplicate of PRIMARY
ALTER TABLE `eci`      DROP INDEX `eci_id`;  -- duplicate of PRIMARY
ALTER TABLE `lists`    DROP INDEX `listid`;  -- duplicate of PRIMARY
ALTER TABLE `clients`  DROP INDEX `idx_email`; -- duplicate of idx_remail

-- 3) Missing indexes on columns used for lookups/joins
ALTER TABLE `assigned` ADD INDEX `idx_cid` (`cid`), ADD INDEX `idx_uid` (`uid`);
ALTER TABLE `eci` ADD INDEX `idx_app_id` (`app_id`),
                  ADD INDEX `idx_req_id` (`req_id`),
                  ADD INDEX `idx_consultant_id` (`consultant_id`),
                  ADD INDEX `idx_sm_id` (`sm_id`),
                  ADD INDEX `idx_eci_date` (`eci_date`);
ALTER TABLE `issues` ADD INDEX `idx_consultant_id` (`consultant_id`),
                     ADD INDEX `idx_sm_id` (`sm_id`);
ALTER TABLE `comments` ADD INDEX `idx_type_post` (`com_type`, `com_postid`);
ALTER TABLE `app_data` ADD INDEX `idx_appdate` (`appdate`),
                       ADD INDEX `idx_subdate` (`subdate`),
                       ADD INDEX `idx_rcdate` (`rcdate`);
ALTER TABLE `lists` ADD INDEX `idx_uid` (`uid`);

-- 4) Widen columns that can be too short (only makes them bigger, never smaller)
ALTER TABLE `users` MODIFY `lastloginip` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL; -- IPv6 support
ALTER TABLE `users` MODIFY `email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL;
ALTER TABLE `req` MODIFY `job_title` varchar(150) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL;
ALTER TABLE `req` MODIFY `rend_client` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL;
ALTER TABLE `app_data` MODIFY `t1ip_name` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL;
ALTER TABLE `app_data` MODIFY `reason_comment` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL;

-- 5) Match ID types with req.reqid (BIGINT) so joins use indexes and FKs are possible later
ALTER TABLE `app_data` MODIFY `reqid` bigint(20) NOT NULL;
ALTER TABLE `eci`      MODIFY `req_id` bigint(20) NOT NULL;
ALTER TABLE `jd`       MODIFY `reqid` bigint(20) NOT NULL;
