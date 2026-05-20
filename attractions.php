<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
requireRole('traveller');
$db = getDB();
$search = trim($_GET['search'] ?? '');
$sql = "
    SELECT to2.TOID, to2.Name, to2.City, a.Price, a.TimeOpen, a.TimeClose,
           d.Name AS DestName, ROUND(AVG(rev.Rating),1) AS AvgRating
    FROM attractions a
    JOIN tourism_offerings to2 ON to2.TOID = a.TOID
    JOIN destinations d ON d.DestID = to2.DestID
    LEFT JOIN reviews rev ON rev.TOID = to2.TOID
    WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (to2.Name LIKE ? OR to2.City LIKE ?)"; $params = ["%$search%","%$search%"]; }
$sql .= " GROUP BY a.AttID ORDER BY to2.Name";
$stmt = $db->prepare($sql); $stmt->execute($params);
$attractions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attractions – Tripistry</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../traveller/nav_traveller.php'; ?>
<div class="container page-wrap">
  <div class="sidebar-layout">
    <?php include '../traveller/sidebar_traveller.php'; ?>
    <div>
      <div class="page-header"><h1>🎡 Tourist Attractions</h1><p><?= count($attractions) ?> attractions</p></div>
      <form method="GET" class="filter-bar">
        <div class="form-group"><label>Search</label><input class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Attraction or city…"></div>
        <div class="form-group" style="align-self:flex-end"><button class="btn btn-primary">Search</button></div>
      </form>
      <div class="package-grid">
        <?php foreach ($attractions as $a): ?>
        <div class="pack-card">
          <div class="pack-card-img" style="font-size:48px">🎡</div>
          <div class="pack-card-body">
            <div class="pack-title"><?= htmlspecialchars($a['Name']) ?></div>
            <div class="pack-dest">📍 <?= htmlspecialchars($a['City']) ?>, <?= htmlspecialchars($a['DestName']) ?></div>
            <?php if ($a['AvgRating']): ?>
            <div class="text-sm" style="color:var(--gold); margin-bottom:6px"><?= str_repeat('★',round($a['AvgRating'])) . str_repeat('☆',5-round($a['AvgRating'])) ?> <?= $a['AvgRating'] ?></div>
            <?php endif; ?>
            <div class="text-sm text-muted">🕐 <?= substr($a['TimeOpen'],0,5) ?> – <?= substr($a['TimeClose'],0,5) ?></div>
          </div>
          <div class="pack-footer">
            <div class="pack-price"><?= $a['Price'] > 0 ? 'R'.number_format($a['Price'],0) : '<span class="text-muted" style="font-size:14px">Free entry</span>' ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>