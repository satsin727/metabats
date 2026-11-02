<?php
/*
 * Shared database helpers for BATS.
 * Loaded once from config.php. Safe to include more than once.
 *
 *  bats_db()                       one shared PDO connection per request
 *                                  (same default settings the pages used before)
 *  bats_col($table, $key, $id, $c) cached "SELECT $c FROM $table WHERE $key = $id"
 *                                  for primary-key lookups in read-only pages
 *  bats_q($value)                  safely quoted SQL literal (prevents SQL injection
 *                                  and broken queries on names like O'Brien)
 */

if (!function_exists('bats_db')) {

    function bats_db()
    {
        static $pdo = null;
        if ($pdo === null) {
            // Same constructor call the pages used, so behaviour is unchanged
            // (PHP 8 defaults: exceptions on, emulated prepares, buffered results).
            $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
        }
        return $pdo;
    }

    /*
     * Row cache for lookups by PRIMARY KEY. A page that used to run
     *   select cfname from consultants where cid = 5
     *   select clname from consultants where cid = 5
     *   select skill  from consultants where cid = 5
     * now runs one query for consultant 5 and answers the rest from memory.
     * Small lookup tables are loaded completely on first use.
     */
    function bats_col($table, $key, $id, $col)
    {
        static $cache = array();
        static $pk = array(
            'consultants' => 'cid', 'clients' => 'cid', 'req' => 'reqid', 'skill' => 'sid',
            'users' => 'uid', 'app_data' => 'app_id', 'eci' => 'eci_id', 'lists' => 'listid',
            'issues' => 'issueid', 'jd' => 'jd_id', 'ulevel' => 'id', 'client_response_type' => 'id',
        );
        static $small = array('skill' => 1, 'users' => 1, 'ulevel' => 1, 'client_response_type' => 1);

        $table = strtolower($table);
        if (!isset($pk[$table]) || $pk[$table] !== $key || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $col)) {
            // Not a primary-key lookup: run it exactly as before (just safely quoted).
            $sql = 'SELECT `' . str_replace('`', '', $col) . '` FROM `' . $table . '` WHERE `' . str_replace('`', '', $key) . '` = ' . bats_q($id);
            return bats_db()->query($sql)->fetchColumn();
        }
        // Same matching rules as MySQL "int_column = value": normalise numeric ids.
        $k = is_numeric($id) ? (string)(0 + $id) : (string)$id;

        if (!isset($cache[$table])) {
            $cache[$table] = array();
            if (isset($small[$table])) {
                foreach (bats_db()->query('SELECT * FROM `' . $table . '`') as $row) {
                    $cache[$table][(string)$row[$key]] = $row;
                }
                $cache[$table]['__complete__'] = true;
            }
        }
        if (!array_key_exists($k, $cache[$table])) {
            if (!empty($cache[$table]['__complete__']) && is_numeric($id)) {
                $cache[$table][$k] = false;
            } else {
                $st = bats_db()->prepare('SELECT * FROM `' . $table . '` WHERE `' . $key . '` = ? LIMIT 1');
                $st->execute(array($id));
                $cache[$table][$k] = $st->fetch(PDO::FETCH_ASSOC);
            }
        }
        $row = $cache[$table][$k];
        if ($row === false || !array_key_exists($col, $row)) {
            if ($row !== false) {
                // Unknown column: let MySQL raise the same error it did before.
                $st = bats_db()->prepare('SELECT `' . $col . '` FROM `' . $table . '` WHERE `' . $key . '` = ?');
                $st->execute(array($id));
                return $st->fetchColumn();
            }
            return false;
        }
        return $row[$col];
    }

    /*
     * Quote a value for direct use inside SQL text.
     * Numbers stay numbers (so "= 5" keeps working exactly the same),
     * NULL becomes NULL, everything else becomes a safely escaped 'string'.
     */
    function bats_q($v)
    {
        if ($v === null) {
            return 'NULL';
        }
        if (is_int($v) || is_float($v)) {
            return (string)$v;
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        return bats_db()->quote((string)$v);
    }
}
