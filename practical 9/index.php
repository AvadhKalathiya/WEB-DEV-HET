<?php
/**
 * Practical 9: Secure User Registration with Database Insert, Duplicate Email Check, and Password Hashing
 * Course: Web Development Frameworks (ITUE203) - CHARUSAT
 */

require_once __DIR__ . '/../practical 8/db.php';

$errors = [];
$successMsg = '';
$formData = [
    'username'  => '',
    'email'     => '',
    'full_name' => '',
    'role'      => 'student'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['username']  = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $formData['email']     = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $formData['full_name'] = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $formData['role']      = trim(filter_input(INPUT_POST, 'role', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'student');
    $password              = $_POST['password'] ?? '';
    $confirmPassword       = $_POST['confirm_password'] ?? '';

    // Validations
    if (empty($formData['username']) || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $formData['username'])) {
        $errors['username'] = 'Username must be 3-30 alphanumeric characters with underscores.';
    }

    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'A valid email address is required.';
    }

    if (empty($formData['full_name']) || strlen($formData['full_name']) < 3) {
        $errors['full_name'] = 'Full Name must be at least 3 characters.';
    }

    if (!in_array($formData['role'], ['student', 'faculty', 'admin'])) {
        $errors['role'] = 'Invalid role chosen.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // Duplicate Email & Username Check via Prepared Statements
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();

            // Check duplicate email
            $checkEmailStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $checkEmailStmt->execute(['email' => $formData['email']]);
            if ($checkEmailStmt->fetch()) {
                $errors['email'] = 'This email address is already registered.';
            }

            // Check duplicate username
            $checkUserStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
            $checkUserStmt->execute(['username' => $formData['username']]);
            if ($checkUserStmt->fetch()) {
                $errors['username'] = 'This username is already taken.';
            }

            // Insert if no duplicate
            if (empty($errors)) {
                // Secure Password Hashing using BCRYPT with cost factor
                $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                $insertStmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, full_name, role, status)
                    VALUES (:username, :email, :password_hash, :full_name, :role, 'active')
                ");

                $insertStmt->execute([
                    'username'      => $formData['username'],
                    'email'         => strtolower($formData['email']),
                    'password_hash' => $passwordHash,
                    'full_name'     => $formData['full_name'],
                    'role'          => $formData['role']
                ]);

                $newId = $pdo->lastInsertId();
                $successMsg = "User registered successfully! Assigned User ID: #$newId with secure BCRYPT hash.";

                // Reset inputs
                $formData = ['username' => '', 'email' => '', 'full_name' => '', 'role' => 'student'];
            }
        } catch (PDOException $e) {
            $errors['db'] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch recently registered users for live verification display
$recentUsers = [];
try {
    $pdo = getDBConnection();
    $recentStmt = $pdo->query("SELECT id, username, email, full_name, role, password_hash, created_at FROM users ORDER BY id DESC LIMIT 5");
    $recentUsers = $recentStmt->fetchAll();
} catch (Exception $e) {
    // Graceful fallback
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 9 - Secure User Registration with Password Hashing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <span class="badge">Practical 9 • ITUE203</span>
            <h1>Secure User Registration & Password Hashing</h1>
            <p>BCRYPT Hashing (`PASSWORD_BCRYPT`), Duplicate Key Prevention, Prepared Statements</p>
        </header>

        <div class="grid-layout">
            <!-- Registration Form Card -->
            <section class="card">
                <h2><i class="fa-solid fa-user-shield"></i> New User Registration</h2>

                <?php if ($successMsg): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?= htmlspecialchars($successMsg) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($errors['db'])): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?= htmlspecialchars($errors['db']) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php" novalidate>
                    <!-- Username -->
                    <div class="form-group <?= isset($errors['username']) ? 'has-error' : '' ?>">
                        <label for="username">Username <span class="req">*</span></label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($formData['username']) ?>" placeholder="e.g. het_patel" required>
                        <?php if (isset($errors['username'])): ?>
                            <span class="error-text"><?= htmlspecialchars($errors['username']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                        <label for="email">Email Address <span class="req">*</span> (Unique)</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" placeholder="e.g. het@charusat.edu.in" required>
                        <?php if (isset($errors['email'])): ?>
                            <span class="error-text"><?= htmlspecialchars($errors['email']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Full Name -->
                    <div class="form-group <?= isset($errors['full_name']) ? 'has-error' : '' ?>">
                        <label for="full_name">Full Name <span class="req">*</span></label>
                        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" placeholder="e.g. Het Patel" required>
                        <?php if (isset($errors['full_name'])): ?>
                            <span class="error-text"><?= htmlspecialchars($errors['full_name']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Role -->
                    <div class="form-group <?= isset($errors['role']) ? 'has-error' : '' ?>">
                        <label for="role">Role / Access Level <span class="req">*</span></label>
                        <select id="role" name="role">
                            <option value="student" <?= $formData['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                            <option value="faculty" <?= $formData['role'] === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                            <option value="admin" <?= $formData['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <!-- Password Fields -->
                    <div class="row">
                        <div class="form-group <?= isset($errors['password']) ? 'has-error' : '' ?>">
                            <label for="password">Password <span class="req">*</span></label>
                            <input type="password" id="password" name="password" placeholder="Min. 8 chars" required>
                            <?php if (isset($errors['password'])): ?>
                                <span class="error-text"><?= htmlspecialchars($errors['password']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group <?= isset($errors['confirm_password']) ? 'has-error' : '' ?>">
                            <label for="confirm_password">Confirm Password <span class="req">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <span class="error-text"><?= htmlspecialchars($errors['confirm_password']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-submit">
                        <i class="fa-solid fa-lock"></i> Register with BCRYPT Hash
                    </button>
                </form>
            </section>

            <!-- Verification & Live Hash Inspector -->
            <section class="card">
                <h2><i class="fa-solid fa-key"></i> Registered Users (Database Inspection)</h2>
                <p class="section-desc">Showing password hashes stored securely in MySQL (Notice never plaintext!):</p>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Password Hash (BCRYPT $2y$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $u): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($u['username']) ?></strong><br>
                                        <small><?= htmlspecialchars($u['email']) ?></small>
                                    </td>
                                    <td><span class="role-badge <?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars(strtoupper($u['role'])) ?></span></td>
                                    <td><code class="hash-code" title="<?= htmlspecialchars($u['password_hash']) ?>"><?= htmlspecialchars(substr($u['password_hash'], 0, 30)) ?>...</code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="security-notes">
                    <h4><i class="fa-solid fa-shield-halved"></i> Security Guarantees</h4>
                    <ul>
                        <li><i class="fa-solid fa-check"></i> <strong>Salted Automatically</strong>: Each hash uses a unique CSPRNG 128-bit salt.</li>
                        <li><i class="fa-solid fa-check"></i> <strong>Anti-Collisions</strong>: Indexed duplicate checks eliminate duplicate emails before insert.</li>
                        <li><i class="fa-solid fa-check"></i> <strong>SQL Injection Proof</strong>: Parameterized PDO queries eliminate SQL injection vectors.</li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
