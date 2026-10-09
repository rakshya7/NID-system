<?php
// NID Card Replacement Payment — prototype. Flow: form -> summary/method -> demo processing -> done.
require 'includes/bootstrap.php';
$stage=$_GET['stage']??'form'; $err=[]; $v=$_SESSION['rep']??[];
$offices=$pdo->query('SELECT id,code,name FROM offices WHERE available=1')->fetchAll(PDO::FETCH_ASSOC);
function oname($o,$id){foreach($o as $x)if($x['id']==$id)return $x['name'];return '';}
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf(); $a=$_POST['a']??'';
 if($a=='form'){ $p=array_map('trim',array_map('strval',$_POST)); $v=['nin'=>$p['nin'],'mobile'=>$p['mobile'],'en'=>$p['en'],'ne'=>$p['ne'],'office'=>(int)$p['office']];
   if(!preg_match('/^\d{3}-\d{3}-\d{3}-\d$/',$v['nin']))$err['nin']=t('nin_bad');
   if(!preg_match('/^9[78]\d{8}$/',$v['mobile']))$err['mobile']=t('mobile_bad');
   if($v['en']==='')$err['en']=t('name_en_bad');
   if(!preg_match('/\p{Devanagari}/u',$v['ne']))$err['ne']=t('name_ne_bad');
   if(!in_array($v['office'],array_column($offices,'id')))$err['office']=t('office_bad');
   if(strtoupper($p['cap'])!==($_SESSION['cap']??'-'))$err['cap']=t('cap_bad');
   if(!$err){$_SESSION['rep']=$v;header('Location: replacement.php?stage=pay');exit;} $stage='form'; }
 elseif($a=='pay'&&$v&&in_array($_POST['method']??'',['Khalti','eSewa','Other'])){
   $pdo->prepare("INSERT INTO replacement_requests(nin,mobile,name_en,name_ne,office_id,amount,payment_method,payment_status,payment_date,appt_date,appt_time) VALUES(?,?,?,?,?,500,?,'paid',NOW(),DATE_ADD(CURDATE(),INTERVAL 14 DAY),'10:30 AM')")->execute([$v['nin'],$v['mobile'],$v['en'],$v['ne'],$v['office'],$_POST['method']]);
   $id=$pdo->lastInsertId(); $code=$pdo->query('SELECT code FROM offices WHERE id='.(int)$v['office'])->fetchColumn();
   $pdo->prepare('UPDATE replacement_requests SET transaction_id=?,token=? WHERE id=?')->execute(['DEMO-NID-2026-'.str_pad($id+4820,6,'0',STR_PAD_LEFT),'REP-'.$code.'-'.str_pad($id+420,5,'0',STR_PAD_LEFT),$id]);
   $_SESSION['rep_id']=$id; unset($_SESSION['rep']); header('Location: replacement.php?stage=processing'); exit; }
 elseif($a=='pay'){$err['pm']=t('pm_bad');$stage='pay';}
}
if($stage=='pay'&&!$v){header('Location: replacement.php');exit;}
if(in_array($stage,['processing','done'])&&empty($_SESSION['rep_id'])){header('Location: replacement.php');exit;}
require 'includes/header.php';
function er($k){global $err;return isset($err[$k])?'<p class="err">'.$err[$k].'</p>':'';}
echo '<div class="card"><h1>'.t('rep_title').' <span class="badge">PROTOTYPE PAYMENT</span></h1>';
if($stage=='form'){ $_SESSION['cap']=substr(strtoupper(bin2hex(random_bytes(4))),0,5);
  echo '<p class="lead">'.t('rep_intro').'</p><form method="post" novalidate>'.csrf().'<input type="hidden" name="a" value="form"><div class="grid2">';
  foreach([['nin','nin','XXX-XXX-XXX-X'],['mobile','mobile','98XXXXXXXX'],['en','en_name',t('ph_en')],['ne','ne_name','आफ्नो पूरा नाम नेपालीमा लेख्नुहोस्']] as [$k,$l,$ph])echo '<div class="fld"><label for="'.$k.'">'.t($l).' <span class="rq">*</span></label><input id="'.$k.'" name="'.$k.'" value="'.e($v[$k]??'').'" placeholder="'.e($ph).'">'.er($k).'</div>';
  echo '<div class="fld"><label for="office">'.t('office_sel').' <span class="rq">*</span> <span class="opt">(mock data)</span></label><select id="office" name="office"><option value="">—</option>';
  foreach($offices as $o)echo '<option value="'.$o['id'].'"'.(($v['office']??0)==$o['id']?' selected':'').'>'.e($o['name']).'</option>'; echo '</select>'.er('office').'</div>';
  echo '<div class="fld"><label for="cap">'.t('captcha').' <span class="rq">*</span></label><div class="cap">'.$_SESSION['cap'].'</div><input id="cap" name="cap" autocomplete="off" placeholder="'.t('enter_cap').'">'.er('cap').'</div></div><p><button class="btn red">'.t('proceed').'</button></p></form>'; }
