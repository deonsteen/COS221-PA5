<?php
// Agency/discounts.php
session_start();

if (!isset($_SESSION['AgentID']) || $_SESSION['role'] !== 'agency') {
    header('Location: ../login.php');
    exit;
}

require_once '../db.php';
$pdo = getDB();

$agentID    = (int) $_SESSION['AgentID'];
$agencyName = htmlspecialchars($_SESSION['AgencyName'] ?? 'Your Agency');
$words      = explode(' ', $agencyName);
$initials   = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

// ── Flash messages ──
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

// ── Filter inputs ──
$filterPack   = $_GET['package'] ?? '';
$filterStatus = $_GET['status']  ?? '';

// ── Build query ──
// Only show discounts that belong to this agent's packages
$where  = ['p.AgentID = ?'];
$params = [$agentID];

if ($filterPack) {
    $where[]  = 'p.PackID = ?';
    $params[] = (int) $filterPack;
}

$today = date('Y-m-d');
if ($filterStatus === 'active') {
    $where[]  = 'd.From <= ? AND d.To >= ?';
    $params[] = $today;
    $params[] = $today;
} elseif ($filterStatus === 'upcoming') {
    $where[]  = 'd.From > ?';
    $params[] = $today;
} elseif ($filterStatus === 'expired') {
    $where[]  = 'd.To < ?';
    $params[] = $today;
}

$sql = "
    SELECT
        d.DiscountID,
        d.PackID,
        d.From,
        d.To,
        d.Details,
        pi.Name        AS PackageName,
        ind.Limit      AS IndLimit
    FROM discounts d
    JOIN packages  p   ON p.PackID   = d.PackID
    JOIN packinfo  pi  ON pi.PackID  = d.PackID
    LEFT JOIN individual_discount ind ON ind.DiscountID = d.DiscountID
    WHERE " . implode(' AND ', $where) . "
    ORDER BY d.From DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Load agent's packages for the filter dropdown ──
