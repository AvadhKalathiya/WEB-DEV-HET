<?php
/**
 * Practical 14: Admin Dashboard with Role-Based Access Control (RBAC) and Dynamic Navigation Menu
 * Course: Web Development Frameworks (ITUE203) - CHARUSAT
 */

session_start();
require_once __DIR__ . '/../practical 8/db.php';
$pdo = getDBConnection();

// Allow switching role for live assessment / testing convenience
if (isset($_GET['switch_role'])) {
    $allowedRoles = ['admin', 'faculty', 'student'];
    if (in_array($_GET['switch_role'], $allowedRoles)) {
        $_SESSION['role'] = $_GET['switch_role'];
        $_SESSION['full_name'] = ucfirst($_GET['switch_role']) . " User";
        header("Location: index.php");
        exit;
    }
}

// Default session role if not yet logged in
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'Portal Administrator';
    $_SESSION['username'] = 'admin';
}

$currentRole = $_SESSION['role'];

// Fetch Analytics & Metrics
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalEvents   = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$avgCgpa       = $pdo->query("SELECT AVG(cgpa) FROM students")->fetchColumn() ?: 0;

// Dynamic Menu Generation based on Role-Based Access Control (RBAC)
$allMenuItems = [
    [
        'id'       => 'overview',
        'title'    => 'Dashboard Overview',
        'icon'     => 'fa-chart-pie',
        'allowed'  => ['admin', 'faculty', 'student'],
        'badge'    => null
    ],
    [
        'id'       => 'students',
        'title'    => 'Student Directory',
        'icon'     => 'fa-graduation-cap',
        'allowed'  => ['admin', 'faculty'],
        'badge'    => $totalStudents
    ],
    [
        'id'       => 'events',
        'title'    => 'Campus Events',
        'icon'     => 'fa-calendar-days',
        'allowed'  => ['admin', 'faculty', 'student'],
        'badge'    => $totalEvents
    ],
    [
        'id'       => 'courses',
        'title'    => 'Course Catalog',
        'icon'     => 'fa-book-bookmark',
        'allowed'  => ['admin', 'faculty', 'student'],
        'badge'    => null
    ],
    [
        'id'       => 'grades',
        'title'    => 'Grade Management',
        'icon'     => 'fa-award',
        'allowed'  => ['admin', 'faculty'],
        'badge'    => null
    ],
    [
        'id'       => 'users',
        'title'    => 'System Users & RBAC',
        'icon'     => 'fa-users-gear',
        'allowed'  => ['admin'],
        'badge'    => $totalUsers
    ],
    [
        'id'       => 'settings',
        'title'    => 'Portal Settings',
        'icon'     => 'fa-sliders',
        'allowed'  => ['admin'],
        'badge'    => null
    ],
];

// Filter menu items dynamically for current user role
$visibleMenu = array_filter($allMenuItems, function($item) use ($currentRole) {
    return in_array($currentRole, $item['allowed']);
});

