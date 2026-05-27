<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
requireRole('traveller');
$u = currentUser();
$db = getDB();


//Cancel bookings
$cancelMsg = $cancelErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $cancelErr = 'Invalid request.';
    } else {
        $hid = filter_input(INPUT_POST, 'holiday_id', FILTER_VALIDATE_INT);
        if ($hid) {
           $chk = $db->prepare("
    SELECT h.HolidayID FROM holidays h
    JOIN clients cl ON cl.ClientID = h.ClientID
    JOIN travellers t ON t.TravID = cl.TravID
    WHERE h.HolidayID = ? AND t.TravID = ? AND h.`From` >= CURDATE()
");
$chk->execute([$hid, $u['sub_id']]);
            if ($chk->fetch()) {
                $db->prepare("DELETE FROM holidays WHERE HolidayID = ?")->execute([$hid]);
                $cancelMsg = 'Booking #' . $hid . ' has been cancelled.';
            } else {
                $cancelErr = 'Unable to cancel that booking.';
            }
        }
    }
}

$cancelResMsg = $cancelResErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_res') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $cancelResErr = 'Invalid request.';
    } else {
        $bid = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        if ($bid) {
            $chk = $db->prepare("
                SELECT BookingID FROM restaurant_bookings
                WHERE BookingID = ? AND TravID = ? AND BookingDate >= CURDATE()
            ");
            $chk->execute([$bid, $u['sub_id']]);
            if ($chk->fetch()) {
                $db->prepare("DELETE FROM restaurant_bookings WHERE BookingID = ?")->execute([$bid]);
                $cancelResMsg = 'Reservation #' . $bid . ' has been cancelled.';
            } else {
                $cancelResErr = 'Unable to cancel that reservation.';
            }
        }
    }
}

//Updated booking  Query, added filter functionality

$search     = trim($_GET['search'] ?? '');
$filter     = trim($_GET['filter'] ?? 'all');
$sortBy     = trim($_GET['sort']   ?? 'date_asc');
$destFilter = trim($_GET['dest']   ?? '');

$validFilters = ['all', 'upcoming', 'past'];
$validSorts   = ['date_asc', 'date_desc', 'price_asc', 'price_desc'];
if (!in_array($filter, $validFilters)) $filter = 'all';
if (!in_array($sortBy, $validSorts))   $sortBy  = 'date_asc';

$sortMap = [
    'date_asc'   => 'h.`From` ASC',
    'date_desc'  => 'h.`From` DESC',
    'price_asc'  => 'p.Price ASC',
    'price_desc' => 'p.Price DESC',
];
$orderBy = $sortMap[$sortBy];

$sql = "
    SELECT h.HolidayID, h.`From`, h.`To`,
      pi.Name AS PackName, pi.Destination, pi.Class, pi.Duration,
      p.Price, p.PackID,
      ag.Name AS AgencyName, ag.AgentID,
      f.DepDateTime, f.ArrDateTime, f.Class AS FlightClass, f.Type AS FlightType,
      dep.City AS DepCity, arr.City AS ArrCity,
      pl.Name AS PlaneName,
      cl.ClientID,
      d.Details AS DiscDetails
    FROM holidays h
    JOIN clients   cl  ON h.ClientID   = cl.ClientID
    JOIN packages  p   ON h.PackID     = p.PackID
    JOIN packinfo  pi  ON pi.PackID    = p.PackID
    JOIN agencies  ag  ON p.AgentID    = ag.AgentID
    JOIN flights   f   ON h.FlightID   = f.FlightID
    JOIN airports  dep ON f.DepPortID  = dep.PortID
    JOIN airports  arr ON f.ArrPortID  = arr.PortID
    JOIN airplanes pl  ON f.PlaneID    = pl.PlaneID
    LEFT JOIN discounts d ON d.PackID  = p.PackID
        AND CURDATE() BETWEEN d.`From` AND d.`To`
    WHERE cl.TravID = ?
