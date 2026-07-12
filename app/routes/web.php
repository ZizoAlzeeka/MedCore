<?php
/**
 * Web routes definition
 * @var Router $router
 */
$router = new Router();

// ===== Public: download full logs / server diagnostics as txt =====
$router->add('GET', '/download-logs', function () {
    $sections = [];

    // Section A: diagnostic snapshot (server, env, db, session, extensions)
    try {
        $sections[] = Logger::diagnosticSnapshot();
    } catch (Throwable $e) {
        $sections[] = "[diagnostic snapshot error] " . $e->getMessage();
    }

    // Section B: full contents of every log file
    $sections[] = '';
    $sections[] = str_repeat('#', 78);
    $sections[] = '##### FULL LOG FILES CONTENT #####';
    $sections[] = str_repeat('#', 78);

    $logFiles = Logger::allFiles();
    if (empty($logFiles)) {
        $sections[] = '(no log files exist yet — request logging will create them)';
    } else {
        foreach ($logFiles as $f) {
            $sections[] = '';
            $sections[] = str_repeat('-', 78);
            $sections[] = '### FILE: ' . basename($f)
                . '  |  size: ' . filesize($f) . ' bytes'
                . '  |  modified: ' . date('Y-m-d H:i:s', filemtime($f));
            $sections[] = str_repeat('-', 78);
            $content = @file_get_contents($f);
            $sections[] = ($content !== false && $content !== '') ? $content : '(empty or unreadable)';
        }
    }

    // Section C: tail of today's log even if just generated
    $todayFile = Logger::logDir() . '/app-' . date('Y-m-d') . '.log';
    $sections[] = '';
    $sections[] = str_repeat('#', 78);
    $sections[] = '##### TODAY LOG TAIL (' . basename($todayFile) . ') #####';
    $sections[] = str_repeat('#', 78);
    if (is_file($todayFile) && is_readable($todayFile)) {
        $tail = @file_get_contents($todayFile);
        $sections[] = $tail ?: '(empty)';
    } else {
        $sections[] = '(today log does not exist yet)';
    }

    $sections[] = '';
    $sections[] = str_repeat('=', 78);
    $sections[] = 'End of report — ' . date('Y-m-d H:i:s');
    $sections[] = str_repeat('=', 78);

    $content = implode("\n", $sections);

    // Log that someone downloaded the logs (audit)
    Logger::audit('downloaded-logs', Auth::id() ?: 0, [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ]);

    $filename = 'medcore-logs-' . date('Y-m-d_His') . '.txt';
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $content;
    exit;
});

// ===== Auth routes (public) =====
$router->add('GET',  '/login',  'AuthController@showLogin');
$router->add('POST', '/login',  'AuthController@login');
$router->add('GET',  '/register', 'AuthController@showRegister');
$router->add('POST', '/register', 'AuthController@register');
$router->add('GET',  '/logout', 'AuthController@logout');

// ===== Admin routes =====
$router->add('GET',  '/admin', 'AdminController@dashboard');
$router->add('GET',  '/admin/users', 'AdminController@users');
$router->add('GET',  '/admin/users/create', 'AdminController@createUser');
$router->add('POST', '/admin/users/store', 'AdminController@storeUser');
$router->add('GET',  '/admin/users/{id}/edit', 'AdminController@editUser');
$router->add('POST', '/admin/users/{id}/update', 'AdminController@updateUser');
$router->add('POST', '/admin/users/{id}/toggle', 'AdminController@toggleUser');
$router->add('GET',  '/admin/departments', 'AdminController@departments');
$router->add('POST', '/admin/departments/store', 'AdminController@storeDepartment');
$router->add('POST', '/admin/departments/{id}/update', 'AdminController@updateDepartment');
$router->add('POST', '/admin/departments/{id}/delete', 'AdminController@deleteDepartment');
$router->add('GET',  '/admin/tests', 'AdminController@testsCatalog');
$router->add('POST', '/admin/tests/store', 'AdminController@storeTest');
$router->add('POST', '/admin/tests/{id}/update', 'AdminController@updateTest');
$router->add('POST', '/admin/tests/{id}/delete', 'AdminController@deleteTest');
$router->add('GET',  '/admin/reports', 'AdminController@reports');
$router->add('GET',  '/admin/settings', 'AdminController@settings');
$router->add('POST', '/admin/settings', 'AdminController@updateSettings');
$router->add('GET',  '/admin/logs', 'AdminController@logs');

