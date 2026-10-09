<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/fields.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// "Use a different citizenship number" link starts a fresh enrollment.
if (isset($_GET['new'])) {
    unset($_SESSION['enr']);
}

/*
|--------------------------------------------------------------------------
| Enrollment session
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['enr']) ||
    !is_array($_SESSION['enr'])
) {
    $_SESSION['enr'] = [
        'step' => 1,
        'otp' => false,
        'mobile_verified' => false,
        'otp_hash' => null,
        'otp_expires' => null,
        'otp_attempts' => 0,
        'verification_stage' => false,
        'ret' => false,
        'd' => []
    ];
}

$S = &$_SESSION['enr'];

$S['step'] = (int)($S['step'] ?? 1);
$S['d'] = $S['d'] ?? [];

$d = &$S['d'];
$err = [];
$a = '';

$N = 11;

/*
|--------------------------------------------------------------------------
| Displayed enrollment steps
|--------------------------------------------------------------------------
| Internal step 1 = verification.
| Internal steps 2–11 = displayed steps 1–10.
*/

$enrollmentNames = [
    'Personal',
    'Birth & Demographic',
    'Citizenship',
    'Contact & Address',
    'Family',
    'Education / Occupation',
    'Identification',
    'Documents',
    'Appointment',
    'Review'
];

/*
|--------------------------------------------------------------------------
| Helper closures
|--------------------------------------------------------------------------
*/

$findExistingApplication = function ($citizenshipNumber) use ($pdo) {

    $sql = "
        SELECT
            a.id,
            a.application_number,
            a.status,
            a.created_at
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        WHERE u.citizenship_number = ?
        ORDER BY a.id DESC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$citizenshipNumber]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
};

$goToStep = function ($step) use (&$S, $N) {

    $step = (int)$step;

    if ($step < 2 || $step > $N) {
        return;
    }

    $S['step'] = $step;
    $S['ret'] = false;
};

$prototypeDisclaimer = '
<div class="info-box">
    <strong>Case-study prototype notice</strong>
    <p>
        This is a prototype demonstration. Token numbers generated are
        mock identifiers. In a real system, you would need to visit your
        local ward office with original documents for final verification
        within 35 days of online submission.
    </p>
    <p>
        This prototype is not connected to the official government NID
        database or real payment systems.
    </p>
</div>
';

