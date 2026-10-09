<?php

/*
|--------------------------------------------------------------------------
| fields.php
|--------------------------------------------------------------------------
|
| NID Enrollment Form Field Definitions
|
| Internal enrollment steps:
|
| 2 = Personal
| 3 = Birth & Demographic
| 4 = Citizenship
| 5 = Contact & Address
| 6 = Family
| 7 = Education / Occupation
| 8 = Identification
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| FIELD FORMAT
|--------------------------------------------------------------------------
|
| [
|     field_name,
|     type,
|     required,
|     options,
|     condition
| ]
|
| Types:
|
| t = text
| m = mobile
| d = date
| e = email
| n = number
| s = select
| r = radio
|
|--------------------------------------------------------------------------
*/


$STEPS = [

    /*
    |--------------------------------------------------------------------------
    | STEP 2 — PERSONAL
    |--------------------------------------------------------------------------
    */

    2 => [
    'title' => 'Personal Information',
    'f' => [
        [
            'pd_first_name',
            't',
            true,
            'First Name',
            'Enter your first name in English'
        ],
        [
            'pd_middle_name',
            't',
            false,
            'Middle Name',
            'Enter your middle name if applicable'
        ],
        [
            'pd_last_name',
            't',
            true,
            'Last Name',
            'Enter your last name in English'
        ],

        [
            'pd_first_name_np',
            't',
            true,
            'प्रथम नाम',
            'नेपालीमा प्रथम नाम लेख्नुहोस्'
        ],
        [
            'pd_middle_name_np',
            't',
            false,
            'बीचको नाम',
            'लागू भएमा नेपालीमा बीचको नाम लेख्नुहोस्'
        ],
        [
            'pd_last_name_np',
            't',
            true,
            'थर',
            'नेपालीमा थर लेख्नुहोस्'
        ],

        [
            'pd_dob',
            'd',
            true,
            'Date of Birth',
            ''
        ],
       [
    'pd_gender',
    's',
    true,
    'Gender',
    [
        'Male',
        'Female',
        'Other'
    ]
],
        [
            'pd_nationality',
            't',
            true,
            'Nationality',
            ''
        ],
        [
    'pd_marital',
    's',
    true,
    'Marital Status',
    [
        'Single',
        'Married',
        'Divorced',
        'Widowed'
    ]
],
        [
            'pd_father_name',
            't',
            true,
            'Father\'s Name',
            ''
        ],
        [
            'pd_mother_name',
            't',
            true,
            'Mother\'s Name',
            ''
        ],
    ]
],
    /*
    |--------------------------------------------------------------------------
    | STEP 3 — BIRTH & DEMOGRAPHIC
    |--------------------------------------------------------------------------
    */

    3 => [

        'title' =>
            'Birth & Demographic Information',

        'f' => [

            [
                'birth_country',
                's',
                1,
                [
                    'Nepal',
                    'India',
                    'China',
                    'Other'
                ]
            ],

            [
                'birth_province',
                's',
                1,
                [
                    'Koshi Province',
                    'Madhesh Province',
                    'Bagmati Province',
                    'Gandaki Province',
                    'Lumbini Province',
                    'Karnali Province',
                    'Sudurpashchim Province'
                ]
            ],

            [
                'birth_district',
                't',
                1
            ],

            [
                'birth_local_level',
                't',
                1
            ],

            [
                'birth_ward',
                'n',
                1
            ],

            [
                'birth_place',
                't',
                1
            ],

            [
                'birth_certificate_no',
                't',
                0
            ],

            [
                'birth_registration_date',
                'd',
                0
            ]

        ]

    ],


    /*
    |--------------------------------------------------------------------------
    | STEP 4 — CITIZENSHIP
    |--------------------------------------------------------------------------
    */

    4 => [

        'title' =>
            'Citizenship Information',

        'f' => [

            [
                'cz_cit_no',
                't',
                1
            ],

            [
                'cz_type',
                's',
                1,
                [
                    'Citizenship by Birth',
                    'Citizenship by Descent',
                    'Naturalized Citizenship'
                ]
            ],

            [
                'cz_cit_district',
                't',
                1
            ],

            [
                'cz_issuing_office',
                't',
                1
            ],

            [
                'cz_issue_date',
                'd',
                1
            ],

            [
                'cz_issuing_authority',
                't',
                1
            ]

        ]

    ],


    /*
    |--------------------------------------------------------------------------
    | STEP 5 — CONTACT & ADDRESS
    |--------------------------------------------------------------------------
    */

    5 => [

        'title' =>
            'Contact & Address',

        'f' => [

            /*
            | Contact
            */

            [
                'ct_mobile',
                'm',
                1
            ],

            [
                'ct_alt_mobile',
                'm',
                0
            ],

            [
                'ct_email',
                'e',
                0
            ],


            /*
            | Permanent address
            */

            [
                'perm_province',
                's',
                1,
                [
                    'Koshi Province',
                    'Madhesh Province',
                    'Bagmati Province',
                    'Gandaki Province',
                    'Lumbini Province',
                    'Karnali Province',
                    'Sudurpashchim Province'
                ]
            ],

            [
                'perm_district',
                't',
                1
            ],

            [
                'perm_local_level',
                't',
                1
            ],

            [
                'perm_ward',
                'n',
                1
            ],

            [
                'perm_tole',
                't',
                1
            ],

            [
                'perm_house',
                't',
                0
            ],


            /*
            | Current address
            */

            [
                'same_addr',
                'r',
                1,
                [
                    'Yes',
                    'No'
                ]
            ],

            [
                'curr_province',
                's',
                1,
                [
                    'Koshi Province',
                    'Madhesh Province',
                    'Bagmati Province',
                    'Gandaki Province',
                    'Lumbini Province',
                    'Karnali Province',
                    'Sudurpashchim Province'
                ],
                [
                    'same_addr',
                    'No'
                ]
            ],

            [
                'curr_district',
                't',
                1,
                null,
                [
                    'same_addr',
                    'No'
                ]
            ],

            [
                'curr_local_level',
                't',
                1,
                null,
                [
                    'same_addr',
                    'No'
                ]
            ],

            [
                'curr_ward',
                'n',
                1,
                null,
                [
                    'same_addr',
                    'No'
                ]
            ],

            [
                'curr_tole',
                't',
                1,
                null,
                [
                    'same_addr',
                    'No'
                ]
            ],

            [
                'curr_house',
                't',
                0,
                null,
                [
                    'same_addr',
                    'No'
                ]
            ]

        ]

    ],


    /*
    |--------------------------------------------------------------------------
    | STEP 6 — FAMILY
    |--------------------------------------------------------------------------
    */

    6 => [

        'title' =>
            'Family Information',

        'f' => [

            /*
            | Father
            */

            [
                'fa_name',
                't',
                1
            ],

            [
                'fa_nname',
                't',
                0
            ],

            [
                'fa_cit',
                't',
                0
            ],

            [
                'fa_nat',
                's',
                0,
                [
                    'Nepali',
                    'Other'
                ]
            ],


            /*
            | Mother
            */

            [
                'mo_name',
                't',
                1
            ],

            [
                'mo_nname',
                't',
                0
            ],

            [
                'mo_cit',
                't',
                0
            ],

            [
                'mo_nat',
                's',
                0,
                [
                    'Nepali',
                    'Other'
                ]
            ],


            /*
            | Spouse
            */

            [
                'sp_name',
                't',
                1,
                null,
                [
                    'pd_marital',
                    'Married'
                ]
            ],

            [
                'sp_nname',
                't',
                0,
                null,
                [
                    'pd_marital',
                    'Married'
                ]
            ],

            [
                'sp_cit',
                't',
                0,
                null,
                [
                    'pd_marital',
                    'Married'
                ]
            ],

            [
                'sp_nat',
                's',
                0,
                [
                    'Nepali',
                    'Other'
                ],
                [
                    'pd_marital',
                    'Married'
                ]
            ],


            /*
            | Guardian
            */

            [
                'gu_name',
                't',
                0
            ],

            [
                'gu_nname',
                't',
                0
            ],

            [
                'gu_cit',
                't',
                0
            ],

            [
                'gu_nat',
                's',
                0,
                [
                    'Nepali',
                    'Other'
                ]
            ]

        ]

    ],


    /*
    |--------------------------------------------------------------------------
    | STEP 7 — EDUCATION / OCCUPATION
    |--------------------------------------------------------------------------
    */

    7 => [

        'title' =>
            'Education & Occupation',

        'f' => [

            [
                'ed_education',
                's',
                0,
                [
                    'No Formal Education',
                    'Primary',
                    'Secondary',
                    'Higher Secondary',
                    'Bachelor',
                    'Master',
                    'MPhil',
                    'PhD',
                    'Other'
                ]
            ],

            [
                'ed_occupation',
                's',
                0,
                [
                    'Student',
                    'Employed',
                    'Self Employed',
                    'Business',
                    'Farmer',
                    'Homemaker',
                    'Unemployed',
                    'Retired',
                    'Other'
                ]
            ],

            [
                'ed_organization',
                't',
                0
            ],

            [
                'ed_designation',
                't',
                0
            ],

            [
                'ed_other_occupation',
                't',
                0
            ]

        ]

    ],


    /*
    |--------------------------------------------------------------------------
    | STEP 8 — IDENTIFICATION
    |--------------------------------------------------------------------------
    */

    8 => [

        'title' =>
            'Identification Information',

        'f' => [

            [
                'id_height',
                'n',
                0
            ],

            [
                'id_blood_group',
                's',
                0,
                [
                    'A+',
                    'A-',
                    'B+',
                    'B-',
                    'AB+',
                    'AB-',
                    'O+',
                    'O-',
                    'Unknown'
                ]
            ],

            [
                'id_eye_color',
                's',
                0,
                [
                    'Black',
                    'Brown',
                    'Other'
                ]
            ],

            [
                'id_distinguishing_mark',
                't',
                0
            ],

            [
                'id_disability',
                's',
                0,
                [
                    'None',
                    'Physical',
                    'Visual',
                    'Hearing',
                    'Speech',
                    'Multiple',
                    'Other'
                ]
            ]

        ]

    ]

];


