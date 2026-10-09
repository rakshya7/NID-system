<?php


// Shared Bootstrap
// Session, Database, Language, Helpers, CSRF, Documents
 

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


 
// DATABASE


try {

    $pdo = new PDO(
        'mysql:host=localhost;dbname=nid_prototype;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );

} catch (Throwable $e) {

    die(
        'Database unavailable. Please import database/nid_prototype.sql.'
    );
}




if (
    isset($_GET['lang']) &&
    in_array($_GET['lang'], ['en', 'ne'], true)
) {
    $_SESSION['lang'] = $_GET['lang'];
}

$L = $_SESSION['lang'] ?? 'en';

$EN = require __DIR__ . '/../lang/en.php';
$T  = require __DIR__ . "/../lang/$L.php";




if (!function_exists('t')) {

    function t($key)
    {
        global $T, $EN;

        return htmlspecialchars(
            $T[$key] ?? $EN[$key] ?? $key,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}



if (!function_exists('e')) {

    function e($value)
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}



if (!function_exists('csrf')) {

    function csrf()
    {
        if (empty($_SESSION['c'])) {
            $_SESSION['c'] = bin2hex(random_bytes(16));
        }

        return
            '<input type="hidden" name="c" value="' .
            e($_SESSION['c']) .
            '">';
    }
}


if (!function_exists('check_csrf')) {

    function check_csrf()
    {
        $sessionToken = $_SESSION['c'] ?? '';
        $postedToken  = $_POST['c'] ?? '';

        if (
            empty($sessionToken) ||
            empty($postedToken) ||
            !hash_equals($sessionToken, $postedToken)
        ) {
            die('Invalid request.');
        }
    }
}




if (!function_exists('valid_date')) {

    function valid_date($date, $future = false)
    {
        if (empty($date)) {
            return false;
        }

        $d = DateTime::createFromFormat(
            'Y-m-d',
            $date
        );

        if (
            !$d ||
            $d->format('Y-m-d') !== $date
        ) {
            return false;
        }

        if (
            $future &&
            $d < new DateTime('today')
        ) {
            return false;
        }

        return true;
    }
}



if (!function_exists('ins')) {

    function ins($table, $data)
    {
        global $pdo;

        if (empty($data)) {
            throw new RuntimeException(
                "Cannot insert empty data into table `$table`."
            );
        }

        $columns = array_keys($data);

        $placeholders = array_fill(
            0,
            count($columns),
            '?'
        );

        $sql =
            "INSERT INTO `$table` (`" .
            implode('`,`', $columns) .
            "`) VALUES (" .
            implode(',', $placeholders) .
            ")";

        try {

            $stmt = $pdo->prepare($sql);

            $stmt->execute(
                array_values($data)
            );

        } catch (Throwable $e) {

            throw new RuntimeException(
                "INSERT FAILED | " .
                "TABLE: `$table` | " .
                "COLUMNS: " .
                implode(', ', $columns) .
                " | ERROR: " .
                $e->getMessage(),
                0,
                $e
            );
        }

        return $pdo->lastInsertId();
    }
}



if (!function_exists('pick')) {

    function pick($data, $prefix)
    {
        $result = [];

        foreach ($data as $key => $value) {

            if (
                strpos($key, $prefix) === 0
            ) {

                $newKey = substr(
                    $key,
                    strlen($prefix)
                );

                if ($newKey !== '') {
                    $result[$newKey] = $value;
                }
            }
        }

        return $result;
    }
}



if (!function_exists('doc_checklist')) {

    function doc_checklist($type)
    {
        $req = [
            'new_enrollment' => [
                'doc_citizenship'
            ],

            'replacement' => []
        ];

        $opt = [
            'new_enrollment' => [
                'doc_migration',
                'doc_marriage',
                'doc_dob',
                'doc_other'
            ],

            'replacement' => [
                'doc_card',
                'doc_orig',
                'doc_other'
            ]
        ];

        $h =
            '<section class="bring">' .
            '<h3>' .
            t('bring_title') .
            '</h3>' .
            '<p class="warn">' .
            t('doc_warn') .
            '</p>';

        $sections = [
            [
                'req_h',
                $req[$type] ?? []
            ],
            [
                $type === 'replacement'
                    ? 'rep_bring_h'
                    : 'cond_h',
                $opt[$type] ?? []
            ]
        ];

        foreach ($sections as [$headingKey, $list]) {

            if (empty($list)) {
                continue;
            }

            $h .=
                '<h4>' .
                t($headingKey) .
                '</h4>' .
                '<ul>';

            foreach ($list as $document) {

                $h .=
                    '<li>' .
                    '<details>' .
                    '<summary>' .
                    '<b>☐ ' .
                    t($document) .
                    '</b>' .
                    '</summary>' .
                    '<p>' .
                    t($document . '_x') .
                    '</p>' .
                    '</details>' .
                    '</li>';
            }

            $h .= '</ul>';
        }

        $h .= '</section>';

        return $h;
    }
}