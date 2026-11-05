<?php
/*
 * Open Tasks (admin.php?action=opentasks)
 * Kanban board + list + activity log. All reads/writes go through tasks_ajax.php.
 */
require_once __DIR__ . '/tasks_lib.php';
$u = tk_user();
tk_schema();
$csrf = tk_csrf();
$view = in_array($_GET['view'] ?? '', ['board', 'list', 'activity', 'settings', 'checklists'], true) ? $_GET['view'] : 'board';
if ($view === 'checklists' && !tk_is_mgr($u)) $view = 'board';
if ($view === 'settings' && !tk_is_admin($u)) $view = 'board';
$users = array_values(array_map(fn($x) => ['uid' => $x['uid'], 'name' => $x['name'], 'level' => $x['level']], tk_users(true)));
$boot = [
    'csrf' => $csrf, 'me' => $u['uid'], 'level' => $u['level'], 'isMgr' => tk_is_mgr($u), 'isAdmin' => tk_is_admin($u), 'view' => $view,
    'stages' => TK_STAGES, 'flow' => TK_FLOW, 'priorities' => TK_PRIORITIES, 'categories' => TK_CATEGORIES, 'users' => $users,
    'levels' => TK_LEVELS, 'openId' => (int)($_GET['task'] ?? 0),
];
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/menu.php';
?>
<style>
.tk-main { float: right; min-width: 0; }
.tk-wrap { padding: 18px 18px 40px; box-sizing: border-box; }
.tk-wrap *, .tk-wrap *::before, .tk-wrap *::after { box-sizing: border-box; }
.tk-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
.tk-head h2 { margin: 0; font-size: 24px; }
.tk-head .tk-sub { color: #667085; font-size: 13px; }
.tk-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
.tk-tabs a { padding: 7px 14px; border-radius: 6px; border: 1px solid #d0d5dd; background: #fff; color: #344054; text-decoration: none; font-weight: 600; }
.tk-tabs a.on { background: #1570ef; border-color: #1570ef; color: #fff; }
.tk-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; margin-bottom: 14px; }
.tk-stat { background: #fff; border: 1px solid #eaecf0; border-radius: 8px; padding: 10px 12px; cursor: pointer; }
.tk-stat:hover { border-color: #84adff; }
.tk-stat b { display: block; font-size: 22px; line-height: 1.2; color: #101828; }
.tk-stat span { font-size: 12px; color: #667085; text-transform: uppercase; letter-spacing: .03em; }
.tk-stat.warn b { color: #d92d20; }
.tk-filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; background: #fff; border: 1px solid #eaecf0; border-radius: 8px; padding: 10px; margin-bottom: 14px; }
.tk-filters .form-control { width: auto; min-width: 130px; height: 32px; padding: 4px 8px; }
.tk-filters input[type=search] { min-width: 200px; }
.tk-filters label { font-weight: normal; margin: 0; font-size: 13px; }
.tk-board { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; align-items: flex-start; }
.tk-col { flex: 1 1 0; min-width: 180px; background: #f2f4f7; border-radius: 10px; display: flex; flex-direction: column; max-height: calc(100vh - 300px); min-height: 200px; }
.tk-col-h { padding: 10px 12px; font-weight: 700; display: flex; align-items: center; gap: 8px; border-top: 4px solid; border-radius: 10px 10px 0 0; }
.tk-col-h .n { margin-left: auto; background: #fff; border-radius: 10px; padding: 0 8px; font-size: 12px; color: #475467; }
.tk-col-b { padding: 6px 8px 10px; overflow-y: auto; flex: 1; min-height: 60px; }
.tk-col.drop-ok .tk-col-b { background: #e0eaff; border-radius: 0 0 10px 10px; }
.tk-ph { height: 46px; border: 2px dashed #2e90fa; border-radius: 8px; background: #f5faff; margin-bottom: 8px; }
.tk-col.drop-no { opacity: .45; }
.tk-card { background: #fff; border: 1px solid #eaecf0; border-left: 4px solid #d0d5dd; border-radius: 8px; padding: 9px 10px; margin-bottom: 8px; cursor: pointer; box-shadow: 0 1px 2px rgba(16,24,40,.05); }
.tk-card:hover { border-color: #84adff; }
.tk-card.dragging { opacity: .5; }
.tk-card .k { font-size: 11px; color: #667085; font-weight: 600; }
.tk-card .t { font-weight: 600; color: #101828; margin: 2px 0 6px; word-break: break-word; }
.tk-card .m { display: flex; flex-wrap: wrap; gap: 5px; align-items: center; font-size: 11px; color: #475467; }
.tk-pill { display: inline-block; border-radius: 10px; padding: 1px 8px; font-size: 11px; font-weight: 600; background: #f2f4f7; color: #344054; white-space: nowrap; }
.p-low { background: #f2f4f7; color: #475467; } .p-medium { background: #eff8ff; color: #175cd3; }
.p-high { background: #fff6ed; color: #c4320a; } .p-critical { background: #fef3f2; color: #b42318; }
.tk-card.pr-critical { border-left-color: #d92d20; } .tk-card.pr-high { border-left-color: #f79009; } .tk-card.pr-medium { border-left-color: #2e90fa; }
.tk-due.over { color: #b42318; font-weight: 700; } .tk-due.today { color: #b54708; font-weight: 700; }
.tk-av { display: inline-flex; width: 22px; height: 22px; border-radius: 50%; background: #d1e0ff; color: #1d4ed8; font-size: 10px; font-weight: 700; align-items: center; justify-content: center; }
.tk-empty { color: #98a2b3; text-align: center; font-size: 12px; padding: 14px 4px; }
.tk-table { background: #fff; }
.tk-table th { white-space: nowrap; background: #f9fafb; }
.tk-table td { vertical-align: middle !important; }
.tk-table tr.over td { background: #fff8f7; }
.tk-stage { display: inline-block; border-radius: 10px; padding: 2px 9px; font-size: 11px; font-weight: 700; color: #fff; white-space: nowrap; }
/* modal */
.tk-modal-bg { position: fixed; inset: 0; background: rgba(16,24,40,.55); z-index: 10000; display: none; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 40px 12px; }
.tk-modal-bg.tk-open { display: flex; }
.tk-modal { background: #fff; border-radius: 12px; width: 100%; max-width: 980px; box-shadow: 0 20px 40px rgba(0,0,0,.25); }
.tk-modal.sm { max-width: 480px; }
.tk-modal-h { padding: 14px 18px; border-bottom: 1px solid #eaecf0; display: flex; align-items: center; gap: 10px; }
.tk-modal-h h3 { margin: 0; font-size: 18px; flex: 1; word-break: break-word; }
.tk-x { border: 0; background: none; font-size: 26px; line-height: 1; color: #667085; cursor: pointer; }
.tk-modal-b { padding: 16px 18px; }
.tk-modal-f { padding: 12px 18px; border-top: 1px solid #eaecf0; display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
.tk-grid { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 18px; }
.tk-side { background: #f9fafb; border: 1px solid #eaecf0; border-radius: 8px; padding: 12px; font-size: 13px; }
.tk-side dt { color: #667085; font-weight: 600; font-size: 11px; text-transform: uppercase; margin-top: 8px; }
.tk-side dd { margin: 2px 0 0; }
.tk-flowbtns { display: flex; flex-wrap: wrap; gap: 6px; margin: 4px 0 12px; }
.tk-steps { display: flex; gap: 0; margin: 0 0 14px; flex-wrap: wrap; }
.tk-steps span { flex: 1; min-width: 80px; text-align: center; font-size: 11px; font-weight: 700; padding: 5px 2px; background: #f2f4f7; color: #98a2b3; border-right: 2px solid #fff; }
.tk-steps span.done { background: #d1fadf; color: #067647; } .tk-steps span.cur { background: #1570ef; color: #fff; }
.tk-steps span:first-child { border-radius: 6px 0 0 6px; } .tk-steps span:last-child { border-radius: 0 6px 6px 0; border-right: 0; }
.tk-itabs { display: flex; gap: 4px; border-bottom: 1px solid #eaecf0; margin: 14px 0 10px; }
.tk-itabs a { padding: 6px 12px; cursor: pointer; color: #475467; font-weight: 600; border-bottom: 2px solid transparent; text-decoration: none; }
.tk-itabs a.on { color: #1570ef; border-bottom-color: #1570ef; }
.tk-cmt { border: 1px solid #eaecf0; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px; }
.tk-cmt .h { font-size: 12px; color: #667085; margin-bottom: 3px; } .tk-cmt .h b { color: #344054; }
.tk-cmt .b, .tk-desc { white-space: pre-wrap; word-break: break-word; }
.tk-tl { list-style: none; padding: 0 0 0 14px; margin: 0; border-left: 2px solid #eaecf0; }
.tk-tl li { position: relative; padding: 0 0 10px 10px; font-size: 13px; }
.tk-tl li::before { content: ''; position: absolute; left: -21px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: #84adff; border: 2px solid #fff; }
.tk-tl li.ev-status::before { background: #7a5af8; } .tk-tl li.ev-comment::before { background: #98a2b3; } .tk-tl li.ev-created::before { background: #079455; }
.tk-tl .w { color: #667085; font-size: 11px; } .tk-tl .d { color: #475467; font-style: italic; white-space: pre-wrap; }
.tk-note { padding: 8px 10px; border-radius: 6px; font-size: 13px; margin-bottom: 10px; }
.tk-note.hold { background: #fffaeb; border: 1px solid #fedf89; } .tk-note.res { background: #ecfdf3; border: 1px solid #abefc6; }
.tk-toast { position: fixed; right: 18px; bottom: 18px; z-index: 10050; background: #101828; color: #fff; padding: 10px 14px; border-radius: 8px; max-width: 420px; display: none; box-shadow: 0 10px 20px rgba(0,0,0,.2); }
.tk-toast.err { background: #b42318; }
.tk-pager { display: flex; gap: 6px; align-items: center; justify-content: flex-end; margin-top: 8px; }
.tk-req { color: #d92d20; }
.tk-panel { background: #fff; border: 1px solid #eaecf0; border-radius: 8px; padding: 14px 16px; margin-bottom: 14px; }
.tk-panel h4 { margin: 0 0 10px; font-weight: 700; }
.tk-chk { font-weight: normal; margin: 0 10px 0 0; cursor: pointer; }
.tk-grid3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 8px 0; }
.tk-grid3 label { font-weight: 600; font-size: 12px; color: #475467; }
.tk-test { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 10px; }
.tk-ok { color: #067647; font-weight: 600; } .tk-bad { color: #b42318; }
.tk-pbox { border: 1px solid #eaecf0; border-radius: 6px; padding: 8px 10px; background: #fcfcfd; font-size: 13px; word-break: break-word; }
.tk-pbox.chat { background: #f0f7f4; }
.tk-savebar { position: sticky; bottom: 0; z-index: 5; background: #fff; border: 1px solid #d0d5dd; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px; box-shadow: 0 -4px 12px rgba(16,24,40,.08); }
.tk-table code { font-size: 11px; }
.tk-prog { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; font-size: 13px; }
.tk-prog-b { flex: 1; height: 8px; background: #eaecf0; border-radius: 5px; overflow: hidden; }
.tk-prog-b span { display: block; height: 100%; background: #12b76a; }
.tk-sl { list-style: none; padding: 0; margin: 0; }
.tk-si { border: 1px solid #eaecf0; border-radius: 8px; margin-bottom: 6px; background: #fff; }
.tk-si-row { display: flex; align-items: flex-start; gap: 8px; padding: 7px 9px; }
.tk-si-cb { margin-top: 3px !important; width: 16px; height: 16px; cursor: pointer; flex: 0 0 auto; }
.tk-si-main { flex: 1; min-width: 0; }
.tk-si-t { color: #101828; font-weight: 600; cursor: pointer; text-decoration: none; word-break: break-word; }
.tk-si.done .tk-si-t { text-decoration: line-through; color: #667085; } .tk-si.na .tk-si-t { color: #98a2b3; text-decoration: line-through; }
.tk-si-c { font-size: 12px; color: #475467; cursor: pointer; white-space: nowrap; text-decoration: none; }
.tk-si-tools { white-space: nowrap; } .tk-si-tools .btn-xs { padding: 0 4px; }
.tk-si-x { border-top: 1px dashed #eaecf0; padding: 8px 10px 10px 33px; background: #fcfcfd; border-radius: 0 0 8px 8px; }
.tk-sadd { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 10px; }
.tk-sadd .form-control { width: auto; flex: 1 1 140px; }
.tk-sadd input[type=date] { flex: 0 0 140px; }
.tk-cp { font-weight: 600; color: #475467; } .tk-cp.all { color: #067647; }
.tk-tplgrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 12px; }
.tk-tplgrid .tk-panel { margin: 0; }
.tk-tl2 { padding-left: 20px; margin: 0 0 6px; } .tk-tl2 li { padding: 3px 0; border-bottom: 1px solid #f2f4f7; }
.tk-tl2 .tk-si-tools { float: right; }
@media (max-width: 767px) {
  .tk-main { float: none; width: 100%; margin-left: 0; }
  .tk-grid { grid-template-columns: 1fr; }
  .tk-col { flex-basis: 82vw; max-height: none; }
  .tk-wrap { padding: 12px 8px 30px; }
}
</style>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 tk-main">
<div class="tk-wrap">
  <div class="tk-head">
    <div>
      <h2>Open Tasks</h2>
      <div class="tk-sub">Track every task from New to Completed &middot; drag cards between stages, or up / down to set the order &middot; every change is logged</div>
    </div>
    <div class="tk-tabs">
      <a href="admin.php?action=opentasks&amp;view=board" class="<?php echo $view === 'board' ? 'on' : ''; ?>">Board</a>
      <a href="admin.php?action=opentasks&amp;view=list" class="<?php echo $view === 'list' ? 'on' : ''; ?>">List</a>
      <a href="admin.php?action=opentasks&amp;view=activity" class="<?php echo $view === 'activity' ? 'on' : ''; ?>">Activity Log</a>
      <?php if (tk_is_mgr($u)) { ?><a href="admin.php?action=opentasks&amp;view=checklists" class="<?php echo $view === 'checklists' ? 'on' : ''; ?>">Checklists</a><?php } ?>
      <?php if (tk_is_admin($u)) { ?><a href="admin.php?action=opentasks&amp;view=settings" class="<?php echo $view === 'settings' ? 'on' : ''; ?>">Notifications</a><?php } ?>
      <?php if ($u['level'] !== 5) { ?><button type="button" class="btn btn-success" id="tk-new">+ New Task</button><?php } ?>
    </div>
  </div>

  <div class="tk-stats" id="tk-stats"></div>

<?php if ($view === 'board' || $view === 'list') { ?>
  <div class="tk-filters" id="tk-filters">
    <input type="search" class="form-control" name="q" placeholder="Search title, description or TSK-0001">
    <select class="form-control" name="mine">
      <option value="">All tasks I can see</option>
      <option value="assigned">Assigned to me</option>
      <option value="created">Created by me</option>
      <?php if (tk_is_mgr($u)) { ?><option value="managing">I am manager</option><option value="review">Waiting for my approval</option><?php } ?>
    </select>
    <select class="form-control" name="assignee"><option value="">Any assignee</option><option value="none">Unassigned</option></select>
    <select class="form-control" name="manager"><option value="">Any manager</option><option value="none">No manager</option></select>
    <select class="form-control" name="priority"><option value="">Any priority</option></select>
    <select class="form-control" name="category"><option value="">Any category</option></select>
    <select class="form-control" name="due"><option value="">Any due date</option><option value="overdue">Overdue</option><option value="today">Due today</option><option value="week">Due in 7 days</option></select>
    <?php if ($view === 'list') { ?>
    <select class="form-control" name="status"><option value="">All stages</option></select>
    <?php } else { ?>
    <select class="form-control" name="done_days" title="Completed tasks shown on the board"><option value="7">Completed: last 7 days</option><option value="30" selected>Completed: last 30 days</option><option value="90">Completed: last 90 days</option><option value="3650">Completed: all</option></select>
    <label><input type="checkbox" name="show_cancelled" value="1"> Show Cancelled</label>
    <?php } ?>
    <button type="button" class="btn btn-default btn-sm" id="tk-reset">Reset</button>
    <?php if ($view === 'list') { ?><a class="btn btn-default btn-sm" id="tk-export" href="#">Download CSV</a><?php } ?>
    <span class="tk-sub" id="tk-updated" style="margin-left:auto"></span>
  </div>
<?php } ?>

<?php if ($view === 'board') { ?>
  <div class="tk-board" id="tk-board"></div>
<?php } elseif ($view === 'list') { ?>
  <div class="table-responsive"><table class="table table-bordered table-hover tk-table" id="tk-list">
    <thead><tr><th>Task</th><th>Title</th><th>Stage</th><th>Priority</th><th>Assignee</th><th>Manager</th><th>Due</th><th>Created</th><th>Created By</th><th>Updated</th><th>Comments</th></tr></thead>
    <tbody><tr><td colspan="11" class="tk-empty">Loading…</td></tr></tbody>
  </table></div>
<?php } elseif ($view === 'activity') { ?>
  <div class="tk-filters" id="tk-afilters">
    <label>From <input type="date" class="form-control" name="from" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>"></label>
    <label>To <input type="date" class="form-control" name="to" value="<?php echo date('Y-m-d'); ?>"></label>
    <select class="form-control" name="user"><option value="">Any user</option></select>
    <select class="form-control" name="event">
      <option value="">Any action</option><option value="created">Created</option><option value="status">Stage changed</option><option value="assigned">Assignee changed</option>
      <option value="manager">Manager changed</option><option value="priority">Priority changed</option><option value="due_date">Due date changed</option>
      <option value="title">Renamed</option><option value="description">Description edited</option><option value="category">Category changed</option><option value="comment">Comment</option><option value="checklist">Checklist added</option><option value="sub_added">Sub-task added</option><option value="sub_done">Sub-task done</option><option value="sub_na">Sub-task N/A</option><option value="sub_reopened">Sub-task reopened</option><option value="sub_edited">Sub-task edited</option><option value="sub_deleted">Sub-task removed</option><option value="deleted">Deleted</option>
    </select>
    <input type="search" class="form-control" name="task" placeholder="Task # (TSK-0001)">
    <button type="button" class="btn btn-primary btn-sm" id="tk-arun">Apply</button>
    <a class="btn btn-default btn-sm" id="tk-acsv" href="#">Download CSV</a>
    <span class="tk-sub" id="tk-atotal" style="margin-left:auto"></span>
  </div>
  <div class="table-responsive"><table class="table table-bordered table-hover tk-table" id="tk-alog">
    <thead><tr><th>Date / Time</th><th>Task</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
    <tbody><tr><td colspan="5" class="tk-empty">Loading…</td></tr></tbody>
  </table></div>
  <div class="tk-pager" id="tk-apager"></div>
<?php } elseif ($view === 'checklists') { ?>
  <div class="tk-panel"><h4>Checklist templates by category</h4>
    <p class="tk-sub" style="margin:0">When a task is created (or its category is changed), the items of its category are added to the task as sub-tasks.
    <b>Required</b> items must be Done, or marked N/A with a reason, before the task can go to In Review or Completed. Changing a template does not change tasks that already have the checklist.</p></div>
  <div class="tk-tplgrid" id="tk-tpl"><div class="tk-empty">Loading…</div></div>
<?php } else { ?>
  <div id="tk-ns"><div class="tk-empty">Loading notification settings…</div></div>
<?php } ?>
</div>
</div>

<!-- task detail / edit modal -->
<div class="tk-modal-bg" id="tk-m"><div class="tk-modal" role="dialog" aria-modal="true" aria-labelledby="tk-m-title">
  <div class="tk-modal-h"><h3 id="tk-m-title">Task</h3><button type="button" class="tk-x" data-close aria-label="Close">&times;</button></div>
  <div class="tk-modal-b" id="tk-m-body"></div>
  <div class="tk-modal-f" id="tk-m-foot"></div>
</div></div>

<!-- move / reason modal -->
<div class="tk-modal-bg" id="tk-r"><div class="tk-modal sm" role="dialog" aria-modal="true" aria-labelledby="tk-r-title">
  <div class="tk-modal-h"><h3 id="tk-r-title">Move task</h3><button type="button" class="tk-x" data-close aria-label="Close">&times;</button></div>
  <div class="tk-modal-b"><p id="tk-r-text"></p><textarea class="form-control" id="tk-r-reason" rows="4" maxlength="1000"></textarea></div>
  <div class="tk-modal-f"><button type="button" class="btn btn-default" data-close>Cancel</button><button type="button" class="btn btn-primary" id="tk-r-ok">Move</button></div>
</div></div>

<div class="tk-toast" id="tk-toast" role="status" aria-live="polite"></div>

<script>
(function () {
  'use strict';
  var B = <?php echo json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
  var API = 'tasks_ajax.php';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var initials = function (n) { return String(n || '?').split(/\s+/).filter(Boolean).slice(0, 2).map(function (x) { return x[0]; }).join('').toUpperCase(); };
  var tasks = [], cache = {};

  function toast(msg, err) {
    var t = $('#tk-toast'); t.textContent = msg; t.className = 'tk-toast' + (err ? ' err' : ''); t.style.display = 'block';
    clearTimeout(toast.h); toast.h = setTimeout(function () { t.style.display = 'none'; }, err ? 7000 : 3500);
  }
  function get(op, params) {
    var q = new URLSearchParams(params || {}); q.set('op', op); q.set('_', Date.now());
    return fetch(API + '?' + q.toString(), { credentials: 'same-origin' }).then(parse);
  }
  function post(op, data) {
    var f = new FormData(); f.append('op', op); f.append('csrf', B.csrf);
    Object.keys(data || {}).forEach(function (k) { f.append(k, data[k] == null ? '' : data[k]); });
    return fetch(API, { method: 'POST', body: f, credentials: 'same-origin' }).then(parse).then(function (j) {
      if (j.notify > 0) { var g = new FormData(); g.append('op', 'notify_run'); g.append('csrf', B.csrf); fetch(API, { method: 'POST', body: g, credentials: 'same-origin', keepalive: true }).catch(function () {}); }
      return j;
    });
  }
  function parse(r) {
    return r.text().then(function (t) {
      var j; try { j = JSON.parse(t); } catch (e) { throw new Error(r.status === 403 ? 'Your session has expired. Please log in again.' : 'Unexpected server response (' + r.status + ').'); }
      if (!j.ok) { var e2 = new Error(j.error || 'Request failed'); e2.conflict = r.status === 409; throw e2; }
      return j;
    });
  }
  function fail(e) { toast(e.message, true); if (e.conflict) load(); }
  function opts(sel, list, valKey, labKey) { list.forEach(function (x) { var o = document.createElement('option'); o.value = valKey ? x[valKey] : x; o.textContent = labKey ? x[labKey] : x; sel.appendChild(o); }); }
  var managers = B.users.filter(function (x) { return x.level === 1 || x.level === 2; });
  var userLabel = B.users.map(function (x) { return { uid: x.uid, label: x.name + ' (' + (B.levels[x.level] || 'User') + ')' }; });
  var mgrLabel = managers.map(function (x) { return { uid: x.uid, label: x.name + ' (' + (B.levels[x.level] || '') + ')' }; });
  var prioList = Object.keys(B.priorities).map(function (k) { return { k: k, v: B.priorities[k] }; });
  var stageList = Object.keys(B.stages).map(function (k) { return { k: k, v: B.stages[k].label }; });

  /* ---------- stats ---------- */
  function stats(s) {
    var box = $('#tk-stats'); if (!box || !s) return;
    var items = [['open_total', 'Open', '', {}], ['mine', 'Assigned to me', '', { mine: 'assigned' }], ['overdue', 'Overdue', s.overdue ? 'warn' : '', { due: 'overdue' }],
      ['due_today', 'Due today', '', { due: 'today' }], ['unassigned', 'Unassigned', s.unassigned ? 'warn' : '', { assignee: 'none' }],
      ['on_hold', 'On hold', '', { status: 'on_hold' }], ['in_review', 'In review', '', { status: 'in_review' }], ['done_7d', 'Completed (7 days)', '', { status: 'completed' }]];
    box.innerHTML = items.map(function (i) { return '<div class="tk-stat ' + i[2] + '" data-f=\'' + esc(JSON.stringify(i[3])) + '\'><b>' + (s[i[0]] || 0) + '</b><span>' + i[1] + '</span></div>'; }).join('');
    $$('.tk-stat', box).forEach(function (el) {
      el.onclick = function () {
        var f = JSON.parse(el.getAttribute('data-f'));
        if (B.view === 'activity' || (f.status && B.view === 'board')) { var q = new URLSearchParams({ action: 'opentasks', view: 'list' }); Object.keys(f).forEach(function (k) { q.set(k, f[k]); }); location.href = 'admin.php?' + q; return; }
        resetFilters(); Object.keys(f).forEach(function (k) { var c = $('#tk-filters [name=' + k + ']'); if (c) c.value = f[k]; }); load();
      };
    });
  }

  /* ---------- filters ---------- */
  var F = $('#tk-filters');
  function filters() {
    var o = {}; if (!F) return o;
    $$('[name]', F).forEach(function (el) { if (el.type === 'checkbox') { if (el.checked) o[el.name] = 1; } else if (el.value !== '') o[el.name] = el.value; });
    return o;
  }
  function resetFilters() { $$('[name]', F).forEach(function (el) { if (el.type === 'checkbox') el.checked = false; else el.value = el.name === 'done_days' ? '30' : ''; }); }
  if (F) {
    opts($('[name=assignee]', F), userLabel, 'uid', 'label');
    opts($('[name=manager]', F), mgrLabel, 'uid', 'label');
    opts($('[name=priority]', F), prioList, 'k', 'v');
    opts($('[name=category]', F), B.categories);
    if ($('[name=status]', F)) opts($('[name=status]', F), stageList, 'k', 'v');
    var urlq = new URLSearchParams(location.search);
    $$('[name]', F).forEach(function (el) { if (urlq.has(el.name)) { if (el.type === 'checkbox') el.checked = true; else el.value = urlq.get(el.name); } });
    var deb; F.addEventListener('input', function (e) { if (e.target.type === 'search') { clearTimeout(deb); deb = setTimeout(load, 350); } });
    F.addEventListener('change', function (e) { if (e.target.type !== 'search') load(); });
    $('#tk-reset').onclick = function () { resetFilters(); load(); };
    if ($('#tk-export')) $('#tk-export').onclick = function (e) { e.preventDefault(); var q = new URLSearchParams(filters()); q.set('op', 'export'); location.href = API + '?' + q; };
  }

  /* ---------- board / list ---------- */
  function load() {
    if (B.view === 'checklists') { return get('stats').then(function (j) { stats(j.stats); }).catch(function () {}); }
    if (B.view === 'settings') { get('stats').then(function (j) { stats(j.stats); }).catch(function () {}); return loadSettings(); }
    if (B.view === 'activity') { get('stats').then(function (j) { stats(j.stats); }).catch(function () {}); return loadActivity(1); }
    var p = filters(); p.view = B.view;
    return get('board', p).then(function (j) {
      tasks = j.tasks; cache = {}; tasks.forEach(function (t) { cache[t.id] = t; });
      stats(j.stats); $('#tk-updated').textContent = 'Updated ' + j.now;
      if (B.view === 'board') renderBoard(); else renderList();
    }).catch(fail);
  }
  function dueHtml(t) {
    if (!t.due) return '';
    return '<span class="tk-due' + (t.overdue ? ' over' : t.due_today ? ' today' : '') + '" title="Due date">' + (t.overdue ? '⚠ ' : '') + esc(t.due) + '</span>';
  }
  function card(t) {
    var drag = (t.next.length || t.can_reorder) ? ' draggable="true"' : '';
    return '<div class="tk-card pr-' + esc(t.priority) + '" data-id="' + t.id + '"' + drag + ' tabindex="0">' +
      '<div class="k">' + esc(t.key) + ' &middot; ' + esc(t.category) + '</div><div class="t">' + esc(t.title) + '</div>' +
      '<div class="m"><span class="tk-pill p-' + esc(t.priority) + '">' + esc(B.priorities[t.priority] || t.priority) + '</span>' + dueHtml(t) +
      (t.check && t.check[1] ? '<span class="tk-cp' + (t.check[0] === t.check[1] ? ' all' : '') + '" title="Checklist: ' + t.check[0] + ' of ' + t.check[1] + ' done">☑ ' + t.check[0] + '/' + t.check[1] + '</span>' : '') +
      (t.comments ? '<span title="Comments">💬 ' + t.comments + '</span>' : '') +
      (t.status === 'on_hold' && t.hold_reason ? '<span title="' + esc(t.hold_reason) + '">⏸ on hold</span>' : '') +
      '<span style="margin-left:auto" title="Assignee: ' + esc(t.assignee_name || 'Unassigned') + (t.manager_name ? ' | Manager: ' + esc(t.manager_name) : '') + '">' +
      (t.assignee_name ? '<span class="tk-av">' + esc(initials(t.assignee_name)) + '</span>' : '<span class="tk-pill">Unassigned</span>') + '</span></div></div>';
  }
  function renderBoard() {
    var cols = Object.keys(B.stages).filter(function (k) { return B.stages[k].board || (k === 'cancelled' && filters().show_cancelled); });
    $('#tk-board').innerHTML = cols.map(function (k) {
      var list = tasks.filter(function (t) { return t.status === k; });
      return '<div class="tk-col" data-stage="' + k + '"><div class="tk-col-h" style="border-top-color:' + B.stages[k].color + '">' + esc(B.stages[k].label) + '<span class="n">' + list.length + '</span></div>' +
        '<div class="tk-col-b">' + (list.length ? list.map(card).join('') : '<div class="tk-empty">No tasks</div>') + '</div></div>';
    }).join('');
    bindBoard();
  }
  var dragId = null;
  /** drop placeholder: dropPh(columnBody, cardToInsertBefore) shows it, dropPh(null) removes it */
  function dropPh(body, ref) {
    var ph = $('.tk-ph');
    if (!body) { if (ph) ph.remove(); return; }
    if (!ph) { ph = document.createElement('div'); ph.className = 'tk-ph'; }
    var empty = $('.tk-empty', body); if (empty) empty.style.display = 'none';
    if (ref) { if (ph.nextElementSibling !== ref) body.insertBefore(ph, ref); } else if (ph.parentNode !== body || ph.nextElementSibling) body.appendChild(ph);
  }
  function bindBoard() {
    $$('.tk-card').forEach(function (c) {
      c.onclick = function () { openTask(+c.getAttribute('data-id')); };
      c.onkeydown = function (e) { if (e.key === 'Enter') openTask(+c.getAttribute('data-id')); };
      c.addEventListener('dragstart', function (e) {
        dragId = +c.getAttribute('data-id'); c.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', String(dragId));
        var t = cache[dragId];
        $$('.tk-col').forEach(function (col) {
          var s = col.getAttribute('data-stage');
          if (s === t.status) { if (t.can_reorder) col.classList.add('drop-target'); }
          else col.classList.add(t.next.indexOf(s) >= 0 ? 'drop-target' : 'drop-no');
        });
      });
      c.addEventListener('dragend', function () { c.classList.remove('dragging'); dragId = null; dropPh(null); $$('.tk-col').forEach(function (col) { col.classList.remove('drop-ok', 'drop-no', 'drop-target'); }); });
    });
    $$('.tk-col').forEach(function (col) {
      var body = $('.tk-col-b', col);
      col.addEventListener('dragover', function (e) {
        if (!dragId || !col.classList.contains('drop-target')) return;
        e.preventDefault(); col.classList.add('drop-ok');
        // show where the card will land: before the first card whose middle is below the pointer
        var cards = $$('.tk-card', body).filter(function (x) { return +x.getAttribute('data-id') !== dragId; }), ref = null;
        for (var i = 0; i < cards.length; i++) { var r = cards[i].getBoundingClientRect(); if (e.clientY < r.top + r.height / 2) { ref = cards[i]; break; } }
        dropPh(body, ref);
      });
      col.addEventListener('dragleave', function (e) { if (!col.contains(e.relatedTarget)) { col.classList.remove('drop-ok'); dropPh(null); } });
      col.addEventListener('drop', function (e) {
        e.preventDefault(); var id = dragId || +e.dataTransfer.getData('text/plain'); var to = col.getAttribute('data-stage');
        col.classList.remove('drop-ok');
        var ph = $('.tk-ph', body), pos = { after_id: 0, before_id: 0 };
        if (ph) {
          var p = ph.previousElementSibling, n = ph.nextElementSibling;
          while (p && (!p.classList.contains('tk-card') || +p.getAttribute('data-id') === id)) p = p.previousElementSibling;
          while (n && (!n.classList.contains('tk-card') || +n.getAttribute('data-id') === id)) n = n.nextElementSibling;
          pos.after_id = p ? +p.getAttribute('data-id') : 0; pos.before_id = n ? +n.getAttribute('data-id') : 0;
        }
        var card = $('.tk-card[data-id="' + id + '"]');
        if (!id || !cache[id]) { dropPh(null); return; }
        var t = cache[id];
        if (t.status === to) {   // re-order inside the column
          if (!t.can_reorder) { dropPh(null); return; }
          var cur = card.previousElementSibling, curN = card.nextElementSibling;
          if ((ph && (ph === cur || ph === curN))) { dropPh(null); return; }   // dropped where it already was
          if (ph && card) body.insertBefore(card, ph);   // move it on screen right away
          dropPh(null);
          post('reorder', { id: id, status: to, after_id: pos.after_id, before_id: pos.before_id }).then(function () { load(); }).catch(function (e2) { fail(e2); load(); });
          return;
        }
        dropPh(null);
        if (t.next.indexOf(to) < 0) { toast('A task in ' + t.status_label + ' cannot be moved to ' + B.stages[to].label + '.', true); return; }
        moveTask(t, to, null, pos);
      });
    });
  }
  function renderList() {
    var tb = $('#tk-list tbody');
    if (!tasks.length) { tb.innerHTML = '<tr><td colspan="11" class="tk-empty">No tasks match these filters.</td></tr>'; return; }
    tb.innerHTML = tasks.map(function (t) {
      return '<tr class="' + (t.overdue ? 'over' : '') + '" data-id="' + t.id + '" style="cursor:pointer"><td><b>' + esc(t.key) + '</b></td><td>' + esc(t.title) + '<div class="tk-sub">' + esc(t.category) + '</div></td>' +
        '<td><span class="tk-stage" style="background:' + B.stages[t.status].color + '">' + esc(t.status_label) + '</span></td>' +
        '<td><span class="tk-pill p-' + esc(t.priority) + '">' + esc(B.priorities[t.priority]) + '</span></td><td>' + esc(t.assignee_name || '—') + '</td><td>' + esc(t.manager_name || '—') + '</td>' +
        '<td>' + (dueHtml(t) || '—') + '</td><td>' + esc(t.created) + '</td><td>' + esc(t.created_name) + '</td><td>' + esc(t.updated) + '</td><td>' + (t.comments || 0) + '</td></tr>';
    }).join('');
    $$('tr[data-id]', tb).forEach(function (tr) { tr.onclick = function () { openTask(+tr.getAttribute('data-id')); }; });
  }

  /* ---------- stage moves ---------- */
  function needsReason(from, to) { return to === 'on_hold' || to === 'cancelled' || from === 'completed' || from === 'cancelled' || (from === 'in_review' && to === 'in_progress'); }
  function moveLabel(from, to) {
    if (from === 'completed' || from === 'cancelled') return 'Reopen';
    if (from === 'in_review' && to === 'completed') return 'Approve & Complete';
    if (from === 'in_review' && to === 'in_progress') return 'Send back for rework';
    if (to === 'in_review') return 'Submit for review';
    if (to === 'in_progress') return from === 'on_hold' ? 'Resume' : 'Start work';
    if (to === 'on_hold') return 'Put on hold';
    if (to === 'new') return 'Move to New';
    if (to === 'todo') return 'Back to To Do';
    if (to === 'completed') return 'Mark Completed';
    if (to === 'cancelled') return 'Cancel Task';
    return B.stages[to].label;
  }
  function moveTask(t, to, after, pos) {
    var req = needsReason(t.status, to), optional = to === 'completed';
    var go = function (reason) {
      post('move', { id: t.id, version: t.version, to: to, reason: reason || '', after_id: pos ? pos.after_id : 0, before_id: pos ? pos.before_id : 0 }).then(function (j) {
        toast(j.task.key + ' moved to ' + j.task.status_label); closeModal('#tk-r'); load(); if (after) after(j.task);
      }).catch(function (e) { fail(e); if (after && e.conflict) openTask(t.id); });
    };
    if (!req && !optional) return go('');
    $('#tk-r-title').textContent = moveLabel(t.status, to) + ' — ' + t.key;
    $('#tk-r-text').textContent = req ? 'Reason (required). This is saved in the activity log.' : 'Resolution / completion note (optional).';
    $('#tk-r-reason').value = ''; openModal('#tk-r'); setTimeout(function () { $('#tk-r-reason').focus(); }, 50);
    $('#tk-r-ok').textContent = moveLabel(t.status, to);
    $('#tk-r-ok').onclick = function () { var r = $('#tk-r-reason').value.trim(); if (req && !r) { toast('Please enter a reason.', true); return; } go(r); };
  }

  /* ---------- modals ---------- */
  function openModal(sel) { $(sel).classList.add('tk-open'); document.body.style.overflow = 'hidden'; }
  function closeModal(sel) { $(sel).classList.remove('tk-open'); if (!$('.tk-modal-bg.tk-open')) document.body.style.overflow = ''; }
  $$('.tk-modal-bg').forEach(function (bg) {
    bg.addEventListener('click', function (e) { if (e.target === bg || e.target.hasAttribute('data-close')) { closeModal('#' + bg.id); if (bg.id === 'tk-m') clearTaskUrl(); } });
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var m = $$('.tk-modal-bg.tk-open').pop(); if (m) { closeModal('#' + m.id); if (m.id === 'tk-m') clearTaskUrl(); } } });
  function clearTaskUrl() { var q = new URLSearchParams(location.search); if (q.has('task')) { q.delete('task'); history.replaceState(null, '', '?' + q); } }

  function selectHtml(name, list, val, valKey, labKey, empty) {
    return '<select class="form-control" name="' + name + '">' + (empty != null ? '<option value="">' + esc(empty) + '</option>' : '') +
      list.map(function (x) { var v = valKey ? x[valKey] : x, l = labKey ? x[labKey] : x; return '<option value="' + esc(v) + '"' + (String(v) === String(val == null ? '' : val) ? ' selected' : '') + '>' + esc(l) + '</option>'; }).join('') + '</select>';
  }
  function formHtml(t) {
    t = t || { title: '', description: '', category: 'General', priority: 'medium', due_date: '', assignee_uid: '', manager_uid: '' };
    var dis = t.id && !t.can_manage ? ' disabled' : '';
    return '<form id="tk-form" autocomplete="off"><fieldset' + dis + '>' +
      '<div class="form-group"><label>Title <span class="tk-req">*</span></label><input class="form-control" name="title" maxlength="200" required value="' + esc(t.title) + '"></div>' +
      '<div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="5" maxlength="20000">' + esc(t.description) + '</textarea></div>' +
      '<div class="row"><div class="col-sm-6 form-group"><label>Assign to</label>' + selectHtml('assignee_uid', userLabel, t.assignee_uid, 'uid', 'label', '— Unassigned —') + '</div>' +
      '<div class="col-sm-6 form-group"><label>Manager (owner / approver)</label>' + selectHtml('manager_uid', mgrLabel, t.manager_uid, 'uid', 'label', '— No manager —') + '</div></div>' +
      '<div class="row"><div class="col-sm-4 form-group"><label>Priority</label>' + selectHtml('priority', prioList, t.priority, 'k', 'v') + '</div>' +
      '<div class="col-sm-4 form-group"><label>Category</label>' + selectHtml('category', B.categories, t.category) + '</div>' +
      '<div class="col-sm-4 form-group"><label>Due date</label><input type="date" class="form-control" name="due_date" value="' + esc(t.due_date) + '"></div></div>' +
      '</fieldset></form>';
  }
  function formData() { var o = {}; $$('#tk-form [name]').forEach(function (el) { o[el.name] = el.value; }); return o; }

  function newTask() {
    $('#tk-m-title').textContent = 'New Task';
    $('#tk-m-body').innerHTML = formHtml(null) + '<p class="tk-sub" id="tk-cl-hint"></p><p class="tk-sub">A task with an assignee starts in <b>To Do</b>; without one it starts in <b>New</b>. If a manager is set, the manager approves the work in <b>In Review</b> before it is Completed.</p>';
    $('#tk-m-foot').innerHTML = '<button type="button" class="btn btn-default" data-close>Cancel</button><button type="button" class="btn btn-success" id="tk-save">Create Task</button>';
    openModal('#tk-m'); setTimeout(function () { $('#tk-form [name=title]').focus(); }, 50);
    var hint = function () { var c = $('#tk-form [name=category]').value, n = (TPL[c] || []).length; $('#tk-cl-hint').innerHTML = n ? '☑ The <b>' + esc(c) + '</b> checklist (' + n + ' item' + (n > 1 ? 's' : '') + ') will be added as sub-tasks.' : ''; };
    $('#tk-form [name=category]').onchange = hint; hint();
    $('#tk-save').onclick = function () {
      var d = formData(); if (!d.title.trim()) { toast('Title is required.', true); return; }
      this.disabled = true; var b = this;
      post('create', d).then(function (j) { toast(j.task.key + ' created'); closeModal('#tk-m'); load(); }).catch(fail).then(function () { b.disabled = false; });
    };
  }
  if ($('#tk-new')) $('#tk-new').onclick = newTask;

  function stepsHtml(t) {
    var order = ['new', 'todo', 'in_progress', 'in_review', 'completed'];
    if (t.status === 'cancelled') return '<div class="tk-note hold"><b>Cancelled</b> ' + esc(t.cancelled) + (t.resolution ? ' — ' + esc(t.resolution) : '') + '</div>';
    var cur = t.status === 'on_hold' ? 'in_progress' : t.status, ci = order.indexOf(cur);
    return '<div class="tk-steps">' + order.map(function (s, i) { return '<span class="' + (i < ci ? 'done' : i === ci ? 'cur' : '') + '">' + esc(B.stages[s].label) + (s === 'in_progress' && t.status === 'on_hold' ? ' (On Hold)' : '') + '</span>'; }).join('') + '</div>';
  }
  function openTask(id, tab) {
    var q = new URLSearchParams(location.search); q.set('task', id); history.replaceState(null, '', '?' + q);
    get('get', { id: id }).then(function (j) {
      var t = j.task; cache[t.id] = t;
      $('#tk-m-title').innerHTML = '<span class="tk-sub">' + esc(t.key) + '</span> ' + esc(t.title);
      var flow = t.next.map(function (to) {
        var cls = to === 'completed' ? 'btn-success' : to === 'cancelled' ? 'btn-danger' : to === 'on_hold' ? 'btn-warning' : to === 'in_review' ? 'btn-info' : 'btn-default';
        return '<button type="button" class="btn btn-sm ' + cls + '" data-to="' + to + '">' + esc(moveLabel(t.status, to)) + '</button>';
      }).join('');
      var waiting = t.status === 'in_review' && t.next.indexOf('completed') < 0 ? '<div class="tk-sub">Waiting for ' + esc(t.manager_name || 'the manager') + ' to approve.</div>' : '';
      var side = '<dl class="tk-side">' +
        '<dt>Stage</dt><dd><span class="tk-stage" style="background:' + B.stages[t.status].color + '">' + esc(t.status_label) + '</span></dd>' +
        '<dt>Assignee</dt><dd>' + esc(t.assignee_name || 'Unassigned') + '</dd><dt>Manager</dt><dd>' + esc(t.manager_name || '—') + '</dd>' +
        '<dt>Priority</dt><dd><span class="tk-pill p-' + esc(t.priority) + '">' + esc(B.priorities[t.priority]) + '</span></dd>' +
        '<dt>Due</dt><dd>' + (dueHtml(t) || '—') + '</dd><dt>Category</dt><dd>' + esc(t.category) + '</dd>' +
        '<dt>Created</dt><dd>' + esc(t.created) + ' by ' + esc(t.created_name) + '</dd>' +
        (t.assigned ? '<dt>Assigned</dt><dd>' + esc(t.assigned) + '</dd>' : '') + (t.started ? '<dt>Work started</dt><dd>' + esc(t.started) + '</dd>' : '') +
        (t.review ? '<dt>Sent for review</dt><dd>' + esc(t.review) + '</dd>' : '') + (t.completed ? '<dt>Completed</dt><dd>' + esc(t.completed) + ' by ' + esc(t.completed_by) + '</dd>' : '') +
        '<dt>Last updated</dt><dd>' + esc(t.updated) + '</dd><dt>Age</dt><dd>' + t.age_days + ' day(s)</dd></dl>';
      var notes = (t.status === 'on_hold' && t.hold_reason ? '<div class="tk-note hold"><b>On hold:</b> ' + esc(t.hold_reason) + '</div>' : '') +
        (t.status === 'completed' && t.resolution ? '<div class="tk-note res"><b>Resolution:</b> ' + esc(t.resolution) + '</div>' : '');
      var cm = j.comments.length ? j.comments.map(function (c) { return '<div class="tk-cmt"><div class="h"><b>' + esc(c.by) + '</b> · ' + esc(c.at) + (c.subtask ? ' · <span class="tk-pill">☑ ' + esc(c.subtask) + '</span>' : '') + '</div><div class="b">' + esc(c.body) + '</div></div>'; }).join('') : '<div class="tk-empty">No comments yet.</div>';
      var about = j.subtasks.length ? '<select class="form-control input-sm" id="tk-cabout" style="margin-bottom:6px"><option value="">About: the whole task</option>' + j.subtasks.map(function (x) { return '<option value="' + x.id + '">About sub-task: ' + esc(x.title) + '</option>'; }).join('') + '</select>' : '';
      var ck = t.check || [0, 0];
      var tl = '<ul class="tk-tl">' + j.activity.map(function (a) { return '<li class="ev-' + esc(a.event) + '"><b>' + esc(a.by) + '</b> ' + esc(a.text) + ' <span class="w">' + esc(a.at) + '</span>' + (a.detail && a.event !== 'created' ? '<div class="d">' + esc(a.detail) + '</div>' : '') + '</li>'; }).join('') + '</ul>';
      $('#tk-m-body').innerHTML = stepsHtml(t) + notes + '<div class="tk-grid"><div>' +
        (flow ? '<div class="tk-flowbtns">' + flow + '</div>' : '') + waiting +
        '<div class="tk-itabs"><a data-tab="checklist" class="on">Checklist (' + ck[0] + '/' + ck[1] + ')</a><a data-tab="details">Details</a><a data-tab="comments">Comments (' + j.comments.length + ')</a><a data-tab="activity">Activity (' + j.activity.length + ')</a></div>' +
        '<div data-pane="checklist">' + checklistHtml(t, j) + '</div>' +
        '<div data-pane="details" style="display:none">' + formHtml(t) + '</div>' +
        '<div data-pane="comments" style="display:none">' + cm + about + '<textarea class="form-control" id="tk-cbody" rows="3" maxlength="5000" placeholder="Write a comment… (visible to everyone on this task)"></textarea><div style="margin-top:6px;text-align:right"><button type="button" class="btn btn-primary btn-sm" id="tk-cadd">Add Comment</button></div></div>' +
        '<div data-pane="activity" style="display:none">' + tl + '</div>' +
        '</div><div>' + side + '</div></div>';
      $('#tk-m-foot').innerHTML = (t.can_delete ? '<button type="button" class="btn btn-link text-danger" id="tk-del" style="margin-right:auto;color:#b42318">Delete task</button>' : '') +
        '<button type="button" class="btn btn-default" data-close>Close</button>' + (t.can_manage ? '<button type="button" class="btn btn-primary" id="tk-save">Save Changes</button>' : '');
      $$('#tk-m-body .tk-itabs a').forEach(function (a) { a.onclick = function () { showTab(a.getAttribute('data-tab')); }; });
      function showTab(name) { $$('#tk-m-body .tk-itabs a').forEach(function (x) { x.classList.toggle('on', x.getAttribute('data-tab') === name); }); $$('#tk-m-body [data-pane]').forEach(function (p) { p.style.display = p.getAttribute('data-pane') === name ? '' : 'none'; }); }
      if (tab) showTab(tab);
      bindChecklist(t, j);
      $$('#tk-m-body [data-to]').forEach(function (b) { b.onclick = function () { moveTask(t, b.getAttribute('data-to'), function () { openTask(t.id); }); }; });
      $('#tk-cadd').onclick = function () {
        var body = $('#tk-cbody').value.trim(); if (!body) { toast('Write a comment first.', true); return; }
        this.disabled = true;
        post('comment', { id: t.id, body: body, subtask_id: $('#tk-cabout') ? $('#tk-cabout').value : '' }).then(function () { toast('Comment added'); openTask(t.id, 'comments'); load(); }).catch(fail);
      };
      if ($('#tk-save')) $('#tk-save').onclick = function () {
        var d = formData(); d.id = t.id; d.version = t.version; if (!d.title.trim()) { toast('Title is required.', true); return; }
        this.disabled = true; var b = this;
        post('update', d).then(function (r) { toast(r.task.key + ' saved'); load(); openTask(t.id); }).catch(function (e) { fail(e); b.disabled = false; if (e.conflict) openTask(t.id); });
      };
      if ($('#tk-del')) $('#tk-del').onclick = function () {
        if (!window.confirm('Delete ' + t.key + '? It will be removed from the board (the activity log keeps the record).')) return;
        post('delete', { id: t.id }).then(function () { toast(t.key + ' deleted'); closeModal('#tk-m'); clearTaskUrl(); load(); }).catch(fail);
      };
      openModal('#tk-m');
    }).catch(function (e) { fail(e); clearTaskUrl(); });
  }


  /* ---------- checklist / sub-tasks (inside a task) ---------- */
  var TPL = {}, openSub = null;
  get('tpl_list').then(function (j) { TPL = j.templates; if (B.view === 'checklists') renderTpl(j); }).catch(function () {});
  function checklistHtml(t, j) {
    var subs = j.subtasks, ck = t.check || [0, 0], pct = ck[1] ? Math.round(ck[0] * 100 / ck[1]) : 0;
    var h = ck[1] ? '<div class="tk-prog"><div class="tk-prog-b"><span style="width:' + pct + '%"></span></div><b>' + ck[0] + ' of ' + ck[1] + ' done</b> <span class="tk-sub">(' + pct + '%)</span></div>' : '';
    if (j.tpl_missing && j.can_add_sub) h += '<div class="tk-note" style="background:#eff8ff;border:1px solid #b2ddff">The <b>' + esc(t.category) + '</b> checklist has ' + j.tpl_missing + ' item(s) not on this task. <button type="button" class="btn btn-xs btn-primary" id="tk-cl-apply">Add them</button></div>';
    h += subs.length ? '<ul class="tk-sl">' + subs.map(function (x, i) {
      var cls = x.status === 'done' ? ' done' : x.status === 'na' ? ' na' : '';
      var meta = [];
      if (x.assignee_name) meta.push('👤 ' + esc(x.assignee_name));
      if (x.due) meta.push('<span class="tk-due' + (x.overdue ? ' over' : '') + '">📅 ' + esc(x.due) + '</span>');
      if (x.status === 'done') meta.push('✔ ' + esc(x.done_by) + ', ' + esc(x.done_at));
      if (x.status === 'na') meta.push('N/A by ' + esc(x.done_by) + ': ' + esc(x.note));
      var tools = x.can_edit ? '<button type="button" class="btn btn-link btn-xs" data-sedit="' + x.id + '" title="Edit">Edit</button>' +
          (x.status === 'open' ? '<button type="button" class="btn btn-link btn-xs" data-sna="' + x.id + '" title="Not applicable">N/A</button>' : '') +
          (i > 0 ? '<button type="button" class="btn btn-link btn-xs" data-sup="' + x.id + '" title="Move up">↑</button>' : '') +
          (i < subs.length - 1 ? '<button type="button" class="btn btn-link btn-xs" data-sdown="' + x.id + '" title="Move down">↓</button>' : '') : '';
      if (x.can_delete) tools += '<button type="button" class="btn btn-link btn-xs" data-sdel="' + x.id + '" title="Remove" style="color:#b42318">✕</button>';
      var cms = j.comments.filter(function (c) { return c.subtask_id === x.id; });
      var acts = j.activity.filter(function (a) { return a.subtask_id === x.id && a.event !== 'comment'; });
      return '<li class="tk-si' + cls + '" data-sid="' + x.id + '"><div class="tk-si-row">' +
        '<input type="checkbox" class="tk-si-cb" data-scb="' + x.id + '"' + (x.status !== 'open' ? ' checked' : '') + (x.can_edit ? '' : ' disabled') + ' title="' + (x.status === 'open' ? 'Mark done' : 'Reopen') + '">' +
        '<div class="tk-si-main"><a class="tk-si-t" data-sopen="' + x.id + '">' + esc(x.title) + '</a>' + (x.required ? ' <span class="tk-pill p-high">Required</span>' : '') + (x.status === 'na' ? ' <span class="tk-pill">N/A</span>' : '') +
        (meta.length ? '<div class="tk-sub">' + meta.join(' &middot; ') + '</div>' : '') + '</div>' +
        '<a class="tk-si-c" data-sopen="' + x.id + '" title="Notes & comments">💬 ' + x.comments + '</a><span class="tk-si-tools">' + tools + '</span></div>' +
        '<div class="tk-si-x" style="' + (openSub === x.id ? '' : 'display:none') + '">' +
        (cms.length ? cms.map(function (c) { return '<div class="tk-cmt"><div class="h"><b>' + esc(c.by) + '</b> · ' + esc(c.at) + '</div><div class="b">' + esc(c.body) + '</div></div>'; }).join('') : '<div class="tk-sub" style="margin-bottom:6px">No notes yet — record what was done for this step.</div>') +
        (acts.length ? '<ul class="tk-tl" style="margin:6px 0 8px">' + acts.map(function (a) { return '<li class="ev-status"><b>' + esc(a.by) + '</b> ' + esc(a.text) + ' <span class="w">' + esc(a.at) + '</span>' + (a.detail ? '<div class="d">' + esc(a.detail) + '</div>' : '') + '</li>'; }).join('') + '</ul>' : '') +
        '<textarea class="form-control input-sm" rows="2" maxlength="5000" data-scmt="' + x.id + '" placeholder="Add a note / comment about this sub-task…"></textarea>' +
        '<div style="text-align:right;margin-top:4px"><button type="button" class="btn btn-primary btn-xs" data-scadd="' + x.id + '">Add Note</button></div></div></li>';
    }).join('') + '</ul>' : '<div class="tk-empty">No sub-tasks yet. Add the steps needed to finish this task.</div>';
    if (j.can_add_sub) h += '<div class="tk-sadd"><input class="form-control input-sm" id="tk-snew" maxlength="250" placeholder="Add a sub-task / activity…">' +
      selectHtml('s_assignee', userLabel, '', 'uid', 'label', 'Assignee (optional)').replace('class="form-control"', 'class="form-control input-sm" id="tk-sassignee"') +
      '<input type="date" class="form-control input-sm" id="tk-sdue" title="Due date (optional)">' +
      (t.can_manage ? '<label class="tk-chk"><input type="checkbox" id="tk-sreq"> Required</label>' : '') +
      '<button type="button" class="btn btn-success btn-sm" id="tk-sadd">Add</button></div>';
    else if (t.status === 'completed' || t.status === 'cancelled') h += '<p class="tk-sub" style="margin-top:8px">The task is ' + esc(t.status_label) + ' — reopen it to change the checklist.</p>';
    return h;
  }
  function bindChecklist(t, j) {
    var body = $('#tk-m-body'), again = function (msg) { if (msg) toast(msg); openTask(t.id, 'checklist'); load(); };
    var subById = {}; j.subtasks.forEach(function (x) { subById[x.id] = x; });
    $$('[data-scb]', body).forEach(function (cb) { cb.onchange = function () {
      var x = subById[+cb.getAttribute('data-scb')];
      post('sub_status', { sub_id: x.id, status: x.status === 'open' ? 'done' : 'open' }).then(function () { again(x.status === 'open' ? '"' + x.title + '" done' : '"' + x.title + '" reopened'); }).catch(function (e) { fail(e); cb.checked = !cb.checked; });
    }; });
    $$('[data-sopen]', body).forEach(function (a) { a.onclick = function () { var id = +a.getAttribute('data-sopen'), li = $('li[data-sid="' + id + '"] .tk-si-x', body), show = li.style.display === 'none'; li.style.display = show ? '' : 'none'; openSub = show ? id : null; if (show) $('textarea', li).focus(); }; });
    $$('[data-scadd]', body).forEach(function (b) { b.onclick = function () {
      var id = +b.getAttribute('data-scadd'), ta = $('[data-scmt="' + id + '"]', body), txt = ta.value.trim(); if (!txt) { toast('Write a note first.', true); return; }
      b.disabled = true; openSub = id; post('comment', { id: t.id, subtask_id: id, body: txt }).then(function () { again('Note added'); }).catch(function (e) { fail(e); b.disabled = false; });
    }; });
    $$('[data-sna]', body).forEach(function (b) { b.onclick = function () {
      var x = subById[+b.getAttribute('data-sna')];
      askReason('Mark "' + x.title + '" as N/A', 'Why is this step not applicable? (required — saved in the activity log)', 'Mark N/A', true, function (r) { return post('sub_status', { sub_id: x.id, status: 'na', note: r }).then(function () { again('Marked N/A'); }); });
    }; });
    $$('[data-sdel]', body).forEach(function (b) { b.onclick = function () { var x = subById[+b.getAttribute('data-sdel')]; if (!window.confirm('Remove sub-task "' + x.title + '"? (The activity log keeps the record.)')) return; post('sub_delete', { sub_id: x.id }).then(function () { again('Sub-task removed'); }).catch(fail); }; });
    $$('[data-sup],[data-sdown]', body).forEach(function (b) { b.onclick = function () { var up = b.hasAttribute('data-sup'); post('sub_move', { sub_id: b.getAttribute(up ? 'data-sup' : 'data-sdown'), dir: up ? 'up' : 'down' }).then(function () { again(); }).catch(fail); }; });
    $$('[data-sedit]', body).forEach(function (b) { b.onclick = function () {
      var x = subById[+b.getAttribute('data-sedit')], row = $('li[data-sid="' + x.id + '"] .tk-si-row', body);
      row.innerHTML = '<div class="tk-sadd" style="margin:0;flex:1"><input class="form-control input-sm" name="e_title" maxlength="250" value="' + esc(x.title) + '">' +
        selectHtml('e_assignee', userLabel, x.assignee_uid, 'uid', 'label', 'No assignee').replace('class="form-control"', 'class="form-control input-sm"') +
        '<input type="date" class="form-control input-sm" name="e_due" value="' + esc(x.due_date) + '">' + (t.can_manage ? '<label class="tk-chk"><input type="checkbox" name="e_req"' + (x.required ? ' checked' : '') + '> Required</label>' : '') +
        '<button type="button" class="btn btn-primary btn-sm" data-esave>Save</button><button type="button" class="btn btn-default btn-sm" data-ecancel>Cancel</button></div>';
      $('[data-ecancel]', row).onclick = function () { again(); };
      $('[data-esave]', row).onclick = function () {
        var d = { sub_id: x.id, title: $('[name=e_title]', row).value, assignee_uid: $('[name=e_assignee]', row).value, due_date: $('[name=e_due]', row).value };
        if ($('[name=e_req]', row)) d.required = $('[name=e_req]', row).checked ? 1 : 0;
        post('sub_update', d).then(function () { again('Sub-task updated'); }).catch(fail);
      };
    }; });
    if ($('#tk-sadd')) {
      var add = function () {
        var title = $('#tk-snew').value.trim(); if (!title) { toast('Type the sub-task first.', true); return; }
        $('#tk-sadd').disabled = true;
        post('sub_add', { id: t.id, title: title, assignee_uid: $('#tk-sassignee').value, due_date: $('#tk-sdue').value, required: $('#tk-sreq') && $('#tk-sreq').checked ? 1 : 0 })
          .then(function () { again('Sub-task added'); setTimeout(function () { if ($('#tk-snew')) $('#tk-snew').focus(); }, 300); }).catch(function (e) { fail(e); $('#tk-sadd').disabled = false; });
      };
      $('#tk-sadd').onclick = add; $('#tk-snew').onkeydown = function (e) { if (e.key === 'Enter') { e.preventDefault(); add(); } };
    }
    if ($('#tk-cl-apply')) $('#tk-cl-apply').onclick = function () { post('checklist_apply', { id: t.id }).then(function (r) { again(r.added + ' checklist item(s) added'); }).catch(fail); };
  }
  /** Small reason dialog (reuses the move dialog). fn(reason) must return a promise. */
  function askReason(title, text, okLabel, required, fn) {
    $('#tk-r-title').textContent = title; $('#tk-r-text').textContent = text; $('#tk-r-reason').value = ''; $('#tk-r-ok').textContent = okLabel;
    openModal('#tk-r'); setTimeout(function () { $('#tk-r-reason').focus(); }, 50);
    $('#tk-r-ok').onclick = function () { var r = $('#tk-r-reason').value.trim(); if (required && !r) { toast('Please enter a reason.', true); return; } fn(r).then(function () { closeModal('#tk-r'); }).catch(fail); };
  }

  /* ---------- checklist templates page ---------- */
  function renderTpl(j) {
    var box = $('#tk-tpl'); if (!box) return;
    box.innerHTML = Object.keys(j.templates).map(function (cat) {
      var items = j.templates[cat];
      return '<div class="tk-panel" data-cat="' + esc(cat) + '"><h4>' + esc(cat) + ' <span class="tk-sub">(' + items.length + ')</span></h4>' +
        (items.length ? '<ol class="tk-tl2">' + items.map(function (x, i) {
          return '<li data-tid="' + x.id + '"><span class="tk-tt">' + esc(x.title) + '</span>' + (x.required ? ' <span class="tk-pill p-high">Required</span>' : '') +
            '<span class="tk-si-tools">' + (i > 0 ? '<button type="button" class="btn btn-link btn-xs" data-tup="' + x.id + '">↑</button>' : '') + (i < items.length - 1 ? '<button type="button" class="btn btn-link btn-xs" data-tdown="' + x.id + '">↓</button>' : '') +
            '<button type="button" class="btn btn-link btn-xs" data-tedit="' + x.id + '">Edit</button><button type="button" class="btn btn-link btn-xs" data-tdel="' + x.id + '" style="color:#b42318">✕</button></span></li>';
        }).join('') + '</ol>' : '<div class="tk-sub" style="margin-bottom:8px">No checklist for this category yet.</div>') +
        '<div class="tk-sadd"><input class="form-control input-sm" maxlength="250" data-tnew placeholder="Add a checklist item…"><label class="tk-chk"><input type="checkbox" data-treq> Required</label><button type="button" class="btn btn-success btn-sm" data-tadd>Add</button></div></div>';
    }).join('');
    var reload = function (msg) { if (msg) toast(msg); get('tpl_list').then(function (r) { TPL = r.templates; renderTpl(r); }).catch(fail); };
    var find = function (id) { var f = null; Object.keys(j.templates).forEach(function (c) { j.templates[c].forEach(function (x) { if (x.id === id) f = x; }); }); return f; };
    $$('[data-tadd]', box).forEach(function (b) {
      var p = b.closest('[data-cat]'), inp = $('[data-tnew]', p);
      var go = function () { var v = inp.value.trim(); if (!v) { toast('Type the checklist item first.', true); return; } post('tpl_save', { category: p.getAttribute('data-cat'), title: v, required: $('[data-treq]', p).checked ? 1 : 0 }).then(function () { reload('Item added'); }).catch(fail); };
      b.onclick = go; inp.onkeydown = function (e) { if (e.key === 'Enter') { e.preventDefault(); go(); } };
    });
    $$('[data-tdel]', box).forEach(function (b) { b.onclick = function () { var x = find(+b.getAttribute('data-tdel')); if (!window.confirm('Remove "' + x.title + '" from the template? Existing tasks keep their copy.')) return; post('tpl_delete', { tpl_id: x.id }).then(function () { reload('Item removed'); }).catch(fail); }; });
    $$('[data-tup],[data-tdown]', box).forEach(function (b) { b.onclick = function () { var up = b.hasAttribute('data-tup'); post('tpl_move', { tpl_id: b.getAttribute(up ? 'data-tup' : 'data-tdown'), dir: up ? 'up' : 'down' }).then(function () { reload(); }).catch(fail); }; });
    $$('[data-tedit]', box).forEach(function (b) { b.onclick = function () {
      var x = find(+b.getAttribute('data-tedit')), li = b.closest('li');
      li.innerHTML = '<div class="tk-sadd" style="margin:0"><input class="form-control input-sm" maxlength="250" value="' + esc(x.title) + '"><label class="tk-chk"><input type="checkbox"' + (x.required ? ' checked' : '') + '> Required</label><button type="button" class="btn btn-primary btn-sm">Save</button><button type="button" class="btn btn-default btn-sm">Cancel</button></div>';
      var bt = $$('button', li); bt[1].onclick = function () { reload(); };
      bt[0].onclick = function () { post('tpl_save', { tpl_id: x.id, title: $('input.form-control', li).value, required: $('input[type=checkbox]', li).checked ? 1 : 0 }).then(function () { reload('Item updated'); }).catch(fail); };
    }; });
  }

  /* ---------- activity log ---------- */
  var A = $('#tk-afilters');
  function aParams(page) { var o = { page: page || 1 }; $$('[name]', A).forEach(function (el) { if (el.value !== '') o[el.name] = el.value; }); return o; }
  function loadActivity(page) {
    return get('activity', aParams(page)).then(function (j) {
      var tb = $('#tk-alog tbody');
      $('#tk-atotal').textContent = j.total + ' action(s) from ' + j.from + ' to ' + j.to;
      tb.innerHTML = j.rows.length ? j.rows.map(function (r) {
        return '<tr><td style="white-space:nowrap">' + esc(r.at) + '</td><td><a href="#" data-id="' + r.task_id + '"><b>' + esc(r.key) + '</b></a> ' + esc(r.title) + '</td><td>' + esc(r.by) + '</td><td>' + esc(r.text) + '</td><td style="white-space:pre-wrap">' + (r.event === 'created' ? '' : esc(r.detail)) + '</td></tr>';
      }).join('') : '<tr><td colspan="5" class="tk-empty">No activity for these filters.</td></tr>';
      $$('a[data-id]', tb).forEach(function (a) { a.onclick = function (e) { e.preventDefault(); openTask(+a.getAttribute('data-id'), 'activity'); }; });
      var pg = $('#tk-apager');
      pg.innerHTML = j.pages > 1 ? '<button class="btn btn-default btn-sm" ' + (j.page <= 1 ? 'disabled' : '') + ' data-p="' + (j.page - 1) + '">‹ Newer</button><span class="tk-sub">Page ' + j.page + ' of ' + j.pages + '</span><button class="btn btn-default btn-sm" ' + (j.page >= j.pages ? 'disabled' : '') + ' data-p="' + (j.page + 1) + '">Older ›</button>' : '';
      $$('button[data-p]', pg).forEach(function (b) { b.onclick = function () { loadActivity(+b.getAttribute('data-p')); }; });
    }).catch(fail);
  }
  if (A) {
    opts($('[name=user]', A), B.users, 'uid', 'name');
    $('#tk-arun').onclick = function () { loadActivity(1); };
    A.addEventListener('change', function () { loadActivity(1); });
    $('#tk-acsv').onclick = function (e) { e.preventDefault(); var q = new URLSearchParams(aParams(1)); q.set('op', 'activity'); q.set('csv', '1'); location.href = API + '?' + q; };
  }

  /* ---------- Notifications settings (admin) ---------- */
  var NS = null;
  function loadSettings() {
    return get('nsettings_get').then(function (j) { NS = j; renderSettings(); loadNlog(''); }).catch(fail);
  }
  function chk(name, on, label, extra) { return '<label class="tk-chk"><input type="checkbox" name="' + name + '"' + (on ? ' checked' : '') + (extra || '') + '> ' + label + '</label>'; }
  function renderSettings() {
    var s = NS.settings, gs = NS.groups, ev = NS.events;
    var groupBoxes = function (name, sel) { return gs.length ? gs.map(function (g) { return chk(name, sel.indexOf(g.id) >= 0, esc(g.name), ' value="' + g.id + '"'); }).join(' ') : '<span class="tk-sub">No active Google Chat groups yet.</span>'; };
    var ph = Object.keys(NS.placeholders).map(function (k) { return '<code title="' + esc(NS.placeholders[k]) + '">' + esc(k) + '</code>'; }).join(' ');
    var rows = Object.keys(ev).map(function (k) {
      var r = s.rules[k];
      return '<tr data-ev="' + k + '"><td><b>' + esc(ev[k]) + '</b></td>' +
        '<td>' + chk('email', r.email, 'Email') + '<div class="tk-sub" style="margin-top:4px">To: ' + ['assignee', 'manager', 'creator'].map(function (w) { return chk('to', r.to.indexOf(w) >= 0, w.charAt(0).toUpperCase() + w.slice(1), ' value="' + w + '"'); }).join(' ') + '</div>' +
        '<input class="form-control input-sm" name="cc" placeholder="Also email (comma separated)" value="' + esc(r.cc || '') + '" style="margin-top:4px"></td>' +
        '<td>' + chk('chat', r.chat_on, 'Google Chat') + '<div class="tk-sub" style="margin-top:4px">Groups (none ticked = default groups):</div><div>' + groupBoxes('groups', r.groups || []) + '</div></td>' +
        '<td><button type="button" class="btn btn-default btn-sm" data-tpl="' + k + '">Edit templates</button></td></tr>' +
        '<tr class="tk-tpl" data-tpl-for="' + k + '" style="display:none"><td colspan="4"><div class="row">' +
        '<div class="col-md-6"><label>Email subject</label><input class="form-control" name="subject" maxlength="300" value="' + esc(r.subject) + '">' +
        '<label style="margin-top:8px">Email body <span class="tk-sub">(**bold**, __underline__)</span></label><textarea class="form-control" name="body" rows="11">' + esc(r.body) + '</textarea></div>' +
        '<div class="col-md-6"><label>Google Chat message <span class="tk-sub">(**bold** or *bold*, _italic_)</span></label><textarea class="form-control" name="chatt" rows="8">' + esc(r.chat) + '</textarea>' +
        '<div style="margin-top:8px"><button type="button" class="btn btn-default btn-sm" data-prev="' + k + '">Preview</button> <button type="button" class="btn btn-link btn-sm" data-reset="' + k + '">Reset to default</button></div>' +
        '<div class="tk-prev" data-prev-for="' + k + '"></div></div></div></td></tr>';
    }).join('');
    $('#tk-ns').innerHTML =
      '<div class="tk-panel"><h4>Email</h4>' + chk('email_enabled', s.email_enabled, '<b>Send email notifications</b>') +
      '<div class="tk-sub" style="margin:8px 0 4px">Send from:</div>' +
      '<label class="tk-chk"><input type="radio" name="smtp_mode" value="shared"' + (s.smtp_mode !== 'custom' ? ' checked' : '') + '> Same Gmail account as Email Distribution ' +
        (NS.shared_smtp.ready ? '<span class="tk-ok">(' + esc(NS.shared_smtp.email) + ')</span>' : '<span class="tk-bad">(not configured yet — <a href="admin.php?action=emaildistribution&amp;page=settings">set it up</a>)</span>') + '</label><br>' +
      '<label class="tk-chk"><input type="radio" name="smtp_mode" value="custom"' + (s.smtp_mode === 'custom' ? ' checked' : '') + '> A separate SMTP account for tasks</label>' +
      '<div id="tk-custom" class="tk-grid3" style="' + (s.smtp_mode === 'custom' ? '' : 'display:none') + '">' +
        '<label>SMTP server<input class="form-control" name="smtp_host" value="' + esc(s.smtp_host) + '"></label>' +
        '<label>Port<input class="form-control" type="number" name="smtp_port" value="' + esc(s.smtp_port) + '"></label>' +
        '<label>Security<select class="form-control" name="smtp_secure">' + [['tls', 'STARTTLS (587)'], ['ssl', 'SSL (465)'], ['none', 'None']].map(function (o) { return '<option value="' + o[0] + '"' + (s.smtp_secure === o[0] ? ' selected' : '') + '>' + o[1] + '</option>'; }).join('') + '</select></label>' +
        '<label>Email / username<input class="form-control" name="smtp_email" value="' + esc(s.smtp_email) + '" autocomplete="off"></label>' +
        '<label>Password' + (s.has_password ? ' <span class="tk-sub">(saved — leave blank to keep)</span>' : '') + '<input class="form-control" type="password" name="smtp_password" autocomplete="new-password" placeholder="' + (s.has_password ? '••••••••' : 'Gmail: use an App Password') + '"></label></div>' +
      '<div class="tk-grid3"><label>From name<input class="form-control" name="smtp_from_name" value="' + esc(s.smtp_from_name) + '"></label>' +
        '<label>Max task emails per hour<input class="form-control" type="number" min="1" max="500" name="email_hourly_limit" value="' + esc(s.email_hourly_limit) + '"></label></div>' +
      chk('skip_actor', s.skip_actor, 'Do not email the person who did the action (they already know)') +
      '<div class="tk-test"><input class="form-control" id="tk-test-to" placeholder="Send a test email to…" style="max-width:280px"><button type="button" class="btn btn-default btn-sm" id="tk-test-email">Send test email</button><span class="tk-sub">Save first — the test uses the saved settings.</span></div></div>' +
      '<div class="tk-panel"><h4>Google Chat</h4>' + chk('chat_enabled', s.chat_enabled, '<b>Post notifications to Google Chat</b>') +
      '<div class="tk-sub" style="margin:8px 0 4px">Default groups for task messages (each event can use its own groups below):</div><div id="tk-dgroups">' + groupBoxes('group_ids', s.group_ids) + '</div>' +
      '<div class="tk-sub" style="margin-top:6px">Groups and their webhooks are managed in <a href="admin.php?action=emaildistribution&amp;page=chatgroups">Email Distribution &gt; Chat Groups</a>.</div>' +
      (gs.length ? '<div class="tk-test"><select class="form-control" id="tk-test-group" style="max-width:260px">' + gs.map(function (g) { return '<option value="' + g.id + '">' + esc(g.name) + '</option>'; }).join('') + '</select><button type="button" class="btn btn-default btn-sm" id="tk-test-chat">Post test message</button></div>' : '') + '</div>' +
      '<div class="tk-panel"><h4>Events, recipients &amp; templates</h4><p class="tk-sub">Placeholders: ' + ph + '</p>' +
      '<div class="table-responsive"><table class="table table-bordered tk-table" id="tk-rules"><thead><tr><th style="width:18%">Event</th><th>Email</th><th>Google Chat</th><th style="width:1%"></th></tr></thead><tbody>' + rows + '</tbody></table></div></div>' +
      '<div class="tk-savebar"><span class="tk-sub">' + (s.updated ? 'Last saved ' + esc(s.updated) + ' by ' + esc(s.updated_by) : 'Not saved yet — notifications are off until you save.') + '</span><button type="button" class="btn btn-primary" id="tk-ns-save">Save Notification Settings</button></div>' +
      '<div class="tk-panel"><h4>Notification log</h4><div class="tk-test" style="margin-top:0"><select class="form-control" id="tk-nl-status" style="max-width:200px"><option value="">All</option><option value="pending">Pending</option><option value="sent">Sent</option><option value="failed">Failed</option><option value="skipped">Skipped</option></select>' +
      '<button type="button" class="btn btn-default btn-sm" id="tk-nl-refresh">Refresh</button><button type="button" class="btn btn-warning btn-sm" id="tk-nl-retry">Retry all failed</button><span class="tk-sub" id="tk-nl-counts"></span></div>' +
      '<div class="table-responsive"><table class="table table-bordered tk-table" id="tk-nlog"><thead><tr><th>Queued</th><th>Task</th><th>Event</th><th>Channel</th><th>To</th><th>Status</th><th>Sent</th><th></th></tr></thead><tbody></tbody></table></div></div>';

    $$('#tk-ns [name=smtp_mode]').forEach(function (r) { r.onchange = function () { $('#tk-custom').style.display = $('#tk-ns [name=smtp_mode]:checked').value === 'custom' ? '' : 'none'; }; });
    $$('#tk-ns [data-tpl]').forEach(function (b) { b.onclick = function () { var tr = $('#tk-ns [data-tpl-for=' + b.getAttribute('data-tpl') + ']'); tr.style.display = tr.style.display === 'none' ? '' : 'none'; }; });
    $$('#tk-ns [data-reset]').forEach(function (b) { b.onclick = function () {
      var k = b.getAttribute('data-reset'), d = NS.defaults[k], tr = $('#tk-ns [data-tpl-for=' + k + ']');
      $('[name=subject]', tr).value = d.subject; $('[name=body]', tr).value = d.body; $('[name=chatt]', tr).value = d.chat; toast('Template reset — click Save to keep it.');
    }; });
    $$('#tk-ns [data-prev]').forEach(function (b) { b.onclick = function () {
      var k = b.getAttribute('data-prev'), tr = $('#tk-ns [data-tpl-for=' + k + ']');
      var sample = { '{recipient}': 'Priya Sharma', '{actor}': 'Satish', '{task_key}': 'TSK-0042', '{title}': 'Follow up Java req with Acme', '{description}': 'Call the client and confirm the rate.', '{priority}': 'High',
        '{category}': 'Client', '{stage}': 'In Review', '{from_stage}': 'In Progress', '{due_date}': '11/10/2026', '{assignee}': 'Priya Sharma', '{manager}': 'Satish', '{creator}': 'Satish',
        '{reason}': 'Client confirmed', '{comment}': 'Spoke to the client today.', '{link}': location.origin + location.pathname + '?action=opentasks&task=42', '{date}': new Date().toLocaleString() };
      var fill = function (t) { return t.replace(/\{[a-z_]+\}/g, function (m) { return sample[m] != null ? sample[m] : m; }); };
      var fmt = function (t) { return esc(t).replace(/\*\*(\S[^\n]*?\S|\S)\*\*/g, '<b>$1</b>').replace(/__(\S[^\n]*?\S|\S)__/g, '<u>$1</u>').replace(/\n/g, '<br>'); };
      var cfmt = function (t) { return esc(t).replace(/\*\*(\S[^\n]*?\S|\S)\*\*/g, '<b>$1</b>').replace(/\*(\S[^\n*]*?\S|\S)\*/g, '<b>$1</b>').replace(/_(\S[^\n_]*?\S|\S)_/g, '<i>$1</i>').replace(/\n/g, '<br>'); };
      $('#tk-ns [data-prev-for=' + k + ']').innerHTML = '<div class="tk-sub" style="margin-top:8px">Email preview</div><div class="tk-pbox"><b>Subject:</b> ' + esc(fill($('[name=subject]', tr).value).replace(/\*\*|__/g, '')) + '<hr style="margin:6px 0">' + fmt(fill($('[name=body]', tr).value)) + '</div>' +
        '<div class="tk-sub" style="margin-top:8px">Google Chat preview</div><div class="tk-pbox chat">' + cfmt(fill($('[name=chatt]', tr).value)) + '</div>';
    }; });
    $('#tk-ns-save').onclick = saveSettings;
    $('#tk-test-email').onclick = function () { var b = this; b.disabled = true; post('notify_test_email', { to: $('#tk-test-to').value.trim() }).then(function (j) { toast(j.result); }).catch(fail).then(function () { b.disabled = false; }); };
    if ($('#tk-test-chat')) $('#tk-test-chat').onclick = function () { var b = this; b.disabled = true; post('notify_test_chat', { group_id: $('#tk-test-group').value }).then(function (j) { toast(j.result); }).catch(fail).then(function () { b.disabled = false; }); };
    $('#tk-nl-refresh').onclick = function () { loadNlog($('#tk-nl-status').value); };
    $('#tk-nl-status').onchange = function () { loadNlog(this.value); };
    $('#tk-nl-retry').onclick = function () { post('notify_retry', {}).then(function (j) { toast(j.requeued + ' re-queued. ' + j.result); loadNlog($('#tk-nl-status').value); }).catch(fail); };
  }
  function saveSettings() {
    var root = $('#tk-ns'), val = function (n) { var el = $('[name=' + n + ']', root); return el ? el.value : ''; }, on = function (n) { var el = $('[name=' + n + ']', root); return el && el.checked ? 1 : 0; };
    var data = { email_enabled: on('email_enabled'), chat_enabled: on('chat_enabled'), smtp_mode: $('[name=smtp_mode]:checked', root).value, smtp_host: val('smtp_host'), smtp_port: val('smtp_port'),
      smtp_secure: val('smtp_secure'), smtp_email: val('smtp_email'), smtp_password: val('smtp_password'), smtp_from_name: val('smtp_from_name'), email_hourly_limit: val('email_hourly_limit'),
      skip_actor: on('skip_actor'), group_ids: $$('#tk-dgroups [name=group_ids]:checked').map(function (x) { return +x.value; }), rules: {} };
    Object.keys(NS.events).forEach(function (k) {
      var tr = $('#tk-ns tr[data-ev=' + k + ']'), tp = $('#tk-ns [data-tpl-for=' + k + ']');
      data.rules[k] = { email: $('[name=email]', tr).checked ? 1 : 0, chat_on: $('[name=chat]', tr).checked ? 1 : 0, cc: $('[name=cc]', tr).value,
        to: $$('[name=to]:checked', tr).map(function (x) { return x.value; }), groups: $$('[name=groups]:checked', tr).map(function (x) { return +x.value; }),
        subject: $('[name=subject]', tp).value, body: $('[name=body]', tp).value, chat_tpl: $('[name=chatt]', tp).value };
    });
    var enc = btoa(unescape(encodeURIComponent(JSON.stringify(data)))).split('').reverse().join('');
    var b = $('#tk-ns-save'); b.disabled = true;
    post('nsettings_save', { payload_x: enc }).then(function () { toast('Notification settings saved'); loadSettings(); }).catch(fail).then(function () { b.disabled = false; });
  }
  function loadNlog(status) {
    get('notify_log', { status: status || '' }).then(function (j) {
      var c = j.counts || {}; $('#tk-nl-counts').textContent = ['pending', 'sent', 'failed', 'skipped'].map(function (k) { return (c[k] || 0) + ' ' + k; }).join(' · ');
      var tb = $('#tk-nlog tbody');
      tb.innerHTML = j.rows.length ? j.rows.map(function (r) {
        var st = { sent: '#079455', failed: '#d92d20', pending: '#1570ef', processing: '#7a5af8', skipped: '#98a2b3' }[r.status] || '#667085';
        return '<tr><td style="white-space:nowrap">' + esc(r.created) + '</td><td><a href="admin.php?action=opentasks&amp;task=' + r.task_id + '"><b>' + esc(r.key) + '</b></a> ' + esc(r.title) + '</td><td>' + esc(r.event) + '<div class="tk-sub">by ' + esc(r.by) + '</div></td><td>' + esc(r.channel) + '</td><td>' + esc(r.to) + '</td>' +
          '<td><span class="tk-stage" style="background:' + st + '">' + esc(r.status) + '</span>' + (r.error ? '<div class="tk-sub" style="color:#b42318;max-width:280px">' + esc(r.error) + '</div>' : '') + (r.attempts > 1 ? '<div class="tk-sub">' + r.attempts + ' attempts</div>' : '') + '</td>' +
          '<td style="white-space:nowrap">' + esc(r.sent) + '</td><td>' + (r.status === 'failed' || r.status === 'skipped' ? '<button type="button" class="btn btn-default btn-xs" data-retry="' + r.id + '">Retry</button>' : '') + '</td></tr>';
      }).join('') : '<tr><td colspan="8" class="tk-empty">No notifications yet.</td></tr>';
      $$('[data-retry]', tb).forEach(function (b) { b.onclick = function () { post('notify_retry', { id: b.getAttribute('data-retry') }).then(function (r) { toast(r.result); loadNlog(status); }).catch(fail); }; });
    }).catch(fail);
  }

  load();
  if (B.openId) openTask(B.openId);
  setInterval(function () { if ((B.view === 'board' || B.view === 'list') && !document.hidden && !$('.tk-modal-bg.tk-open')) load(); }, 60000);   // keep the board fresh
})();
</script>
<?php require __DIR__ . '/includes/footer.php';
