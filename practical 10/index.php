<?php
/**
 * Practical 10: Secure Login/Logout with Sessions, Role-Based Access Control (RBAC), Timeout, and Remember-Me Token
 * Course: Web Development Frameworks (ITUE203) - CHARUSAT
 */

require_once __DIR__ . '/../practical 8/db.php';

// Configure hardened session settings
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');

session_start();

// Inactivity timeout: 15 minutes (900 seconds)
define('SESSION_TIMEOUT_DURATION', 900);

// Check Session Expiration Timeout
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_DURATION)) {
        // Expired
        session_unset();
        session_destroy();
        header("Location: index.php?timeout=1");
        exit;
    }
    // Refresh activity timestamp
    $_SESSION['last_activity'] = time();
}

$pdo = getDBConnection();
$errorMsg = '';
$infoMsg = '';

// Check if user was logged out due to timeout
if (isset($_GET['timeout'])) {
    $infoMsg = "Your session expired due to inactivity. Please log in again.";
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Clear Remember-Me Token from DB if present
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
    }

    // Clear Remember-Me Cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }

    // Destroy session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: index.php?logged_out=1");
    exit;
}

if (isset($_GET['logged_out'])) {
    $infoMsg = "You have been logged out securely.";
}

// Auto-Login via Remember-Me Cookie if not already logged in
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT id, username, email, full_name, role, status FROM users WHERE remember_token = :token AND status = 'active' LIMIT 1");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if ($user) {
        // Establish Session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
    }
}

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
    $remember   = isset($_POST['remember_me']);

    if (empty($identifier) || empty($password)) {
        $errorMsg = 'Please enter both username/email and password.';
    } else {
        // Authenticate against database
        $stmt = $pdo->prepare("
            SELECT id, username, email, password_hash, full_name, role, status
            FROM users
            WHERE (username = :id1 OR email = :id2)
            LIMIT 1
        ");
        $stmt->execute(['id1' => $identifier, 'id2' => $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $errorMsg = 'Your account has been deactivated. Please contact administrator.';
            } else {
                // Prevent Session Fixation attack
                session_regenerate_id(true);

                // Set Session Variables
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['email']         = $user['email'];
                $_SESSION['full_name']     = $user['full_name'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['last_activity'] = time();

                // Handle Remember-Me Token (30 days persistence)
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $updateStmt = $pdo->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
                    $updateStmt->execute(['token' => $token, 'id' => $user['id']]);

                    // Set HttpOnly cookie for 30 days
                    setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
                }

                header("Location: index.php");
                exit;
            }
        } else {
            $errorMsg = 'Invalid username/email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 10 - Secure Login/Logout, RBAC, Sessions & Timeout</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <span class="badge">Practical 10 • ITUE203</span>
            <h1>Session Authentication, RBAC & Token Security</h1>
            <p>Session Regeneration, 15-Minute Timeout, Remember-Me Persistent Cookie & Role Gates</p>
        </header>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <!-- Login State -->
            <div class="login-wrapper">
                <div class="card login-card">
                    <h2><i class="fa-solid fa-lock"></i> Portal Authentication</h2>
                    <p class="subtitle">Enter credentials to authenticate session</p>

                    <?php if ($infoMsg): ?>
                        <div class="alert alert-info">
                            <i class="fa-solid fa-circle-info"></i>
                            <span><?= htmlspecialchars($infoMsg) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorMsg): ?>
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-circle-xmark"></i>
                            <span><?= htmlspecialchars($errorMsg) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php">
                        <input type="hidden" name="login_submit" value="1">

                        <div class="form-group">
                            <label for="identifier">Username or Email</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-user"></i>
                                <input type="text" id="identifier" name="identifier" placeholder="admin or student email" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-key"></i>
                                <input type="password" id="password" name="password" placeholder="••••••••" required>
                            </div>
                        </div>

                        <div class="form-row remember-row">
                            <label class="remember-label">
                                <input type="checkbox" name="remember_me" value="1">
                                <span>Remember me on this browser (Token Auth)</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-right-to-bracket"></i> Login
                        </button>
                    </form>

                    <div class="demo-creds">
                        <p><strong>Demo Test Accounts:</strong></p>
                        <p><span class="badge-role admin">Admin:</span> <code>admin</code> / <code>Admin@123</code></p>
                        <p><span class="badge-role faculty">Faculty:</span> <code>faculty1</code> / <code>Admin@123</code></p>
                        <p><em>(Or register a student in Practical 9 and log in here!)</em></p>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Logged In / RBAC Dashboard State -->
            <div class="dashboard">
                <div class="user-strip card">
                    <div class="user-meta">
                        <div class="avatar"><i class="fa-solid fa-user-check"></i></div>
                        <div>
                            <h2>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h2>
                            <p class="role-caption">
                                Logged in as <span class="badge-role <?= htmlspecialchars($_SESSION['role']) ?>"><?= strtoupper(htmlspecialchars($_SESSION['role'])) ?></span>
                                &bull; Username: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                            </p>
                        </div>
                    </div>
                    <div class="user-actions">
                        <a href="index.php?action=logout" class="btn btn-logout">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Secure Logout
                        </a>
                    </div>
                </div>

                <div class="rbac-grid">
                    <!-- Session Security Diagnostics Card -->
                    <div class="card">
                        <h3><i class="fa-solid fa-shield-virus"></i> Session Security Telemetry</h3>
                        <ul class="diag-list">
                            <li><span>Session ID:</span> <code><?= substr(session_id(), 0, 16) ?>...</code> (Regenerated on login)</li>
                            <li><span>Inactivity Timer:</span> Active (Auto-terminates after <?= SESSION_TIMEOUT_DURATION / 60 ?> mins)</li>
                            <li><span>Last Activity:</span> <?= date('H:i:s', $_SESSION['last_activity']) ?></li>
                            <li><span>HttpOnly Cookie:</span> Enabled (<span class="text-green">XSS Protected</span>)</li>
                            <li><span>SameSite Policy:</span> Lax (<span class="text-green">CSRF Protected</span>)</li>
                        </ul>
                    </div>

                    <!-- Role-Based Access Control Gates -->
                    <div class="card">
                        <h3><i class="fa-solid fa-user-gear"></i> Role-Based Access Control (RBAC) Gates</h3>

                        <!-- Admin Only Module -->
                        <div class="rbac-module <?= $_SESSION['role'] === 'admin' ? 'unlocked' : 'locked' ?>">
                            <div class="module-title">
                                <i class="fa-solid <?= $_SESSION['role'] === 'admin' ? 'fa-unlock text-green' : 'fa-lock text-red' ?>"></i>
                                <strong>Admin Gate</strong> (Administrative Operations)
                            </div>
                            <p>
                                <?= $_SESSION['role'] === 'admin'
                                    ? '<span class="status-ok">ACCESS GRANTED: Full system privileges enabled.</span>'
                                    : '<span class="status-denied">ACCESS DENIED: Requires "admin" role privileges.</span>'
                                ?>
                            </p>
                        </div>

                        <!-- Faculty Module -->
                        <div class="rbac-module <?= in_array($_SESSION['role'], ['admin', 'faculty']) ? 'unlocked' : 'locked' ?>">
                            <div class="module-title">
                                <i class="fa-solid <?= in_array($_SESSION['role'], ['admin', 'faculty']) ? 'fa-unlock text-green' : 'fa-lock text-red' ?>"></i>
                                <strong>Faculty Gate</strong> (Grading & Attendance Management)
                            </div>
                            <p>
                                <?= in_array($_SESSION['role'], ['admin', 'faculty'])
                                    ? '<span class="status-ok">ACCESS GRANTED: Faculty portal features active.</span>'
                                    : '<span class="status-denied">ACCESS DENIED: Requires "faculty" or higher role.</span>'
                                ?>
                            </p>
                        </div>

                        <!-- Student Module -->
                        <div class="rbac-module unlocked">
                            <div class="module-title">
                                <i class="fa-solid fa-unlock text-green"></i>
                                <strong>Student Gate</strong> (View Courses, Fee & Attendance)
                            </div>
                            <p><span class="status-ok">ACCESS GRANTED: Available for all authenticated roles.</span></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
