<?php
require_once __DIR__ . '../db.php';
require_once __DIR__ . '../auth.php';
requireRole('agency');
$u = currentUser();
$db = getDB();
$agentId = $u['sub_id'];
 
// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $pid = filter_input(INPUT_POST, 'pack_id', FILTER_VALIDATE_INT);
    if ($pid) {
        // Verify ownership
        $check = $db->prepare("SELECT PackID FROM packages WHERE PackID=? AND AgentID=?");
        $check->execute([$pid, $agentId]);
        if ($check->fetch()) {
            $db->prepare("DELETE FROM packages WHERE PackID=?")->execute([$pid]);
            $success = 'Package deleted.';
        }
    }
}
 
$packages = $db->prepare("
    SELECT p.PackID, p.Price, pi.Name, pi.Destination, pi.Duration, pi.Class,
           COUNT(DISTINCT h.HolidayID) AS BookingCount,
           d.Details AS ActiveDiscount
    FROM packages p
    JOIN packinfo pi ON pi.PackID = p.PackID
    LEFT JOIN clients cl ON cl.AgentID = p.AgentID
    LEFT JOIN holidays h ON h.PackID = p.PackID
    LEFT JOIN discounts d ON d.PackID = p.PackID AND CURDATE() BETWEEN d.`From` AND d.`To`
    WHERE p.AgentID = ?
    GROUP BY p.PackID
    ORDER BY p.PackID DESC
");
$packages->execute([$agentId]);
$allPackages = $packages->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Packages – Tripistry</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav_agency.php'; ?>
<div class="container page-wrap">
  <div class="sidebar-layout">
    <?php include '../includes/sidebar_agency.php'; ?>
    <div>
      <div class="flex-between mb-3">
        <div class="page-header" style="margin:0"><h1>My Packages</h1></div>
        <a href="package_new.php" class="btn btn-primary">➕ New Package</a>
      </div>
 
      <?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
 
      <div class="card">
        <div style="overflow-x:auto">
          <table class="table">
            <thead><tr><th>Package</th><th>Destination</th><th>Duration</th><th>Class</th><th>Price</th><th>Bookings</th><th>Discount</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($allPackages as $p): ?>
            <tr>
              <td><strong><?= htmlspecialchars($p['Name']) ?></strong></td>
              <td><?= htmlspecialchars($p['Destination']) ?></td>
              <td><?= $p['Duration'] ?> days</td>
              <td><span class="pack-badge badge-<?= strtolower($p['Class']) ?>"><?= $p['Class'] ?></span></td>
              <td><strong style="color:var(--teal)">R<?= number_format($p['Price'],0) ?></strong></td>
              <td><?= $p['BookingCount'] ?></td>
              <td><?= $p['ActiveDiscount'] ? '<span class="text-sm" style="color:var(--green)">🏷️ Active</span>' : '<span class="text-muted text-sm">None</span>' ?></td>
              <td style="white-space:nowrap">
                <a href="package_edit.php?id=<?= $p['PackID'] ?>" class="btn btn-outline btn-sm">Edit</a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this package?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="pack_id" value="<?= $p['PackID'] ?>">
                  <button class="btn btn-danger btn-sm">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
 