";
$params = [$u['sub_id']];

if ($filter === 'upcoming') {
    $sql .= " AND h.`To` >= CURDATE()";
} elseif ($filter === 'past') {
    $sql .= " AND h.`To` < CURDATE()";
}
if ($search) {
    $sql .= " AND (pi.Name LIKE ? OR pi.Destination LIKE ? OR ag.Name LIKE ? OR dep.City LIKE ? OR arr.City LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($destFilter) {
    $sql .= " AND pi.Destination LIKE ?";
    $params[] = "%$destFilter%";
}
$sql .= " ORDER BY $orderBy";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$allBookings = $stmt->fetchAll();



// Restaurant bookings
// Restaurant bookings
$resSql = "
    SELECT rb.BookingID, rb.BookingDate, rb.BookingTime, rb.PartySize,
           to2.Name AS ResName, to2.City,
           d.Name AS DestName,
           res.TimeOpen, res.TimeClose
    FROM restaurant_bookings rb
    JOIN restaurants res ON res.ResID = rb.ResID
    JOIN tourism_offerings to2 ON to2.TOID = res.TOID
    JOIN destinations d ON d.DestID = to2.DestID
    WHERE rb.TravID = ?
";
$resParams = [$u['sub_id']];

if ($filter === 'upcoming') {
    $resSql .= " AND rb.BookingDate >= CURDATE()";
} elseif ($filter === 'past') {
    $resSql .= " AND rb.BookingDate < CURDATE()";
}
if ($search) {
    $resSql .= " AND (to2.Name LIKE ? OR to2.City LIKE ? OR d.Name LIKE ?)";
    $like = "%$search%";
    $resParams = array_merge($resParams, [$like, $like, $like]);
}

$resSql .= " ORDER BY rb.BookingDate ASC";

$resStmt = $db->prepare($resSql);
$resStmt->execute($resParams);
$resBookings = $resStmt->fetchAll();


