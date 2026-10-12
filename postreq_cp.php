<?php
// Smart Post Requirement: include via admin.php?action=... like the original postrek.php.
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['id'])) { header('Location: admin.php'); exit; }

function spr_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function spr_post($key, $default = '') { return trim((string)($_POST[$key] ?? $default)); }

try {
    $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    $auth = $conn->prepare('SELECT uid, name, level, sess, def_lid FROM users WHERE uid = ?');
    $auth->execute([(int)$_SESSION['id']]);
    $dta = $auth->fetch();
    if (!$dta || !isset($_SESSION['username']) || !hash_equals((string)$dta['sess'], (string)$_SESSION['username']) || !in_array((int)$dta['level'], [1,2], true)) {
        header('Location: login.php'); exit;
    }
    $skills = $conn->query('SELECT sid, skillname FROM skill ORDER BY skillname ASC')->fetchAll();
    $users = ((int)$dta['level'] === 1)
        ? $conn->query('SELECT uid, name FROM users WHERE status = 1 ORDER BY name ASC')->fetchAll()
        : [['uid' => $dta['uid'], 'name' => $dta['name']]];
} catch (PDOException $e) {
    error_log('Smart requirement initialization: '.$e->getMessage());
    http_response_code(500); exit('Unable to load the requirement form.');
}

