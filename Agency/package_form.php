<?php
// Agency/package_form.php
session_start();

if (!isset($_SESSION['sub_id']) || $_SESSION['role'] !== 'agency') {
    header('Location: ../login.php');
    exit;
}

require_once '../db.php';
$pdo = getDB();

$agentID    = (int) $_SESSION['sub_id'];
$stmtName = $pdo->prepare("SELECT Name FROM agencies WHERE AgentID = ?");
$stmtName->execute([$agentID]);
$agencyName = htmlspecialchars($stmtName->fetchColumn() ?: 'Your Agency');
$words      = explode(' ', $agencyName);
$initials   = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

// ── Are we editing an existing package? ──
$editID  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit  = $editID > 0;
$errors  = [];

// Default field values
$fields = [
    'Name'        => '',
    'Destination' => '',
    'Price'       => '',
    'Duration'    => '',
    'Class'       => 'Standard',
];

// ── If editing, load existing data ──
if ($isEdit) {
    $stmt = $pdo->prepare("
        SELECT p.PackID, p.Price, pi.Name, pi.Destination, pi.Duration, pi.Class
        FROM packages p
        JOIN packinfo pi ON pi.PackID = p.PackID
        WHERE p.PackID = ? AND p.AgentID = ?
    ");
    $stmt->execute([$editID, $agentID]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    // If package not found or doesn't belong to this agent, redirect
    if (!$existing) {
        header('Location: packages.php');
        exit;
    }

    $fields = [
        'Name'        => $existing['Name'],
        'Destination' => $existing['Destination'],
        'Price'       => $existing['Price'],
        'Duration'    => $existing['Duration'],
        'Class'       => $existing['Class'],
    ];
}

// ── Handle form submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitise + validate
    $fields['Name']        = trim($_POST['Name']        ?? '');
    $fields['Destination'] = trim($_POST['Destination'] ?? '');
    $fields['Price']       = trim($_POST['Price']       ?? '');
    $fields['Duration']    = trim($_POST['Duration']    ?? '');
    $fields['Class']       = trim($_POST['Class']       ?? 'Standard');

    if ($fields['Name'] === '')
        $errors[] = 'Package name is required.';
    if ($fields['Destination'] === '')
        $errors[] = 'Destination is required.';
    if (!is_numeric($fields['Price']) || (float)$fields['Price'] <= 0)
        $errors[] = 'Price must be a positive number.';
    if (!is_numeric($fields['Duration']) || (int)$fields['Duration'] <= 0)
        $errors[] = 'Duration must be a positive whole number.';
    if (!in_array($fields['Class'], ['Standard', 'Premium', 'Luxury']))
        $errors[] = 'Invalid class selected.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($isEdit) {
                // Update packages table
                $stmtP = $pdo->prepare("UPDATE packages SET Price = ? WHERE PackID = ? AND AgentID = ?");
                $stmtP->execute([(float)$fields['Price'], $editID, $agentID]);

                // Update packinfo table
                $stmtPI = $pdo->prepare("
                    UPDATE packinfo
                    SET Name = ?, Destination = ?, Duration = ?, Class = ?
                    WHERE PackID = ?
                ");
                $stmtPI->execute([
                    $fields['Name'],
                    $fields['Destination'],
                    (int)$fields['Duration'],
                    $fields['Class'],
                    $editID
                ]);

                $pdo->commit();
                header('Location: packages.php?success=updated');
                exit;

            } else {
                // Insert into packages
                $stmtP = $pdo->prepare("INSERT INTO packages (AgentID, Price) VALUES (?, ?)");
                $stmtP->execute([$agentID, (float)$fields['Price']]);
                $newPackID = (int) $pdo->lastInsertId();

                // Insert into packinfo
                $stmtPI = $pdo->prepare("
                    INSERT INTO packinfo (PackID, Name, Destination, Duration, Class)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtPI->execute([
                    $newPackID,
                    $fields['Name'],
                    $fields['Destination'],
                    (int)$fields['Duration'],
                    $fields['Class'],
                ]);

                $pdo->commit();
                header('Location: packages.php?success=created');
                exit;
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error. Please try again.';
        }
    }
}

$pageTitle = $isEdit ? 'Edit Package' : 'New Package';
$pageDesc  = $isEdit ? 'Update the details for this package.' : 'Fill in the details to create a new travel package.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?> | Tripistry</title>
  <link rel="stylesheet" href="css/agency.css">
  <style>
    .form-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow);
      max-width: 640px;
    }
    .form-card-header {
      padding: 20px 28px;
      border-bottom: 1px solid var(--border);
    }
    .form-card-header h2 {
      font-family: 'Fraunces', serif;
      font-size: 20px; font-weight: 500;
    }
    .form-card-header p { color: var(--muted); font-size: 13px; margin-top: 2px; }
    .form-card-body { padding: 28px; }
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    .form-actions {
      display: flex; align-items: center; gap: 10px;
      padding: 20px 28px;
      border-top: 1px solid var(--border);
      background: #fafafa;
      border-radius: 0 0 var(--radius-lg) var(--radius-lg);
    }
    @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="agency_dashboard.php" class="nav-logo">Trip<em>istry</em></a>
  <div class="nav-right">
    <div class="nav-agency-badge">
      <div class="avatar"><?= $initials ?></div>
      <?= $agencyName ?>
    </div>
    <a href="../logout.php" class="btn-logout">⎋ Logout</a>
  </div>