// ===== Doctor routes =====
$router->add('GET',  '/doctor', 'DoctorController@dashboard');
$router->add('GET',  '/doctor/appointments', 'DoctorController@appointments');
$router->add('GET',  '/doctor/patients', 'DoctorController@patients');
$router->add('GET',  '/doctor/patients/{id}', 'DoctorController@patientProfile');
$router->add('GET',  '/doctor/patients/{id}/order-test', 'DoctorController@showOrderTest');
$router->add('POST', '/doctor/patients/{id}/order-test', 'DoctorController@storeOrderTest');
$router->add('POST', '/doctor/orders/{id}/decision', 'DoctorController@orderDecision');
$router->add('GET',  '/doctor/schedule', 'DoctorController@schedule');
$router->add('POST', '/doctor/schedule/store', 'DoctorController@storeSchedule');
$router->add('POST', '/doctor/schedule/{id}/delete', 'DoctorController@deleteSchedule');
$router->add('GET',  '/doctor/orders/{orderId}/treatment', 'DoctorController@showTreatmentForm');
$router->add('POST', '/doctor/orders/{orderId}/treatment', 'DoctorController@storeTreatment');
$router->add('POST', '/doctor/patients/{id}/refer', 'DoctorController@referPatient');

// ===== Reception routes =====
$router->add('GET',  '/reception', 'ReceptionController@dashboard');
$router->add('GET',  '/reception/search-patient', 'ReceptionController@searchPatient');
$router->add('GET',  '/reception/book', 'ReceptionController@showBook');
$router->add('POST', '/reception/book', 'ReceptionController@storeBooking');
$router->add('GET',  '/reception/appointments', 'ReceptionController@appointments');
$router->add('POST', '/reception/appointments/{id}/cancel', 'ReceptionController@cancelAppointment');
$router->add('GET',  '/reception/doctor-schedules', 'ReceptionController@doctorSchedules');
$router->add('POST', '/reception/register-patient', 'ReceptionController@registerPatient');

// ===== Lab Tech routes =====
$router->add('GET',  '/labtech', 'LabTechController@dashboard');
$router->add('GET',  '/labtech/orders', 'LabTechController@orders');
$router->add('GET',  '/labtech/orders/{id}/upload', 'LabTechController@showUpload');
$router->add('POST', '/labtech/orders/{id}/upload', 'LabTechController@storeUpload');

// ===== Patient routes =====
$router->add('GET',  '/patient', 'PatientController@dashboard');
$router->add('GET',  '/patient/results', 'PatientController@results');
$router->add('GET',  '/patient/results/{id}', 'PatientController@resultDetail');
$router->add('GET',  '/patient/treatment', 'PatientController@treatment');
$router->add('GET',  '/patient/appointments', 'PatientController@appointments');
$router->add('GET',  '/patient/print-report', 'PatientController@printReport');

// ===== Notifications =====
$router->add('GET',  '/notifications', 'NotificationController@index');
$router->add('POST', '/notifications/{id}/read', 'NotificationController@markRead');
$router->add('POST', '/notifications/read-all', 'NotificationController@markAllRead');
$router->ajax('GET',  '/notifications/unread-count', 'NotificationController@unreadCount');

// ===== Profile =====
$router->add('GET',  '/profile', 'ProfileController@show');
$router->add('POST', '/profile', 'ProfileController@update');

// ===== AJAX endpoints =====
$router->ajax('GET',  '/ajax/tests/search', 'TestOrderController@ajaxSearchTests');

