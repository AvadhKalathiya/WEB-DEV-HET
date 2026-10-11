<?php
/**
 * Practical 12: Event Management CRUD with Poster Upload and File Validation
 * Course: Web Development Frameworks (ITUE203) - CHARUSAT
 */

require_once __DIR__ . '/../practical 8/db.php';
$pdo = getDBConnection();

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$msg = '';
$error = '';

// Handle Delete Event
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    $fetchStmt = $pdo->prepare("SELECT poster_path FROM events WHERE id = :id");
    $fetchStmt->execute(['id' => $delId]);
    $event = $fetchStmt->fetch();

    if ($event) {
        if (!empty($event['poster_path']) && file_exists(__DIR__ . '/' . $event['poster_path'])) {
            unlink(__DIR__ . '/' . $event['poster_path']);
        }
        $delStmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
        $delStmt->execute(['id' => $delId]);
        header("Location: index.php?msg=deleted");
        exit;
    }
}

// Handle Add / Edit Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_event'])) {
    $eventId     = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $eventDate   = trim($_POST['event_date'] ?? '');
    $venue       = trim($_POST['venue'] ?? '');

    if (empty($title) || empty($description) || empty($category) || empty($eventDate) || empty($venue)) {
        $error = "Please fill in all event details.";
    } else {
        $posterPath = null;

        // If editing, preserve old poster unless a new one is uploaded
        if ($eventId) {
            $currStmt = $pdo->prepare("SELECT poster_path FROM events WHERE id = :id");
            $currStmt->execute(['id' => $eventId]);
            $existing = $currStmt->fetch();
            $posterPath = $existing['poster_path'] ?? null;
        }

        // Process File Upload with Strict Validations
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['poster'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = "File upload failed with error code: " . $file['error'];
            } else {
                // Validate size (Limit: 2MB)
                $maxBytes = 2 * 1024 * 1024;
                if ($file['size'] > $maxBytes) {
                    $error = "Poster file size cannot exceed 2MB.";
                } else {
                    // Validate MIME Type & Extension
                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    if (!in_array($ext, $allowedExts) || !in_array($mime, $allowedMimes)) {
                        $error = "Invalid image format! Only JPG, PNG, and WebP are allowed.";
                    } else {
                        // Generate secure unique filename
                        $newFilename = 'poster_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        $destination = $uploadDir . $newFilename;

                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            // Unlink previous file if replacing
                            if ($posterPath && file_exists(__DIR__ . '/' . $posterPath)) {
                                unlink(__DIR__ . '/' . $posterPath);
                            }
                            $posterPath = 'uploads/' . $newFilename;
                        } else {
                            $error = "Could not save uploaded poster to storage directory.";
                        }
                    }
                }
            }
        }

        // Persist to Database if no error
        if (empty($error)) {
            try {
                if ($eventId) {
                    $updateStmt = $pdo->prepare("
                        UPDATE events
                        SET title = :title, description = :desc, category = :cat,
                            event_date = :edate, venue = :venue, poster_path = :poster
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        'title'  => $title,
                        'desc'   => $description,
                        'cat'    => $category,
                        'edate'  => $eventDate,
                        'venue'  => $venue,
                        'poster' => $posterPath,
                        'id'     => $eventId
                    ]);
                    header("Location: index.php?msg=updated");
                    exit;
                } else {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO events (title, description, category, event_date, venue, poster_path)
                        VALUES (:title, :desc, :cat, :edate, :venue, :poster)
                    ");
                    $insertStmt->execute([
                        'title'  => $title,
                        'desc'   => $description,
                        'cat'    => $category,
                        'edate'  => $eventDate,
                        'venue'  => $venue,
                        'poster' => $posterPath
                    ]);
                    header("Location: index.php?msg=created");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch edit record if requested
$editEvent = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editStmt = $pdo->prepare("SELECT * FROM events WHERE id = :id");
    $editStmt->execute(['id' => (int)$_GET['id']]);
    $editEvent = $editStmt->fetch();
}

// Fetch all events
$events = $pdo->query("SELECT * FROM events ORDER BY event_date ASC")->fetchAll();

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $msg = 'Campus event scheduled successfully with poster.';
    if ($_GET['msg'] === 'updated') $msg = 'Event details updated successfully.';
    if ($_GET['msg'] === 'deleted') $msg = 'Event and associated poster file removed.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 12 - Event Management with Poster Upload & File Validation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <span class="badge">Practical 12 • ITUE203</span>
            <h1>Campus Event Management & Secure File Upload</h1>
            <p>MIME Type Verification, File Size Bounds (Max 2MB), Storage Isolation & CRUD</p>
        </header>

        <?php if ($msg): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="event-layout">
            <!-- Left: Event Entry Form -->
            <section class="card form-card">
                <h2>
                    <i class="fa-solid <?= $editEvent ? 'fa-calendar-pen' : 'fa-calendar-plus' ?>"></i>
                    <?= $editEvent ? 'Update Event' : 'Create Campus Event' ?>
                </h2>

                <form method="POST" action="index.php" enctype="multipart/form-data">
                    <input type="hidden" name="save_event" value="1">
                    <?php if ($editEvent): ?>
                        <input type="hidden" name="event_id" value="<?= $editEvent['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Event Title <span class="req">*</span></label>
                        <input type="text" name="title" value="<?= htmlspecialchars($editEvent['title'] ?? '') ?>" placeholder="e.g. Spoural Tech Fest 2026" required>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label>Category <span class="req">*</span></label>
                            <select name="category" required>
                                <option value="Hackathon" <?= ($editEvent && $editEvent['category'] === 'Hackathon') ? 'selected' : '' ?>>Hackathon</option>
                                <option value="Workshop" <?= ($editEvent && $editEvent['category'] === 'Workshop') ? 'selected' : '' ?>>Workshop</option>
                                <option value="Cultural" <?= ($editEvent && $editEvent['category'] === 'Cultural') ? 'selected' : '' ?>>Cultural</option>
                                <option value="Sports" <?= ($editEvent && $editEvent['category'] === 'Sports') ? 'selected' : '' ?>>Sports</option>
                                <option value="Seminar" <?= ($editEvent && $editEvent['category'] === 'Seminar') ? 'selected' : '' ?>>Seminar</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Event Date <span class="req">*</span></label>
                            <input type="date" name="event_date" value="<?= htmlspecialchars($editEvent['event_date'] ?? date('Y-m-d')) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Venue / Location <span class="req">*</span></label>
                        <input type="text" name="venue" value="<?= htmlspecialchars($editEvent['venue'] ?? '') ?>" placeholder="e.g. FTE Auditorium / Lab 304" required>
                    </div>

                    <div class="form-group">
                        <label>Event Description <span class="req">*</span></label>
                        <textarea name="description" rows="3" placeholder="Provide event schedule and details..." required><?= htmlspecialchars($editEvent['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group file-group">
                        <label>Event Poster (Max 2MB - JPG, PNG, WebP)</label>
                        <input type="file" name="poster" accept="image/jpeg,image/png,image/webp">
                        <?php if ($editEvent && !empty($editEvent['poster_path'])): ?>
                            <div class="current-poster">
                                <small>Current Poster:</small><br>
                                <img src="<?= htmlspecialchars($editEvent['poster_path']) ?>" alt="Poster" class="thumb-mini">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-cloud-arrow-up"></i> <?= $editEvent ? 'Update Event' : 'Publish Event' ?>
                        </button>
                        <?php if ($editEvent): ?>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <!-- Right: Event Cards Gallery -->
            <section class="card gallery-card">
                <h2><i class="fa-solid fa-calendar-days"></i> Scheduled Events (<?= count($events) ?>)</h2>

                <?php if (empty($events)): ?>
                    <p class="empty-msg">No campus events published yet. Add an event with poster to get started.</p>
                <?php else: ?>
                    <div class="events-grid">
                        <?php foreach ($events as $ev): ?>
                            <div class="event-item">
                                <div class="poster-box">
                                    <?php if (!empty($ev['poster_path']) && file_exists(__DIR__ . '/' . $ev['poster_path'])): ?>
                                        <img src="<?= htmlspecialchars($ev['poster_path']) ?>" alt="Poster">
                                    <?php else: ?>
                                        <div class="no-poster">
                                            <i class="fa-solid fa-image"></i>
                                            <span>No Poster Uploaded</span>
                                        </div>
                                    <?php endif; ?>
                                    <span class="category-tag"><?= htmlspecialchars($ev['category']) ?></span>
                                </div>
                                <div class="event-body">
                                    <h3><?= htmlspecialchars($ev['title']) ?></h3>
                                    <p class="event-meta">
                                        <span><i class="fa-regular fa-calendar"></i> <?= date('M d, Y', strtotime($ev['event_date'])) ?></span> &bull;
                                        <span><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($ev['venue']) ?></span>
                                    </p>
                                    <p class="event-desc"><?= htmlspecialchars($ev['description']) ?></p>

                                    <div class="event-actions">
                                        <a href="index.php?action=edit&id=<?= $ev['id'] ?>" class="btn-sm edit">
                                            <i class="fa-solid fa-pen"></i> Edit
                                        </a>
                                        <a href="index.php?action=delete&id=<?= $ev['id'] ?>" class="btn-sm delete" onclick="return confirm('Delete this event and poster?')">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</body>
</html>