/*
|--------------------------------------------------------------------------
| LABELS
|--------------------------------------------------------------------------
*/

$FIELD_LABELS = [

    /*
    | Personal
    */

    

    'pd_first_name' => 'First Name',
'pd_middle_name' => 'Middle Name',
'pd_last_name' => 'Last Name',

'pd_first_name_np' => 'प्रथम नाम',
'pd_middle_name_np' => 'बीचको नाम',
'pd_last_name_np' => 'थर',

    'pd_dob' =>
        'Date of Birth',

    'pd_gender' =>
        'Gender',

    'pd_nationality' =>
        'Nationality',

    'pd_marital' =>
        'Marital Status',

    'pd_father_name' =>
        'Father\'s Full Name',

    'pd_mother_name' =>
        'Mother\'s Full Name',


    /*
    | Birth
    */

    'birth_country' =>
        'Country of Birth',

    'birth_province' =>
        'Province of Birth',

    'birth_district' =>
        'District of Birth',

    'birth_local_level' =>
        'Local Level',

    'birth_ward' =>
        'Ward Number',

    'birth_place' =>
        'Place of Birth',

    'birth_certificate_no' =>
        'Birth Registration Number',

    'birth_registration_date' =>
        'Birth Registration Date',


    /*
    | Citizenship
    */

    'cz_cit_no' =>
        'Citizenship Number',

    'cz_type' =>
        'Citizenship Type',

    'cz_cit_district' =>
        'Issuing District',

    'cz_issuing_office' =>
        'Issuing Office',

    'cz_issue_date' =>
        'Citizenship Issue Date',

    'cz_issuing_authority' =>
        'Issuing Authority',


    /*
    | Contact
    */

    'ct_mobile' =>
        'Mobile Number',

    'ct_alt_mobile' =>
        'Alternate Mobile Number',

    'ct_email' =>
        'Email Address',


    /*
    | Permanent address
    */

    'perm_province' =>
        'Province',

    'perm_district' =>
        'District',

    'perm_local_level' =>
        'Local Level',

    'perm_ward' =>
        'Ward Number',

    'perm_tole' =>
        'Tole / Street',

    'perm_house' =>
        'House Number',


    /*
    | Current address
    */

    'same_addr' =>
        'Current Address Same as Permanent Address?',

    'curr_province' =>
        'Current Province',

    'curr_district' =>
        'Current District',

    'curr_local_level' =>
        'Current Local Level',

    'curr_ward' =>
        'Current Ward Number',

    'curr_tole' =>
        'Current Tole / Street',

    'curr_house' =>
        'Current House Number',


    /*
    | Family
    */

    'fa_name' =>
        'Father\'s Full Name',

    'fa_nname' =>
        'Father\'s Name (Nepali)',

    'fa_cit' =>
        'Father\'s Citizenship Number',

    'fa_nat' =>
        'Father\'s Nationality',

    'mo_name' =>
        'Mother\'s Full Name',

    'mo_nname' =>
        'Mother\'s Name (Nepali)',

    'mo_cit' =>
        'Mother\'s Citizenship Number',

    'mo_nat' =>
        'Mother\'s Nationality',

    'sp_name' =>
        'Spouse\'s Full Name',

    'sp_nname' =>
        'Spouse\'s Name (Nepali)',

    'sp_cit' =>
        'Spouse\'s Citizenship Number',

    'sp_nat' =>
        'Spouse\'s Nationality',

    'gu_name' =>
        'Guardian\'s Full Name',

    'gu_nname' =>
        'Guardian\'s Name (Nepali)',

    'gu_cit' =>
        'Guardian\'s Citizenship Number',

    'gu_nat' =>
        'Guardian\'s Nationality',


    /*
    | Education
    */

    'ed_education' =>
        'Education Level',

    'ed_occupation' =>
        'Occupation',

    'ed_organization' =>
        'Organization / Institution',

    'ed_designation' =>
        'Designation',

    'ed_other_occupation' =>
        'Other Occupation',


    /*
    | Identification
    */

    'id_height' =>
        'Height (cm)',

    'id_blood_group' =>
        'Blood Group',

    'id_eye_color' =>
        'Eye Color',

    'id_distinguishing_mark' =>
        'Distinguishing Mark',

    'id_disability' =>
        'Disability Status'

];