$stmtPacks = $pdo->prepare("
    SELECT p.PackID, pi.Name
    FROM packages p
    JOIN packinfo pi ON pi.PackID = p.PackID
    WHERE p.AgentID = ?
    ORDER BY pi.Name ASC
");
$stmtPacks->execute([$agentID]);
$agentPackages = $stmtPacks->fetchAll(PDO::FETCH_ASSOC);

// ── Status helper ──
function discountStatus(string $from, string $to): array {
    $today = date('Y-m-d');
    if ($to < $today)   return ['Expired',  'status-expired'];
    if ($from > $today) return ['Upcoming', 'status-upcoming'];
    return ['Active', 'status-active'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Discounts — <?= $agencyName ?> | Tripistry</title>
  <link rel="stylesheet" href="css/agency.css">
  <style>
    .status-active   { background:#f0fdf4; color:var(--green);  border:1px solid #bbf7d0; }
    .status-upcoming { background:#fffbeb; color:var(--gold);   border:1px solid #fde68a; }
    .status-expired  { background:#f9fafb; color:var(--muted);  border:1px solid var(--border); }
    .status-pill {
      display: inline-block;
      font-size: 11px; font-weight: 600;
      padding: 2px 10px; border-radius: 99px;
      text-transform: uppercase; letter-spacing: .4px;
    }
    .ind-badge {
      display:inline-flex; align-items:center; gap:4px;
      font-size:11px; font-weight:600;
      background:var(--teal-pale); color:var(--teal);
      border-radius:4px; padding:2px 8px;
    }
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

    <div class="page-header flex-between">
      <div>
        <h1>Discounts</h1>
        <p><?= count($discounts) ?> discount<?= count($discounts) !== 1 ? 's' : '' ?> found</p>
      </div>
      <?php if (!empty($agentPackages)): ?>
        <a href="discount_form.php" class="btn btn-primary">+ New Discount</a>
      <?php else: ?>
        <span class="text-muted text-sm" style="font-style:italic;">
          Create a package first to add discounts
        </span>
      <?php endif; ?>
    </div>

    <!-- Flash messages -->
    <?php if ($success === 'created'): ?>
      <div class="alert alert-success">✓ Discount created successfully.</div>
    <?php elseif ($success === 'updated'): ?>
      <div class="alert alert-success">✓ Discount updated successfully.</div>
    <?php elseif ($success === 'deleted'): ?>
      <div class="alert alert-success">✓ Discount deleted successfully.</div>
    <?php elseif ($error === 'delete_failed'): ?>
      <div class="alert alert-error">✗ Could not delete discount. Please try again.</div>
    <?php endif; ?>

    <!-- FILTER BAR -->
    <form method="GET" action="discounts.php">
      <div class="filter-bar">
        <div class="form-group">
          <label>Package</label>
          <select name="package" class="form-control">
            <option value="">All Packages</option>
            <?php foreach ($agentPackages as $pack): ?>
              <option value="<?= $pack['PackID'] ?>"
                <?= (int)$filterPack === (int)$pack['PackID'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($pack['Name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <option value="active"   <?= $filterStatus === 'active'   ? 'selected' : '' ?>>Active</option>
            <option value="upcoming" <?= $filterStatus === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
            <option value="expired"  <?= $filterStatus === 'expired'  ? 'selected' : '' ?>>Expired</option>
          </select>
        </div>
        <div class="form-group" style="flex:0">
          <label>&nbsp;</label>
          <button type="submit" class="btn btn-primary">Filter</button>
        </div>
        <?php if ($filterPack || $filterStatus): ?>
          <div class="form-group" style="flex:0">
            <label>&nbsp;</label>
            <a href="discounts.php" class="btn btn-outline">Clear</a>
          </div>
        <?php endif; ?>
      </div>
    </form>

    <!-- DISCOUNTS TABLE -->
    <div class="card">
      <?php if (empty($discounts)): ?>
        <div class="empty-state">
          <div class="empty-icon">🏷️</div>
          <p>No discounts found.<br>
            <?php if (!empty($agentPackages)): ?>
              <a href="discount_form.php">Create your first discount</a>
            <?php else: ?>
              <a href="package_form.php">Create a package first</a>
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Package</th>
              <th>Details</th>
              <th>Valid From</th>
              <th>Valid To</th>
              <th>Type</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($discounts as $disc):
              [$statusLabel, $statusClass] = discountStatus($disc['From'], $disc['To']);
            ?>
              <tr>
                <td>
                  <span style="font-weight:500;">
                    <?= htmlspecialchars($disc['PackageName']) ?>
                  </span>
                </td>
                <td style="max-width:220px;">
                  <span class="text-sm" style="color:var(--ink-2);">
                    <?= htmlspecialchars($disc['Details']) ?>
                  </span>
                </td>
                <td class="text-sm text-muted">
                  <?= date('d M Y', strtotime($disc['From'])) ?>
                </td>
                <td class="text-sm text-muted">
                  <?= date('d M Y', strtotime($disc['To'])) ?>
                </td>
                <td>
                  <?php if ($disc['IndLimit'] !== null): ?>
                    <span class="ind-badge">👤 Individual (<?= $disc['IndLimit'] ?>)</span>
                  <?php else: ?>
                    <span class="text-sm text-muted">Group</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="status-pill <?= $statusClass ?>">
                    <?= $statusLabel ?>
                  </span>
                </td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <a href="discount_form.php?id=<?= $disc['DiscountID'] ?>"
                       class="btn btn-outline btn-sm">✏️ Edit</a>
                    <button
                      class="btn btn-danger btn-sm"
                      onclick="confirmDelete(<?= $disc['DiscountID'] ?>, '<?= htmlspecialchars(addslashes($disc['Details'])) ?>')"
                    >🗑 Delete</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <div class="modal-title">Delete Discount</div>
    <p style="color:var(--ink-2);font-size:14px;margin-bottom:20px;">
      Are you sure you want to delete the discount:<br>
      <strong id="deleteName"></strong>?
      This cannot be undone.
    </p>
    <div style="display:flex;gap:10px;justify-content:flex-end;">
      <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
      <form id="deleteForm" method="POST" action="discount_delete.php">
        <input type="hidden" name="DiscountID" id="deleteDiscountID">
        <button type="submit" class="btn btn-danger">Delete</button>
      </form>
    </div>
  </div>
</div>

<script>
function confirmDelete(id, details) {
  document.getElementById('deleteName').textContent      = details;
  document.getElementById('deleteDiscountID').value      = id;
  document.getElementById('deleteModal').classList.add('open');
}
function closeModal() {
  document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>

</body>
</html>