elseif($stage=='pay'){ echo '<h2>'.t('sum_title').'</h2><div class="pv"><dl><dt>'.t('service').'</dt><dd>'.t('rep_svc').'</dd><dt>'.t('amount').'</dt><dd>NPR 500</dd><dt>'.t('office').'</dt><dd>'.e(oname($offices,$v['office'])).'</dd><dt>'.t('applicant').'</dt><dd>'.e($v['en']).' / '.e($v['ne']).'</dd><dt>NIN</dt><dd>'.e($v['nin']).'</dd><dt>'.t('mobile').'</dt><dd>'.e(substr($v['mobile'],0,2).'XXXXXX'.substr($v['mobile'],-2)).'</dd></dl></div>';
  echo '<form method="post">'.csrf().'<input type="hidden" name="a" value="pay"><fieldset><legend>'.t('choose_pm').'</legend>';foreach(['Khalti','eSewa','Other'] as $m)echo '<label class="rad"><input type="radio" name="method" value="'.$m.'"> '.$m.'</label>';
  echo '</fieldset>'.er('pm').'<p class="demo"><b>'.t('pay_proto').'</b></p><p><button class="btn red">'.t('pay_now').'</button> <a class="btn alt" href="replacement.php">'.t('back').'</a></p></form>'; }
elseif($stage=='processing'){ echo '<meta http-equiv="refresh" content="3;url=replacement.php?stage=done"><p><b>'.t('processing').'</b></p><p class="hint">'.t('pay_proto').'</p>'; }
else{ $q=$pdo->prepare('SELECT r.*,o.name FROM replacement_requests r JOIN offices o ON o.id=r.office_id WHERE r.id=?');$q->execute([$_SESSION['rep_id']]);$r=$q->fetch(PDO::FETCH_ASSOC);
  echo '<h2>✓ '.t('pay_ok').'</h2><p class="demo"><b>'.t('pay_proto').'</b></p><div class="pv"><dl><dt>'.t('service').'</dt><dd>'.t('rep_svc').'</dd><dt>'.t('amount').'</dt><dd>NPR 500</dd><dt>'.t('txn').'</dt><dd>'.e($r['transaction_id']).'</dd><dt>'.t('paid_on').'</dt><dd>'.e($r['payment_date']).'</dd><dt>NIN</dt><dd>'.e($r['nin']).'</dd><dt>'.t('applicant').'</dt><dd>'.e($r['name_en']).' / '.e($r['name_ne']).'</dd></dl></div>';
  echo '<h2>'.t('rep_appt').'</h2><div class="pv"><dl><dt>'.t('office').'</dt><dd>'.e($r['name']).'</dd><dt>'.t('date').'</dt><dd>'.e($r['appt_date']).'</dd><dt>'.t('time').'</dt><dd>'.e($r['appt_time']).'</dd><dt>'.t('token').'</dt><dd>'.e($r['token']).' <span class="tag">'.t('mock_token').'</span></dd></dl></div>'.doc_checklist('replacement').'<p><button class="btn" onclick="window.print()">'.t('print').'</button> <a class="btn alt" href="index.php">'.t('back_home').'</a> <a class="btn alt" href="status.php">'.t('status').'</a></p>'; }
echo '</div>'; require 'includes/footer.php';
