<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
requireRole('traveller');
$db = getDB();
 
$flights = $db->query("
    SELECT f.FlightID, f.DepDateTime, f.ArrDateTime, f.Class, f.Type,
           dep.Name AS DepAirport, dep.City AS DepCity,
           arr.Name AS ArrAirport, arr.City AS ArrCity,
           pl.Name AS PlaneName
    FROM flights f
    JOIN airports dep ON dep.PortID = f.DepPortID
    JOIN airports arr ON arr.PortID = f.ArrPortID
    JOIN airplanes pl ON pl.PlaneID = f.PlaneID
    ORDER BY f.DepDateTime
")->fetchAll();
 
$search = trim($_GET['search'] ?? '');
$class  = trim($_GET['class']  ?? '');
$type   = trim($_GET['type']   ?? '');
 
$sql = "SELECT f.FlightID, f.DepDateTime, f.ArrDateTime, f.Class, f.Type,
               dep.Name AS DepAirport, dep.City AS DepCity,
               arr.Name AS ArrAirport, arr.City AS ArrCity,
               pl.Name AS PlaneName
        FROM flights f
        JOIN airports dep ON dep.PortID = f.DepPortID
        JOIN airports arr ON arr.PortID = f.ArrPortID
        JOIN airplanes pl ON pl.PlaneID = f.PlaneID
        WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (dep.City LIKE ? OR arr.City LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($class)  { $sql .= " AND f.Class = ?"; $params[] = $class; }
if ($type)   { $sql .= " AND f.Type = ?";  $params[] = $type; }
$sql .= " ORDER BY f.DepDateTime LIMIT 50";
$stmt = $db->prepare($sql); $stmt->execute($params);
$flights = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Flights – Tripistry</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include __DIR__ . '/../nav_traveller.php'; ?>
<div class="container page-wrap">
  <div class="sidebar-layout">
    <?php include __DIR__ . '/../sidebar_traveller.php'; ?>
    <div>
      <div class="page-header"><h1>✈️ Available Flights</h1></div>
      <form method="GET" class="filter-bar">
        <div class="form-group"><label>City (from/to)</label><input class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="e.g. Paris"></div>
        <div class="form-group"><label>Class</label>
          <select class="form-control" name="class">
            <option value="">Any</option><option <?=$class==='Economy'?'selected':''?>>Economy</option><option <?=$class==='Business'?'selected':''?>>Business</option><option <?=$class==='First'?'selected':''?>>First</option>
          </select>
        </div>
        <div class="form-group"><label>Type</label>
          <select class="form-control" name="type">
            <option value="">Any</option><option <?=$type==='Direct'?'selected':''?>>Direct</option><option <?=$type==='Connecting'?'selected':''?>>Connecting</option>
          </select>
        </div>
        <div class="form-group" style="align-self:flex-end"><button class="btn btn-primary">Filter</button></div>
      </form>
 
      <div class="card">
        <div style="overflow-x:auto">
          <table class="table">
            <thead><tr><th>Route</th><th>Departure</th><th>Arrival</th><th>Class</th><th>Type</th><th>Aircraft</th></tr></thead>
            <tbody>
            <?php foreach ($flights as $f): ?>
            <tr>
              <td><strong><?= htmlspecialchars($f['DepCity']) ?></strong> → <strong><?= htmlspecialchars($f['ArrCity']) ?></strong>
                  <div class="text-sm text-muted"><?= htmlspecialchars($f['DepAirport']) ?></div></td>
              <td><?= date('d M Y', strtotime($f['DepDateTime'])) ?><div class="text-sm text-muted"><?= date('H:i', strtotime($f['DepDateTime'])) ?></div></td>
              <td><?= date('d M Y', strtotime($f['ArrDateTime'])) ?><div class="text-sm text-muted"><?= date('H:i', strtotime($f['ArrDateTime'])) ?></div></td>
              <td><span class="pack-badge <?= $f['Class']==='First'?'badge-luxury':($f['Class']==='Business'?'badge-premium':'badge-standard') ?>"><?= $f['Class'] ?></span></td>
              <td><?= $f['Type'] ?></td>
              <td class="text-sm"><?= htmlspecialchars($f['PlaneName']) ?></td>
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