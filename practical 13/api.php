<?php
/**
 * Practical 13: RESTful JSON API Endpoint for AJAX/Fetch-Based Asynchronous Operations
 * Supported Methods:
 * - GET    : Read all students or search with query
 * - POST   : Create new student record
 * - PUT    : Update existing student record
 * - DELETE : Delete student record
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Handle preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../practical 8/db.php';
$pdo = getDBConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Helper JSON Response function
function sendJson($status, $data = [], $message = '') {
    http_response_code($status);
    echo json_encode([
        'success' => ($status >= 200 && $status < 300),
        'message' => $message,
        'data'    => $data,
        'timestamp' => time()
    ]);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            // Search / Read records
            $search = trim($_GET['search'] ?? '');
            if ($search !== '') {
                $stmt = $pdo->prepare("
                    SELECT s.*, d.name AS department_name, d.code AS department_code
                    FROM students s
                    JOIN departments d ON s.department_id = d.id
                    WHERE s.full_name LIKE :s1 OR s.student_id LIKE :s2 OR s.email LIKE :s3
                    ORDER BY s.id DESC
                ");
                $param = "%$search%";
                $stmt->execute(['s1' => $param, 's2' => $param, 's3' => $param]);
            } else {
                $stmt = $pdo->query("
                    SELECT s.*, d.name AS department_name, d.code AS department_code
                    FROM students s
                    JOIN departments d ON s.department_id = d.id
                    ORDER BY s.id DESC
                ");
            }
            $students = $stmt->fetchAll();
            sendJson(200, $students, 'Students retrieved successfully');
            break;

        case 'POST':
            // Create record
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: $_POST;

            $sid   = strtoupper(trim($data['student_id'] ?? ''));
            $name  = trim($data['full_name'] ?? '');
            $email = strtolower(trim($data['email'] ?? ''));
            $phone = trim($data['phone'] ?? '');
            $dept  = (int)($data['department_id'] ?? 1);
            $sem   = (int)($data['semester'] ?? 1);
            $cgpa  = (float)($data['cgpa'] ?? 0.0);

            if (empty($sid) || empty($name) || empty($email)) {
                sendJson(422, null, 'Student ID, Name, and Email are required.');
            }

            // Check duplicate
            $chk = $pdo->prepare("SELECT id FROM students WHERE student_id = :sid OR email = :email");
            $chk->execute(['sid' => $sid, 'email' => $email]);
            if ($chk->fetch()) {
                sendJson(409, null, 'Duplicate record: Student ID or Email already exists.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO students (student_id, full_name, email, phone, department_id, semester, cgpa)
                VALUES (:sid, :name, :email, :phone, :dept, :sem, :cgpa)
            ");
            $stmt->execute([
                'sid' => $sid, 'name' => $name, 'email' => $email,
                'phone' => $phone, 'dept' => $dept, 'sem' => $sem, 'cgpa' => $cgpa
            ]);
            $insertedId = $pdo->lastInsertId();

            sendJson(201, ['id' => $insertedId], 'Student created successfully without page reload');
            break;

        case 'PUT':
            // Update record
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);

            $id    = (int)($data['id'] ?? 0);
            $sid   = strtoupper(trim($data['student_id'] ?? ''));
            $name  = trim($data['full_name'] ?? '');
            $email = strtolower(trim($data['email'] ?? ''));
            $phone = trim($data['phone'] ?? '');
            $dept  = (int)($data['department_id'] ?? 1);
            $sem   = (int)($data['semester'] ?? 1);
            $cgpa  = (float)($data['cgpa'] ?? 0.0);

            if (!$id || empty($sid) || empty($name)) {
                sendJson(422, null, 'Valid Student ID and primary details required for update.');
            }

            $stmt = $pdo->prepare("
                UPDATE students
                SET student_id = :sid, full_name = :name, email = :email,
                    phone = :phone, department_id = :dept, semester = :sem, cgpa = :cgpa
                WHERE id = :id
            ");
            $stmt->execute([
                'sid' => $sid, 'name' => $name, 'email' => $email,
                'phone' => $phone, 'dept' => $dept, 'sem' => $sem, 'cgpa' => $cgpa,
                'id' => $id
            ]);

            sendJson(200, ['id' => $id], 'Student record updated asynchronously');
            break;

        case 'DELETE':
            // Delete record
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            $id = (int)($data['id'] ?? ($_GET['id'] ?? 0));

            if (!$id) {
                sendJson(422, null, 'Student ID parameter missing');
            }

            $stmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
            $stmt->execute(['id' => $id]);

            sendJson(200, ['id' => $id], 'Student record deleted smoothly');
            break;

        default:
            sendJson(405, null, 'HTTP Method Not Allowed');
            break;
    }
} catch (Exception $e) {
    sendJson(500, null, 'Server Error: ' . $e->getMessage());
}
