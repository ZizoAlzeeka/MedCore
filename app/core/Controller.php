<?php
/**
 * Base Controller class
 */
class Controller
{
    protected function view($name, $data = [], $statusCode = 200)
    {
        http_response_code($statusCode);
        extract($data);
        $viewFile = dirname(__DIR__) . "/views/{$name}.php";
        if (!file_exists($viewFile)) {
            throw new Exception("View not found: {$name}");
        }
        require $viewFile;
    }

    protected function viewWithLayout($name, $data = [], $layout = 'app')
    {
        extract($data);
        $contentFile = dirname(__DIR__) . "/views/{$name}.php";
        if (!file_exists($contentFile)) {
            throw new Exception("View not found: {$name}");
        }
        ob_start();
        require $contentFile;
        $content = ob_get_clean();
        $view = $content;
        require dirname(__DIR__) . "/views/layouts/{$layout}.php";
    }

    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect($path)
    {
        header('Location: ' . $path);
        exit;
    }

    protected function redirectSuccess($path, $message)
    {
        flash('success', $message);
        $this->redirect($path);
    }

    protected function redirectError($path, $message)
    {
        flash('error', $message);
        $this->redirect($path);
    }

    protected function input($key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function validate($rules, $data = null)
    {
        $data = $data ?? $_POST;
        $errors = [];
        foreach ($rules as $field => $rule) {
            $ruleParts = explode('|', $rule);
            $value = $data[$field] ?? null;
            foreach ($ruleParts as $r) {
                if ($r === 'required' && empty($value)) {
                    $errors[$field] = "حقل مطلوب";
                    break;
                }
                if ($r === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "بريد إلكتروني غير صالح";
                    break;
                }
                if (strpos($r, 'min:') === 0) {
                    $min = (int) substr($r, 4);
                    if (!empty($value) && mb_strlen($value) < $min) {
                        $errors[$field] = "الحد الأدنى $min أحرف";
                        break;
                    }
                }
                if (strpos($r, 'in:') === 0) {
                    $allowed = explode(',', substr($r, 3));
                    if (!empty($value) && !in_array($value, $allowed)) {
                        $errors[$field] = "قيمة غير صالحة";
                        break;
                    }
                }
                if ($r === 'numeric_en' && !empty($value) && !preg_match('/^[0-9]+$/', $value)) {
                    $errors[$field] = "أرقام إنجليزية فقط";
                    break;
                }
            }
        }
        return $errors;
    }
}