</nav>

<div class="dashboard-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-section">
      <span class="sidebar-label">Overview</span>
      <a href="agency_dashboard.php"><span class="icon">🏠</span> Dashboard</a>
    </div>
    <div class="sidebar-section">
      <span class="sidebar-label">Manage</span>
      <a href="packages.php" class="active"><span class="icon">📦</span> Packages</a>
      <a href="discounts.php"><span class="icon">🏷️</span> Discounts</a>
    </div>
    <div class="sidebar-section">
      <span class="sidebar-label">People</span>
      <a href="clients.php"><span class="icon">👥</span> Clients</a>
    </div>
    <div class="sidebar-section">
      <span class="sidebar-label">Feedback</span>
      <a href="reviews.php"><span class="icon">⭐</span> Reviews</a>
    </div>
    <hr class="sidebar-divider">
    <a href="../logout.php"><span class="icon">🚪</span> Logout</a>
  </aside>

  <!-- MAIN -->
  <main class="main-content">

    <!-- Breadcrumb -->
    <div style="font-size:13px;color:var(--muted);margin-bottom:20px;">
      <a href="packages.php" style="color:var(--teal);">Packages</a>
      <span style="margin:0 6px;">›</span>
      <span><?= $pageTitle ?></span>
    </div>

    <div class="page-header">
      <h1><?= $pageTitle ?></h1>
      <p><?= $pageDesc ?></p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error" style="max-width:640px;">
        <?php foreach ($errors as $e): ?>
          <div>✗ <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="form-card">
      <div class="form-card-header">
        <h2><?= $isEdit ? '✏️ Edit Package' : '📦 Package Details' ?></h2>
        <p>Fields marked * are required.</p>
      </div>

      <form method="POST" action="package_form.php<?= $isEdit ? '?id=' . $editID : '' ?>">
        <div class="form-card-body">

          <!-- Package Name -->
          <div class="form-group">
            <label for="Name">Package Name *</label>
            <input
              type="text"
              id="Name"
              name="Name"
              class="form-control"
              placeholder="e.g. Paris Romantic Escape"
              value="<?= htmlspecialchars($fields['Name']) ?>"
              maxlength="150"
              required
            >
          </div>

          <!-- Destination -->
          <div class="form-group">
            <label for="Destination">Destination *</label>
            <input
              type="text"
              id="Destination"
              name="Destination"
              class="form-control"
              placeholder="e.g. Paris, France"
              value="<?= htmlspecialchars($fields['Destination']) ?>"
              maxlength="150"
              required
            >
          </div>

          <!-- Price + Duration -->
          <div class="form-row">
            <div class="form-group">
              <label for="Price">Price (R) *</label>
              <input
                type="number"
                id="Price"
                name="Price"
                class="form-control"
                placeholder="e.g. 25000"
                value="<?= htmlspecialchars($fields['Price']) ?>"
                min="1"
                step="0.01"
                required
              >
            </div>
            <div class="form-group">
              <label for="Duration">Duration (days) *</label>
              <input
                type="number"
                id="Duration"
                name="Duration"
                class="form-control"
                placeholder="e.g. 7"
                value="<?= htmlspecialchars($fields['Duration']) ?>"
                min="1"
                max="365"
                required
              >
            </div>
          </div>

          <!-- Class -->
          <div class="form-group">
            <label for="Class">Package Class *</label>
            <select id="Class" name="Class" class="form-control" required>
              <option value="Standard" <?= $fields['Class'] === 'Standard' ? 'selected' : '' ?>>
                Standard
              </option>
              <option value="Premium" <?= $fields['Class'] === 'Premium' ? 'selected' : '' ?>>
                Premium
              </option>
              <option value="Luxury" <?= $fields['Class'] === 'Luxury' ? 'selected' : '' ?>>
                Luxury
              </option>
            </select>
          </div>

        </div><!-- /.form-card-body -->

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <?= $isEdit ? '💾 Save Changes' : '+ Create Package' ?>
          </button>
          <a href="packages.php" class="btn btn-outline">Cancel</a>
        </div>

      </form>
    </div><!-- /.form-card -->

  </main>
</div>

</body>
</html>