/*
|--------------------------------------------------------------------------
| LABEL FUNCTION
|--------------------------------------------------------------------------
*/

function lbl($key)
{
    global $FIELD_LABELS;

    return $FIELD_LABELS[$key]
        ?? ucwords(
            str_replace(
                '_',
                ' ',
                $key
            )
        );
}


/*
|--------------------------------------------------------------------------
| CHECK FIELD VISIBILITY
|--------------------------------------------------------------------------
*/

function vis($field, $data)
{
    $type = $field[1] ?? 't';

    // Select/radio definitions can use either:
    // [key, type, required, options]
    // [key, type, required, label, options]
    // [key, type, required, options, condition]
    if ($type === 's' || $type === 'r') {
        if (
            isset($field[3]) && is_array($field[3]) &&
            isset($field[4]) && is_array($field[4])
        ) {
            // In this format, [3] is the options list and [4] is a condition.
            $condition = $field[4];
            $fieldName = $condition[0] ?? '';
            $expected = $condition[1] ?? '';

            if ($fieldName === '') {
                return true;
            }

            return (string)($data[$fieldName] ?? '') === (string)$expected;
        }

        // [4] is an options list when [3] is a label string.
        return true;
    }

    // Text/date/email/number/mobile definitions may use:
    // [key, type, required, label, hint]
    // [key, type, required, null, condition]
    if (isset($field[4]) && is_array($field[4])) {
        $condition = $field[4];
        $fieldName = $condition[0] ?? '';
        $expected = $condition[1] ?? '';

        if ($fieldName === '') {
            return true;
        }

        return (string)($data[$fieldName] ?? '') === (string)$expected;
    }

    return true;
}