// Destination dropdown options — always unfiltered
$dests = $db->prepare("
    SELECT DISTINCT pi.Destination
    FROM holidays h
    JOIN clients cl ON cl.ClientID = h.ClientID
    JOIN packages p ON p.PackID = h.PackID
    JOIN packinfo pi ON pi.PackID = p.PackID
    WHERE cl.TravID = ?
    ORDER BY pi.Destination
");
$dests->execute([$u['sub_id']]);
$destOptions = $dests->fetchAll(PDO::FETCH_COLUMN);

$csrf = csrfToken();
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
  <?php include __DIR__ . '/../nav_traveller.php'; ?>
  <div class="container page-wrap">
    <div class="sidebar-layout">
      <?php include __DIR__ . '/../sidebar_traveller.php'; ?>
      <div>
        <div class="page-header">
          <h1>My Bookings</h1>
          <p><?= count($allBookings) ?> booking<?= count($allBookings) !== 1 ? 's' : '' ?><?= ($search || $destFilter || $filter !== 'all') ? ' found' : ' total' ?></p>
        </div>

  
              <?php if ($cancelMsg): ?>
  <div class="alert alert-success"><?= htmlspecialchars($cancelMsg) ?></div>
<?php endif; ?>
<?php if ($cancelErr): ?>
  <div class="alert alert-error"><?= htmlspecialchars($cancelErr) ?></div>
<?php endif; ?>


<?php if ($cancelResMsg): ?>
  <div class="alert alert-success"><?= htmlspecialchars($cancelResMsg) ?></div>
<?php endif; ?>
<?php if ($cancelResErr): ?>
  <div class="alert alert-error"><?= htmlspecialchars($cancelResErr) ?></div>
<?php endif; ?>

<form method="GET" class="filter-bar">
  <div class="form-group">
    <label>Search</label>
    <input class="form-control" name="search"
      value="<?= htmlspecialchars($search) ?>"
      placeholder="Package, destination, agency…">
  </div>
  <div class="form-group">
    <label>Destination</label>
    <select class="form-control" name="dest">
      <option value="">All destinations</option>
      <?php foreach ($destOptions as $d): ?>
        <option value="<?= htmlspecialchars($d) ?>" <?= $destFilter === $d ? 'selected' : '' ?>>
          <?= htmlspecialchars($d) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label>Show</label>
    <select class="form-control" name="filter">
      <option value="all"      <?= $filter === 'all'      ? 'selected' : '' ?>>All bookings</option>
      <option value="upcoming" <?= $filter === 'upcoming' ? 'selected' : '' ?>>Upcoming only</option>
      <option value="past"     <?= $filter === 'past'     ? 'selected' : '' ?>>Past only</option>
    </select>
  </div>
  <div class="form-group">
    <label>Sort by</label>
    <select class="form-control" name="sort">
      <option value="date_asc"   <?= $sortBy === 'date_asc'   ? 'selected' : '' ?>>Date: Soonest first</option>
      <option value="date_desc"  <?= $sortBy === 'date_desc'  ? 'selected' : '' ?>>Date: Latest first</option>
      <option value="price_asc"  <?= $sortBy === 'price_asc'  ? 'selected' : '' ?>>Price: Low → High</option>
      <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price: High → Low</option>
    </select>
  </div>
  <div style="align-self:flex-end; display:flex; gap:6px">
    <button type="submit" class="btn btn-primary">Search</button>
    <a href="bookings.php" class="btn btn-outline">Reset</a>
</div>
</form>


        <?php if ($allBookings): ?>
          <?php foreach ($allBookings as $b): 
            $isPast   = $b['To'] < date('Y-m-d');
            $daysAway = (int) round((strtotime($b['From']) - time()) / 86400);?>

            <div class="card" style="margin-bottom:16px">
              <div class="card-body">
                <div class="flex-between" style="margin-bottom:12px">
                  <div>
                    <div style="font-family:'Fraunces',serif; font-size:18px"><?= htmlspecialchars($b['PackName']) ?></div>
                    <div class="text-sm text-muted">📍 <?= htmlspecialchars($b['Destination']) ?> &nbsp;·&nbsp; 🏢
                      <?= htmlspecialchars($b['AgencyName']) ?></div>
                  </div>

                  <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end">
                       <span class="pack-badge badge-<?= strtolower($b['Class']) ?>"><?= $b['Class'] ?></span>
                   <?php if ($isPast): ?>
                         <span style="font-size:12px; font-weight:600; background:#f3f4f6; color:var(--muted); border:1px solid var(--border); border-radius:20px; padding:3px 10px;">✓ Completed</span>
                    <?php elseif ($daysAway <= 7): ?>
                    <span style="font-size:12px; font-weight:600; background:#fef2f2; color:var(--red); border:1px solid #fecaca; border-radius:20px; padding:3px 10px;">
                       🗓 <?= $daysAway <= 0 ? 'Today!' : ($daysAway === 1 ? 'Tomorrow!' : $daysAway . ' days away') ?>
                         </span>
                  <?php elseif ($daysAway <= 30): ?>
                       <span style="font-size:12px; font-weight:600; background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; border-radius:20px; padding:3px 10px;">🗓 <?= $daysAway ?> days away</span>
                  <?php else: ?>
                    <span style="font-size:12px; font-weight:600; background:#f0fdf4; color:var(--green); border:1px solid #bbf7d0; border-radius:20px; padding:3px 10px;">🗓 <?= $daysAway ?> days away</span>
                      <?php endif; ?>
                       </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; font-size:14px">
                  <div>
                    <div class="text-muted text-sm">Travel Dates</div>
                    <div><strong><?= date('d M Y', strtotime($b['From'])) ?> – <?= date('d M Y', strtotime($b['To'])) ?></strong></div>
                  </div>
                  <div>
                    <div class="text-muted text-sm">Flight</div>
                    <div><?= htmlspecialchars($b['DepCity']) ?> → <?= htmlspecialchars($b['ArrCity']) ?></div>
                    <div class="text-sm text-muted"><?= date('d M, H:i', strtotime($b['DepDateTime'])) ?> ·
                      <?= $b['FlightClass'] ?> · <?= htmlspecialchars($b['PlaneName']) ?></div>
                  </div>
                  <div>
                    <div class="text-muted text-sm">Price</div>
                    <div style="font-size:20px; font-weight:600; color:var(--teal)">R<?= number_format($b['Price'], 0) ?>
                    </div>
                    <?php if ($b['DiscDetails']): ?>
                    <div class="text-sm" style="color:var(--green)">🏷️ <?= htmlspecialchars($b['DiscDetails']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
                <div style="margin-top:12px; border-top:1px solid var(--border); padding-top:12px; display:flex; gap:10px; flex-wrap:wrap">
                    <a href="/COS221-PA5/packages_detail.php?id=<?= $b['PackID'] ?>" class="btn btn-outline btn-sm">View Package</a>
              <?php if ($isPast): ?>
                  <a href="past_bookings.php" class="btn btn-outline btn-sm">⭐ Leave a Review</a>
                <?php else: ?>
                <a href="reviews.php" class="btn btn-outline btn-sm">⭐ Write Review</a>
                <button class="btn btn-danger btn-sm"
                 onclick="openCancel(<?= $b['HolidayID'] ?>, '<?= htmlspecialchars(addslashes($b['PackName'])) ?>')">
                     🗑 Cancel
                     </button>
                 <?php endif; ?>
                  </div>

              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="card card-body text-muted" style="text-align:center; padding:48px">
           <?php if ($search || $destFilter || $filter !== 'all'): ?>
                  No bookings match your search. <a href="bookings.php">Clear filters</a>
                    <?php else: ?>
                 No bookings yet. <a href="packages.php">Browse packages</a> to plan your next trip!
          <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if ($resBookings): ?>
  <h2 style="font-family:'Fraunces',serif; margin:32px 0 12px">🍽️ Restaurant Reservations</h2>
  <?php foreach ($resBookings as $rb): 
    $isPast = $rb['BookingDate'] < date('Y-m-d');
    $daysAway = (int) round((strtotime($rb['BookingDate']) - time()) / 86400);
  ?>
    <div class="card" style="margin-bottom:16px">
      <div class="card-body">
        <div class="flex-between" style="margin-bottom:12px">
          <div>
            <div style="font-family:'Fraunces',serif; font-size:18px"><?= htmlspecialchars($rb['ResName']) ?></div>
            <div class="text-sm text-muted">📍 <?= htmlspecialchars($rb['City']) ?>, <?= htmlspecialchars($rb['DestName']) ?></div>
          </div>
          <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end">
            <?php if ($isPast): ?>
              <span style="font-size:12px; font-weight:600; background:#f3f4f6; color:var(--muted); border:1px solid var(--border); border-radius:20px; padding:3px 10px;">✓ Completed</span>
            <?php elseif ($daysAway <= 7): ?>
              <span style="font-size:12px; font-weight:600; background:#fef2f2; color:var(--red); border:1px solid #fecaca; border-radius:20px; padding:3px 10px;">
                🗓 <?= $daysAway <= 0 ? 'Today!' : ($daysAway === 1 ? 'Tomorrow!' : $daysAway . ' days away') ?>
              </span>
            <?php elseif ($daysAway <= 30): ?>
              <span style="font-size:12px; font-weight:600; background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; border-radius:20px; padding:3px 10px;">🗓 <?= $daysAway ?> days away</span>
            <?php else: ?>
              <span style="font-size:12px; font-weight:600; background:#f0fdf4; color:var(--green); border:1px solid #bbf7d0; border-radius:20px; padding:3px 10px;">🗓 <?= $daysAway ?> days away</span>
            <?php endif; ?>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; font-size:14px">
          <div>
            <div class="text-muted text-sm">Date</div>
            <div><strong><?= date('d M Y', strtotime($rb['BookingDate'])) ?></strong></div>
          </div>
          <div>
            <div class="text-muted text-sm">Time</div>
            <div><?= substr($rb['BookingTime'], 0, 5) ?></div>
            <div class="text-sm text-muted">Open: <?= substr($rb['TimeOpen'], 0, 5) ?> – <?= substr($rb['TimeClose'], 0, 5) ?></div>
          </div>
          <div>
            <div class="text-muted text-sm">Party Size</div>
            <div style="font-size:20px; font-weight:600; color:var(--teal)">👥 <?= $rb['PartySize'] ?></div>
          </div>
        </div>

        <?php if (!$isPast): ?>
          <div style="margin-top:12px; border-top:1px solid var(--border); padding-top:12px; display:flex; gap:10px; flex-wrap:wrap">
            <button class="btn btn-danger btn-sm"
              onclick="openCancelRes(<?= $rb['BookingID'] ?>, '<?= htmlspecialchars(addslashes($rb['ResName'])) ?>')">
              🗑 Cancel
            </button>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
      </div>
    </div>
  </div>


  <div class="modal-overlay" id="cancelModal">
  <div class="modal-box">
    <h3>Cancel Booking?</h3>
    <p id="cancelModalText" class="text-sm text-muted" style="margin-bottom:8px"></p>
    <div class="alert alert-error" style="font-size:13px">⚠️ This cannot be undone.</div>
    <form method="POST">
      <input type="hidden" name="action"     value="cancel">
      <input type="hidden" name="csrf"       value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="holiday_id" id="cancelHolidayId">
      <div class="modal-actions">
        <button type="submit" class="btn btn-danger">Yes, cancel</button>
        <button type="button" class="btn btn-outline" onclick="closeCancel()">Keep booking</button>
      </div>
    </form>
  </div>
</div>



<div class="modal-overlay" id="cancelResModal">
  <div class="modal-box">
    <h3>Cancel Reservation?</h3>
    <p id="cancelResModalText" class="text-sm text-muted" style="margin-bottom:8px"></p>
    <div class="alert alert-error" style="font-size:13px">⚠️ This cannot be undone.</div>
    <form method="POST">
      <input type="hidden" name="action"     value="cancel_res">
      <input type="hidden" name="csrf"       value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="booking_id" id="cancelResBookingId">
      <div class="modal-actions">
        <button type="submit" class="btn btn-danger">Yes, cancel</button>
        <button type="button" class="btn btn-outline" onclick="closeCancelRes()">Keep reservation</button>
      </div>
    </form>
  </div>
</div>
<script>
function openCancel(id, name) {
  document.getElementById('cancelHolidayId').value = id;
  document.getElementById('cancelModalText').textContent =
    'You are about to cancel "' + name + '" (Booking #' + id + ').';
  document.getElementById('cancelModal').classList.add('open');
}
function closeCancel() {
  document.getElementById('cancelModal').classList.remove('open');
}
document.getElementById('cancelModal').addEventListener('click', function(e) {
  if (e.target === this) closeCancel();
});


function openCancelRes(id, name) {
  document.getElementById('cancelResBookingId').value = id;
  document.getElementById('cancelResModalText').textContent =
    'You are about to cancel your reservation at "' + name + '" (Booking #' + id + ').';
  document.getElementById('cancelResModal').classList.add('open');
}
function closeCancelRes() {
  document.getElementById('cancelResModal').classList.remove('open');
}
document.getElementById('cancelResModal').addEventListener('click', function(e) {
  if (e.target === this) closeCancelRes();
});
</script>



</body>

</html>