// ===== Public AJAX: LOINC search via NLM Clinical Tables API (admin/tests modal) =====
$router->ajax('GET', '/ajax/loinc/search', function () {
    $q = trim($_GET['q'] ?? '');
    if (mb_strlen($q) < 2) {
        return ['success' => true, 'results' => []];
    }

    // Primary source: NLM Clinical Tables (free, no API key required)
    // Endpoint: https://clinicaltables.nlm.nih.gov/api/loinc_items/v3/search
    // Response format: [totalCount, [loincNumbers...], null, [[names...]]]
    $results = [];
    $upstreamHit = false;

    $url = 'https://clinicaltables.nlm.nih.gov/api/loinc_items/v3/search?terms=' . urlencode($q) . '&max=20&df=text,LOINC_NUM';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'MedCore/1.0 (admin tests catalog)',
    ]);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw && $httpCode === 200) {
        $data = json_decode($raw, true);
        // Format: [count, [codes], null, [[name], [name], ...]]
        if (is_array($data) && count($data) >= 4 && is_array($data[1]) && is_array($data[3])) {
            $upstreamHit = true;
            $codes = $data[1];
            $names = $data[3];
            $count = min(count($codes), count($names));
            for ($i = 0; $i < $count; $i++) {
                $nameStr = is_array($names[$i]) ? implode(' - ', array_filter($names[$i])) : (string)$names[$i];
                $results[] = [
                    'loinc_code'  => $codes[$i],
                    'name_en'     => $nameStr,
                    'name_ar'     => '', // NLM doesn't provide Arabic — admin fills
                    'short_name'  => '',
                    'category'    => '',
                    'sample_type' => '',
                    'source'      => 'NLM',
                ];
            }
        }
    }

    // If NLM failed or returned no results, fallback to our local DB
    if (empty($results)) {
        try {
            $qq = "%$q%";
            $rows = Database::fetchAll(
                "SELECT loinc_code, name_ar, name_en, category, sample_type
                 FROM tests_catalog
                 WHERE loinc_code LIKE ? OR name_en LIKE ? OR name_ar LIKE ? OR category LIKE ?
                 ORDER BY name_ar LIMIT 20",
                [$qq, $qq, $qq, $qq]
            );
            foreach ($rows as $row) {
                $results[] = [
                    'loinc_code'   => $row['loinc_code'],
                    'name_en'      => $row['name_en'] ?? '',
                    'name_ar'      => $row['name_ar'] ?? '',
                    'short_name'   => '',
                    'category'     => $row['category'] ?? '',
                    'sample_type'  => $row['sample_type'] ?? '',
                    'source'       => 'LOCAL',
                ];
            }
        } catch (Throwable $e) {
            Logger::warning('LOINC search local fallback failed: ' . $e->getMessage());
        }
    }

    Logger::info('LOINC search', [
        'query' => $q,
        'upstream_hit' => $upstreamHit,
        'results_count' => count($results),
        'curl_err' => $err ?: null,
        'http_code' => $httpCode,
    ]);

    return ['success' => true, 'results' => $results, 'source' => $upstreamHit ? 'NLM' : 'LOCAL'];
});
$router->ajax('GET',  '/ajax/check-duplicate', 'TestOrderController@ajaxCheckDuplicate');
$router->ajax('GET',  '/ajax/doctors/by-department', 'ReceptionController@ajaxDoctorsByDept');
$router->ajax('GET',  '/ajax/doctor/{id}/slots', 'ReceptionController@ajaxDoctorSlots');

// ===== Home =====
$router->add('GET', '/', function() {
    if (Auth::check()) {
        $home = [
            'admin' => '/admin',
            'doctor' => '/doctor',
            'reception' => '/reception',
            'lab_tech' => '/labtech',
            'patient' => '/patient',
        ];
        redirect($home[Auth::role()] ?? '/login');
    }
    redirect('/login');
});

return $router;