/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function fieldValue($key)
{
    global $d;

    return e(
        $d[$key] ?? ''
    );
}


/*
|--------------------------------------------------------------------------
| RENDER FIELD
|--------------------------------------------------------------------------
*/
function fld($field)
{
    global $d, $err;

    $key      = $field[0] ?? '';
    $type     = $field[1] ?? 't';
    $required = !empty($field[2]);

    if (!vis($field, $d)) {
        return;
    }

    $id = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    $value = $d[$key] ?? '';
    $label = lbl($key);
    $hint = '';
    $options = [];

    if ($type === 's' || $type === 'r') {
        // Supports both [key, type, required, options] and
        // [key, type, required, label, options].
        if (isset($field[3]) && is_array($field[3])) {
            $options = $field[3];
        } elseif (isset($field[4]) && is_array($field[4])) {
            $options = $field[4];
        }

        $label = lbl($key);
    } else {
        if (isset($field[3]) && is_string($field[3]) && $field[3] !== '') {
            $label = $field[3];
        }

        if (isset($field[4]) && is_string($field[4])) {
            $hint = $field[4];
        }
    }

    echo '<div class="fld">';

    /*
    |--------------------------------------------------------------------------
    | LABEL
    |--------------------------------------------------------------------------
    */
    echo '<label for="' . e($id) . '">';
    echo e($label);

    if ($required) {
        echo ' <span class="rq">*</span>';
    }

    echo '</label>';

    /*
    |--------------------------------------------------------------------------
    | TEXT
    |--------------------------------------------------------------------------
    */
    if ($type === 't') {

        echo '
        <input
            id="' . e($id) . '"
            name="' . e($key) . '"
            type="text"
            value="' . e($value) . '"
            ' . ($required ? 'required' : '') . '
        >
        ';
    }

    /*
    |--------------------------------------------------------------------------
    | MOBILE
    |--------------------------------------------------------------------------
    */
    elseif ($type === 'm') {

        echo '
        <input
            id="' . e($id) . '"
            name="' . e($key) . '"
            type="tel"
            inputmode="numeric"
            maxlength="10"
            value="' . e($value) . '"
            placeholder="98XXXXXXXX"
            ' . ($required ? 'required' : '') . '
        >
        ';
    }

    /*
    |--------------------------------------------------------------------------
    | DATE
    |--------------------------------------------------------------------------
    */
    elseif ($type === 'd') {

        echo '
        <input
            id="' . e($id) . '"
            name="' . e($key) . '"
            type="date"
            value="' . e($value) . '"
            ' . ($required ? 'required' : '') . '
        >
        ';
    }

    /*
    |--------------------------------------------------------------------------
    | EMAIL
    |--------------------------------------------------------------------------
    */
    elseif ($type === 'e') {

        echo '
        <input
            id="' . e($id) . '"
            name="' . e($key) . '"
            type="email"
            value="' . e($value) . '"
            placeholder="example@email.com"
            ' . ($required ? 'required' : '') . '
        >
        ';
    }

    /*
    |--------------------------------------------------------------------------
    | NUMBER
    |--------------------------------------------------------------------------
    */
    elseif ($type === 'n') {

        echo '
        <input
            id="' . e($id) . '"
            name="' . e($key) . '"
            type="number"
            value="' . e($value) . '"
            min="1"
            ' . ($required ? 'required' : '') . '
        >
        ';
    }

    /*
    |--------------------------------------------------------------------------
    | SELECT
    |--------------------------------------------------------------------------
    */
    elseif ($type === 's') {

        echo '
        <select
            id="' . e($id) . '"
            name="' . e($key) . '"
            ' . ($required ? 'required' : '') . '
        >
            <option value="">
                Select ' . e($label) . '
            </option>
        ';

        foreach ($options as $option) {

            echo '
            <option
                value="' . e($option) . '"
                ' . ($value === $option ? 'selected' : '') . '
            >
                ' . e($option) . '
            </option>
            ';
        }

        echo '
        </select>
        ';
    }

    /*
    |--------------------------------------------------------------------------
    | RADIO
    |--------------------------------------------------------------------------
    */
    elseif ($type === 'r') {

        echo '<div class="radio-group">';

        foreach ($options as $option) {

            echo '
            <label class="rad">
                <input
                    type="radio"
                    name="' . e($key) . '"
                    value="' . e($option) . '"
                    ' . ($value === $option ? 'checked' : '') . '
                    ' . ($required ? 'required' : '') . '
                >
                ' . e($option) . '
            </label>
            ';
        }

        echo '</div>';
    }

    /*
    |--------------------------------------------------------------------------
    | FILE
    |--------------------------------------------------------------------------
    */
    elseif ($type === 'f') {

        echo '
        <input
            id="' . e($id) . '"
            name="' . e($key) . '"
            type="file"
            ' . ($required ? 'required' : '') . '
        >
        ';
    }

    /*
    |--------------------------------------------------------------------------
    | ERROR
    |--------------------------------------------------------------------------
    */
    if (isset($err[$key])) {

        echo '
        <p class="err">
            ' . e($err[$key]) . '
        </p>
        ';
    }

    /*
    |--------------------------------------------------------------------------
    | FIELD HINT
    |--------------------------------------------------------------------------
    */
    if ($hint !== '') {

        echo '
        <p class="hint">
            ' . e($hint) . '
        </p>
        ';
    }

    /*
    |--------------------------------------------------------------------------
    | SPECIAL HINTS
    |--------------------------------------------------------------------------
    */
    if ($key === 'cz_cit_no') {

        echo '
        <p class="hint">
            Enter the citizenship number exactly
            as printed on your citizenship certificate.
        </p>
        ';
    }

    if ($key === 'ct_mobile') {

        echo '
        <p class="hint">
            This number was verified before starting
            the enrollment.
        </p>
        ';
    }

    if ($key === 'id_height') {

        echo '
        <p class="hint">
            Enter height in centimeters.
        </p>
        ';
    }

    echo '</div>';
}


