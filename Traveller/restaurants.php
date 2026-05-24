<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
requireRole('traveller');
$db = getDB();
$search = trim($_GET['search'] ?? '');
$sql = "
    SELECT to2.TOID, to2.Name, to2.City, res.TimeOpen, res.TimeClose, res.ResID,
          d.Name AS DestName, ROUND(AVG(rev.Rating),1) AS AvgRating,
          GROUP_CONCAT(DISTINCT mi.Item ORDER BY mi.Price DESC SEPARATOR ', ') AS MenuSample
    FROM restaurants res
    JOIN tourism_offerings to2 ON to2.TOID = res.TOID
    JOIN destinations d ON d.DestID = to2.DestID
    LEFT JOIN reviews rev ON rev.TOID = to2.TOID
    LEFT JOIN menu_items mi ON mi.ResID = res.ResID
    WHERE 1=1";
$params = [];
if ($search) {
  $sql .= " AND (to2.Name LIKE ? OR to2.City LIKE ? OR d.Name LIKE ?)";
  $params = ["%$search%", "%$search%", "%$search%"];
}
$sql .= " GROUP BY res.ResID ORDER BY to2.Name";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restaurants – Tripistry</title>
  <link rel="stylesheet" href="../css/style.css">
</head>

<body>
  <?php include __DIR__ . '/../nav_traveller.php'; ?>
  <div class="container page-wrap">
    <div class="sidebar-layout">
      <?php include __DIR__ . '/../sidebar_traveller.php'; ?>
      <div>
        <div class="page-header">
          <h1>🍽️ Restaurants</h1>
          <p><?= count($restaurants) ?> restaurants</p>
        </div>
        <form method="GET" class="filter-bar">
          <div class="form-group"><label>Search</label><input class="form-control" name="search"
              value="<?= htmlspecialchars($search) ?>" placeholder="Restaurant or city…"></div>
          <div class="form-group" style="align-self:flex-end"><button class="btn btn-primary">Search</button></div>
        </form>
        <div class="package-grid">
          <?php foreach ($restaurants as $r): ?>
            <div class="pack-card">
              <div class="pack-card-img" style="font-size:48px">🍽️</div>
              <div class="pack-card-body">
                <div class="pack-title"><?= htmlspecialchars($r['Name']) ?></div>
                <div class="pack-dest">📍 <?= htmlspecialchars($r['City']) ?>, <?= htmlspecialchars($r['DestName']) ?>
                </div>
                <?php if ($r['AvgRating']): ?>
                  <div class="text-sm" style="color:var(--gold); margin-bottom:6px">
                    <?= str_repeat('★', round($r['AvgRating'])) . str_repeat('☆', 5 - round($r['AvgRating'])) ?>
                    <?= $r['AvgRating'] ?></div>
                <?php endif; ?>
                <div class="text-sm text-muted">🕐 <?= substr($r['TimeOpen'], 0, 5) ?> – <?= substr($r['TimeClose'], 0, 5) ?>
                </div>
                <?php if ($r['MenuSample']): ?>
                  <div class="text-sm text-muted" style="margin-top:6px">Menu:
                    <?= htmlspecialchars(mb_strimwidth($r['MenuSample'], 0, 80, '…')) ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</body>

</html>