<?php
require_once __DIR__ . '../db.php';
require_once __DIR__ . '../auth.php';
requireRole('agency');
$u = currentUser();
$db = getDB();
$agentId = $u['sub_id'];
 
// Agency name
$agInfo = $db->prepare("SELECT Name FROM agencies WHERE AgentID=?");
$agInfo->execute([$agentId]); $ag = $agInfo->fetch();
 
// Stats
$packCount = $db->prepare("SELECT COUNT(*) FROM packages WHERE AgentID=?"); $packCount->execute([$agentId]);
$clientCount = $db->prepare("SELECT COUNT(*) FROM clients WHERE AgentID=?"); $clientCount->execute([$agentId]);
$bookCount = $db->prepare("SELECT COUNT(*) FROM holidays h JOIN clients c ON h.ClientID=c.ClientID WHERE c.AgentID=?"); $bookCount->execute([$agentId]);
$avgRating = $db->prepare("SELECT ROUND(AVG(Rating),1) FROM agency_experiences WHERE AgentID=?"); $avgRating->execute([$agentId]);
 
// Recent bookings
$recent = $db->prepare("
    SELECT h.HolidayID, h.`From`, h.`To`, pi.Name AS PackName, u.Username AS TravName
    FROM holidays h
    JOIN clients c ON h.ClientID=c.ClientID
    JOIN packages p ON h.PackID=p.PackID
    JOIN packinfo pi ON pi.PackID=p.PackID
    JOIN travellers t ON c.TravID=t.TravID
    JOIN users u ON t.UserID=u.UserID
    WHERE c.AgentID=?
    ORDER BY h.HolidayID DESC LIMIT 6
");
$recent->execute([$agentId]); $recentBookings = $recent->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agency Dashboard – Tripistry</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav_agency.php'; ?>
<div class="container page-wrap">
  <div class="sidebar-layout">
    <?php include '../includes/sidebar_agency.php'; ?>
    <div>
      <div class="page-header">
        <h1><?= htmlspecialchars($ag['Name'] ?? $u['username']) ?></h1>
        <p>Agency Dashboard</p>
      </div>
 
      <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:16px; margin-bottom:28px">
        <?php foreach ([
          ['label'=>'Packages',  'val'=>$packCount->fetchColumn(),  'icon'=>'📦'],
          ['label'=>'Clients',   'val'=>$clientCount->fetchColumn(), 'icon'=>'🤝'],
          ['label'=>'Bookings',  'val'=>$bookCount->fetchColumn(),   'icon'=>'📋'],
          ['label'=>'Avg Rating','val'=>($avgRating->fetchColumn() ?: '—').''.($avgRating->fetchColumn() ? '/5':''), 'icon'=>'⭐'],
        ] as $s): ?>
        <div class="card card-body" style="text-align:center">
          <div style="font-size:28px; margin-bottom:4px"><?= $s['icon'] ?></div>
          <div style="font-size:28px; font-weight:700; color:var(--teal)"><?= $s['val'] ?></div>
          <div class="text-muted text-sm"><?= $s['label'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
 
      <div class="flex-between mb-3">
        <h2 style="font-family:'Fraunces',serif; font-size:20px">Recent Bookings</h2>
        <a href="package_new.php" class="btn btn-primary btn-sm">➕ New Package</a>
      </div>
      <div class="card">
        <?php if ($recentBookings): ?>
        <div style="overflow-x:auto">
          <table class="table">
            <thead><tr><th>Traveller</th><th>Package</th><th>Dates</th></tr></thead>
            <tbody>
            <?php foreach ($recentBookings as $b): ?>
            <tr>
              <td><?= htmlspecialchars($b['TravName']) ?></td>
              <td><?= htmlspecialchars($b['PackName']) ?></td>
              <td class="text-sm text-muted"><?= $b['From'] ?> – <?= $b['To'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="card-body text-muted">No bookings yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>