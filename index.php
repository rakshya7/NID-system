<?php require 'includes/header.php'; ?>
</main><section class="hero"><div class="wrap"><h1><?=t('hero')?></h1><p><?=t('hero_p')?></p>
<a class="btn red" href="enroll.php"><?=t('start')?></a> <a class="btn alt" href="status.php"><?=t('check')?></a></div></section><main class="wrap">
<h2><?=t('svc_title')?></h2><div class="svc">
<?php foreach([['01','enroll.php','enroll','svc_enroll'],['02','status.php','status','svc_status'],['03','replacement.php','repl','svc_repl'],['04','information.php','info','svc_info']] as [$n,$u,$a,$b])echo '<a class="sc" href="'.$u.'"><small>'.$n.'</small><b>'.t($a).'</b><span>'.t($b).'</span></a>'; ?></div>
<section id="need" class="card"><h2><?=t('need_title')?></h2>
<form method="get" action="index.php#need"><label for="w"><?=t('svc_ask')?></label><select id="w" name="w" onchange="this.form.submit()">
<?php $w=$_GET['w']??'new_enrollment'; foreach(['new_enrollment'=>'need_new','replacement'=>'need_rep'] as $k=>$v) echo '<option value="'.$k.'"'.($w==$k?' selected':'').'>'.t($v).'</option>'; ?></select><noscript><button class="btn"><?=t('next')?></button></noscript></form>
<?= doc_checklist($w=='replacement'?'replacement':'new_enrollment') ?><p><b><?=t('after')?></b></p></section>
<?php require 'includes/footer.php'; ?>
