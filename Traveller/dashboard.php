<?php

require_once __DIR__ . '/../db.php';    
require_once __DIR__ . '/../auth.php';  
requireRole('traveller');
$u  = currentUser();
$db = getDB();
 
// Stats
$bookings = $db->prepare("SELECT COUNT(*) FROM holidays h JOIN clients c ON h.ClientID=c.ClientID WHERE h.TravID=?");
$bookings->execute([$u['sub_id']]);
$bookingCount = $bookings->fetchColumn();
 
$reviews = $db->prepare("SELECT COUNT(*) FROM reviews WHERE TravID=?");
$reviews->execute([$u['sub_id']]);
$reviewCount = $reviews->fetchColumn();
 
// Recent bookings — backtick From/To because they are reserved words
$recent = $db->prepare("
    SELECT h.HolidayID, h.`From`, h.`To`,
           pi.Name AS PackName, pi.Destination, p.Price, ag.Name AS AgencyName
    FROM holidays h
    JOIN clients c   ON h.ClientID = c.ClientID
    JOIN packages p  ON h.PackID   = p.PackID
    JOIN packinfo pi ON pi.PackID  = p.PackID
    JOIN agencies ag ON p.AgentID  = ag.AgentID
    WHERE h.TravID = ?
    ORDER BY h.HolidayID DESC LIMIT 5
");
$recent->execute([$u['sub_id']]);
$recentBookings = $recent->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – Tripistry</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include __DIR__ . '/../nav_traveller.php'; ?>

<div class="container page-wrap">
  <div class="sidebar-layout">
    <?php include __DIR__ . '/../sidebar_traveller.php'; ?>
 
    <div>
      <div class="page-header">
        <h1>Welcome back, <?= htmlspecialchars($u['username']) ?> 👋</h1>
        <p>Here's an overview of your travel activity.</p>
      </div>
 
      <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:16px; margin-bottom:28px;">
        <div class="card card-body" style="text-align:center">
          <div style="font-size:32px; font-weight:700; color:var(--teal)"><?= $bookingCount ?></div>
          <div class="text-muted text-sm">Bookings</div>
        </div>
        <div class="card card-body" style="text-align:center">
          <div style="font-size:32px; font-weight:700; color:var(--teal)"><?= $reviewCount ?></div>
          <div class="text-muted text-sm">Reviews Written</div>
        </div>
      </div>
 
      <div class="card">
        <div class="card-body flex-between" style="padding-bottom:0; margin-bottom:0">
          <h2 style="font-family:'Fraunces',serif; font-size:20px">Recent Bookings</h2>
          <a href="bookings.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <?php if ($recentBookings): ?>
        <div style="overflow-x:auto">
          <table class="table">
            <thead>
              <tr><th>Package</th><th>Destination</th><th>Agency</th><th>Dates</th><th>Price</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($recentBookings as $b): ?>
            <tr>
              <td><strong><?= htmlspecialchars($b['PackName']) ?></strong></td>
              <td><?= htmlspecialchars($b['Destination']) ?></td>
              <td><?= htmlspecialchars($b['AgencyName']) ?></td>
              <td class="text-sm text-muted"><?= $b['From'] ?> – <?= $b['To'] ?></td>
              <td><strong style="color:var(--teal)">R<?= number_format($b['Price'],0) ?></strong></td>
              <td><a href="/COS221-PA5/packages_detail.php?id=<?= $b['HolidayID'] ?>" class="btn btn-sm btn-outline">View</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="card-body text-muted">No bookings yet. <a href="packages.php">Browse packages</a> to get started!</div>
        <?php endif; ?>
      </div>
 
    </div>
  </div>
</div>
</body>
</html>