<?php
require_once __DIR__ . '/../db.php';
// require_once __DIR__ . '/../auth.php';
// requireRole('traveller');
// $u = currentUser();

$u = [
    'user_id'  => 1,
    'username' => 'testuser',
    'role'     => 'traveller',
    'sub_id'   => 1
];
$db = getDB();

// Upcoming trips
$upcomingStmt = $db->prepare("
    SELECT h.HolidayID, h.`From`, h.`To`,
           pi.Name AS PackName, pi.Destination, pi.Class,
           p.Price, p.PackID, ag.Name AS AgencyName,
           DATEDIFF(h.`From`, CURDATE()) AS DaysUntil
    FROM holidays h
    JOIN clients cl  ON h.ClientID = cl.ClientID
    JOIN packages p  ON h.PackID   = p.PackID
    JOIN packinfo pi ON pi.PackID  = p.PackID
    JOIN agencies ag ON p.AgentID  = ag.AgentID
    WHERE cl.TravID = ? AND h.`From` > CURDATE()
    ORDER BY h.`From` ASC
");
$upcomingStmt->execute([$u['sub_id']]);
$upcomingTrips = $upcomingStmt->fetchAll();
$nextTrip = $upcomingTrips[0] ?? null;

// Currently travelling
$activeStmt = $db->prepare("
    SELECT pi.Name AS PackName, pi.Destination, pi.Class,
           h.`From`, h.`To`, p.PackID, ag.Name AS AgencyName,
           DATEDIFF(h.`To`, CURDATE()) AS DaysLeft
    FROM holidays h
    JOIN clients cl  ON h.ClientID = cl.ClientID
    JOIN packages p  ON h.PackID   = p.PackID
    JOIN packinfo pi ON pi.PackID  = p.PackID
    JOIN agencies ag ON p.AgentID  = ag.AgentID
    WHERE cl.TravID = ? AND h.`From` <= CURDATE() AND h.`To` >= CURDATE()
");
$activeStmt->execute([$u['sub_id']]);
$activeTrip = $activeStmt->fetch();

// Past trips (last 3)
$pastStmt = $db->prepare("
    SELECT pi.Name AS PackName, pi.Destination, pi.Class,
           h.`From`, h.`To`, p.PackID
    FROM holidays h
    JOIN clients cl  ON h.ClientID = cl.ClientID
    JOIN packages p  ON h.PackID   = p.PackID
    JOIN packinfo pi ON pi.PackID  = p.PackID
    WHERE cl.TravID = ? AND h.`To` < CURDATE()
    ORDER BY h.`To` DESC LIMIT 3
");
$pastStmt->execute([$u['sub_id']]);
$pastTrips = $pastStmt->fetchAll();

function countdown(int $days): string {
    if ($days === 0) return 'Today! 🎉';
    if ($days === 1) return 'Tomorrow';
    return $days . ' days away';
}

$destIcons = [
    'Paris'=>'🗼','Bali'=>'🌴','Tokyo'=>'🏯','Cape Town'=>'🏔','Dubai'=>'🌆',
    'Maldives'=>'🏝','Rome'=>'🏛','Bangkok'=>'🛺','Santorini'=>'🌅','Serengeti'=>'🦁',
    'Marrakech'=>'🕌','Barcelona'=>'🎨','Singapore'=>'🌃','Zanzibar'=>'⛵',
    'Kyoto'=>'🌸','Cairo'=>'🗿','Mauritius'=>'🐠','Nairobi'=>'🦒',
    'Victoria Falls'=>'💧','New York'=>'🗽','Istanbul'=>'🕍','Lisbon'=>'🏰',
    'Kruger Park'=>'🐘','Phuket'=>'🤿','Amsterdam'=>'🚲','Johannesburg'=>'💎',
    'Durban'=>'🌊','Prague'=>'🏰','Miami'=>'🌴','Sydney'=>'🦘',
];
function destIcon(string $dest, array $icons): string {
    return $icons[trim(explode(',', $dest)[0])] ?? '✈️';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Lobby – Tripistry</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>

<!-- NAV -->
<?php include __DIR__ . '/../nav_traveller.php'; ?>

<div class="container page-wrap">
  <div class="sidebar-layout">

    <!-- SIDEBAR -->
    <?php include __DIR__ . '/../sidebar_traveller.php'; ?>

    <div>
      <div class="page-header">
        <h1>My Lobby</h1>
        <p>Your upcoming and past adventures, all in one place.</p>
      </div>

      <!-- ── CURRENTLY TRAVELLING ── -->
      <?php if ($activeTrip): ?>
      <div class="alert alert-success" style="font-size:15px; margin-bottom:28px">
        ✈️ You're currently on a trip! &nbsp;<strong><?= htmlspecialchars($activeTrip['PackName']) ?></strong>
        → <?= htmlspecialchars($activeTrip['Destination']) ?> &nbsp;·&nbsp;
        <?= $activeTrip['DaysLeft'] ?> day<?= $activeTrip['DaysLeft'] != 1 ? 's' : '' ?> left.
        <a href="../packages_detail.php?id=<?= $activeTrip['PackID'] ?>" class="btn btn-sm btn-outline" style="margin-left:12px">View Package</a>
      </div>
      <?php endif; ?>

      <!-- ── NEXT ADVENTURE HERO ── -->
      <?php if ($nextTrip): ?>
      <div style="
        background: linear-gradient(135deg, var(--teal-d), var(--teal));
        border-radius: var(--radius-lg);
        padding: 36px 40px;
        color: #fff;
        margin-bottom: 32px;
        position: relative;
        overflow: hidden;
      ">
        <div style="position:absolute; right:40px; top:50%; transform:translateY(-50%); font-size:120px; opacity:.12; pointer-events:none">
          <?= destIcon($nextTrip['Destination'], $destIcons) ?>
        </div>

        <div style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:1px; opacity:.7; margin-bottom:8px">
          Next Adventure
        </div>
        <h2 style="font-family:'Fraunces',serif; font-size:30px; margin-bottom:6px">
          <?= htmlspecialchars($nextTrip['PackName']) ?>
        </h2>
        <p style="opacity:.8; margin-bottom:20px">
          📍 <?= htmlspecialchars($nextTrip['Destination']) ?>
          &nbsp;·&nbsp; 🏢 <?= htmlspecialchars($nextTrip['AgencyName']) ?>
        </p>

        <div style="display:flex; gap:32px; align-items:center; flex-wrap:wrap">
          <div>
            <div style="font-size:42px; font-weight:700; line-height:1">
              <?= countdown((int)$nextTrip['DaysUntil']) ?>
            </div>
            <div style="opacity:.7; font-size:13px; margin-top:4px">
              <?= date('d M Y', strtotime($nextTrip['From'])) ?> – <?= date('d M Y', strtotime($nextTrip['To'])) ?>
            </div>
          </div>
          <div style="display:flex; gap:12px; margin-left:auto">
            <span class="pack-badge badge-<?= strtolower($nextTrip['Class']) ?>" style="border:1px solid rgba(255,255,255,.4); background:rgba(255,255,255,.15); color:#fff">
              <?= $nextTrip['Class'] ?>
            </span>
            <a href="../packages_detail.php?id=<?= $nextTrip['PackID'] ?>" class="btn btn-sm" style="background:#fff; color:var(--teal)">
              View Package →
            </a>
          </div>
        </div>
      </div>

      <!-- ── ALL UPCOMING TRIPS ── -->
      <?php if (count($upcomingTrips) > 1): ?>
      <h2 style="font-family:'Fraunces',serif; font-size:20px; margin-bottom:16px">All Upcoming Trips</h2>
      <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:40px">
        <?php foreach (array_slice($upcomingTrips, 1) as $trip): ?>
        <div class="card card-body" style="display:flex; align-items:center; gap:16px">
          <div style="font-size:36px"><?= destIcon($trip['Destination'], $destIcons) ?></div>
          <div style="flex:1">
            <div style="font-weight:600"><?= htmlspecialchars($trip['PackName']) ?></div>
            <div class="text-sm text-muted">
              📍 <?= htmlspecialchars($trip['Destination']) ?>
              &nbsp;·&nbsp; <?= date('d M Y', strtotime($trip['From'])) ?> – <?= date('d M Y', strtotime($trip['To'])) ?>
            </div>
          </div>
          <div style="text-align:right">
            <div style="font-size:18px; font-weight:700; color:var(--teal)"><?= countdown((int)$trip['DaysUntil']) ?></div>
            <span class="pack-badge badge-<?= strtolower($trip['Class']) ?>"><?= $trip['Class'] ?></span>
          </div>
          <a href="../packages_detail.php?id=<?= $trip['PackID'] ?>" class="btn btn-outline btn-sm">View</a>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <!-- ── NO UPCOMING TRIPS ── -->
      <div class="card card-body" style="text-align:center; padding:56px">
        <div style="font-size:56px; margin-bottom:12px">🗺️</div>
        <h2 style="font-family:'Fraunces',serif; margin-bottom:8px">No upcoming trips</h2>
        <p class="text-muted" style="margin-bottom:20px">Time to plan your next adventure!</p>
        <a href="../packages.php" class="btn btn-primary" style="display:inline-flex">Browse Packages →</a>
      </div>
      <?php endif; ?>

      <!-- ── PAST ADVENTURES ── -->
      <?php if ($pastTrips): ?>
      <h2 style="font-family:'Fraunces',serif; font-size:20px; margin-bottom:16px; margin-top:40px">Past Adventures</h2>
      <div class="card">
        <table class="table">
          <thead>
            <tr><th>Package</th><th>Destination</th><th>Dates</th><th>Class</th></tr>
          </thead>
          <tbody>
          <?php foreach ($pastTrips as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['PackName']) ?></td>
            <td><?= destIcon($p['Destination'], $destIcons) ?> <?= htmlspecialchars($p['Destination']) ?></td>
            <td class="text-sm text-muted"><?= date('d M Y', strtotime($p['From'])) ?> – <?= date('d M Y', strtotime($p['To'])) ?></td>
            <td><span class="pack-badge badge-<?= strtolower($p['Class']) ?>"><?= $p['Class'] ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

</body>
</html>
