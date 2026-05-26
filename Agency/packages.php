<?php
// Agency/packages.php
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

// ── Flash messages from redirects ──
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

// ── Filter inputs ──
$filterClass = $_GET['class']       ?? '';
$filterDest  = $_GET['destination'] ?? '';
$filterSort  = $_GET['sort']        ?? 'newest';

// ── Build query with filters ──
$where  = ['p.AgentID = ?'];
$params = [$agentID];

if ($filterClass) {
    $where[]  = 'pi.Class = ?';
    $params[] = $filterClass;
}
if ($filterDest) {
    $where[]  = 'pi.Destination LIKE ?';
    $params[] = '%' . $filterDest . '%';
}

$orderBy = match($filterSort) {
    'price_asc'  => 'p.Price ASC',
    'price_desc' => 'p.Price DESC',
    'duration'   => 'pi.Duration ASC',
    'name'       => 'pi.Name ASC',
    default      => 'p.PackID DESC',
};

$sql = "
    SELECT p.PackID, p.Price, pi.Name, pi.Destination, pi.Duration, pi.Class
    FROM packages p
    JOIN packinfo pi ON pi.PackID = p.PackID
    WHERE " . implode(' AND ', $where) . "
    ORDER BY {$orderBy}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

function classBadge(string $class): string {
    $map = ['Standard' => 'badge-standard', 'Premium' => 'badge-premium', 'Luxury' => 'badge-luxury'];
    $cls = $map[$class] ?? 'badge-standard';
    return "<span class=\"badge {$cls}\">" . htmlspecialchars($class) . "</span>";
}

