<?php
/**
 * Practical 7: PHP Form Processing with Server-Side Validation and CSV/JSON Storage
 * Course: Web Development Frameworks (ITUE203) - CHARUSAT
 */

// Data storage paths
$storageDir = __DIR__ . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0777, true);
}
$csvFile = $storageDir . '/registrations.csv';
$jsonFile = $storageDir . '/registrations.json';

// Initialize CSV header if not exists
if (!file_exists($csvFile)) {
    $fp = fopen($csvFile, 'w');
    fputcsv($fp, ['Timestamp', 'FullName', 'StudentID', 'Email', 'Phone', 'Department', 'Year']);
    fclose($fp);
}

// Initialize JSON file if not exists
if (!file_exists($jsonFile)) {
    file_put_contents($jsonFile, json_encode([], JSON_PRETTY_PRINT));
}

// State variables
$errors = [];
$successMessage = '';
$formData = [
    'fullname' => '',
    'studentid' => '',
    'email' => '',
    'phone' => '',
    'department' => '',
    'year' => ''
];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and trim inputs
    $formData['fullname'] = trim(filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $formData['studentid'] = trim(filter_input(INPUT_POST, 'studentid', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $formData['email'] = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $formData['phone'] = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $formData['department'] = trim(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $formData['year'] = trim(filter_input(INPUT_POST, 'year', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

    // Server-Side Validations
    if (empty($formData['fullname']) || strlen($formData['fullname']) < 3) {
        $errors['fullname'] = 'Full Name is required and must be at least 3 characters.';
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $formData['fullname'])) {
        $errors['fullname'] = 'Full Name must contain only alphabets and spaces.';
    }

    if (empty($formData['studentid'])) {
        $errors['studentid'] = 'Student ID is required.';
    } elseif (!preg_match("/^[D]?[0-9]{2}[A-Z]{2,4}[0-9]{3}$/i", $formData['studentid'])) {
        $errors['studentid'] = 'Invalid Student ID format. Example: 23IT001 or D23IT001.';
    }

    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'A valid email address is required.';
    }

    if (empty($formData['phone'])) {
        $errors['phone'] = 'Contact phone number is required.';
    } elseif (!preg_match("/^[6-9][0-9]{9}$/", $formData['phone'])) {
        $errors['phone'] = 'Phone must be a valid 10-digit Indian number starting with 6-9.';
    }

    if (empty($formData['department'])) {
        $errors['department'] = 'Please select a department.';
    }

    if (empty($formData['year'])) {
        $errors['year'] = 'Please select an academic year.';
    }

    // If validation succeeds, persist data into both CSV & JSON
    if (empty($errors)) {
        $timestamp = date('Y-m-d H:i:s');
        $record = [
            'timestamp' => $timestamp,
            'fullname' => $formData['fullname'],
            'studentid' => strtoupper($formData['studentid']),
            'email' => strtolower($formData['email']),
            'phone' => $formData['phone'],
            'department' => $formData['department'],
            'year' => $formData['year']
        ];

        // 1. Append to CSV
        $fp = fopen($csvFile, 'a');
        fputcsv($fp, [
            $record['timestamp'],
            $record['fullname'],
            $record['studentid'],
            $record['email'],
            $record['phone'],
            $record['department'],
            $record['year']
        ]);
        fclose($fp);

        // 2. Append to JSON
        $existingJson = json_decode(file_get_contents($jsonFile), true) ?: [];
        $existingJson[] = $record;
        file_put_contents($jsonFile, json_encode($existingJson, JSON_PRETTY_PRINT));

        $successMessage = "Student {$record['fullname']} ({$record['studentid']}) registered successfully!";

        // Reset form inputs after successful submission
        $formData = [
            'fullname' => '',
            'studentid' => '',
            'email' => '',
            'phone' => '',
            'department' => '',
            'year' => ''
        ];
    }
}

// Load current records from JSON for display
$storedRecords = json_decode(file_get_contents($jsonFile), true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 7 - PHP Form Processing with CSV/JSON Storage</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <span class="badge">Practical 7 • ITUE203</span>
            <h1>PHP Server-Side Validation & File Storage</h1>
            <p>Data validation engine with multi-format persistence (CSV & JSON)</p>
        </header>

        <div class="grid-layout">
            <!-- Left: Registration Form -->
            <section class="card">
                <h2><i class="fa-solid fa-pen-to-square"></i> Student Submission Form</h2>

                <?php if ($successMessage): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?= htmlspecialchars($successMessage) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>Please fix the highlighted errors below before proceeding.</span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php" novalidate>
                    <!-- Full Name -->
                    <div class="form-group <?= isset($errors['fullname']) ? 'has-error' : '' ?>">
                        <label for="fullname">Full Name <span class="req">*</span></label>
                        <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($formData['fullname']) ?>" placeholder="e.g. Het Patel">
                        <?php if (isset($errors['fullname'])): ?>
                            <span class="error-text"><?= htmlspecialchars($errors['fullname']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Student ID -->
                    <div class="form-group <?= isset($errors['studentid']) ? 'has-error' : '' ?>">
                        <label for="studentid">Student ID <span class="req">*</span></label>
                        <input type="text" id="studentid" name="studentid" value="<?= htmlspecialchars($formData['studentid']) ?>" placeholder="e.g. 23IT001">
                        <?php if (isset($errors['studentid'])): ?>
                            <span class="error-text"><?= htmlspecialchars($errors['studentid']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Email & Phone -->
                    <div class="row">
                        <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                            <label for="email">Email <span class="req">*</span></label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" placeholder="student@charusat.edu.in">
                            <?php if (isset($errors['email'])): ?>
                                <span class="error-text"><?= htmlspecialchars($errors['email']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                            <label for="phone">Phone <span class="req">*</span></label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($formData['phone']) ?>" placeholder="10-digit number">
                            <?php if (isset($errors['phone'])): ?>
                                <span class="error-text"><?= htmlspecialchars($errors['phone']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Department & Year -->
                    <div class="row">
                        <div class="form-group <?= isset($errors['department']) ? 'has-error' : '' ?>">
                            <label for="department">Department <span class="req">*</span></label>
                            <select id="department" name="department">
                                <option value="">-- Select --</option>
                                <option value="IT" <?= $formData['department'] === 'IT' ? 'selected' : '' ?>>IT</option>
                                <option value="CE" <?= $formData['department'] === 'CE' ? 'selected' : '' ?>>CE</option>
                                <option value="CSE" <?= $formData['department'] === 'CSE' ? 'selected' : '' ?>>CSE</option>
                                <option value="EC" <?= $formData['department'] === 'EC' ? 'selected' : '' ?>>EC</option>
                            </select>
                            <?php if (isset($errors['department'])): ?>
                                <span class="error-text"><?= htmlspecialchars($errors['department']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group <?= isset($errors['year']) ? 'has-error' : '' ?>">
                            <label for="year">Year <span class="req">*</span></label>
                            <select id="year" name="year">
                                <option value="">-- Select --</option>
                                <option value="1st Year" <?= $formData['year'] === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                <option value="2nd Year" <?= $formData['year'] === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3rd Year" <?= $formData['year'] === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4th Year" <?= $formData['year'] === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                            </select>
                            <?php if (isset($errors['year'])): ?>
                                <span class="error-text"><?= htmlspecialchars($errors['year']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-submit">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Process & Save
                    </button>
                </form>
            </section>

            <!-- Right: Stored Data Table -->
            <section class="card">
                <div class="card-header-flex">
                    <h2><i class="fa-solid fa-database"></i> Stored Records (<?= count($storedRecords) ?>)</h2>
                    <div class="links">
                        <a href="storage/registrations.csv" download class="file-link"><i class="fa-solid fa-file-csv"></i> CSV</a>
                        <a href="storage/registrations.json" target="_blank" class="file-link"><i class="fa-solid fa-file-code"></i> JSON</a>
                    </div>
                </div>

                <div class="table-container">
                    <?php if (empty($storedRecords)): ?>
                        <p class="empty-state">No student records recorded yet. Submit the form to add entries.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Dept</th>
                                    <th>Year</th>
                                    <th>Email</th>
                                    <th>Logged At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse($storedRecords) as $row): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['studentid']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['fullname']) ?></td>
                                        <td><span class="dept-tag"><?= htmlspecialchars($row['department']) ?></span></td>
                                        <td><?= htmlspecialchars($row['year']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td><small><?= htmlspecialchars($row['timestamp']) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
