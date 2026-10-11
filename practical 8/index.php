<?php
/**
 * Practical 8: Database Connectivity Verification & ER Schema Viewer
 */
require_once __DIR__ . '/db.php';

$pdoStatus = false;
$mysqliStatus = false;
$tablesInfo = [];
$errorMsg = '';

try {
    $pdo = getDBConnection();
    $pdoStatus = true;

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $countStmt->fetchColumn();

        $colStmt = $pdo->query("DESCRIBE `$table`");
        $cols = $colStmt->fetchAll();

        $tablesInfo[$table] = [
            'count' => $count,
            'columns' => $cols
        ];
    }

    $mysqli = getMySQLiConnection();
    $mysqliStatus = true;
    $mysqli->close();
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 8 - MySQL Schema & Connectivity</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <span class="badge">Practical 8 • ITUE203</span>
            <h1>MySQL Schema Design & Connectivity Diagnostics</h1>
            <p>Database: <code><?= DB_NAME ?></code> on <code><?= DB_HOST ?>:<?= DB_PORT ?></code></p>
        </header>

        <!-- Status Cards -->
        <div class="status-grid">
            <div class="status-card <?= $pdoStatus ? 'ok' : 'err' ?>">
                <i class="fa-solid fa-server icon"></i>
                <div class="status-content">
                    <h3>PDO Connection</h3>
                    <p><?= $pdoStatus ? 'Connected & Active (utf8mb4)' : 'Connection Failed' ?></p>
                </div>
            </div>

            <div class="status-card <?= $mysqliStatus ? 'ok' : 'err' ?>">
                <i class="fa-solid fa-plug icon"></i>
                <div class="status-content">
                    <h3>MySQLi Driver</h3>
                    <p><?= $mysqliStatus ? 'Connected & Handshake Successful' : 'Connection Failed' ?></p>
                </div>
            </div>

            <div class="status-card ok">
                <i class="fa-solid fa-table-cells icon"></i>
                <div class="status-content">
                    <h3>Schema Entities</h3>
                    <p><?= count($tablesInfo) ?> Relational Tables Defined</p>
                </div>
            </div>
        </div>

        <!-- Entity Relationship Design Summary -->
        <section class="er-section card">
            <h2><i class="fa-solid fa-diagram-project"></i> Relational ER Model Architecture</h2>
            <div class="er-diagram-box">
                <div class="entity-card">
                    <h4>departments</h4>
                    <ul>
                        <li><span class="key pk">PK</span> id (INT)</li>
                        <li>code (VARCHAR) [UNIQUE]</li>
                        <li>name (VARCHAR)</li>
                    </ul>
                </div>
                <div class="relation-arrow">1 : N</div>
                <div class="entity-card">
                    <h4>students</h4>
                    <ul>
                        <li><span class="key pk">PK</span> id (INT)</li>
                        <li>student_id (VARCHAR) [UNIQUE]</li>
                        <li><span class="key fk">FK</span> user_id (INT)</li>
                        <li><span class="key fk">FK</span> department_id (INT)</li>
                        <li>full_name, email, phone, cgpa</li>
                    </ul>
                </div>
                <div class="relation-arrow">N : 1</div>
                <div class="entity-card">
                    <h4>users</h4>
                    <ul>
                        <li><span class="key pk">PK</span> id (INT)</li>
                        <li>username (VARCHAR) [UNIQUE]</li>
                        <li>email (VARCHAR) [UNIQUE]</li>
                        <li>password_hash, role, status</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Live Database Tables Explorer -->
        <section class="tables-section card">
            <h2><i class="fa-solid fa-database"></i> Live Database Schema & Row Counts</h2>
            <div class="tables-grid">
                <?php foreach ($tablesInfo as $tbl => $info): ?>
                    <div class="table-box">
                        <div class="tbl-header">
                            <h3><i class="fa-solid fa-table"></i> <?= htmlspecialchars($tbl) ?></h3>
                            <span class="row-count"><?= $info['count'] ?> rows</span>
                        </div>
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Null</th>
                                        <th>Key</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($info['columns'] as $col): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($col['Field']) ?></strong></td>
                                            <td><code><?= htmlspecialchars($col['Type']) ?></code></td>
                                            <td><?= htmlspecialchars($col['Null']) ?></td>
                                            <td><?= $col['Key'] ? "<span class='key-tag'>{$col['Key']}</span>" : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</body>
</html>
