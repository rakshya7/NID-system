<?php require 'includes/header.php';
$r=null;$bad=false;
if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();
 $q=$pdo->prepare('SELECT a.* FROM applications a JOIN users u ON u.id=a.user_id WHERE a.application_number=? AND u.date_of_birth=?');$q->execute([trim($_POST['no']??''),$_POST['dob']??'']);$r=$q->fetch();$bad=!$r;}
?><div class="card"><h1><?=t('status')?></h1><p class="hint">Demo: NID-2026-000101 / 1995-04-12</p><form method="post"><?=csrf()?>
<label for="no"><?=t('appno')?></label><input id="no" name="no" required><label for="dob"><?=t('dob')?></label><input id="dob" name="dob" type="date" required><p><button class="btn"><?=t('lookup')?></button></p></form>
<?php if($bad)echo '<p class="err">'.t('notfound').'</p>';
if($r){ $steps=['submitted','verified','appointment_scheduled','biometric_pending','processing','card_ready']; $i=array_search($r['status'],$steps); $act=$r['status']=='action_required';
 echo '<h2>'.e($r['application_number']).'</h2>'; if($act)echo '<p class="warn"><b>⚠ '.t('action').':</b> '.e($r['remarks']).'</p>';
 echo '<ul class="tl">'; foreach($steps as $k=>$s){ $c=$i===false?'':($k<$i||($k==$i&&$s=='card_ready')?'done':($k==$i?'cur':'')); $m=$c=='done'?'✓ ':($c=='cur'?'● ':'○ '); $l=$c=='done'?'done_l':($c=='cur'?'cur_l':'pend_l');
  echo '<li class="'.$c.'">'.$m.t('st_'.$s).' <span class="tag">'.t($l).'</span></li>'; } echo '</ul>';
 if($r['status']=='appointment_scheduled'||$r['status']=='submitted')echo doc_checklist($r['application_type']); }
?></div><?php require 'includes/footer.php'; ?>
