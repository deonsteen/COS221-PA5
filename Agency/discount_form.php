<?php
// Agency/discount_form.php
session_start();

if (!isset($_SESSION['AgentID']) || $_SESSION['role'] !== 'agency') {
    header('Location: ../login.php');
    exit;
}

require_once '../db.php';
$pdo = getDB();

$agentID    = (int) $_SESSION['AgentID'];
$stmtName = $pdo->prepare("SELECT Name FROM agencies WHERE AgentID = ?");
$stmtName->execute([$agentID]);
$agencyName = htmlspecialchars($stmtName->fetchColumn() ?: 'Your Agency');
$words      = explode(' ', $agencyName);
$initials   = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

// ── Load this agent's packages for the dropdown ──
$stmtPacks = $pdo->prepare("
    SELECT p.PackID, pi.Name
    FROM packages p
    JOIN packinfo pi ON pi.PackID = p.PackID
    WHERE p.AgentID = ?
    ORDER BY pi.Name ASC
");
$stmtPacks->execute([$agentID]);
$agentPackages = $stmtPacks->fetchAll(PDO::FETCH_ASSOC);

// If no packages exist, redirect back
if (empty($agentPackages)) {
    header('Location: discounts.php');
    exit;
}

// ── Are we editing? ──
$editID = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $editID > 0;
$errors = [];

// Default values
$fields = [
    'PackID'   => '',
    'From'     => '',
    'To'       => '',
    'Details'  => '',
    'Type'     => 'group',    // 'individual' or 'group'
    'IndLimit' => 1,
];

// ── If editing, load existing data ──
if ($isEdit) {
    $stmtExist = $pdo->prepare("
        SELECT d.DiscountID, d.PackID, d.From, d.To, d.Details,
               ind.Limit AS IndLimit
        FROM discounts d
        JOIN packages p ON p.PackID = d.PackID
        LEFT JOIN individual_discount ind ON ind.DiscountID = d.DiscountID
        WHERE d.DiscountID = ? AND p.AgentID = ?
    ");
    $stmtExist->execute([$editID, $agentID]);
    $existing = $stmtExist->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        header('Location: discounts.php');
        exit;
    }

    $fields = [
        'PackID'   => $existing['PackID'],
        'From'     => $existing['From'],
        'To'       => $existing['To'],
        'Details'  => $existing['Details'],
        'Type'     => $existing['IndLimit'] !== null ? 'individual' : 'group',
        'IndLimit' => $existing['IndLimit'] ?? 1,
    ];
}

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fields['PackID']   = (int)   ($_POST['PackID']   ?? 0);
    $fields['From']     = trim(    $_POST['From']     ?? '');
    $fields['To']       = trim(    $_POST['To']       ?? '');
    $fields['Details']  = trim(    $_POST['Details']  ?? '');
    $fields['Type']     = trim(    $_POST['Type']     ?? 'group');
    $fields['IndLimit'] = (int)   ($_POST['IndLimit'] ?? 1);

    // Validate
    if ($fields['PackID'] <= 0)
        $errors[] = 'Please select a package.';
    if ($fields['From'] === '')
        $errors[] = 'Start date is required.';
    if ($fields['To'] === '')
        $errors[] = 'End date is required.';
    if ($fields['From'] && $fields['To'] && $fields['To'] <= $fields['From'])
        $errors[] = 'End date must be after start date.';
    if ($fields['Details'] === '')
        $errors[] = 'Discount details are required.';
    if (!in_array($fields['Type'], ['individual', 'group']))
        $errors[] = 'Invalid discount type.';
    if ($fields['Type'] === 'individual' && $fields['IndLimit'] < 1)
        $errors[] = 'Individual limit must be at least 1.';

    // Verify the selected package belongs to this agent
    if ($fields['PackID'] > 0) {
        $stmtOwn = $pdo->prepare("SELECT PackID FROM packages WHERE PackID = ? AND AgentID = ?");
        $stmtOwn->execute([$fields['PackID'], $agentID]);
        if (!$stmtOwn->fetch()) $errors[] = 'Invalid package selected.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($isEdit) {
                // Update discounts
                $stmtD = $pdo->prepare("
                    UPDATE discounts
                    SET PackID = ?, `From` = ?, `To` = ?, Details = ?
                    WHERE DiscountID = ?
                ");
                $stmtD->execute([
                    $fields['PackID'],
                    $fields['From'],
                    $fields['To'],
                    $fields['Details'],
                    $editID
                ]);

                // Handle individual_discount subtype
                if ($fields['Type'] === 'individual') {
                    // Upsert into individual_discount
                    $stmtI = $pdo->prepare("
                        INSERT INTO individual_discount (DiscountID, `Limit`)
                        VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE `Limit` = VALUES(`Limit`)
                    ");
                    $stmtI->execute([$editID, $fields['IndLimit']]);
                } else {
                    // Remove from individual_discount if switching to group
                    $stmtDel = $pdo->prepare("DELETE FROM individual_discount WHERE DiscountID = ?");
                    $stmtDel->execute([$editID]);
                }

                $pdo->commit();
                header('Location: discounts.php?success=updated');
                exit;

            } else {
                // Insert into discounts
                $stmtD = $pdo->prepare("
                    INSERT INTO discounts (PackID, `From`, `To`, Details)
                    VALUES (?, ?, ?, ?)
                ");
                $stmtD->execute([
                    $fields['PackID'],
                    $fields['From'],
                    $fields['To'],
                    $fields['Details'],
                ]);
                $newDiscID = (int) $pdo->lastInsertId();

                // Insert into individual_discount if individual type
                if ($fields['Type'] === 'individual') {
                    $stmtI = $pdo->prepare("
                        INSERT INTO individual_discount (DiscountID, `Limit`) VALUES (?, ?)
                    ");
                    $stmtI->execute([$newDiscID, $fields['IndLimit']]);
                }

                $pdo->commit();
                header('Location: discounts.php?success=created');
                exit;
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error. Please try again.';
        }
    }
}

$pageTitle = $isEdit ? 'Edit Discount' : 'New Discount';
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
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-actions {
      display: flex; align-items: center; gap: 10px;
      padding: 20px 28px;
      border-top: 1px solid var(--border);
      background: #fafafa;
      border-radius: 0 0 var(--radius-lg) var(--radius-lg);
    }

    /* Type toggle */
    .type-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 18px; }
    .type-radio  { display: none; }
    .type-label  {
      display: flex; flex-direction: column; align-items: center; gap: 6px;
      padding: 14px 10px;
      border: 1.5px solid var(--border); border-radius: var(--radius);
      cursor: pointer; font-size: 13px; font-weight: 500;
      color: var(--muted); transition: all .15s; text-align: center;
    }
    .type-label .icon { font-size: 24px; }
    .type-radio:checked + .type-label {
      border-color: var(--teal); color: var(--teal); background: var(--teal-pale);
    }
    #indLimitGroup { display: none; }

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
      <a href="packages.php"><span class="icon">📦</span> Packages</a>
      <a href="discounts.php" class="active"><span class="icon">🏷️</span> Discounts</a>
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
      <a href="discounts.php" style="color:var(--teal);">Discounts</a>
      <span style="margin:0 6px;">›</span>
      <span><?= $pageTitle ?></span>
    </div>

    <div class="page-header">
      <h1><?= $pageTitle ?></h1>
      <p><?= $isEdit ? 'Update this discount.' : 'Add a discount to one of your packages.' ?></p>
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
        <h2><?= $isEdit ? '✏️ Edit Discount' : '🏷️ Discount Details' ?></h2>
        <p>Fields marked * are required.</p>
      </div>

      <form method="POST" action="discount_form.php<?= $isEdit ? '?id=' . $editID : '' ?>">
        <div class="form-card-body">

          <!-- Package -->
          <div class="form-group">
            <label for="PackID">Package *</label>
            <select id="PackID" name="PackID" class="form-control" required>
              <option value="">— Select a package —</option>
              <?php foreach ($agentPackages as $pack): ?>
                <option value="<?= $pack['PackID'] ?>"
                  <?= (int)$fields['PackID'] === (int)$pack['PackID'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($pack['Name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Details -->
          <div class="form-group">
            <label for="Details">Discount Details *</label>
            <input
              type="text"
              id="Details"
              name="Details"
              class="form-control"
              placeholder="e.g. 15% off for June bookings"
              value="<?= htmlspecialchars($fields['Details']) ?>"
              maxlength="255"
              required
            >
          </div>

          <!-- Date range -->
          <div class="form-row">
            <div class="form-group">
              <label for="From">Valid From *</label>
              <input
                type="date"
                id="From"
                name="From"
                class="form-control"
                value="<?= htmlspecialchars($fields['From']) ?>"
                required
              >
            </div>
            <div class="form-group">
              <label for="To">Valid To *</label>
              <input
                type="date"
                id="To"
                name="To"
                class="form-control"
                value="<?= htmlspecialchars($fields['To']) ?>"
                required
              >
            </div>
          </div>

          <!-- Discount type -->
          <div class="form-group">
            <label>Discount Type *</label>
            <div class="type-toggle">
              <input
                type="radio" name="Type" id="typeGroup"
                class="type-radio" value="group"
                <?= $fields['Type'] === 'group' ? 'checked' : '' ?>
              >
              <label for="typeGroup" class="type-label">
                <span class="icon">👥</span>
                <strong>Group</strong>
                <span style="font-size:11px;font-weight:400;">Open to all travellers</span>
              </label>

              <input
                type="radio" name="Type" id="typeIndividual"
                class="type-radio" value="individual"
                <?= $fields['Type'] === 'individual' ? 'checked' : '' ?>
              >
              <label for="typeIndividual" class="type-label">
                <span class="icon">👤</span>
                <strong>Individual</strong>
                <span style="font-size:11px;font-weight:400;">Limited redemptions</span>
              </label>
            </div>
          </div>

          <!-- Individual limit (shown only for individual type) -->
          <div class="form-group" id="indLimitGroup">
            <label for="IndLimit">Redemption Limit *</label>
            <input
              type="number"
              id="IndLimit"
              name="IndLimit"
              class="form-control"
              placeholder="e.g. 1"
              value="<?= (int)$fields['IndLimit'] ?>"
              min="1"
              max="100"
            >
            <span class="text-sm text-muted" style="margin-top:4px;display:block;">
              How many times this discount can be used per person.
            </span>
          </div>

        </div><!-- /.form-card-body -->

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <?= $isEdit ? '💾 Save Changes' : '+ Create Discount' ?>
          </button>
          <a href="discounts.php" class="btn btn-outline">Cancel</a>
        </div>

      </form>
    </div>

  </main>
</div>

<script>
// Show/hide individual limit based on type selection
const radios    = document.querySelectorAll('input[name="Type"]');
const limitGrp  = document.getElementById('indLimitGroup');
const limitInp  = document.getElementById('IndLimit');

function toggleLimit() {
  const isInd = document.getElementById('typeIndividual').checked;
  limitGrp.style.display = isInd ? 'block' : 'none';
  limitInp.required      = isInd;
}

radios.forEach(r => r.addEventListener('change', toggleLimit));
toggleLimit(); // run on page load

// Date validation: To must be after From
document.getElementById('From').addEventListener('change', function() {
  document.getElementById('To').min = this.value;
});
</script>

</body>
</html>
