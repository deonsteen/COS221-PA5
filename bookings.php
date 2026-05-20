<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
requireRole('traveller');
$u = currentUser();
$db = getDB();
 
$bookings = $db->prepare("
    SELECT h.HolidayID, h.`From`, h.`To`,
           pi.Name AS PackName, pi.Destination, pi.Class,
           p.Price, p.PackID,
           ag.Name AS AgencyName,
           f.DepDateTime, f.ArrDateTime, f.Class AS FlightClass,
           dep.City AS DepCity, arr.City AS ArrCity,
           pl.Name AS PlaneName
    FROM holidays h
    JOIN clients   cl  ON h.ClientID   = cl.ClientID
    JOIN packages  p   ON h.PackID     = p.PackID
    JOIN packinfo  pi  ON pi.PackID    = p.PackID
    JOIN agencies  ag  ON p.AgentID    = ag.AgentID
    JOIN flights   f   ON h.FlightID   = f.FlightID
    JOIN airports  dep ON f.DepPortID  = dep.PortID
    JOIN airports  arr ON f.ArrPortID  = arr.PortID
    JOIN airplanes pl  ON f.PlaneID    = pl.PlaneID
    WHERE cl.TravID = ?
    ORDER BY h.HolidayID DESC
");
$bookings->execute([$u['sub_id']]);
$allBookings = $bookings->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings – Tripistry</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav_traveller.php'; ?>
<div class="container page-wrap">
  <div class="sidebar-layout">
    <?php include '../includes/sidebar_traveller.php'; ?>
    <div>
      <div class="page-header">
        <h1>My Bookings</h1>
        <p><?= count($allBookings) ?> booking<?= count($allBookings)!==1?'s':'' ?> total</p>
      </div>
 
      <?php if ($allBookings): ?>
      <?php foreach ($allBookings as $b): ?>
      <div class="card" style="margin-bottom:16px">
        <div class="card-body">
          <div class="flex-between" style="margin-bottom:12px">
            <div>
              <div style="font-family:'Fraunces',serif; font-size:18px"><?= htmlspecialchars($b['PackName']) ?></div>
              <div class="text-sm text-muted">📍 <?= htmlspecialchars($b['Destination']) ?> &nbsp;·&nbsp; 🏢 <?= htmlspecialchars($b['AgencyName']) ?></div>
            </div>
            <span class="pack-badge badge-<?= strtolower($b['Class']) ?>"><?= $b['Class'] ?></span>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; font-size:14px">
            <div>
              <div class="text-muted text-sm">Travel Dates</div>
              <div><strong><?= $b['From'] ?> – <?= $b['To'] ?></strong></div>
            </div>
            <div>
              <div class="text-muted text-sm">Flight</div>
              <div><?= htmlspecialchars($b['DepCity']) ?> → <?= htmlspecialchars($b['ArrCity']) ?></div>
              <div class="text-sm text-muted"><?= date('d M, H:i', strtotime($b['DepDateTime'])) ?> · <?= $b['FlightClass'] ?> · <?= htmlspecialchars($b['PlaneName']) ?></div>
            </div>
            <div>
              <div class="text-muted text-sm">Price</div>
              <div style="font-size:20px; font-weight:600; color:var(--teal)">R<?= number_format($b['Price'],0) ?></div>
            </div>
          </div>
          <div style="margin-top:12px; border-top:1px solid var(--border); padding-top:12px">
            <a href="package_detail.php?id=<?= $b['PackID'] ?>" class="btn btn-outline btn-sm">View Package</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="card card-body text-muted" style="text-align:center; padding:48px">
        No bookings yet. <a href="packages.php">Browse packages</a> to plan your next trip!
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>