$sourceOptions = [1=>'Inbox',2=>'Posting',3=>'Cold Calls',4=>'AMC',5=>'Prohires',6=>'Google Groups',7=>'LinkedIn',8=>'Job Portal - Dice',9=>'Job Portal - Techfetch',10=>'Job Portal - SimplyHired',11=>'Job Portal - Careerbuilder',12=>'Job Portal - Ziprecruiter',13=>'Job Portal - Monster',14=>'Job Portal - other',15=>'Company Websites',16=>'I-Labor',18=>'SATS',17=>'Other'];
$jobOptions = [1=>'Contract',2=>'Contract to hire',3=>'W2',4=>'FTE'];
$tierOptions = [1=>'Tier 1',2=>'Tier 2',3=>'IP',4=>'End Client'];
$nationalityOptions = [1=>'American',2=>'Indian'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['spr_csrf']) || !hash_equals($_SESSION['spr_csrf'], $csrf)) $errors[] = 'Session verification failed. Refresh and try again.';
    $jobTitle = spr_post('job_title'); $location = spr_post('rlocation');
    $duration = spr_post('rduration'); $email = strtolower(spr_post('cemail'));
    $description = spr_post('rdesc'); $skillId = (int)spr_post('skillid');
    $jobtype = (int)spr_post('jobtype'); $source = (int)spr_post('req_source');
    $tier = (int)spr_post('ttype'); $nationality = (int)spr_post('nationality');
    $uid = (int)spr_post('uid'); $endClient = spr_post('rend_client');
    $rateRaw = spr_post('rrate');
    if ($jobTitle === '' || $location === '' || $duration === '' || $description === '') $errors[] = 'Job title, location, duration and job description are required.';
    if (strlen($jobTitle) > 50 || strlen($location) > 60 || strlen($duration) > 20 || strlen($endClient) > 30) $errors[] = 'One or more fields exceed the database column length.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 50) $errors[] = 'Enter a valid client contact email (up to 50 characters).';
    if (!in_array($jobtype, array_keys($jobOptions), true) || !in_array($source, array_keys($sourceOptions), true) || !in_array($tier, array_keys($tierOptions), true) || !in_array($nationality, array_keys($nationalityOptions), true)) $errors[] = 'Choose valid dropdown options.';
    if (!in_array($skillId, array_map('intval', array_column($skills, 'sid')), true)) $errors[] = 'Select a valid skill.';
    if (!in_array($uid, array_map('intval', array_column($users, 'uid')), true)) $errors[] = 'Select an authorized SM.';
    if ($rateRaw !== '' && (!ctype_digit($rateRaw) || (float)$rateRaw > 2147483647)) $errors[] = 'Rate must be a whole number within the supported range.';
    if (!$errors) {
        // The existing req.emp_type ENUM only allows C2C, W2 and FTE.
        // Contract-to-hire is distinguished by jobtype=2, with emp_type=C2C.
        $employment = [1=>'C2C',2=>'C2C',3=>'W2',4=>'FTE'][$jobtype];
        try {
            $conn->beginTransaction();
            $clientStmt = $conn->prepare('SELECT cid FROM clients WHERE LOWER(remail) = :email ORDER BY cid ASC LIMIT 1');
            $clientStmt->execute([':email'=>$email]);
            $clientId = $clientStmt->fetchColumn();
            if (!$clientId) {
                $owner = $conn->prepare('SELECT def_lid FROM users WHERE uid = ?');
                $owner->execute([$uid]);
                $lid = $owner->fetchColumn();
                if ($lid === false) throw new RuntimeException('Selected SM does not have a default list.');
                $domain = substr($email, strpos($email, '@') + 1);
                $clientInsert = $conn->prepare("INSERT INTO clients (lid, uid, companyname, rname, rfname, remail, domain, rphone, rlocation, rtimezon, tier, status, filetarget) VALUES (:lid,:uid,NULL,NULL,NULL,:email,:domain,NULL,NULL,NULL,NULL,1,'manual')");
                $clientInsert->execute([':lid'=>(int)$lid, ':uid'=>$uid, ':email'=>$email, ':domain'=>$domain]);
                $clientId = (int)$conn->lastInsertId();
            }
            $reqInsert = $conn->prepare('INSERT INTO req (uid,cid,emp_type,jobtype,job_title,rlocation,rduration,rrate,rend_client,skillid,req_source,ttype,nationality,datetime) VALUES (:uid,:cid,:emp,:jobtype,:title,:location,:duration,:rate,:endclient,:skill,:source,:tier,:nationality,:created)');
            $reqInsert->execute([':uid'=>$uid,':cid'=>(int)$clientId,':emp'=>$employment,':jobtype'=>$jobtype,':title'=>$jobTitle,':location'=>$location,':duration'=>$duration,':rate'=>$rateRaw === '' ? null : (int)$rateRaw,':endclient'=>$endClient,':skill'=>$skillId,':source'=>$source,':tier'=>$tier,':nationality'=>$nationality,':created'=>date('Y-m-d H:i:s')]);
            $reqId = (int)$conn->lastInsertId();
            $jdInsert = $conn->prepare('INSERT INTO jd (reqid,rdesc) VALUES (?,?)');
            $jdInsert->execute([$reqId, $description]);
            $conn->commit();
            unset($_SESSION['spr_csrf']);
            header('Location: admin.php?action=showreqs'); exit;
        } catch (Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            error_log('Smart requirement save: '.$e->getMessage());
            $errors[] = 'Unable to save the requirement. Check the server log or contact the administrator.';
        }
    }
}
if (empty($_SESSION['spr_csrf'])) $_SESSION['spr_csrf'] = bin2hex(random_bytes(32));
require('includes/header.php');
require('includes/menu.php');
?>
<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
  <div class="row"><ol class="breadcrumb"><li><a href="admin.php"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li><li class="active">Smart Post Requirement</li></ol></div>
  <div class="row"><div class="col-lg-12"><div class="panel panel-default"><div class="panel-body">
    <style>
      .spr-paste{width:100%;min-height:190px;resize:vertical;padding:10px;border:1px solid #ccc;border-radius:4px;font-family:inherit}
      .spr-row{margin-bottom:12px}.spr-row td{padding:5px 4px;vertical-align:top}.spr-row label{padding-top:6px}
      .spr-input{max-width:340px;width:100%;padding:7px;border:1px solid #ccc;border-radius:4px}
      .spr-wide{max-width:100%}.spr-note{color:#666;font-size:12px;margin-top:5px}.spr-actions{margin:10px 0 18px}
      .spr-msg{margin-top:8px;color:#555}.spr-label{font-weight:600}.spr-small{max-width:210px}
      @media(max-width:720px){.spr-row td{display:block;width:100%!important}.spr-input{max-width:100%}}
    </style>
    <h4>Paste Job Requirement</h4>
    <p>Paste the complete job posting or recruiter email. Click <strong>Detect Fields</strong>, review the results and select any missing values before saving.</p>
    <?php if ($errors): ?><div class="alert alert-danger" role="alert"><?php foreach ($errors as $err): ?><div><?= spr_h($err) ?></div><?php endforeach; ?></div><?php endif; ?>
    <textarea id="spr_paste" class="spr-paste" placeholder="Paste the job description, including subject, client email, location, duration and rate if available..."><?= spr_h(spr_post('paste_text')) ?></textarea>
    <div class="spr-actions"><button type="button" id="spr_detect" class="btn btn-primary">Detect Fields</button> <button type="button" id="spr_clear" class="btn btn-default">Clear Pasted Text</button><div id="spr_feedback" class="spr-msg" aria-live="polite"></div></div>
    <form method="post" action="" id="spr_form">
      <input type="hidden" name="csrf_token" value="<?= spr_h($_SESSION['spr_csrf']) ?>">
      <input type="hidden" name="paste_text" id="spr_hidden_paste" value="">
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr class="spr-row"><td width="17%"><label for="spr_skill">Skill:</label></td><td width="83%"><select name="skillid" id="spr_skill" class="form-control-in" required><option value="">Select skill</option><?php foreach ($skills as $s): ?><option value="<?= (int)$s['sid'] ?>" <?= spr_post('skillid') === (string)$s['sid'] ? 'selected' : '' ?>><?= spr_h($s['skillname']) ?></option><?php endforeach; ?></select> <?php if ((int)$dta['level'] === 1): ?><a href="admin.php?action=addskill">Add Skill</a><?php endif; ?> &nbsp; <label for="spr_uid">SM:</label> <select name="uid" id="spr_uid" class="form-control-in"><?php foreach ($users as $u): ?><option value="<?= (int)$u['uid'] ?>" <?= (string)$u['uid'] === spr_post('uid', (string)$dta['uid']) ? 'selected' : '' ?>><?= spr_h($u['name']) ?></option><?php endforeach; ?></select><div class="spr-note">Skill is suggested automatically, but you can change it.</div></td></tr>
        <tr class="spr-row"><td><label for="spr_title">Job Title:</label></td><td><input class="spr-input" id="spr_title" name="job_title" maxlength="50" required value="<?= spr_h(spr_post('job_title')) ?>"> &nbsp; <label for="spr_source">Requirement Source:</label> <select name="req_source" id="spr_source" class="form-control-in"><?php foreach ($sourceOptions as $key=>$name): ?><option value="<?= $key ?>" <?= (string)$key === spr_post('req_source','1') ? 'selected' : '' ?>><?= spr_h($name) ?></option><?php endforeach; ?></select></td></tr>
        <tr class="spr-row"><td><label for="spr_location">Location:</label></td><td><input class="spr-input" id="spr_location" name="rlocation" maxlength="60" required value="<?= spr_h(spr_post('rlocation')) ?>"> &nbsp; <label for="spr_duration">Duration:</label> <input class="spr-input spr-small" id="spr_duration" name="rduration" maxlength="20" required value="<?= spr_h(spr_post('rduration')) ?>"></td></tr>
        <tr class="spr-row"><td><label for="spr_jobtype">Job Type:</label></td><td><select name="jobtype" id="spr_jobtype" class="form-control-in"><?php foreach ($jobOptions as $key=>$name): ?><option value="<?= $key ?>" <?= (string)$key === spr_post('jobtype','1') ? 'selected' : '' ?>><?= spr_h($name) ?></option><?php endforeach; ?></select> &nbsp; <label for="spr_email">Email:</label> <span class="search-box" style="display:inline-block;position:relative"><input type="email" class="spr-input" id="spr_email" name="cemail" autocomplete="off" required maxlength="50" value="<?= spr_h(spr_post('cemail')) ?>" placeholder="Recruiter/client email"><span class="result"></span></span></td></tr>
        <tr class="spr-row"><td><label for="spr_rate">Rate ($/hr):</label></td><td><input type="number" min="0" max="2147483647" step="1" class="spr-input spr-small" id="spr_rate" name="rrate" value="<?= spr_h(spr_post('rrate')) ?>"> &nbsp; <label for="spr_client">End Client:</label> <input class="spr-input" id="spr_client" name="rend_client" maxlength="30" value="<?= spr_h(spr_post('rend_client')) ?>"></td></tr>
        <tr class="spr-row"><td><label for="spr_tier">Tier Type:</label></td><td><select name="ttype" id="spr_tier" class="form-control-in"><?php foreach ($tierOptions as $key=>$name): ?><option value="<?= $key ?>" <?= (string)$key === spr_post('ttype','2') ? 'selected' : '' ?>><?= spr_h($name) ?></option><?php endforeach; ?></select> &nbsp; <label for="spr_nationality">Nationality:</label> <select name="nationality" id="spr_nationality" class="form-control-in"><?php foreach ($nationalityOptions as $key=>$name): ?><option value="<?= $key ?>" <?= (string)$key === spr_post('nationality','2') ? 'selected' : '' ?>><?= spr_h($name) ?></option><?php endforeach; ?></select></td></tr>
        <tr class="spr-row"><td><label for="spr_description">Job Description:</label></td><td><textarea id="spr_description" name="rdesc" class="spr-paste" required><?= spr_h(spr_post('rdesc')) ?></textarea><div class="spr-note">Original pasted requirement is copied here. Review and edit before saving.</div></td></tr>
        <tr class="spr-row"><td></td><td><button type="submit" name="save" value="1" class="btn btn-primary">Save Requirement</button></td></tr>
      </table>
    </form>
  </div></div></div></div>
</div>
<?php require('includes/footer.php'); ?>
<script>
(function(){
  'use strict';
  var paste = document.getElementById('spr_paste');
  var feedback = document.getElementById('spr_feedback');
  var fields = {title:document.getElementById('spr_title'),location:document.getElementById('spr_location'),duration:document.getElementById('spr_duration'),email:document.getElementById('spr_email'),rate:document.getElementById('spr_rate'),client:document.getElementById('spr_client'),jobtype:document.getElementById('spr_jobtype'),skill:document.getElementById('spr_skill'),description:document.getElementById('spr_description')};
  function firstMatch(text, patterns){ for(var i=0;i<patterns.length;i++){var m=text.match(patterns[i]);if(m && m[1] && m[1].trim()) return m[1].trim();} return ''; }
  function singleLine(s){return (s||'').split(/\r?\n/)[0].replace(/\s+/g,' ').trim();}
  function setField(el,value){ if(value && el) el.value=value; }
  function detect(){
    var raw=paste.value.trim(); if(!raw){feedback.textContent='Paste a requirement first.';return;}
    var t=raw.replace(/\r/g,'');
    var title=firstMatch(t,[/^(?:job\s*title|position\s*title|position|role|title|requirement)\s*[:\-]\s*([^\n]+)/im,/^subject\s*:\s*(?:re\s*:\s*)?(?:urgent\s*[-:]?\s*)?([^\n]+)/im]);
    if(!title){var heading=t.split('\n').map(function(x){return x.trim();}).find(function(x){return x.length>8 && x.length<90 && /(?:developer|engineer|architect|analyst|administrator|consultant|manager|specialist|lead|tester|scientist|designer|devops|qa|sailpoint)/i.test(x);});title=heading||'';}
    title=singleLine(title).replace(/^(?:urgent|immediate|hot\s+requirement)\s*[-:]\s*/i,'').slice(0,50);
    var loc=singleLine(firstMatch(t,[/^(?:job\s*location|work\s*location|location|onsite\s*location|city|worksite)\s*[:\-]\s*([^\n]+)/im, /\b(?:located\s+in|based\s+in)\s+([A-Za-z][A-Za-z .,-]{2,45})/i]));
    var duration=singleLine(firstMatch(t,[/^(?:duration|contract\s*duration|length\s*of\s*contract|term)\s*[:\-]\s*([^\n]+)/im,/\b(\d{1,2}\s*(?:\+\s*)?(?:months?|mos?|years?|yrs?)(?:\s*(?:\+|extendable|extension))?)\b/i]));
    var rate=firstMatch(t,[/^(?:pay\s*rate|bill\s*rate|rate|hourly\s*rate|budget)\s*[:\-]?\s*\$?\s*(\d{2,4})(?:\s*(?:\/hr|per\s*hour|hourly))?/im,/\$\s*(\d{2,4})\s*(?:\/\s*(?:hr|hour)|per\s*hour)/i]);
    var client=singleLine(firstMatch(t,[/^(?:end\s*client|client\s*name|end\s*customer|customer\s*name)\s*[:\-]\s*([^\n]+)/im]));
    var email=firstMatch(t,[/^(?:contact\s*email|recruiter\s*email|email\s*id|email|contact)\s*[:\-]\s*([^\s\n<>;,]+@[^\s\n<>;,]+)/im, /\b([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})\b/i]);
    var jt=firstMatch(t,[/^(?:employment\s*type|job\s*type|engagement\s*type|position\s*type)\s*[:\-]\s*([^\n]+)/im]);
    var classification=jt||((title+'\n'+t.slice(0,1200)));
    var type=''; if(/contract\s*(?:to|[- ]?2)\s*hire|c2h/i.test(classification))type='2';else if(/\b(?:full[ -]?time|permanent|fte)\b/i.test(classification))type='4';else if(/\bw2\b/i.test(classification))type='3';else if(/\b(?:contract|c2c|corp\s*to\s*corp)\b/i.test(classification))type='1';
    setField(fields.title,title);setField(fields.location,loc.slice(0,60));setField(fields.duration,duration.slice(0,20));setField(fields.rate,rate);setField(fields.client,client.slice(0,30));setField(fields.email,email.slice(0,50));setField(fields.jobtype,type);
    // Match the longest specific skill name first. Require word boundaries to avoid partial terms.
    var options=Array.prototype.slice.call(fields.skill.options).filter(function(o){return o.value;});
    options.sort(function(a,b){return b.text.trim().length-a.text.trim().length;});
    var haystack=(title+'\n'+t).toLowerCase();var chosen='';
    for(var i=0;i<options.length;i++){
      var name=options[i].text.trim().toLowerCase(); if(name.length<2)continue;
      var escaped=name.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
      if(new RegExp('(^|[^a-z0-9])'+escaped+'(?=$|[^a-z0-9])','i').test(haystack)){chosen=options[i].value;break;}
    }
    if(chosen)fields.skill.value=chosen;
    fields.description.value=raw;
    var detected=[title,loc,duration,rate,client,email,type,chosen].filter(Boolean).length;
    feedback.textContent='Detected '+detected+' of 8 fields. Please verify all values, especially email, skill, rate and job type, before saving.';
  }
  document.getElementById('spr_detect').addEventListener('click',detect);
  document.getElementById('spr_clear').addEventListener('click',function(){paste.value='';feedback.textContent='Pasted text cleared; form fields were left unchanged.';});
  document.getElementById('spr_form').addEventListener('submit',function(){document.getElementById('spr_hidden_paste').value=paste.value;});
  // Retain existing client email autocomplete from postrek.php.
  if(window.jQuery){jQuery(function($){var timer,request;
    $('.search-box input[name="cemail"]').on('input',function(){var input=$(this),value=$.trim(input.val()),results=input.siblings('.result');clearTimeout(timer);if(request)request.abort();if(value.length<2){results.empty().hide();return;}
      timer=setTimeout(function(){request=$.ajax({url:'backendsearch.php',type:'GET',data:{term:value},dataType:'html'}).done(function(html){if($.trim(html))results.html(html).show();else results.empty().hide();}).fail(function(){results.empty().hide();});},200);
    });
    $(document).on('click','.result .client-search-item',function(){var item=$(this);item.closest('.search-box').find('input[name="cemail"]').val(item.attr('data-email'));item.closest('.result').empty().hide();});
  });}
})();
</script>
