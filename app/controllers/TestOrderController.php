<?php
class TestOrderController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            // For AJAX requests, return JSON; otherwise redirect
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'غير مصرح — يرجى تسجيل الدخول'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            redirect('/login');
        }
    }

    // AJAX: search tests by query
    public function ajaxSearchTests()
    {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            $this->json(['success' => true, 'tests' => []]);
        }
        $tests = (new TestCatalog())->search($q);
        $this->json(['success' => true, 'tests' => $tests]);
    }

    // AJAX: check if duplicate exists
    public function ajaxCheckDuplicate()
    {
        $patientId = (int) ($_GET['patient_id'] ?? 0);
        $testId = (int) ($_GET['test_id'] ?? 0);
        if (!$patientId || !$testId) {
            $this->json(['success' => false, 'message' => 'بيانات ناقصة']);
        }
        $test = Database::fetch("SELECT * FROM tests_catalog WHERE id = ?", [$testId]);
        if (!$test) {
            $this->json(['success' => false, 'message' => 'التحليل غير موجود']);
        }
        $window = (new Setting())->getDuplicateWindowDays();
        $dup = (new TestOrder())->checkDuplicate($patientId, $test['loinc_code'], $window);
        if ($dup) {
            $this->json([
                'success' => true,
                'duplicate' => true,
                'prev' => [
                    'ordered_at' => $dup['ordered_at'],
                    'days_diff' => (int) ((time() - strtotime($dup['ordered_at'])) / 86400),
                    'result_value' => $dup['result_value'],
                    'unit' => $dup['unit'],
                    'normal_range' => $dup['normal_range'],
                    'flag' => $dup['flag'],
                    'doctor_name' => $dup['doctor_name'],
                    'test_name' => $dup['name_ar'],
                ]
            ]);
        }
        $this->json(['success' => true, 'duplicate' => false]);
    }
}
