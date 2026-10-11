<?php
/**
 * Practical 11: Student Management CRUD with Search, Filter, and Prepared MySQL Statements
 * Course: Web Development Frameworks (ITUE203) - CHARUSAT
 */

require_once __DIR__ . '/../practical 8/db.php';
$pdo = getDBConnection();

$msg = '';
$error = '';

// Handle Delete Student Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    try {
        $delStmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
        $delStmt->execute(['id' => $delId]);
        header("Location: index.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Cannot delete student: " . $e->getMessage();
    }
}

// Handle Create / Update Student Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_student'])) {
    $studentDbId = !empty($_POST['db_id']) ? (int)$_POST['db_id'] : null;
    $studentId   = strtoupper(trim($_POST['student_id'] ?? ''));
    $fullName    = trim($_POST['full_name'] ?? '');
    $email       = strtolower(trim($_POST['email'] ?? ''));
    $phone       = trim($_POST['phone'] ?? '');
    $deptId      = (int)($_POST['department_id'] ?? 0);
    $semester    = (int)($_POST['semester'] ?? 1);
    $cgpa        = (float)($_POST['cgpa'] ?? 0.0);

    // Validations
    if (empty($studentId) || empty($fullName) || empty($email) || empty($deptId)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            if ($studentDbId) {
                // Update Student
                $checkDuplicate = $pdo->prepare("SELECT id FROM students WHERE (student_id = :sid OR email = :email) AND id != :id");
                $checkDuplicate->execute(['sid' => $studentId, 'email' => $email, 'id' => $studentDbId]);
                if ($checkDuplicate->fetch()) {
                    $error = "Another student already exists with this Student ID or Email.";
                } else {
                    $updateStmt = $pdo->prepare("
                        UPDATE students
                        SET student_id = :sid, full_name = :name, email = :email, phone = :phone,
                            department_id = :dept, semester = :sem, cgpa = :cgpa
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        'sid'   => $studentId,
                        'name'  => $fullName,
                        'email' => $email,
                        'phone' => $phone,
                        'dept'  => $deptId,
                        'sem'   => $semester,
                        'cgpa'  => $cgpa,
                        'id'    => $studentDbId
                    ]);
                    header("Location: index.php?msg=updated");
                    exit;
                }
            } else {
                // Insert New Student
                $checkDuplicate = $pdo->prepare("SELECT id FROM students WHERE student_id = :sid OR email = :email");
                $checkDuplicate->execute(['sid' => $studentId, 'email' => $email]);
                if ($checkDuplicate->fetch()) {
                    $error = "A student with this Student ID or Email already exists.";
                } else {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO students (student_id, full_name, email, phone, department_id, semester, cgpa)
                        VALUES (:sid, :name, :email, :phone, :dept, :sem, :cgpa)
                    ");
                    $insertStmt->execute([
                        'sid'   => $studentId,
                        'name'  => $fullName,
                        'email' => $email,
                        'phone' => $phone,
                        'dept'  => $deptId,
                        'sem'   => $semester,
                        'cgpa'  => $cgpa
                    ]);
                    header("Location: index.php?msg=created");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch all departments for select dropdown
$deptStmt = $pdo->query("SELECT id, code, name FROM departments ORDER BY name ASC");
$departments = $deptStmt->fetchAll();

// Handle Edit Mode
$editStudent = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editStmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $editStmt->execute(['id' => (int)$_GET['id']]);
    $editStudent = $editStmt->fetch();
}

// Filters & Search Parameters
$searchQ   = trim($_GET['search'] ?? '');
$filterDept = trim($_GET['dept_filter'] ?? '');
$filterSem  = trim($_GET['sem_filter'] ?? '');
$sortBy     = trim($_GET['sort'] ?? 'name_asc');

// Build Dynamic Filtered SQL Query
$sql = "
    SELECT s.*, d.name AS department_name, d.code AS department_code
    FROM students s
    JOIN departments d ON s.department_id = d.id
    WHERE 1=1
";
$params = [];

if ($searchQ !== '') {
    $sql .= " AND (s.full_name LIKE :search1 OR s.student_id LIKE :search2 OR s.email LIKE :search3)";
    $params['search1'] = "%$searchQ%";
    $params['search2'] = "%$searchQ%";
    $params['search3'] = "%$searchQ%";
}

if ($filterDept !== '' && $filterDept !== 'ALL') {
    $sql .= " AND s.department_id = :fdept";
    $params['fdept'] = (int)$filterDept;
}

if ($filterSem !== '' && $filterSem !== 'ALL') {
    $sql .= " AND s.semester = :fsem";
    $params['fsem'] = (int)$filterSem;
}

switch ($sortBy) {
    case 'name_desc': $sql .= " ORDER BY s.full_name DESC"; break;
    case 'cgpa_desc': $sql .= " ORDER BY s.cgpa DESC"; break;
    case 'cgpa_asc':  $sql .= " ORDER BY s.cgpa ASC"; break;
    case 'id_asc':    $sql .= " ORDER BY s.student_id ASC"; break;
    default:          $sql .= " ORDER BY s.full_name ASC"; break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $msg = 'Student record registered successfully.';
    if ($_GET['msg'] === 'updated') $msg = 'Student record updated successfully.';
    if ($_GET['msg'] === 'deleted') $msg = 'Student record removed from system.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 11 - Student Management CRUD with Search & Filter</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <span class="badge">Practical 11 • ITUE203</span>
            <h1>Student Management CRUD System</h1>
            <p>Prepared SQL Queries, Dynamic Parameterized Filtering, Search & Relational Joins</p>
        </header>

        <?php if ($msg): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="crud-layout">
            <!-- Left: Add / Edit Form -->
            <section class="card form-card">
                <h2>
                    <i class="fa-solid <?= $editStudent ? 'fa-user-pen' : 'fa-user-plus' ?>"></i>
                    <?= $editStudent ? 'Edit Student Details' : 'Add New Student' ?>
                </h2>

                <form method="POST" action="index.php">
                    <input type="hidden" name="save_student" value="1">
                    <?php if ($editStudent): ?>
                        <input type="hidden" name="db_id" value="<?= $editStudent['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Student ID (Roll No) <span class="req">*</span></label>
                        <input type="text" name="student_id" value="<?= htmlspecialchars($editStudent['student_id'] ?? '') ?>" placeholder="e.g. 23IT099" required>
                    </div>

                    <div class="form-group">
                        <label>Full Name <span class="req">*</span></label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($editStudent['full_name'] ?? '') ?>" placeholder="e.g. Het Patel" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address <span class="req">*</span></label>
                        <input type="email" name="email" value="<?= htmlspecialchars($editStudent['email'] ?? '') ?>" placeholder="e.g. het@charusat.edu.in" required>
                    </div>

                    <div class="form-group">
                        <label>Contact Phone</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($editStudent['phone'] ?? '') ?>" placeholder="10-digit number">
                    </div>

                    <div class="form-group">
                        <label>Department <span class="req">*</span></label>
                        <select name="department_id" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ($editStudent && $editStudent['department_id'] == $d['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label>Semester</label>
                            <select name="semester">
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>" <?= ($editStudent && $editStudent['semester'] == $s) ? 'selected' : '' ?>>Sem <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>CGPA</label>
                            <input type="number" step="0.01" min="0" max="10" name="cgpa" value="<?= htmlspecialchars($editStudent['cgpa'] ?? '8.50') ?>">
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> <?= $editStudent ? 'Update Record' : 'Save Student' ?>
                        </button>
                        <?php if ($editStudent): ?>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <!-- Right: Search, Filter & Records Table -->
            <section class="card table-card">
                <!-- Search & Filter Controls -->
                <form method="GET" action="index.php" class="filter-bar">
                    <div class="search-input">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($searchQ) ?>" placeholder="Search student name, ID or email...">
                    </div>

                    <div class="filter-controls">
                        <select name="dept_filter" onchange="this.form.submit()">
                            <option value="ALL">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ($filterDept == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['code']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select name="sem_filter" onchange="this.form.submit()">
                            <option value="ALL">All Semesters</option>
                            <?php for ($s = 1; $s <= 8; $s++): ?>
                                <option value="<?= $s ?>" <?= ($filterSem == (string)$s) ? 'selected' : '' ?>>Sem <?= $s ?></option>
                            <?php endfor; ?>
                        </select>

                        <select name="sort" onchange="this.form.submit()">
                            <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                            <option value="name_desc" <?= $sortBy === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                            <option value="cgpa_desc" <?= $sortBy === 'cgpa_desc' ? 'selected' : '' ?>>CGPA (High to Low)</option>
                            <option value="id_asc" <?= $sortBy === 'id_asc' ? 'selected' : '' ?>>Roll No (Asc)</option>
                        </select>

                        <button type="submit" class="btn btn-search"><i class="fa-solid fa-filter"></i> Apply</button>
                        <?php if ($searchQ !== '' || $filterDept !== '' || $filterSem !== ''): ?>
                            <a href="index.php" class="btn btn-reset" title="Reset Filters"><i class="fa-solid fa-xmark"></i></a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Student Records Count & Listing -->
                <div class="records-header">
                    <span>Found <strong><?= count($students) ?></strong> students</span>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Sem</th>
                                <th>CGPA</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" class="empty-cell">No students found matching current query.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($s['student_id']) ?></strong></td>
                                        <td>
                                            <?= htmlspecialchars($s['full_name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($s['email']) ?></small>
                                        </td>
                                        <td><span class="badge-dept"><?= htmlspecialchars($s['department_code']) ?></span></td>
                                        <td>Sem <?= htmlspecialchars($s['semester']) ?></td>
                                        <td><span class="cgpa-pill"><?= number_format((float)$s['cgpa'], 2) ?></span></td>
                                        <td><?= htmlspecialchars($s['phone']) ?></td>
                                        <td>
                                            <div class="action-links">
                                                <a href="index.php?action=edit&id=<?= $s['id'] ?>" class="btn-action edit" title="Edit">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <a href="index.php?action=delete&id=<?= $s['id'] ?>" class="btn-action delete" title="Delete" onclick="return confirm('Are you sure you want to delete this student?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