function destEmoji(string $dest): string {
    $map = [
        'Paris' => '🗼', 'Bali' => '🌴', 'Tokyo' => '🗾', 'Cape Town' => '🏔',
        'Dubai' => '🏙', 'Rome' => '🏛', 'Maldives' => '🏝', 'Safari' => '🦁',
        'Zanzibar' => '🌊', 'Santorini' => '🌅', 'Bangkok' => '🛕', 'Kyoto' => '🌸',
        'Kruger' => '🦁', 'Mauritius' => '🌺', 'Singapore' => '✨', 'New York' => '🗽',
        'Barcelona' => '🎨', 'Lisbon' => '🌞', 'Istanbul' => '🕌', 'Nairobi' => '🌍',
        'Cairo' => '🏺', 'Serengeti' => '🦒', 'Phuket' => '🏖', 'Marrakech' => '🕌',
        'Prague' => '🏰', 'Victoria' => '💧', 'Durban' => '🌊', 'Johannesburg' => '🌆',
    ];
    foreach ($map as $keyword => $emoji) {
        if (stripos($dest, $keyword) !== false) return $emoji;
    }
    return '✈️';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Packages — <?= $agencyName ?> | Tripistry</title>
  <link rel="stylesheet" href="css/agency.css">
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

    <div class="page-header flex-between">
      <div>
        <h1>Packages</h1>
        <p><?= count($packages) ?> package<?= count($packages) !== 1 ? 's' : '' ?> found</p>
      </div>
      <a href="package_form.php" class="btn btn-primary">+ New Package</a>
    </div>

    <?php if ($success === 'created'): ?>
      <div class="alert alert-success">✓ Package created successfully.</div>
    <?php elseif ($success === 'updated'): ?>
      <div class="alert alert-success">✓ Package updated successfully.</div>
    <?php elseif ($success === 'deleted'): ?>
      <div class="alert alert-success">✓ Package deleted successfully.</div>
    <?php elseif ($error === 'delete_failed'): ?>
      <div class="alert alert-error">✗ Could not delete package. Please try again.</div>
    <?php endif; ?>

    <!-- FILTER BAR -->
    <form method="GET" action="packages.php">
      <div class="filter-bar">
        <div class="form-group">
          <label>Destination</label>
          <input
            type="text"
            name="destination"
            class="form-control"
            placeholder="e.g. Bali"
            value="<?= htmlspecialchars($filterDest) ?>"
          >
        </div>
        <div class="form-group">
          <label>Class</label>
          <select name="class" class="form-control">
            <option value="">All Classes</option>
            <option value="Standard" <?= $filterClass === 'Standard' ? 'selected' : '' ?>>Standard</option>
            <option value="Premium"  <?= $filterClass === 'Premium'  ? 'selected' : '' ?>>Premium</option>
            <option value="Luxury"   <?= $filterClass === 'Luxury'   ? 'selected' : '' ?>>Luxury</option>
          </select>
        </div>
        <div class="form-group">
          <label>Sort By</label>
          <select name="sort" class="form-control">
            <option value="newest"     <?= $filterSort === 'newest'     ? 'selected' : '' ?>>Newest First</option>
            <option value="price_asc"  <?= $filterSort === 'price_asc'  ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_desc" <?= $filterSort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="duration"   <?= $filterSort === 'duration'   ? 'selected' : '' ?>>Duration</option>
            <option value="name"       <?= $filterSort === 'name'       ? 'selected' : '' ?>>Name A–Z</option>
          </select>
        </div>
        <div class="form-group" style="flex:0">
          <label>&nbsp;</label>
          <button type="submit" class="btn btn-primary">Filter</button>
        </div>
        <?php if ($filterClass || $filterDest || $filterSort !== 'newest'): ?>
          <div class="form-group" style="flex:0">
            <label>&nbsp;</label>
            <a href="packages.php" class="btn btn-outline">Clear</a>
          </div>
        <?php endif; ?>
      </div>
    </form>

    <!-- PACKAGES TABLE -->
    <div class="card">
      <?php if (empty($packages)): ?>
        <div class="empty-state">
          <div class="empty-icon">📦</div>
          <p>No packages found.<br>
            <a href="package_form.php">Create your first package</a>
          </p>
        </div>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Package</th>
              <th>Destination</th>
              <th>Duration</th>
              <th>Class</th>
              <th>Price</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($packages as $pack): ?>
              <tr>
                <td>
                  <div style="display:flex; align-items:center; gap:10px;">
                    <div class="pack-thumb" style="width:36px;height:36px;font-size:16px;border-radius:8px;background:linear-gradient(135deg,var(--teal-d),var(--teal-l));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                      <?= destEmoji($pack['Destination']) ?>
                    </div>
                    <span style="font-weight:500;"><?= htmlspecialchars($pack['Name']) ?></span>
                  </div>
                </td>
                <td class="text-muted text-sm"><?= htmlspecialchars($pack['Destination']) ?></td>
                <td class="text-sm"><?= $pack['Duration'] ?> days</td>
                <td><?= classBadge($pack['Class']) ?></td>
                <td style="font-weight:600;color:var(--teal);">
                  R<?= number_format($pack['Price'], 0, '.', ' ') ?>
                </td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <a href="package_form.php?id=<?= $pack['PackID'] ?>" class="btn btn-outline btn-sm">✏️ Edit</a>
                    <button
                      class="btn btn-danger btn-sm"
                      onclick="confirmDelete(<?= $pack['PackID'] ?>, '<?= htmlspecialchars(addslashes($pack['Name'])) ?>')"
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
    <div class="modal-title">Delete Package</div>
    <p style="color:var(--ink-2);font-size:14px;margin-bottom:20px;">
      Are you sure you want to delete <strong id="deleteName"></strong>?
      This cannot be undone.
    </p>
    <div style="display:flex;gap:10px;justify-content:flex-end;">
      <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
      <form id="deleteForm" method="POST" action="package_delete.php">
        <input type="hidden" name="PackID" id="deletePackID">
        <button type="submit" class="btn btn-danger">Delete</button>
      </form>
    </div>
  </div>
</div>

<script>
function confirmDelete(id, name) {
  document.getElementById('deleteName').textContent  = name;
  document.getElementById('deletePackID').value      = id;
  document.getElementById('deleteModal').classList.add('open');
}
function closeModal() {
  document.getElementById('deleteModal').classList.remove('open');
}
// Close modal if clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>

</body>
</html>