// Recent system activity log
$recentStudents = $pdo->query("
    SELECT s.student_id, s.full_name, d.code as dept, s.cgpa, s.created_at
    FROM students s
    JOIN departments d ON s.department_id = d.id
    ORDER BY s.id DESC LIMIT 4
")->fetchAll();

$recentEvents = $pdo->query("
    SELECT title, category, event_date, venue FROM events ORDER BY event_date ASC LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 14 - Admin Dashboard with RBAC & Dynamic Menu</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar Navigation (Generated Dynamically) -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <h2>CHARUSAT</h2>
                    <small>Admin & RBAC Portal</small>
                </div>
            </div>

            <!-- Role Badge & Role Switcher for Demo -->
            <div class="role-selector-box">
                <label>Active Security Role:</label>
                <div class="role-pill <?= $currentRole ?>">
                    <i class="fa-solid fa-circle-user"></i> <?= strtoupper($currentRole) ?>
                </div>
                <div class="role-switch-buttons">
                    <small>Simulate Role:</small><br>
                    <a href="index.php?switch_role=admin" class="btn-role <?= $currentRole === 'admin' ? 'active' : '' ?>">Admin</a>
                    <a href="index.php?switch_role=faculty" class="btn-role <?= $currentRole === 'faculty' ? 'active' : '' ?>">Faculty</a>
                    <a href="index.php?switch_role=student" class="btn-role <?= $currentRole === 'student' ? 'active' : '' ?>">Student</a>
                </div>
            </div>

            <!-- Dynamically Rendered Navigation Menu -->
            <nav class="sidebar-nav">
                <span class="nav-heading">DYNAMIC RBAC MENU</span>
                <ul>
                    <?php foreach ($visibleMenu as $idx => $m): ?>
                        <li class="<?= $idx === 0 ? 'active' : '' ?>">
                            <a href="#<?= $m['id'] ?>">
                                <i class="fa-solid <?= $m['icon'] ?>"></i>
                                <span><?= htmlspecialchars($m['title']) ?></span>
                                <?php if ($m['badge'] !== null): ?>
                                    <span class="nav-badge"><?= $m['badge'] ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <p>Practical 14 &bull; CO1, CO4, CO5</p>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-title">
                    <span class="badge">Practical 14 • ITUE203</span>
                    <h1>Campus Operations Dashboard</h1>
                </div>
                <div class="topbar-user">
                    <div class="user-info">
                        <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>
                        <small>Role: <?= strtoupper($currentRole) ?></small>
                    </div>
                    <div class="avatar"><?= strtoupper(substr($currentRole, 0, 1)) ?></div>
                </div>
            </header>

            <!-- KPI Metric Cards Grid -->
            <section class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon blue"><i class="fa-solid fa-user-graduate"></i></div>
                    <div class="metric-data">
                        <span class="metric-label">Enrolled Students</span>
                        <span class="metric-val"><?= $totalStudents ?></span>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon amber"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="metric-data">
                        <span class="metric-label">Campus Events</span>
                        <span class="metric-val"><?= $totalEvents ?></span>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon green"><i class="fa-solid fa-chart-line"></i></div>
                    <div class="metric-data">
                        <span class="metric-label">Average CGPA</span>
                        <span class="metric-val"><?= number_format((float)$avgCgpa, 2) ?></span>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon purple"><i class="fa-solid fa-users"></i></div>
                    <div class="metric-data">
                        <span class="metric-label">Portal Users</span>
                        <span class="metric-val"><?= $totalUsers ?></span>
                    </div>
                </div>
            </section>

            <!-- Role Access Alert Banner -->
            <div class="access-banner">
                <i class="fa-solid fa-circle-info"></i>
                <div>
                    <strong>Active Role Permissions (<?= strtoupper($currentRole) ?>):</strong>
                    <?php if ($currentRole === 'admin'): ?>
                        Full Unrestricted Access: Can view, modify, and delete students, manage system users, publish events, and alter site configurations.
                    <?php elseif ($currentRole === 'faculty'): ?>
                        Faculty Access: Can view student records, manage course grades, and organize events. Administrative settings and user tables are hidden.
                    <?php else: ?>
                        Student Access: Read-only access to academic schedules, events, and personal curriculum. All administrative panels and CRUD operations are dynamically locked.
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dashboard Content Grid -->
            <div class="content-grid">
                <!-- Recent Registrations Card -->
                <div class="card">
                    <div class="card-head">
                        <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Student Admissions</h3>
                        <a href="../practical 11/index.php" class="card-link">View All</a>
                    </div>
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Dept</th>
                                    <th>CGPA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentStudents)): ?>
                                    <tr><td colspan="4" class="empty-msg">No students enrolled.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentStudents as $rs): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($rs['student_id']) ?></strong></td>
                                            <td><?= htmlspecialchars($rs['full_name']) ?></td>
                                            <td><span class="tag-dept"><?= htmlspecialchars($rs['dept']) ?></span></td>
                                            <td><span class="pill-cgpa"><?= number_format((float)$rs['cgpa'], 2) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Upcoming Events Card -->
                <div class="card">
                    <div class="card-head">
                        <h3><i class="fa-solid fa-calendar-star"></i> Upcoming Campus Events</h3>
                        <a href="../practical 12/index.php" class="card-link">Manage</a>
                    </div>
                    <div class="events-list">
                        <?php if (empty($recentEvents)): ?>
                            <p class="empty-msg">No events on calendar.</p>
                        <?php else: ?>
                            <?php foreach ($recentEvents as $re): ?>
                                <div class="event-row">
                                    <div class="event-date">
                                        <span><?= date('M', strtotime($re['event_date'])) ?></span>
                                        <strong><?= date('d', strtotime($re['event_date'])) ?></strong>
                                    </div>
                                    <div class="event-info">
                                        <h4><?= htmlspecialchars($re['title']) ?></h4>
                                        <p><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($re['venue']) ?> &bull; <span class="tag-cat"><?= htmlspecialchars($re['category']) ?></span></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
