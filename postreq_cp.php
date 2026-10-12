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
    if ($duration === '') { $duration = $jobtype === 4 ? 'FTE' : ($jobtype === 3 ? '6+ months W2' : '6+ months'); }
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
    <p>Paste a job posting or an email thread. Fields are detected automatically after pasting; review and correct them before saving.</p>
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
        <tr class="spr-row"><td><label for="spr_description">Job Description:</label></td><td><textarea id="spr_description" name="rdesc" class="spr-paste" required><?= spr_h(spr_post('rdesc')) ?></textarea><div class="spr-note">Only the detected job description is copied here, excluding email headers, replies and signatures. Review before saving.</div></td></tr>
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
  function cleanText(s){return (s||'').replace(/\u00a0/g,' ').replace(/[\t ]+/g,' ').trim();}
  function emailAddresses(s){return (s.match(/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/ig)||[]).map(function(x){return x.toLowerCase();});}
  function extractEmail(t){
    // In a forwarded thread, the current From header is the recruiter; the To
    // header is usually our mailbox. Never choose our own email as client.
    var header=/^\s*(?:from|sender)\s*:\s*([^\n]+)/gim,m,candidates=[];
    while((m=header.exec(t))!==null){emailAddresses(m[1]).forEach(function(e){candidates.push(e);});}
    // Prioritize the outermost From header; if it is our own, find an external From.
    var external=function(e){return !/@metahorizon\.com$/i.test(e);};
    var first=candidates.find(external); if(first)return first;
    var contact=firstMatch(t,[/^\s*(?:recruiter\s*email|contact\s*email|email\s*id|email)\s*:\s*([^\n]+)/im]);
    first=emailAddresses(contact).find(external); if(first)return first;
    // Last resort: external addresses elsewhere, but exclude generic footer/privacy
    // mailboxes when possible. Leave empty rather than use our own email.
    var all=emailAddresses(t).filter(external);
    return all.find(function(e){return !/^(?:privacy|unsubscribe|noreply|no-reply|support)@/i.test(e);})||'';
  }
  function bodyLines(t){
    return t.split('\n').map(cleanText).filter(function(line){
      return line && !/^(?:from|to|sent|date|subject|cc|bcc|on .* wrote:|forwarded message|caution:|you don't often get email|this email and any files|click here|www\.|https?:\/\/|regards|thanks|thank you|an official partner|lead\s*[-–]\s*recruiting|t\s*:\s*\(?\+?\d)/i.test(line)
        && !/^(?:re:|fw:|fwd:)/i.test(line);
    });
  }
  function extractJobDescription(text){
    var lines=text.replace(/\r/g,'').split('\n');
    var role=/(?:full[ -]?stack|back[ -]?end|front[ -]?end|developer|engineer|architect|analyst|administrator|consultant|programmer|specialist|devops|tester|data scientist|project manager|business analyst)/i;
    var requirement=/(?:^\s*(?:job\s*(?:title|description|location)|position|role|location|work\s*location|duration|rate|end\s*client|responsibilities|requirements|qualifications|skills|experience|must\s*have|nice\s*to\s*have)\s*[:\-]|\b(?:looking for|hiring|seeking|developers?|engineers?|must have|should have|experience with|working with)\b)/i;
    var noise=/^(?:-{2,}\s*forwarded message|from\s*:|to\s*:|cc\s*:|bcc\s*:|sent\s*:|date\s*:|subject\s*:|on .{5,120} wrote\s*:|caution\s*:|you don't often get email|learn why this is important|regards\b|best regards\b|thanks\b|thank you\b|sincerely\b|metahorizon inc\b|certified minority|an e-verify employer|email\s*:|o\s*:\s*\(?\d|t\s*:\s*\(?\+?\d|www\.|https?:\/\/|click here|not willing to|opt-out|this email and any files|warning: computer viruses|the recipient should|although the company|if you are not the named addressee|please notify the sender|an official partner|innova-.*\.png|liicon\.png|cp-cfc-.*\.png)/i;
    var footer=/(?:this email and any files transmitted|click here to learn how we collect|not willing to innovate|confidential and intended solely|computer viruses can be transmitted|you don't often get email)/i;
    // Favor the newest substantive job section, even inside a forwarded thread.
    var segments=[], current=[];
    function flush(){if(current.length){segments.push(current);current=[];}}
    for(var i=0;i<lines.length;i++){
      var line=lines[i].trim();
      if(footer.test(line)){flush();continue;}
      if(/^(?:on .+ wrote:|from\s*:|-------- forwarded message)/i.test(line)){flush();continue;}
      if(noise.test(line)){flush();continue;}
      if(!line){if(current.length)current.push('');continue;}
      if(/^(?:yes|no|hi\b|hello\b|hope you are doing well|can we submit candidates|mayuri saha|satish kumar sinha|lead\s*[-–]\s*recruiting|innova solutions|1455 lincoln parkway|1303 w walnut hill)/i.test(line)) {flush();continue;}
      if(/\b(?:@|\.png\b|\.jpg\b|\.jpeg\b)\b/i.test(line) && !requirement.test(line)){flush();continue;}
      current.push(line);
    }
    flush();
    var ranked=segments.map(function(seg){
      var joined=seg.join('\n');
      var count=seg.filter(function(l){return requirement.test(l);}).length;
      var bullets=seg.filter(function(l){return /^[-•*]\s+/.test(l);}).length;
      return {seg:seg,score:count*5+bullets*2+(role.test(joined)?5:0)};
    }).sort(function(a,b){return b.score-a.score;});
    if(!ranked.length || ranked[0].score<5)return '';
    var chosen=ranked[0].seg.slice();
    // Include nearby job details, but remove unrelated conversational preamble.
    while(chosen.length && !requirement.test(chosen[0]) && !role.test(chosen[0]))chosen.shift();
    while(chosen.length && !chosen[chosen.length-1])chosen.pop();
    return chosen.join('\n').replace(/\n{3,}/g,'\n\n').trim();
  }
  function detect(){
    var raw=paste.value.trim(); if(!raw){feedback.textContent='Paste a requirement first.';return;}
    var t=raw.replace(/\r/g,'');
    var lines=bodyLines(t);
    var title=singleLine(firstMatch(t,[/^\s*(?:job\s*title|position\s*title|position|role|title|requirement)\s*[:\-]\s*([^\n]+)/im]));
    // Subject is only used if it actually describes a job, not a reply such as
    // "Tried Reaching You", "Re: Hello", or "Following up".
    if(!title){
      var roleLine=lines.find(function(line){
        return line.length<110 && /\b(?:full[ -]?stack|developer|engineer|architect|analyst|administrator|consultant|programmer|specialist|data scientist|devops|qa|tester|designer)\b/i.test(line)
          && !/\b(?:we are looking for|we need|job description|experience with|must have|should have|responsibilities)\b/i.test(line);
      });
      title=roleLine||'';
    }
    if(!title){
      var subj=firstMatch(t,[/^\s*subject\s*:\s*(?:re\s*:\s*|fw[d]?\s*:\s*)*([^\n]+)/im]);
      if(/\b(?:developer|engineer|architect|analyst|consultant|administrator|specialist|devops|full[ -]?stack)\b/i.test(subj))title=subj;
    }
    title=singleLine(title).replace(/^(?:urgent|immediate|hot\s+requirement)\s*[-:]\s*/i,'').replace(/^[\-•]\s*/,'').replace(/[.!,;]+$/,'').slice(0,50);
    var loc=singleLine(firstMatch(t,[/^\s*(?:job\s*location|work\s*location|location|onsite\s*location|city|worksite)\s*(?:is|:|\-)\s*([^\n]+)/im,/\b(?:located\s+in|based\s+in)\s+([A-Za-z][A-Za-z .,-]{2,55})/i]));
    loc=loc.replace(/[.\s]+$/,'');
    var duration=singleLine(firstMatch(t,[/^\s*(?:duration|contract\s*duration|length\s*of\s*contract|term)\s*[:\-]\s*([^\n]+)/im,/\b(\d{1,2}\s*(?:\+\s*)?(?:months?|mos?|years?|yrs?)(?:\s*(?:\+|extendable|extension))?)\b/i]));
    var rate=firstMatch(t,[/^\s*(?:pay\s*rate|bill\s*rate|rate|hourly\s*rate|budget)\s*[:\-]?\s*\$?\s*(\d{2,4})(?:\s*(?:\/hr|per\s*hour|hourly))?/im,/\$\s*(\d{2,4})\s*(?:\/\s*(?:hr|hour)|per\s*hour)/i]);
    var client=singleLine(firstMatch(t,[/^\s*(?:end\s*client|client\s*name|end\s*customer|customer\s*name)\s*[:\-]\s*([^\n]+)/im]));
    var email=extractEmail(t);
    var jt=firstMatch(t,[/^\s*(?:employment\s*type|job\s*type|engagement\s*type|position\s*type)\s*[:\-]\s*([^\n]+)/im]);
    // A question about C2C in an email thread does not establish that the job is C2C.
    var classification=jt || lines.filter(function(x){return /\b(?:c2c|w2|fte|contract|full[ -]?time|permanent)\b/i.test(x) && !/\?|can we|do you|would you/i.test(x);}).slice(0,4).join('\n');
    var type='';if(/contract\s*(?:to|[- ]?2)\s*hire|c2h/i.test(classification))type='2';else if(/\b(?:full[ -]?time|permanent|fte)\b/i.test(classification))type='4';else if(/\bw2\b/i.test(classification))type='3';else if(/\b(?:contract|c2c|corp\s*to\s*corp)\b/i.test(classification))type='1';
    // Explicit duration in the requirement takes priority. Otherwise use the
    // requested default for the detected employment type.
    if(type==='4' && !duration)duration='FTE';
    else if(type==='3' && !duration)duration='6+ months W2';
    else if((type==='1'||type==='2') && !duration)duration='6+ months';
    // When no employment type is detected, use the selected dropdown (Contract by default).
    if(!duration){var effectiveType=type||fields.jobtype.value;duration=effectiveType==='4'?'FTE':(effectiveType==='3'?'6+ months W2':'6+ months');}
    setField(fields.title,title);setField(fields.location,loc.slice(0,60));setField(fields.duration,duration.slice(0,20));setField(fields.rate,rate);setField(fields.client,client.slice(0,30));setField(fields.email,email.slice(0,50));setField(fields.jobtype,type);
    var options=Array.prototype.slice.call(fields.skill.options).filter(function(o){return o.value;});
    var titleLow=title.toLowerCase(), bodyLow=(extractJobDescription(t)||t).toLowerCase();
    // Role-family classification is based primarily on the job title, not incidental
    // mentions of technologies in a long description. First matching family wins.
    var families=[
      {label:'Sailpoint', test:/\b(?:sail\s*point|identity\s*(?:and|&)\s*access\s*management|identity\s*governance|iga|iam|identitynow|identity\s*security\s*cloud|isc)\b/i},
      {label:'Data Engineer', test:/\b(?:data\s*(?:scientist|science|analyst|engineer|engineering|fabric|warehouse|architect|analytics)|big\s*data|bigdata|databricks?|data\s*bricks?|pyspark|spark\s*engineer|etl\s*developer|ai\s*engineer|machine\s*learning\s*engineer|ml\s*engineer|mlops\s*engineer)\b/i},
      {label:'Devops', test:/\b(?:dev\s*ops|devops|cloud\s*(?:engineer|engineering|administrator|architect)|build\s*(?:and|&)\s*release|release\s*engineer|site\s*reliability|sre|platform\s*engineer|systems?\s*(?:engineer|administrator)|infrastructure\s*engineer|ci\s*\/\s*cd\s*engineer)\b/i},
      {label:'BA', test:/\b(?:business\s*analyst|business\s*analysis|project\s*(?:coordinator|analyst|manager)|program\s*manager|business\s*systems?\s*analyst|\bba\b)\b/i}
    ];
    var normalized=function(s){return (s||'').toLowerCase().replace(/[^a-z0-9]/g,'');};
    var chosen='', family='', matchedBy='';
    for(var f=0;f<families.length;f++){
      if(families[f].test.test(titleLow)){family=families[f].label;break;}
    }
    // Only fall back to description if the title is absent or generic.
    if(!family && (!titleLow || /^(?:urgent|requirement|opening|position|hiring|job|opportunity)/i.test(titleLow))){
      for(var f2=0;f2<families.length;f2++){
        if(families[f2].test.test(bodyLow)){family=families[f2].label;break;}
      }
    }
    if(family){
      var target=normalized(family);
      var match=options.find(function(o){return normalized(o.text.trim())===target;});
      // Accept conventional spelling variants, but never silently map to a
      // different category or invent a skill ID.
      if(!match && target==='sailpoint')match=options.find(function(o){return /^sail\s*point(?:\s+isc)?$/i.test(o.text.trim());});
      if(!match && target==='devops')match=options.find(function(o){return /^dev\s*ops$/i.test(o.text.trim());});
      if(!match && target==='dataengineer')match=options.find(function(o){return /^data\s*engineer(?:ing)?$/i.test(o.text.trim());});
      if(!match && target==='ba')match=options.find(function(o){return /^(?:ba|business\s*analyst)$/i.test(o.text.trim());});
      if(match){chosen=match.value;matchedBy='role family';}
    }
    if(!chosen){
      var bestScore=0;
      for(var i=0;i<options.length;i++){
        var name=options[i].text.trim().toLowerCase();if(name.length<2)continue;
        var escaped=name.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
        var re=new RegExp('(^|[^a-z0-9])'+escaped+'(?=$|[^a-z0-9])','i');
        var score=(re.test(titleLow)?100:0)+(re.test(bodyLow)?10:0)+Math.min(name.length,30)/100;
        if(/\bjava\b/i.test(titleLow) && /\bjava\b/i.test(name))score+=35;
        if(score>bestScore && score>=10){bestScore=score;chosen=options[i].value;}
      }
      if(chosen)matchedBy='skill name';
    }
    if(chosen){fields.skill.value=chosen;fields.skill.dataset.autoDetected='1';}
    else {fields.skill.value='';fields.skill.dataset.autoDetected='0';}
    var jobOnly=extractJobDescription(t);
    fields.description.value=jobOnly;
    if(!jobOnly){feedback.textContent='No clear job description found. Review the pasted text and enter the job description manually.';return;}
    var detected=[title,loc,duration,rate,client,email,type,chosen].filter(Boolean).length;
    var missing=[];if(!title)missing.push('job title');if(!loc)missing.push('location');if(!duration)missing.push('duration');if(!email)missing.push('recruiter email');if(!chosen)missing.push('skill');
    feedback.textContent=(chosen?'Skill suggested from '+matchedBy+': '+fields.skill.options[fields.skill.selectedIndex].text+'. ':'No matching skill was found in the dropdown. ')+'Detected '+detected+' of 8 fields.'+(missing.length?' Please review or enter: '+missing.join(', ')+'.':'')+' Review all values before saving.';
  }
  fields.skill.addEventListener('change',function(){this.dataset.autoDetected='0';});
  fields.jobtype.addEventListener('change',function(){
    var current=fields.duration.value.trim();
    if(current==='' || current==='FTE' || current==='6+ months' || current==='6+ months W2'){
      fields.duration.value=this.value==='4'?'FTE':(this.value==='3'?'6+ months W2':'6+ months');
    }
  });
  var pasteTimer;
  paste.addEventListener('input',function(){clearTimeout(pasteTimer);pasteTimer=setTimeout(detect,350);});
  paste.addEventListener('paste',function(){clearTimeout(pasteTimer);pasteTimer=setTimeout(detect,120);});
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
