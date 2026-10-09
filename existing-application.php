<?php
require 'includes/header.php';

$x = $_SESSION['existing_application'] ?? null;

if (!$x) {
    header('Location: enroll.php');
    exit;
}
?>
<div class="card">

    <h1><?= t('ex_title') ?></h1>

    <p class="warn"><?= t('ex_msg') ?></p>

    <div class="pv">
        <dl>
            <dt><?= t('appno') ?></dt>
            <dd><?= e($x['application_number'] ?? '') ?></dd>

            <dt><?= t('cur_status') ?></dt>
            <dd><?= e(str_replace('_', ' ', ucfirst($x['status'] ?? ''))) ?></dd>

            <dt><?= t('sub_date') ?></dt>
            <dd><?= e($x['created_at'] ?? '') ?></dd>
        </dl>
    </div>

    <p>
        <a class="btn" href="status.php"><?= t('status') ?></a>
        <a class="btn alt" href="enroll.php?new=1"><?= t('other_cit') ?></a>
        <a class="btn alt" href="index.php"><?= t('back_home') ?></a>
    </p>

</div>
<?php require 'includes/footer.php'; ?>