/*
|--------------------------------------------------------------------------
| DOCUMENT CHECKLIST
|--------------------------------------------------------------------------
|
| Context:
|
| new_enrollment
|
|--------------------------------------------------------------------------
*/




/*
|--------------------------------------------------------------------------
| TRANSLATION FALLBACKS
|--------------------------------------------------------------------------
|
| These are only used if your translation files do not
| already provide the requested strings.
|
|--------------------------------------------------------------------------
*/

if (!function_exists('field_t')) {

    function field_t($key)
    {
        $fallbacks = [

            'req_s' =>
                'Required',

            'mobile_bad' =>
                'Enter a valid mobile number.',

            'date_bad' =>
                'Enter a valid date.',

            'email_bad' =>
                'Enter a valid email address.',

            'num_bad' =>
                'Enter a valid number.',

            'docs_bad' =>
                'Please confirm that you will bring the required documents.',

            'confirm_bad' =>
                'Please confirm that the information provided is correct.',

            'next' =>
                'Continue',

            'back' =>
                'Back',

            'edit' =>
                'Edit',

            'office' =>
                'Appointment Office',

            'date' =>
                'Appointment Date',

            'time' =>
                'Appointment Time',

            'token_note' =>
                'Your token number will be generated after submission.',

            'upload_note' =>
                'Document uploads are not required in this prototype. Bring original documents to your appointment.',

            'docs_ack' =>
                'I confirm that I will bring the required original documents.',

            'confirm' =>
                'I confirm that the information provided above is accurate.',

            'cz_warn' =>
                'Please enter your citizenship information exactly as shown on your citizenship certificate.'

        ];

        return $fallbacks[$key]
            ?? $key;
    }

}