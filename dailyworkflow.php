<?php
/*
 * Daily Workflow (admin.php?action=dailyworkflow)
 * My Day (tick today's activities) · Team (managers/admins) · Activities (manage) · Report · Log
 */
require_once __DIR__ . '/dw_lib.php';
$u = tk_user();
dw_schema();
$isMgr = tk_is_mgr($u);
$views = $isMgr ? ['day', 'team', 'activities', 'report', 'log'] : ['day', 'report', 'log'];
$view = in_array($_GET['view'] ?? '', $views, true) ? $_GET['view'] : 'day';
$people = [];
foreach (dw_visible_uids($u) as $id) $people[] = ['uid' => $id, 'name' => tk_uname($id) . ($id === $u['uid'] ? ' (me)' : ''), 'role' => TK_LEVELS[tk_users()[$id]['level'] ?? 0] ?? '', 'manage' => dw_can_manage($u, $id)];
$boot = ['csrf' => tk_csrf(), 'me' => $u['uid'], 'isMgr' => $isMgr, 'isAdmin' => tk_is_admin($u), 'view' => $view, 'people' => $people,
    'days' => DW_DAYS, 'sections' => DW_SECTIONS, 'today' => date('Y-m-d'), 'uid' => (int)($_GET['uid'] ?? 0) ?: $u['uid'], 'date' => dw_date($_GET['date'] ?? '')];
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/menu.php';
$tab = function ($v, $label) use ($view) { return '<a href="admin.php?action=dailyworkflow&amp;view=' . $v . '" class="' . ($view === $v ? 'on' : '') . '">' . $label . '</a>'; };
?>
<style>
.dw-main { float: right; min-width: 0; }
.dw-wrap { padding: 18px 18px 40px; box-sizing: border-box; max-width: 1400px; }
.dw-wrap *, .dw-wrap *::before, .dw-wrap *::after { box-sizing: border-box; }
.dw-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
.dw-head h2 { margin: 0; font-size: 24px; }
.dw-sub { color: #667085; font-size: 13px; }
.dw-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
.dw-tabs a { padding: 7px 14px; border-radius: 6px; border: 1px solid #d0d5dd; background: #fff; color: #344054; text-decoration: none; font-weight: 600; }
.dw-tabs a.on { background: #1570ef; border-color: #1570ef; color: #fff; }
.dw-bar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; background: #fff; border: 1px solid #eaecf0; border-radius: 8px; padding: 10px; margin-bottom: 14px; }
.dw-bar .form-control { width: auto; height: 32px; padding: 4px 8px; }
.dw-bar label { font-weight: normal; margin: 0; font-size: 13px; }
.dw-panel { background: #fff; border: 1px solid #eaecf0; border-radius: 8px; padding: 14px 16px; margin-bottom: 14px; }
.dw-panel h4 { margin: 0 0 10px; font-weight: 700; }
.dw-sum { display: grid; grid-template-columns: minmax(240px, 1fr) minmax(0, 2fr); gap: 14px; align-items: center; }
.dw-big { font-size: 30px; font-weight: 700; color: #101828; line-height: 1.1; }
.dw-prog { height: 10px; background: #eaecf0; border-radius: 6px; overflow: hidden; margin: 8px 0 4px; display: flex; }
.dw-prog .d { background: #12b76a; } .dw-prog .s { background: #fdb022; }
.dw-week { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; }
.dw-wd { border: 1px solid #eaecf0; border-radius: 8px; padding: 6px; text-align: center; cursor: pointer; background: #fcfcfd; font-size: 12px; }
.dw-wd:hover { border-color: #84adff; } .dw-wd.cur { border-color: #1570ef; box-shadow: 0 0 0 2px #d1e0ff inset; }
.dw-wd b { display: block; font-size: 15px; color: #101828; }
.dw-wd.full b { color: #067647; } .dw-wd.miss b { color: #b42318; } .dw-wd.none b { color: #98a2b3; }
.dw-sec { margin: 16px 0 6px; font-size: 12px; font-weight: 700; color: #475467; text-transform: uppercase; letter-spacing: .05em; }
.dw-item { display: flex; gap: 12px; align-items: flex-start; background: #fff; border: 1px solid #eaecf0; border-left: 4px solid #d0d5dd; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; }
.dw-item.done { border-left-color: #12b76a; background: #f6fef9; } .dw-item.skipped { border-left-color: #fdb022; background: #fffcf5; } .dw-item.overdue { border-left-color: #f04438; }
.dw-cb { width: 22px; height: 22px; margin-top: 1px !important; cursor: pointer; flex: 0 0 auto; }
.dw-cb:disabled { cursor: not-allowed; }
.dw-body { flex: 1; min-width: 0; }
.dw-t { font-weight: 600; color: #101828; font-size: 15px; word-break: break-word; }
.dw-item.done .dw-t { text-decoration: line-through; color: #475467; } .dw-item.skipped .dw-t { color: #667085; }
.dw-d { color: #667085; font-size: 13px; white-space: pre-wrap; margin-top: 2px; }
.dw-m { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 5px; font-size: 12px; color: #475467; }
.dw-pill { display: inline-block; border-radius: 10px; padding: 1px 8px; font-size: 11px; font-weight: 600; background: #f2f4f7; color: #344054; white-space: nowrap; }
.dw-pill.red { background: #fef3f2; color: #b42318; } .dw-pill.amber { background: #fffaeb; color: #b54708; } .dw-pill.green { background: #ecfdf3; color: #067647; } .dw-pill.blue { background: #eff8ff; color: #175cd3; }
.dw-acts { display: flex; gap: 4px; flex-wrap: wrap; align-items: center; }
.dw-acts input[type=number] { width: 80px; height: 28px; }
.dw-note { background: #f9fafb; border-radius: 6px; padding: 5px 8px; margin-top: 6px; font-size: 13px; white-space: pre-wrap; }
.dw-banner { background: #eff8ff; border: 1px solid #b2ddff; border-radius: 8px; padding: 8px 12px; margin-bottom: 12px; font-size: 13px; }
.dw-banner.warn { background: #fffaeb; border-color: #fedf89; }
.dw-empty { color: #98a2b3; text-align: center; padding: 26px 8px; }
.dw-table { background: #fff; } .dw-table th { background: #f9fafb; white-space: nowrap; } .dw-table td { vertical-align: middle !important; }
.dw-cell { display: inline-block; min-width: 44px; border-radius: 6px; padding: 2px 4px; font-weight: 700; font-size: 12px; text-align: center; }
.dw-cell.full { background: #d1fadf; color: #067647; } .dw-cell.part { background: #fef0c7; color: #93370d; } .dw-cell.zero { background: #fee4e2; color: #b42318; } .dw-cell.na { color: #98a2b3; }
.dw-meter { display: inline-block; width: 90px; height: 7px; background: #eaecf0; border-radius: 4px; overflow: hidden; vertical-align: middle; margin-right: 6px; }
.dw-meter span { display: block; height: 100%; background: #12b76a; }
.dw-days label { font-weight: normal; margin-right: 8px; }
.dw-modal-bg { position: fixed; inset: 0; background: rgba(16,24,40,.55); z-index: 10000; display: none; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 50px 12px; }
.dw-modal-bg.dw-open { display: flex; }
.dw-modal { background: #fff; border-radius: 12px; width: 100%; max-width: 620px; box-shadow: 0 20px 40px rgba(0,0,0,.25); }
.dw-modal-h { padding: 14px 18px; border-bottom: 1px solid #eaecf0; display: flex; align-items: center; } .dw-modal-h h3 { margin: 0; font-size: 18px; flex: 1; }
.dw-modal-b { padding: 16px 18px; } .dw-modal-f { padding: 12px 18px; border-top: 1px solid #eaecf0; display: flex; gap: 8px; justify-content: flex-end; }
.dw-x { border: 0; background: none; font-size: 26px; line-height: 1; color: #667085; cursor: pointer; }
.dw-people { max-height: 180px; overflow-y: auto; border: 1px solid #eaecf0; border-radius: 6px; padding: 6px 10px; }
.dw-people label { display: block; font-weight: normal; margin: 2px 0; }
.dw-toast { position: fixed; right: 18px; bottom: 18px; z-index: 10050; background: #101828; color: #fff; padding: 10px 14px; border-radius: 8px; max-width: 420px; display: none; }
.dw-toast.err { background: #b42318; }
.dw-actrow.stopped td { color: #98a2b3; }
@media (max-width: 767px) {
  .dw-main { float: none; width: 100%; margin-left: 0; }
  .dw-sum { grid-template-columns: 1fr; }
  .dw-wrap { padding: 12px 8px 30px; }
  .dw-item { flex-wrap: wrap; }
}
</style>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 dw-main">
<div class="dw-wrap">
  <div class="dw-head">
    <div><h2>Daily Workflow</h2><div class="dw-sub">Your daily routine — tick each activity as you finish it. Every tick is time-stamped.</div></div>
    <div class="dw-tabs">
      <?php echo $tab('day', 'My Day');
      if ($isMgr) echo $tab('team', 'Team') . $tab('activities', 'Activities');
      echo $tab('report', 'Report') . $tab('log', 'Log'); ?>
      <?php if ($isMgr) { ?><button type="button" class="btn btn-success" id="dw-add">+ Add Activity</button><?php } ?>
    </div>
  </div>
  <div id="dw-view"><div class="dw-empty">Loading…</div></div>
</div>
</div>

<div class="dw-modal-bg" id="dw-m"><div class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="dw-m-title">
  <div class="dw-modal-h"><h3 id="dw-m-title"></h3><button type="button" class="dw-x" data-close aria-label="Close">&times;</button></div>
  <div class="dw-modal-b" id="dw-m-body"></div>
  <div class="dw-modal-f" id="dw-m-foot"></div>
</div></div>
<div class="dw-toast" id="dw-toast" role="status" aria-live="polite"></div>

<script>
(function () {
  'use strict';
  var B = <?php echo json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
  var API = 'dw_ajax.php', V = $('#dw-view');
  function $(s, r) { return (r || document).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
  var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  function toast(m, err) { var t = $('#dw-toast'); t.textContent = m; t.className = 'dw-toast' + (err ? ' err' : ''); t.style.display = 'block'; clearTimeout(toast.h); toast.h = setTimeout(function () { t.style.display = 'none'; }, err ? 7000 : 3000); }
  function parse(r) { return r.text().then(function (t) { var j; try { j = JSON.parse(t); } catch (e) { throw new Error(r.status === 403 ? 'Your session has expired. Please log in again.' : 'Unexpected server response (' + r.status + ').'); } if (!j.ok) throw new Error(j.error || 'Request failed'); return j; }); }
  function get(op, p) { var q = new URLSearchParams(p || {}); q.set('op', op); q.set('_', Date.now()); return fetch(API + '?' + q, { credentials: 'same-origin' }).then(parse); }
  function post(op, d) {
    var f = new FormData(); f.append('op', op); f.append('csrf', B.csrf);
    Object.keys(d || {}).forEach(function (k) { var v = d[k]; if (Array.isArray(v)) v.forEach(function (x) { f.append(k + '[]', x); }); else f.append(k, v == null ? '' : v); });
    return fetch(API, { method: 'POST', body: f, credentials: 'same-origin' }).then(parse);
  }
  function fail(e) { toast(e.message, true); }
  function addDays(d, n) { var x = new Date(d + 'T12:00:00'); x.setDate(x.getDate() + n); return x.toISOString().slice(0, 10); }
  function setUrl(p) { var q = new URLSearchParams(location.search); Object.keys(p).forEach(function (k) { if (p[k] === null) q.delete(k); else q.set(k, p[k]); }); history.replaceState(null, '', '?' + q); }
  function peopleSelect(id, val, withAll) {
    return '<select class="form-control" id="' + id + '">' + (withAll ? '<option value="">Everyone I can see</option>' : '') + B.people.map(function (p) { return '<option value="' + p.uid + '"' + (p.uid === val ? ' selected' : '') + '>' + esc(p.name) + (p.role ? ' — ' + esc(p.role) : '') + '</option>'; }).join('') + '</select>';
  }
  function openM(title, body, foot) { $('#dw-m-title').textContent = title; $('#dw-m-body').innerHTML = body; $('#dw-m-foot').innerHTML = foot; $('#dw-m').classList.add('dw-open'); }
  function closeM() { $('#dw-m').classList.remove('dw-open'); }
  $('#dw-m').addEventListener('click', function (e) { if (e.target.id === 'dw-m' || e.target.hasAttribute('data-close')) closeM(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeM(); });

  /* ================= My Day ================= */
  var S = { uid: B.uid, date: B.date };
  function loadDay() {
    setUrl({ view: 'day', uid: S.uid === B.me ? null : S.uid, date: S.date === B.today ? null : S.date });
    return get('day', { uid: S.uid, date: S.date }).then(renderDay).catch(fail);
  }
  function renderDay(j) {
    var pctD = j.total ? j.done * 100 / j.total : 0, pctS = j.total ? j.skipped * 100 / j.total : 0, open = j.total - j.done - j.skipped;
    var bar = '<div class="dw-bar">' + (B.people.length > 1 ? '<label>Person</label>' + peopleSelect('dw-user', j.uid) : '') +
      '<button type="button" class="btn btn-default btn-sm" id="dw-prev">‹ Previous day</button><input type="date" class="form-control" id="dw-date" value="' + j.date + '">' +
      '<button type="button" class="btn btn-default btn-sm" id="dw-next">Next day ›</button>' + (j.is_today ? '' : '<button type="button" class="btn btn-primary btn-sm" id="dw-today">Today</button>') +
      '<span class="dw-sub" style="margin-left:auto">' + esc(j.label) + '</span></div>';
    var banner = '';
    if (!j.own) banner = '<div class="dw-banner">You are viewing <b>' + esc(j.name) + '</b>\'s day (read-only — only they can tick their activities).' + (j.can_manage ? ' <a href="admin.php?action=dailyworkflow&amp;view=activities&amp;uid=' + j.uid + '">Manage their activities</a>' : '') + '</div>';
    else if (j.future) banner = '<div class="dw-banner">This day has not started yet — you can tick these activities on the day.</div>';
    else if (j.too_old) banner = '<div class="dw-banner warn">This day is more than 7 days ago and can no longer be changed.</div>';
    else if (!j.is_today) banner = '<div class="dw-banner warn">You are updating a past day — ticks made now are marked <b>late</b>.</div>';
    var week = '<div class="dw-week">' + j.week.map(function (w) {
      var c = !w.total ? 'none' : (w.done + w.skipped === w.total ? 'full' : (w.date < B.today ? 'miss' : ''));
      return '<div class="dw-wd ' + c + (w.date === j.date ? ' cur' : '') + '" data-date="' + w.date + '">' + esc(w.label) + '<b>' + (w.total ? w.done + '/' + w.total : '–') + '</b></div>';
    }).join('') + '</div>';
    var sum = '<div class="dw-panel dw-sum"><div><div class="dw-big">' + j.done + ' <span class="dw-sub" style="font-size:16px">of ' + j.total + ' done</span></div>' +
      '<div class="dw-prog"><span class="d" style="width:' + pctD + '%"></span><span class="s" style="width:' + pctS + '%"></span></div>' +
      '<div class="dw-sub">' + (j.skipped ? j.skipped + ' skipped · ' : '') + open + ' open' + (j.total && !open ? ' — <b style="color:#067647">all done 🎉</b>' : '') + '</div></div>' +
      '<div><div class="dw-sub" style="margin-bottom:6px">Last 7 days</div>' + week + '</div></div>';
    var list = '';
    if (!j.items.length) list = '<div class="dw-panel dw-empty">No daily activities ' + (j.own ? 'for you' : 'for ' + esc(j.name)) + ' on this day.' + (j.can_manage ? '<br><br><button type="button" class="btn btn-success btn-sm" id="dw-add2">+ Add an activity</button>' : (j.own ? '<br>Your manager adds the activities for your daily workflow.' : '')) + '</div>';
    else {
      var sec = null;
      j.items.forEach(function (it) {
        if (it.section !== sec) { sec = it.section; list += '<div class="dw-sec">' + esc(sec) + '</div>'; }
        var meta = [];
        if (it.due_time) meta.push('<span class="dw-pill ' + (it.overdue ? 'red' : 'blue') + '">' + (it.overdue ? '⚠ overdue · ' : '') + 'by ' + esc(it.due_time) + '</span>');
        else if (it.overdue) meta.push('<span class="dw-pill red">⚠ missed</span>');
        if (it.target) meta.push('<span class="dw-pill">Target ' + it.target + (it.count_done != null ? ' · done ' + it.count_done : '') + '</span>');
        if (it.status === 'done') meta.push('<span class="dw-pill green">✔ ' + esc(it.checked_at) + '</span>' + (it.late ? ' <span class="dw-pill amber">late</span>' : ''));
        if (it.status === 'skipped') meta.push('<span class="dw-pill amber">Skipped ' + esc(it.checked_at) + '</span>');
        if (!it.active) meta.push('<span class="dw-pill">stopped</span>');
        var acts = '';
        if (j.can_check) {
          if (it.target && it.status !== 'skipped') acts += '<input type="number" min="0" class="form-control input-sm" data-count="' + it.id + '" placeholder="Count" value="' + (it.count_done != null ? it.count_done : '') + '" title="How many did you do?">';
          acts += '<button type="button" class="btn btn-link btn-xs" data-note="' + it.id + '">' + (it.note ? 'Edit note' : 'Add note') + '</button>';
          if (it.status === 'open') acts += '<button type="button" class="btn btn-link btn-xs" data-skip="' + it.id + '" style="color:#b54708">Skip</button>';
          if (it.status === 'skipped') acts += '<button type="button" class="btn btn-link btn-xs" data-reopen="' + it.id + '">Undo skip</button>';
        }
        list += '<div class="dw-item ' + (it.status !== 'open' ? it.status : (it.overdue ? 'overdue' : '')) + '">' +
          '<input type="checkbox" class="dw-cb" data-cb="' + it.id + '"' + (it.status === 'done' ? ' checked' : '') + (j.can_check && it.status !== 'skipped' ? '' : ' disabled') + ' aria-label="Done: ' + esc(it.title) + '">' +
          '<div class="dw-body"><div class="dw-t">' + esc(it.title) + '</div>' + (it.details ? '<div class="dw-d">' + esc(it.details) + '</div>' : '') +
          (meta.length ? '<div class="dw-m">' + meta.join(' ') + '</div>' : '') + (it.note ? '<div class="dw-note">📝 ' + esc(it.note) + '</div>' : '') + '</div>' +
          (acts ? '<div class="dw-acts">' + acts + '</div>' : '') + '</div>';
      });
    }
    V.innerHTML = bar + banner + sum + list;
    var items = {}; j.items.forEach(function (x) { items[x.id] = x; });
    var go = function (d) { S.date = d; loadDay(); };
    $('#dw-prev').onclick = function () { go(addDays(j.date, -1)); };
    $('#dw-next').onclick = function () { go(addDays(j.date, 1)); };
    if ($('#dw-today')) $('#dw-today').onclick = function () { go(B.today); };
    $('#dw-date').onchange = function () { if (this.value) go(this.value); };
    if ($('#dw-user')) $('#dw-user').onchange = function () { S.uid = +this.value; loadDay(); };
    $$('.dw-wd').forEach(function (w) { w.onclick = function () { go(w.getAttribute('data-date')); }; });
    if ($('#dw-add2')) $('#dw-add2').onclick = function () { actForm(null, j.uid); };
    var send = function (id, status, note, count) {
      var it = items[id];
      return post('check', { activity_id: id, date: j.date, status: status, note: note != null ? note : it.note, count: count != null ? count : (it.count_done != null ? it.count_done : '') }).then(function () { loadDay(); });
    };
    $$('[data-cb]').forEach(function (cb) { cb.onchange = function () {
      var id = +cb.getAttribute('data-cb'), c = $('[data-count="' + id + '"]');
      send(id, cb.checked ? 'done' : 'open', null, c ? c.value : null).then(function () { toast(cb.checked ? 'Done ✔' : 'Un-ticked'); }).catch(function (e) { fail(e); cb.checked = !cb.checked; });
    }; });
    $$('[data-count]').forEach(function (inp) { inp.onchange = function () { var id = +inp.getAttribute('data-count'); if (items[id].status === 'done') send(id, 'done', null, inp.value).then(function () { toast('Count saved'); }).catch(fail); }; });
    $$('[data-note]').forEach(function (b) { b.onclick = function () {
      var it = items[+b.getAttribute('data-note')];
      openM('Note — ' + it.title, '<textarea class="form-control" id="dw-ntext" rows="4" maxlength="1000" placeholder="What did you do? Any details for your manager…">' + esc(it.note) + '</textarea><p class="dw-sub" style="margin-top:6px">Saving a note on an open activity also marks it done.</p>',
        '<button type="button" class="btn btn-default" data-close>Cancel</button><button type="button" class="btn btn-primary" id="dw-nsave">Save note</button>');
      $('#dw-nsave').onclick = function () { send(it.id, it.status === 'open' ? 'done' : it.status, $('#dw-ntext').value).then(function () { closeM(); toast('Note saved'); }).catch(fail); };
    }; });
    $$('[data-skip]').forEach(function (b) { b.onclick = function () {
      var it = items[+b.getAttribute('data-skip')];
      openM('Skip — ' + it.title, '<p>Why are you skipping this today? (required — your manager will see it)</p><textarea class="form-control" id="dw-stext" rows="3" maxlength="1000"></textarea>',
        '<button type="button" class="btn btn-default" data-close>Cancel</button><button type="button" class="btn btn-warning" id="dw-ssave">Skip activity</button>');
      $('#dw-ssave').onclick = function () { var r = $('#dw-stext').value.trim(); if (!r) { toast('Please give a reason.', true); return; } send(it.id, 'skipped', r).then(function () { closeM(); toast('Skipped'); }).catch(fail); };
    }; });
    $$('[data-reopen]').forEach(function (b) { b.onclick = function () { send(+b.getAttribute('data-reopen'), 'open', '').then(function () { toast('Back to open'); }).catch(fail); }; });
  }

  /* ================= Team ================= */
  var T = { date: B.date };
  function loadTeam() {
    return get('team', { date: T.date }).then(function (j) {
      var rows = j.rows.map(function (r) {
        var pct = r.total ? Math.round((r.done + r.skipped) * 100 / r.total) : null, cls = r.total === 0 ? 'na' : (r.open === 0 ? 'full' : (r.done ? 'part' : 'zero'));
        return '<tr><td><a href="admin.php?action=dailyworkflow&amp;view=day&amp;uid=' + r.uid + '&amp;date=' + j.date + '"><b>' + esc(r.name) + '</b></a>' + (r.me ? ' <span class="dw-pill blue">me</span>' : '') + '<div class="dw-sub">' + esc(r.role) + '</div></td>' +
          '<td><span class="dw-cell ' + cls + '">' + (r.total ? r.done + '/' + r.total : '—') + '</span></td><td>' + (r.skipped || '') + '</td><td>' + (r.total ? (r.open ? '<span style="color:#b42318;font-weight:600">' + r.open + '</span>' : '0') : '') + '</td>' +
          '<td>' + (pct != null ? '<span class="dw-meter"><span style="width:' + pct + '%"></span></span>' + pct + '%' : '') + '</td>' +
          '<td>' + (r.week_pct != null ? r.week_pct + '% <span class="dw-sub">(' + r.week_done + '/' + r.week_total + ')</span>' : '—') + '</td><td>' + r.activities + '</td>' +
          '<td style="white-space:nowrap"><a class="btn btn-default btn-xs" href="admin.php?action=dailyworkflow&amp;view=day&amp;uid=' + r.uid + '&amp;date=' + j.date + '">View day</a> <a class="btn btn-default btn-xs" href="admin.php?action=dailyworkflow&amp;view=activities&amp;uid=' + r.uid + '">Activities</a></td></tr>';
      }).join('');
      V.innerHTML = '<div class="dw-bar"><button type="button" class="btn btn-default btn-sm" id="dw-tprev">‹</button><input type="date" class="form-control" id="dw-tdate" value="' + j.date + '"><button type="button" class="btn btn-default btn-sm" id="dw-tnext">›</button>' +
        '<span class="dw-sub" style="margin-left:auto">' + esc(j.label) + '</span></div>' +
        '<div class="table-responsive"><table class="table table-bordered table-hover dw-table"><thead><tr><th>Person</th><th>Done</th><th>Skipped</th><th>Open</th><th>Today</th><th>Last 7 days</th><th>Activities</th><th></th></tr></thead><tbody>' +
        (rows || '<tr><td colspan="8" class="dw-empty">Nobody has a daily workflow yet. Use <b>+ Add Activity</b>.</td></tr>') + '</tbody></table></div>' +
        (j.without.length ? '<p class="dw-sub">No daily activities yet: ' + j.without.map(function (w) { return '<a href="#" data-addfor="' + w.uid + '">' + esc(w.name) + '</a>'; }).join(', ') + '</p>' : '');
      var go = function (d) { T.date = d; loadTeam(); };
      $('#dw-tprev').onclick = function () { go(addDays(j.date, -1)); }; $('#dw-tnext').onclick = function () { go(addDays(j.date, 1)); };
      $('#dw-tdate').onchange = function () { if (this.value) go(this.value); };
      $$('[data-addfor]').forEach(function (a) { a.onclick = function (e) { e.preventDefault(); actForm(null, +a.getAttribute('data-addfor')); }; });
    }).catch(fail);
  }

  /* ================= Activities (manage) ================= */
  var AU = +(new URLSearchParams(location.search).get('uid') || 0) || B.me;
  function loadActs() {
    setUrl({ view: 'activities', uid: AU });
    return get('acts', { uid: AU }).then(function (j) {
      var person = B.people.filter(function (p) { return p.uid === AU; })[0] || {};
      var rows = j.acts.map(function (a) {
        return '<tr class="dw-actrow' + (a.active ? '' : ' stopped') + '"><td>' + esc(a.section) + '</td><td><b>' + esc(a.title) + '</b>' + (a.details ? '<div class="dw-sub" style="white-space:pre-wrap">' + esc(a.details) + '</div>' : '') + '</td>' +
          '<td>' + esc(a.days_label) + '</td><td>' + esc(a.due_label || '—') + '</td><td>' + (a.target || '—') + '</td>' +
          '<td>' + (a.active ? '<span class="dw-pill green">Active</span>' : '<span class="dw-pill">Stopped ' + esc(a.end) + '</span>') + '<div class="dw-sub">since ' + esc(a.start) + ' · by ' + esc(a.created_by) + '</div></td>' +
          '<td style="white-space:nowrap">' + (a.can_manage ? (a.active ? '<button type="button" class="btn btn-link btn-xs" data-up="' + a.id + '">↑</button><button type="button" class="btn btn-link btn-xs" data-down="' + a.id + '">↓</button><button type="button" class="btn btn-default btn-xs" data-edit="' + a.id + '">Edit</button> <button type="button" class="btn btn-default btn-xs" data-stop="' + a.id + '" style="color:#b42318">Stop</button>'
            : '<button type="button" class="btn btn-default btn-xs" data-resume="' + a.id + '">Restart</button>') : '') + '</td></tr>';
      }).join('');
      V.innerHTML = '<div class="dw-bar"><label>Person</label>' + peopleSelect('dw-auser', AU) +
        (person.manage ? '<button type="button" class="btn btn-success btn-sm" id="dw-aadd">+ Add activity for ' + esc((person.name || '').replace(' (me)', '')) + '</button><button type="button" class="btn btn-default btn-sm" id="dw-copy">Copy this routine to…</button>' : '') +
        '<a class="btn btn-default btn-sm" style="margin-left:auto" href="admin.php?action=dailyworkflow&amp;view=day&amp;uid=' + AU + '">View their day</a></div>' +
        '<div class="table-responsive"><table class="table table-bordered dw-table"><thead><tr><th>Section</th><th>Activity</th><th>Days</th><th>Due by</th><th>Target</th><th>Status</th><th></th></tr></thead><tbody>' +
        (rows || '<tr><td colspan="7" class="dw-empty">No activities yet.</td></tr>') + '</tbody></table></div>' +
        '<p class="dw-sub">Stopping an activity keeps its history; it stops appearing from tomorrow. Edits apply to today and future days.</p>';
      var acts = {}; j.acts.forEach(function (a) { acts[a.id] = a; });
      $('#dw-auser').onchange = function () { AU = +this.value; loadActs(); };
      if ($('#dw-aadd')) $('#dw-aadd').onclick = function () { actForm(null, AU); };
      if ($('#dw-copy')) $('#dw-copy').onclick = function () { copyForm(AU); };
      $$('[data-edit]').forEach(function (b) { b.onclick = function () { actForm(acts[+b.getAttribute('data-edit')], AU); }; });
      $$('[data-stop]').forEach(function (b) { b.onclick = function () { var a = acts[+b.getAttribute('data-stop')]; if (!window.confirm('Stop "' + a.title + '" for ' + a.owner + '? It will no longer appear from tomorrow (history is kept).')) return; post('act_stop', { id: a.id }).then(function () { toast('Stopped'); loadActs(); }).catch(fail); }; });
      $$('[data-resume]').forEach(function (b) { b.onclick = function () { post('act_resume', { id: b.getAttribute('data-resume') }).then(function () { toast('Restarted'); loadActs(); }).catch(fail); }; });
      $$('[data-up],[data-down]').forEach(function (b) { b.onclick = function () { var up = b.hasAttribute('data-up'); post('act_move', { id: b.getAttribute(up ? 'data-up' : 'data-down'), dir: up ? 'up' : 'down' }).then(loadActs).catch(fail); }; });
    }).catch(fail);
  }
  function actForm(a, forUid) {
    var manageable = B.people.filter(function (p) { return p.manage; });
    var days = a ? a.days : [1, 2, 3, 4, 5];
    var body = '<div class="form-group"><label>Activity <span style="color:#d92d20">*</span></label><input class="form-control" id="dw-ftitle" maxlength="250" value="' + esc(a ? a.title : '') + '" placeholder="e.g. Check new requirements and share with the team"></div>' +
      '<div class="form-group"><label>Details / instructions</label><textarea class="form-control" id="dw-fdetails" rows="3" maxlength="5000">' + esc(a ? a.details : '') + '</textarea></div>' +
      '<div class="row"><div class="col-sm-5 form-group"><label>Section</label><select class="form-control" id="dw-fsection">' + B.sections.map(function (s) { return '<option' + ((a ? a.section : 'Other') === s ? ' selected' : '') + '>' + esc(s) + '</option>'; }).join('') + '</select></div>' +
      '<div class="col-sm-4 form-group"><label>Due by (time)</label><input type="time" class="form-control" id="dw-fdue" value="' + esc(a ? a.due_time : '') + '"></div>' +
      '<div class="col-sm-3 form-group"><label>Daily target</label><input type="number" min="1" class="form-control" id="dw-ftarget" value="' + esc(a && a.target ? a.target : '') + '" placeholder="e.g. 10"></div></div>' +
      '<div class="form-group dw-days"><label style="display:block">Repeat on</label>' + Object.keys(B.days).map(function (k) { return '<label><input type="checkbox" name="dw-day" value="' + k + '"' + (days.indexOf(+k) >= 0 ? ' checked' : '') + '> ' + B.days[k] + '</label>'; }).join('') + '</div>' +
      (a ? '<p class="dw-sub">For: <b>' + esc(a.owner) + '</b></p>' :
        '<div class="form-group"><label>Assign to (one copy per person)</label><div class="dw-people">' + manageable.map(function (p) { return '<label><input type="checkbox" name="dw-who" value="' + p.uid + '"' + (p.uid === forUid ? ' checked' : '') + '> ' + esc(p.name) + (p.role ? ' <span class="dw-sub">' + esc(p.role) + '</span>' : '') + '</label>'; }).join('') + '</div>' +
        '<label class="dw-sub" style="font-weight:normal;margin-top:4px"><input type="checkbox" id="dw-fall"> Select everyone</label></div>' +
        '<div class="form-group"><label>Start from</label><input type="date" class="form-control" id="dw-fstart" value="' + B.today + '" style="max-width:180px"></div>');
    openM(a ? 'Edit daily activity' : 'Add daily activity', body, '<button type="button" class="btn btn-default" data-close>Cancel</button><button type="button" class="btn btn-primary" id="dw-fsave">' + (a ? 'Save' : 'Add Activity') + '</button>');
    if ($('#dw-fall')) $('#dw-fall').onchange = function () { var on = this.checked; $$('[name=dw-who]').forEach(function (c) { c.checked = on; }); };
    setTimeout(function () { $('#dw-ftitle').focus(); }, 50);
    $('#dw-fsave').onclick = function () {
      var d = { title: $('#dw-ftitle').value, details: $('#dw-fdetails').value, section: $('#dw-fsection').value, due_time: $('#dw-fdue').value, target: $('#dw-ftarget').value,
        days: $$('[name=dw-day]:checked').map(function (c) { return c.value; }) };
      if (a) d.id = a.id; else { d.uids = $$('[name=dw-who]:checked').map(function (c) { return c.value; }); d.start_date = $('#dw-fstart').value; }
      if (!d.title.trim()) { toast('Activity name is required.', true); return; }
      var b = this; b.disabled = true;
      post('act_save', d).then(function (r) { closeM(); toast(a ? 'Activity saved' : 'Added for ' + r.saved + ' person(s)'); refresh(); }).catch(function (e) { fail(e); b.disabled = false; });
    };
  }
  function copyForm(fromUid) {
    var others = B.people.filter(function (p) { return p.manage && p.uid !== fromUid; });
    openM('Copy routine', '<p>Copy all active activities of <b>' + esc((B.people.filter(function (p) { return p.uid === fromUid; })[0] || {}).name) + '</b> to:</p><div class="dw-people">' +
      others.map(function (p) { return '<label><input type="checkbox" name="dw-cto" value="' + p.uid + '"> ' + esc(p.name) + '</label>'; }).join('') + '</div><p class="dw-sub" style="margin-top:6px">Activities a person already has (same name) are not copied twice.</p>',
      '<button type="button" class="btn btn-default" data-close>Cancel</button><button type="button" class="btn btn-primary" id="dw-csave">Copy</button>');
    $('#dw-csave').onclick = function () { post('act_copy', { from_uid: fromUid, uids: $$('[name=dw-cto]:checked').map(function (c) { return c.value; }) }).then(function (r) { closeM(); toast(r.added + ' activities copied'); }).catch(fail); };
  }

  /* ================= Report ================= */
  var R = { from: addDays(B.today, -13), to: B.today, uid: B.isMgr ? '' : B.me };
  function loadReport() {
    return get('report', R).then(function (j) {
      var head = '<tr><th>Person</th>' + j.days.map(function (d) { return '<th style="text-align:center;font-size:11px">' + d.label + '</th>'; }).join('') + '<th>Done</th><th>Completion</th></tr>';
      var body = j.rows.map(function (r) {
        return '<tr><td style="white-space:nowrap"><b>' + esc(r.name) + '</b></td>' + r.cells.map(function (c, i) {
          var d = j.days[i].date;
          if (!c) return '<td style="text-align:center"><span class="dw-cell na">·</span></td>';
          var fin = c[0] + c[1], cls = fin === c[2] ? 'full' : (d >= j.today ? '' : (c[0] ? 'part' : 'zero'));
          return '<td style="text-align:center"><a href="admin.php?action=dailyworkflow&amp;view=day&amp;uid=' + r.uid + '&amp;date=' + d + '" class="dw-cell ' + cls + '" title="' + c[0] + ' done, ' + c[1] + ' skipped of ' + c[2] + '">' + c[0] + '/' + c[2] + '</a></td>';
        }).join('') + '<td>' + r.done + '/' + r.total + '</td><td style="white-space:nowrap">' + (r.pct != null ? '<span class="dw-meter"><span style="width:' + r.pct + '%"></span></span>' + r.pct + '%' : '—') + '</td></tr>';
      }).join('');
      V.innerHTML = '<div class="dw-bar"><label>From</label><input type="date" class="form-control" id="dw-rfrom" value="' + j.from + '"><label>To</label><input type="date" class="form-control" id="dw-rto" value="' + j.to + '">' +
        (B.people.length > 1 ? peopleSelect('dw-ruser', +R.uid || 0, true) : '') + '<button type="button" class="btn btn-primary btn-sm" id="dw-rgo">Apply</button><a href="#" class="btn btn-default btn-sm" id="dw-rcsv">Download CSV (every activity)</a></div>' +
        '<div class="table-responsive"><table class="table table-bordered dw-table">' + head + (body || '<tr><td colspan="' + (j.days.length + 3) + '" class="dw-empty">No daily activities in this period.</td></tr>') + '</table></div>' +
        '<p class="dw-sub">Cells show done / scheduled for each day (skipped items count as completed in the %). Click a cell to open that day. <span class="dw-cell full">all</span> <span class="dw-cell part">some</span> <span class="dw-cell zero">none</span></p>';
      if ($('#dw-ruser')) $('#dw-ruser').value = R.uid;
      $('#dw-rgo').onclick = function () { R.from = $('#dw-rfrom').value; R.to = $('#dw-rto').value; if ($('#dw-ruser')) R.uid = $('#dw-ruser').value; loadReport(); };
      $('#dw-rcsv').onclick = function (e) { e.preventDefault(); var q = new URLSearchParams({ op: 'report', csv: 1, from: $('#dw-rfrom').value, to: $('#dw-rto').value, uid: $('#dw-ruser') ? $('#dw-ruser').value : R.uid }); location.href = API + '?' + q; };
    }).catch(fail);
  }

  /* ================= Log ================= */
  var L = { from: addDays(B.today, -7), to: B.today, uid: '' };
  function loadLog() {
    return get('log', L).then(function (j) {
      V.innerHTML = '<div class="dw-bar"><label>From</label><input type="date" class="form-control" id="dw-lfrom" value="' + L.from + '"><label>To</label><input type="date" class="form-control" id="dw-lto" value="' + L.to + '">' +
        (B.people.length > 1 ? peopleSelect('dw-luser', 0, true) : '') + '<button type="button" class="btn btn-primary btn-sm" id="dw-lgo">Apply</button></div>' +
        '<div class="table-responsive"><table class="table table-bordered dw-table"><thead><tr><th>Date / Time</th><th>Person</th><th>Done by</th><th>Action</th><th>For day</th><th>Details</th></tr></thead><tbody>' +
        (j.rows.map(function (r) { return '<tr><td style="white-space:nowrap">' + esc(r.at) + '</td><td>' + esc(r.owner) + '</td><td>' + esc(r.by) + '</td><td>' + esc(r.text) + '</td><td>' + esc(r.for_date) + '</td><td>' + esc(r.detail) + '</td></tr>'; }).join('') ||
         '<tr><td colspan="6" class="dw-empty">Nothing logged in this period.</td></tr>') + '</tbody></table></div>';
      if ($('#dw-luser')) $('#dw-luser').value = L.uid;
      $('#dw-lgo').onclick = function () { L.from = $('#dw-lfrom').value; L.to = $('#dw-lto').value; if ($('#dw-luser')) L.uid = $('#dw-luser').value; loadLog(); };
    }).catch(fail);
  }

  function refresh() { ({ day: loadDay, team: loadTeam, activities: loadActs, report: loadReport, log: loadLog })[B.view](); }
  if ($('#dw-add')) $('#dw-add').onclick = function () { actForm(null, B.view === 'activities' ? AU : (B.view === 'day' ? S.uid : B.me)); };
  refresh();
  setInterval(function () { if (B.view === 'day' && !document.hidden && !$('#dw-m.dw-open') && S.date === B.today) loadDay(); }, 120000);
})();
</script>
<?php require __DIR__ . '/includes/footer.php';
