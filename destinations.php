<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
requireRole('traveller');
$db = getDB();
 
$destinations = $db->query("
    SELECT d.DestID, d.Name, d.City,
           COUNT(DISTINCT p.PackID) AS PackageCount,
           COUNT(DISTINCT to2.TOID) AS OfferingCount,
           MIN(p.Price) AS MinPrice
    FROM destinations d
    LEFT JOIN airports ap ON ap.DestID = d.DestID
    LEFT JOIN tourism_offerings to2 ON to2.DestID = d.DestID
    LEFT JOIN packinfo pi ON pi.Destination LIKE CONCAT('%', d.Name, '%')
    LEFT JOIN packages p ON p.PackID = pi.PackID
    GROUP BY d.DestID
    ORDER BY d.Name
")->fetchAll();
 
$icons = ['Paris'=>'🗼','Bali'=>'🌴','Tokyo'=>'🏯','Cape Town'=>'🏔','Dubai'=>'🌆','Maldives'=>'🏝','Rome'=>'🏛','Bangkok'=>'🛺','Santorini'=>'🌅','Serengeti'=>'🦁','Marrakech'=>'🕌','Barcelona'=>'🎨','Singapore'=>'🌃','Zanzibar'=>'⛵','Kyoto'=>'🌸','Cairo'=>'🗿','Mauritius'=>'🐠','Nairobi'=>'🦒','Victoria Falls'=>'💧','New York'=>'🗽','Istanbul'=>'🕍','Lisbon'=>'🏰','Kruger Park'=>'🐘','Phuket'=>'🤿','Amsterdam'=>'🚲','Johannesburg'=>'💎','Durban'=>'🌊','Prague'=>'🏰','Miami'=>'🌴','Sydney'=>'🦘'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Destinations – Tripistry</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.dest-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:16px; }
.dest-card {
  background:var(--white); border:1px solid var(--border); border-radius:var(--radius-lg);
  padding:24px; text-align:center; cursor:pointer;
  transition:transform .2s, box-shadow .2s; text-decoration:none; color:inherit;
  display:block;
}
.dest-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); }
.dest-icon { font-size:48px; margin-bottom:12px; }
.dest-name { font-family:'Fraunces',serif; font-size:18px; font-weight:500; margin-bottom:4px; }
</style>
</head>
<body>
<?php include '../traveller/nav_traveller.php'; ?>
<div class="container page-wrap">
  <div class="sidebar-layout">
    <?php include '../traveller/sidebar_traveller.php'; ?>
    <div>
      <div class="page-header"><h1>Destinations</h1><p>Explore <?= count($destinations) ?> destinations worldwide</p></div>
      <div class="dest-grid">
        <?php foreach ($destinations as $d): ?>
        <a class="dest-card" href="packages.php?dest=<?= urlencode($d['Name']) ?>">
          <div class="dest-icon"><?= $icons[$d['Name']] ?? '✈️' ?></div>
          <div class="dest-name"><?= htmlspecialchars($d['Name']) ?></div>
          <div class="text-sm text-muted"><?= htmlspecialchars($d['City']) ?></div>
          <div class="text-sm" style="color:var(--teal); margin-top:8px">
            <?= $d['PackageCount'] ?> package<?= $d['PackageCount']!==1?'s':'' ?>
          </div>
          <?php if ($d['MinPrice']): ?>
          <div class="text-sm text-muted">From R<?= number_format($d['MinPrice'],0) ?></div>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>