/*
|--------------------------------------------------------------------------
| Process form submissions
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    check_csrf();

    $a = $_POST['a'] ?? '';

    $p = [];

    foreach ($_POST as $key => $value) {
        $p[$key] = is_string($value) ? trim($value) : '';
    }

    $st = (int)$S['step'];

    /*
    |--------------------------------------------------------------------------
    | 1. Citizenship number and mobile verification
    |--------------------------------------------------------------------------
    */

    if ($a === 'verify' && $st === 1) {

        $cit = $p['cit'] ?? '';
        $mobile = $p['mobile'] ?? '';

        if ($cit === '') {
            $err['cit'] = 'Citizenship Number is required.';
        }

        if ($mobile === '') {
            $err['mobile'] = 'Mobile Number is required.';
        } elseif (!preg_match('/^9[78]\d{8}$/', $mobile)) {
            $err['mobile'] = 'Enter a valid 10-digit Nepali mobile number.';
        }

        if (!$err) {

            /*
             * Check for an existing application BEFORE sending OTP.
             * The citizenship number is used for this duplicate check.
             */

            $existing = $findExistingApplication($cit);

            if ($existing) {

                $_SESSION['existing_application'] = $existing;

                header('Location: existing-application.php');
                exit;
            }

            $d['cz_cit_no'] = $cit;
            $d['ct_mobile'] = $mobile;

            /*
             * Demo OTP: 123456.
             * In production, deliver an OTP through an authorized SMS
             * provider and never display it on the page.
             */

            $demoOtp = '123456';

            $S['otp_hash'] = password_hash(
                $demoOtp,
                PASSWORD_DEFAULT
            );

            $S['otp_expires'] = time() + 300;
            $S['otp_attempts'] = 0;
            $S['otp'] = false;
            $S['mobile_verified'] = false;
            $S['verification_stage'] = true;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Verify OTP
    |--------------------------------------------------------------------------
    */

    elseif ($a === 'otp' && $st === 1) {

        $code = $p['code'] ?? '';

        // Accept Devanagari digits (१२३४५६) and stray spaces.
        $code = preg_replace(
            '/\s+/',
            '',
            strtr($code, [
                '०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4',
                '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9'
            ])
        );

        if (empty($S['verification_stage'])) {

            $err['code'] = 'Your verification session was reset. '
                . 'Please enter your citizenship number and mobile number again.';

        } elseif (time() > (int)$S['otp_expires']) {

            $err['code'] = 'Your OTP has expired. Please request a new one.';

        } elseif ((int)$S['otp_attempts'] >= 5) {

            $err['code'] = 'Too many incorrect attempts. Request a new OTP.';

        } elseif (
            !preg_match('/^\d{6}$/', $code) ||
            !password_verify($code, $S['otp_hash'] ?? '')
        ) {

            $S['otp_attempts']++;
            $err['code'] = 'Incorrect OTP. Please try again.';

        } else {

            $S['otp'] = true;
            $S['mobile_verified'] = true;
            $d['ct_mobile_verified'] = $d['ct_mobile'] ?? '';
            $S['verification_stage'] = false;
            $S['otp_hash'] = null;
            $S['otp_expires'] = null;
            $S['otp_attempts'] = 0;

            // Internal step 2 is displayed as Step 1 of 10.
            // No redirect: the form is drawn straight away below.
            $S['step'] = 2;
            $S['ret'] = false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Resend demo OTP
    |--------------------------------------------------------------------------
    */

    elseif ($a === 'resend' && $st === 1) {

        if (empty($S['verification_stage'])) {

            $err['code'] = 'Enter your mobile number first.';

        } else {

            $S['otp_hash'] = password_hash(
                '123456',
                PASSWORD_DEFAULT
            );

            $S['otp_expires'] = time() + 300;
            $S['otp_attempts'] = 0;
            $S['otp'] = false;
            $S['mobile_verified'] = false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Change mobile number
    |--------------------------------------------------------------------------
    */

    elseif ($a === 'change_mobile' && $st === 1) {

        $S['otp'] = false;
        $S['mobile_verified'] = false;
        $S['verification_stage'] = false;
        $S['otp_hash'] = null;
        $S['otp_expires'] = null;
        $S['otp_attempts'] = 0;
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Navigate to a previous enrollment section
    |--------------------------------------------------------------------------
    */

    elseif ($a === 'goto') {

        if (empty($S['otp']) || empty($S['mobile_verified'])) {

            $S['step'] = 1;

        } else {

            $target = (int)($p['n'] ?? 0);

            if ($target >= 2 && $target <= $N) {

                $S['from_review'] = ($st === 11);
                $S['step'] = $target;
                $S['ret'] = true;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Save enrollment section
    |--------------------------------------------------------------------------
    */

    elseif (
        $a === 'sec' &&
        isset($STEPS[$st]) &&
        $st >= 2 &&
        $st <= 8
    ) {

        if (empty($S['otp']) || empty($S['mobile_verified'])) {

            $S['step'] = 1;

        } else {

            $fs = $STEPS[$st]['f'];

            /*
             * Preserve submitted values for redisplay if validation fails.
             */

            foreach ($fs as $f) {
                $key = $f[0];

                if (array_key_exists($key, $p)) {
                    $d[$key] = $p[$key];
                }
            }

            foreach ($fs as $f) {

                $key = $f[0];
                $value = $d[$key] ?? '';
                $type = $f[1] ?? 't';

                if (!vis($f, $d)) {
                    continue;
                }

                if (!empty($f[2]) && $value === '') {
                    $err[$key] = lbl($key) . ' is required.';
                    continue;
                }

                if ($value === '') {
                    continue;
                }

                if (
                    $type === 'm' &&
                    !preg_match('/^9[78]\d{8}$/', $value)
                ) {
                    $err[$key] = 'Enter a valid 10-digit mobile number.';
                }

                if ($type === 'd' && !valid_date($value)) {
                    $err[$key] = 'Enter a valid date.';
                }

                if (
                    $type === 'e' &&
                    !filter_var($value, FILTER_VALIDATE_EMAIL)
                ) {
                    $err[$key] = 'Enter a valid email address.';
                }

                if ($type === 'n') {

                    // Ward numbers are 1-35; height is in centimetres.
                    [$min, $max] = ($key === 'id_height')
                        ? [50, 250]
                        : [1, 35];

                    if (
                        !ctype_digit((string)$value) ||
                        (int)$value < $min ||
                        (int)$value > $max
                    ) {
                        $err[$key] = "Enter a valid number between $min and $max.";
                    }
                }
            }

            /*
             * Do not allow changing the verified mobile number without
             * repeating OTP verification.
             */

            if ($st === 5) {

                $submittedMobile = $d['ct_mobile'] ?? '';
                $verifiedMobile = $d['ct_mobile_verified'] ?? '';

                if ($submittedMobile !== $verifiedMobile) {

                    $S['otp'] = false;
                    $S['mobile_verified'] = false;
                    $S['verification_stage'] = false;
                    $S['step'] = 1;

                    $err['mobile'] =
                        'Your mobile number changed. Please verify it again.';
                }
            }

            if (!$err) {

                // After "Edit" on the review page, go back to the review.
                // Otherwise (normal flow or the Back button) go to the next step.
                $backToReview = !empty($S['ret']) && !empty($S['from_review']);

                $S['ret'] = false;
                $S['from_review'] = false;
                $S['step'] = $backToReview ? 11 : $st + 1;

                /*
                 * Preserve the mobile number that was verified.
                 */

                if ($st === 5) {
                    $d['ct_mobile_verified'] = $d['ct_mobile'] ?? '';
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 7. Document acknowledgement
    |--------------------------------------------------------------------------
    */

    elseif ($a === 'docs' && $st === 9) {

        if (empty($S['otp']) || empty($S['mobile_verified'])) {

            $S['step'] = 1;

        } elseif (empty($p['ack'])) {

            $err['ack'] = 'Please confirm that you have reviewed the documents.';

        } else {

            $d['docs_ack'] = 1;
            $S['step'] = 10;
            $S['ret'] = false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 8. Appointment
    |--------------------------------------------------------------------------
    */

    elseif ($a === 'appt' && $st === 10) {

        if (empty($S['otp']) || empty($S['mobile_verified'])) {

            $S['step'] = 1;

        } else {

            foreach (['office', 'date', 'time'] as $key) {

                if (($p[$key] ?? '') === '') {
                    $err[$key] = ucfirst($key) . ' is required.';
                }
            }

            if (
                empty($err['date']) &&
                !valid_date($p['date'], true)
            ) {
                $err['date'] = 'Select a valid appointment date.';
            }

            if (!$err) {

                $d['office'] = (int)$p['office'];
                $d['date'] = $p['date'];
                $d['time'] = $p['time'];

                $stmt = $pdo->prepare(
                    'SELECT id FROM offices WHERE id = ? AND available = 1'
                );

                $stmt->execute([$d['office']]);

                if (!$stmt->fetchColumn()) {

                    $err['office'] = 'Please select an available office.';

                } else {

                    $S['step'] = 11;
                    $S['ret'] = false;
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 9. Final submission
    |--------------------------------------------------------------------------
    */

    elseif ($a === 'submit' && $st === 11) {

        if (empty($S['otp']) || empty($S['mobile_verified'])) {

            $S['step'] = 1;

        } elseif (empty($p['ok'])) {

            $err['ok'] = 'Please confirm your details before submitting.';

        } else {

            // Set to false once the prototype is working, so visitors
            // never see raw database messages.
            $debugSubmit = true;

            // Empty string -> NULL, so DATE / TINYINT columns accept it.
            $nz = function ($value) {
                $value = is_string($value) ? trim($value) : $value;
                return ($value === '' || $value === null) ? null : $value;
            };

            try {

                $pdo->beginTransaction();

                /*
                 * One citizen = one enrollment. Check again right before
                 * inserting, in case another tab already submitted.
                 */

                $existing = $findExistingApplication(
                    $d['cz_cit_no'] ?? ''
                );

                if ($existing) {

                    $pdo->rollBack();

                    $_SESSION['existing_application'] = $existing;

                    header('Location: existing-application.php');
                    exit;
                }

                /*
                 * Find or create the citizen record.
                 */

                $q = $pdo->prepare(
                    'SELECT id FROM users WHERE citizenship_number = ?'
                );

                $q->execute([$d['cz_cit_no']]);

                $uid = $q->fetchColumn();

                if (!$uid) {

                    $uid = ins('users', [
                        'citizenship_number' => $d['cz_cit_no'],
                        'date_of_birth'      => $d['pd_dob'],
                        'mobile'             => $d['ct_mobile'] ?? null,
                        'mobile_verified'    => 1
                    ]);
                }

                /*
                 * Personal details
                 * Every key below is a real column of personal_details.
                 */

                $englishName = trim(implode(' ', array_filter([
                    $d['pd_first_name']  ?? '',
                    $d['pd_middle_name'] ?? '',
                    $d['pd_last_name']   ?? ''
                ])));

                $nepaliName = trim(implode(' ', array_filter([
                    $d['pd_first_name_np']  ?? '',
                    $d['pd_middle_name_np'] ?? '',
                    $d['pd_last_name_np']   ?? ''
                ])));

                ins('personal_details', [
                    'user_id'              => $uid,
                    'full_name'            => $englishName,
                    'nepali_name'          => $nz($nepaliName),
                    'first_name'           => $nz($d['pd_first_name']  ?? null),
                    'middle_name'          => $nz($d['pd_middle_name'] ?? null),
                    'last_name'            => $nz($d['pd_last_name']   ?? null),
                    'dob'                  => $d['pd_dob'],
                    'gender'               => strtolower($d['pd_gender'] ?? ''),
                    'nationality'          => $nz($d['pd_nationality'] ?? null),
                    'marital_status'       => $nz($d['pd_marital']     ?? null),
                    'previous_name'        => $nz($d['pd_previous_name'] ?? null),
                    'father_name'          => $nz($d['pd_father_name'] ?? null),
                    'mother_name'          => $nz($d['pd_mother_name'] ?? null),
                    'citizenship_district' => $nz($d['cz_cit_district'] ?? null),
                    'birth_place'          => $nz($d['birth_place']       ?? null),
                    'birth_country'        => $nz($d['birth_country']     ?? null),
                    'birth_province'       => $nz($d['birth_province']    ?? null),
                    'birth_district'       => $nz($d['birth_district']    ?? null),
                    'birth_local'          => $nz($d['birth_local_level'] ?? null),
                    'birth_ward'           => $nz($d['birth_ward']        ?? null),
                    'mother_tongue'        => $nz($d['pd_mother_tongue']  ?? null)
                ]);

                /*
                 * Citizenship details
                 * Form fields are named cz_*, columns are named cit_*,
                 * so they are mapped by hand instead of with pick().
                 */

                ins('citizenship_details', [
                    'user_id'         => $uid,
                    'cit_no'          => $nz($d['cz_cit_no']            ?? null),
                    'cit_type'        => $nz($d['cz_type']              ?? null),
                    'cit_district'    => $nz($d['cz_cit_district']      ?? null),
                    'cit_office'      => $nz($d['cz_issuing_office']    ?? null),
                    'cit_issue_date'  => $nz($d['cz_issue_date']        ?? null),
                    'cit_authority'   => $nz($d['cz_issuing_authority'] ?? null)
                ]);

                /*
                 * Contact details
                 */

                ins('contact_details', [
                    'user_id'      => $uid,
                    'mobile'       => $d['ct_mobile'],
                    'alt_mobile'   => $nz($d['ct_alt_mobile'] ?? null),
                    'email'        => $nz($d['ct_email']      ?? null),
                    'district'     => $nz($d['perm_district']    ?? null),
                    'municipality' => $nz($d['perm_local_level'] ?? null),
                    'ward'         => $nz($d['perm_ward']        ?? null),
                    'address'      => $nz($d['perm_tole']        ?? null)
                ]);

                /*
                 * Education / occupation
                 */

                ins('education_occupation', [
                    'user_id'    => $uid,
                    'education'  => $nz($d['ed_education']    ?? null),
                    'occupation' => $nz($d['ed_occupation']   ?? null),
                    'employer'   => $nz($d['ed_organization'] ?? null)
                ]);

                /*
                 * Addresses (permanent, and current when different)
                 */

                $addressRow = function ($prefix) use ($d, $nz) {
                    return [
                        'province'    => $nz($d[$prefix . 'province']    ?? null),
                        'district'    => $nz($d[$prefix . 'district']    ?? null),
                        'local_level' => $nz($d[$prefix . 'local_level'] ?? null),
                        'ward'        => $nz($d[$prefix . 'ward']        ?? null),
                        'tole'        => $nz($d[$prefix . 'tole']        ?? null),
                        'house_no'    => $nz($d[$prefix . 'house']       ?? null)
                    ];
                };

                $permanentAddress = $addressRow('perm_');

                $currentAddress = (($d['same_addr'] ?? 'Yes') === 'No')
                    ? $addressRow('curr_')
                    : $permanentAddress;

                ins('addresses', ['user_id' => $uid, 'kind' => 'permanent'] + $permanentAddress);
                ins('addresses', ['user_id' => $uid, 'kind' => 'current']   + $currentAddress);

                /*
                 * Family members
                 */

                $family = [
                    ['Father', $d['pd_father_name'] ?? $d['fa_name'] ?? '', 'fa_'],
                    ['Mother', $d['pd_mother_name'] ?? $d['mo_name'] ?? '', 'mo_']
                ];

                if (($d['pd_marital'] ?? '') === 'Married') {
                    $family[] = ['Spouse', $d['sp_name'] ?? '', 'sp_'];
                }

                if (!empty($d['gu_name'])) {
                    $family[] = ['Guardian', $d['gu_name'], 'gu_'];
                }

                foreach ($family as [$relationship, $name, $prefix]) {

                    if ($name === '') {
                        continue;
                    }

                    ins('family_members', [
                        'user_id'            => $uid,
                        'relationship'       => $relationship,
                        'name'               => $name,
                        'nepali_name'        => $nz($d[$prefix . 'nname'] ?? null),
                        'citizenship_number' => $nz($d[$prefix . 'cit']   ?? null),
                        'nationality'        => $nz($d[$prefix . 'nat']   ?? null)
                    ]);
                }

                /*
                 * Application
                 */

                $aid = ins('applications', [
                    'user_id'      => $uid,
                    'status'       => 'appointment_scheduled',
                    'prev_applied' => $nz($d['ap_prev']    ?? null),
                    'prev_nid'     => $nz($d['ap_prev_nid'] ?? null),
                    'prev_app_no'  => $nz($d['ap_prev_no']  ?? null)
                ]);

                $applicationNumber = 'NID-' . date('Y') . '-' .
                    str_pad($aid, 6, '0', STR_PAD_LEFT);

                $pdo->prepare(
                    'UPDATE applications SET application_number = ? WHERE id = ?'
                )->execute([$applicationNumber, $aid]);

                /*
                 * Appointment and mock token
                 */

                $stmt = $pdo->prepare(
                    'SELECT code, name, address FROM offices
                     WHERE id = ? AND available = 1'
                );

                $stmt->execute([$d['office']]);

                $office = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$office) {
                    throw new RuntimeException(
                        'Appointment office could not be found.'
                    );
                }

                $tokenNumber = 'NID-' . $office['code'] . '-' .
                    str_pad($aid + 426, 5, '0', STR_PAD_LEFT);

                ins('appointments', [
                    'application_id'   => $aid,
                    'office_id'        => $d['office'],
                    'appointment_date' => $d['date'],
                    'appointment_time' => $d['time'],
                    'token_number'     => $tokenNumber
                ]);

                /*
                 * Status history
                 */

                foreach (['submitted', 'appointment_scheduled'] as $status) {
                    ins('status_history', [
                        'application_id' => $aid,
                        'status'         => $status
                    ]);
                }

                $pdo->commit();

                /*
                 * Everything success.php needs.
                 */

                $_SESSION['done'] = [
                    'no'      => $applicationNumber,
                    'tok'     => $tokenNumber,
                    'office'  => $office['name'],
                    'address' => $office['address'],
                    'date'    => $d['date'],
                    'time'    => $d['time']
                ];

                unset($_SESSION['enr']);

                header('Location: success.php');
                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('NID enrollment submission error: ' . $e->getMessage());

                $err['submit'] = 'We could not save your enrollment. Please try again.'
                    . ($debugSubmit ? ' [Debug: ' . $e->getMessage() . ']' : '');
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Render page
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';

$step = (int)$S['step'];

/*
 * Verification is not part of the 10 displayed enrollment steps.
 * Internal step 2 must display as step 1.
 */

$isEnrollment = (
    $step >= 2 &&
    $step <= 11 &&
    !empty($S['otp']) &&
    !empty($S['mobile_verified'])
);

$displayStep = $isEnrollment ? $step - 1 : 0;
$displayTotal = 10;

$names = $T['steps'] ?? $EN['steps'] ?? [];

?>

<?php if (!$isEnrollment): ?>

    <div class="card">

        <h1>New NID Enrollment</h1>

        <p class="lead">
            Verify your citizenship details and mobile number before
            starting the enrollment form.
        </p>

        <div class="info-box">
            <strong>Prototype demonstration</strong>
            <p>
                This page uses mock OTP verification. It is not connected
                to the official government NID system.
            </p>
        </div>

        <?php if (isset($err['code'])): ?>
            <p class="err"><?= e($err['code']) ?></p>
        <?php endif; ?>

        <form method="post">
            <?= csrf() ?>

            <?php if (empty($S['verification_stage'])): ?>

                <input type="hidden" name="a" value="verify">

                <div class="grid2">

                    <div class="fld">
                        <label for="cit">Citizenship Number *</label>

                        <input
                            id="cit"
                            name="cit"
                            type="text"
                            value="<?= e($d['cz_cit_no'] ?? '') ?>"
                            required
                        >

                        <?php if (isset($err['cit'])): ?>
                            <p class="err"><?= e($err['cit']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="fld">
                        <label for="mobile">Mobile Number *</label>

                        <input
                            id="mobile"
                            name="mobile"
                            type="tel"
                            inputmode="numeric"
                            maxlength="10"
                            placeholder="98XXXXXXXX"
                            value="<?= e($d['ct_mobile'] ?? '') ?>"
                            required
                        >

                        <?php if (isset($err['mobile'])): ?>
                            <p class="err"><?= e($err['mobile']) ?></p>
                        <?php endif; ?>
                    </div>

                </div>

                <button class="btn" type="submit">
                    Check and Send OTP
                </button>

            <?php else: ?>

                <input type="hidden" name="a" value="otp">

                <p class="ok-msg">
                    OTP verification is required to continue.
                </p>

                <p>
                    Mobile Number:
                    <strong>
                        <?= e(
                            substr($d['ct_mobile'] ?? '', 0, 2) .
                            'XXXXXX' .
                            substr($d['ct_mobile'] ?? '', -2)
                        ) ?>
                    </strong>
                </p>

                <div class="info-box">
                    <strong>Demo OTP: 123456</strong>
                    <p>
                        This is a demonstration code only. No real SMS
                        has been sent.
                    </p>
                </div>

                <div class="fld">
                    <label for="code">Enter OTP *</label>

                    <input
                        id="code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="one-time-code"
                        required
                    >

                </div>

                <button class="btn" type="submit">
                    Verify OTP
                </button>

                <button
                    class="btn alt"
                    type="submit"
                    name="a"
                    value="resend"
                    formnovalidate
                >
                    Resend Demo OTP
                </button>

                <button
                    class="btn alt"
                    type="submit"
                    name="a"
                    value="change_mobile"
                    formnovalidate
                >
                    Change Mobile Number
                </button>

            <?php endif; ?>

        </form>

    </div>

<?php else: ?>

    <?php
    $stepName = $enrollmentNames[$displayStep - 1] ?? 'Enrollment';
    ?>

    <div class="stepper">

        <p class="sn">
            Step <?= $displayStep ?> of <?= $displayTotal ?>
            — <b><?= e($stepName) ?></b>
        </p>

        <div class="bar">
            <i style="width: <?= round(($displayStep / $displayTotal) * 100) ?>%"></i>
        </div>

        <ol>
            <?php foreach ($enrollmentNames as $i => $name): ?>

                <?php
                $number = $i + 1;

                $class = '';

                if ($number < $displayStep) {
                    $class = 'ok';
                } elseif ($number === $displayStep) {
                    $class = 'on';
                }
                ?>

                <li class="<?= e($class) ?>">
                    <?= sprintf('%02d', $number) ?>
                    <?= e($name) ?>
                </li>

            <?php endforeach; ?>
        </ol>

    </div>

    <div class="card">

        <div class="ok-msg">
            ✓ Mobile Number Verified
            <strong>
                <?= e(
                    substr($d['ct_mobile'] ?? '', 0, 2) .
                    'XXXXXX' .
                    substr($d['ct_mobile'] ?? '', -2)
                ) ?>
            </strong>
        </div>

        <p class="hint">
            Citizenship Number:
            <strong><?= e($d['cz_cit_no'] ?? '') ?></strong>
        </p>

        <?php
        $sx = $T['sx'][$step] ?? $EN['sx'][$step] ?? '';

        if ($sx) {
            echo '<p class="lead">' . e($sx) . '</p>';
        }
        ?>

        <form method="post">

            <?= csrf() ?>

            <?php if ($step >= 2 && $step <= 8 && isset($STEPS[$step])): ?>

                <input type="hidden" name="a" value="sec">

                <?php if ($step === 4): ?>
                    <div class="warn">
                        Please enter your citizenship details exactly
                        as they appear on your citizenship certificate.
                    </div>
                <?php endif; ?>

                <div class="grid2">
<?php foreach ($STEPS[$step]['f'] as $f): ?>
    <?php fld($f); ?>
<?php endforeach; ?>
                </div>

                <button class="btn" type="submit">
                    Save and Continue
                </button>

            <?php elseif ($step === 9): ?>

                <input type="hidden" name="a" value="docs">

                <?= doc_checklist('new_enrollment') ?>

                <label class="rad">
                    <input
                        type="checkbox"
                        name="ack"
                        value="1"
                        <?= !empty($d['docs_ack']) ? 'checked' : '' ?>
                    >

                    I have reviewed the document checklist.
                </label>

                <?php if (isset($err['ack'])): ?>
                    <p class="err"><?= e($err['ack']) ?></p>
                <?php endif; ?>

                <p class="hint">
                    Bring the required original documents to your
                    appointment. Conditional documents are needed only
                    when applicable to your circumstances.
                </p>

                <button class="btn" type="submit">
                    Continue to Appointment
                </button>

            <?php elseif ($step === 10): ?>

                <input type="hidden" name="a" value="appt">

                <div class="grid2">

                    <div class="fld">
                        <label for="of">Appointment Office *</label>

                        <select id="of" name="office" required>
                            <option value="">Select an office</option>

                            <?php
                            $offices = $pdo->query(
                                'SELECT * FROM offices
                                 WHERE available = 1
                                 ORDER BY name'
                            );

                            foreach ($offices as $office):
                            ?>

                                <option
                                    value="<?= (int)$office['id'] ?>"
                                    <?= (int)($d['office'] ?? 0) === (int)$office['id']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e(
                                        $office['name'] . ' — ' .
                                        $office['address']
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <?php if (isset($err['office'])): ?>
                            <p class="err"><?= e($err['office']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="fld">
                        <label for="dt">Appointment Date *</label>

                        <input
                            id="dt"
                            name="date"
                            type="date"
                            min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>"
                            value="<?= e($d['date'] ?? '') ?>"
                            required
                        >

                        <?php if (isset($err['date'])): ?>
                            <p class="err"><?= e($err['date']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="fld">
                        <label for="tm">Appointment Time *</label>

                        <select id="tm" name="time" required>
                            <option value="">Select a time</option>

                            <?php
                            foreach (
                                [
                                    '10:00 AM',
                                    '10:30 AM',
                                    '11:00 AM',
                                    '11:30 AM',
                                    '1:00 PM',
                                    '1:30 PM'
                                ] as $time
                            ):
                            ?>

                                <option
                                    value="<?= e($time) ?>"
                                    <?= ($d['time'] ?? '') === $time
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= e($time) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <?php if (isset($err['time'])): ?>
                            <p class="err"><?= e($err['time']) ?></p>
                        <?php endif; ?>
                    </div>

                </div>

                <p class="hint">
                    Appointment availability and token numbers are mock
                    data in this case-study prototype.
                </p>

                <button class="btn" type="submit">
                    Review Application
                </button>

            <?php elseif ($step === 11): ?>

                <h2>Review Your Application</h2>

                <p class="lead">
                    Review the information below before submitting.
                </p>

                <?php
                foreach ($STEPS as $internalStep => $definition):

                    $sectionName =
                        $enrollmentNames[$internalStep - 2] ??
                        'Enrollment Details';
                ?>

                    <div class="pv">

                        <h3>
                            <?= e($sectionName) ?>

                            <button
                                class="btn alt sm"
                                type="submit"
                                name="a"
                                value="goto"
                                onclick="this.form.n.value=<?= (int)$internalStep ?>;"
                                formnovalidate
                            >
                                Edit
                            </button>
                        </h3>

                        <dl>
                            <?php foreach ($definition['f'] as $f): ?>

                                <?php
                                $key = $f[0];

                                if (
                                    vis($f, $d) &&
                                    isset($d[$key]) &&
                                    $d[$key] !== ''
                                ):
                                ?>

                                    <dt><?= e(lbl($key)) ?></dt>
                                    <dd><?= e($d[$key]) ?></dd>

                                <?php endif; ?>

                            <?php endforeach; ?>
                        </dl>

                    </div>

                <?php endforeach; ?>

                <?php
                $stmt = $pdo->prepare(
                    'SELECT name, address FROM offices WHERE id = ?'
                );

                $stmt->execute([$d['office'] ?? 0]);

                $office = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>

                <div class="pv">

                    <h3>
                        Appointment

                        <button
                            class="btn alt sm"
                            type="submit"
                            name="a"
                            value="goto"
                            onclick="this.form.n.value=10;"
                            formnovalidate
                        >
                            Edit
                        </button>
                    </h3>

                    <dl>
                        <dt>Office</dt>
                        <dd><?= e($office['name'] ?? 'Not selected') ?></dd>

                        <dt>Office Address</dt>
                        <dd><?= e($office['address'] ?? '') ?></dd>

                        <dt>Date</dt>
                        <dd><?= e($d['date'] ?? '') ?></dd>

                        <dt>Time</dt>
                        <dd><?= e($d['time'] ?? '') ?></dd>
                    </dl>

                </div>

                <?= doc_checklist('new_enrollment') ?>

                <?= $prototypeDisclaimer ?>

                
                <label class="rad">
                    <input type="checkbox" name="ok" value="1" required>

                    I confirm that the information provided is accurate
                    to the best of my knowledge.
                </label>

                <?php if (isset($err['ok'])): ?>
                    <p class="err"><?= e($err['ok']) ?></p>
                <?php endif; ?>

                <?php if (isset($err['submit'])): ?>
                    <p class="err"><?= e($err['submit']) ?></p>
                <?php endif; ?>

                <button class="btn red" type="submit" name="a" value="submit">
                    Submit Enrollment
                </button>

                <input type="hidden" name="n" value="2">

            <?php endif; ?>

        </form>

        <?php if ($step > 2 && $step <= 10): ?>

            <form method="post">

                <?= csrf() ?>

                <input type="hidden" name="a" value="goto">

                <input
                    type="hidden"
                    name="n"
                    value="<?= $step - 1 ?>"
                >

                <button class="btn alt" type="submit">
                    ← Back
                </button>

            </form>

        <?php endif; ?>

    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>