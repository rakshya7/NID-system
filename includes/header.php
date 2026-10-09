<?php require_once __DIR__.'/bootstrap.php'; ?>
<!DOCTYPE html><html lang="<?=e($L)?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=t('site')?> — Prototype</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="topbar"><div class="wrap"><span><?=t('gov')?> · <?=t('moha')?></span><span><a href="information.php"></a> <a href="?lang=ne">नेपाली</a> | <a href="?lang=en">English</a></span></div></div>
<header><div class="wrap brand">
<?php if(is_file(__DIR__.'/../assets/govlogo.png'))echo '<img class="emb" src="assets/govlogo.png" alt="">'; else echo '<div class="emb ph" title="Placeholder — drop the emblem at assets/emblem.png">EMBLEM<br>placeholder</div>'; ?>
<div><div class="g1"><?=t('gov')?> · <?=t('moha')?></div><div class="g2"><?=t('dept')?></div><div class="g3"><?=t('site')?> <span class="badge">PROTOTYPE / CASE STUDY</span></div></div></div>
<nav class="wrap" aria-label="Main"><a href="index.php"><?=t('home')?></a><a href="enroll.php"><?=t('enroll')?></a><a href="status.php"><?=t('status')?></a><a href="replacement.php"><?=t('repl')?></a><a href="information.php"><?=t('info')?></a></a></nav></header>
<div class="proto"><div class="wrap"><b>PROTOTYPE:</b> <?=t('proto')?><br><b><?=t('proto2')?></b></div></div><main class="wrap">
