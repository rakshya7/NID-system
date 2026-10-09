<?php require 'includes/header.php'; $x=$_SESSION['done']??null; if(!$x){header('Location: enroll.php');exit;} unset($_SESSION['enr']);
$q=$pdo->prepare('SELECT a.*,o.name FROM appointments a JOIN offices o ON o.id=a.office_id JOIN applications p ON p.id=a.application_id WHERE p.application_number=?');$q->execute([$x['no']]);$r=$q->fetch(); ?>
<div class="card"><h1>✓ <?=t('done')?></h1><p><?=t('done_p')?></p>
<p><b><?=t('appno')?>:</b> <?=e($x['no'])?></p><p><b><?=t('office')?>:</b> <?=e($r['name'])?><br><b><?=t('date')?>:</b> <?=e($r['appointment_date'])?> · <?=e($r['appointment_time'])?><br><b><?=t('token')?>:</b> <?=e($x['tok'])?></p>
<p class="hint"><?=t('token_note')?></p><p><b><?=t('arrive')?></b></p><?=doc_checklist('new_enrollment')?>
<a class="btn" href="status.php"><?=t('status')?></a> <a class="btn alt" href="index.php"><?=t('home')?></a></div>
<?php require 'includes/footer.php'; ?>
