<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
requireRole('traveller');
$u  = currentUser();
$db = getDB();

// Handle booking POST
$bookMsg = $bookErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $bookErr = 'Invalid request.';
    } else {
        $resId     = filter_input(INPUT_POST, 'res_id',      FILTER_VALIDATE_INT);
        $date      = $_POST['booking_date'] ?? '';
        $time      = $_POST['booking_time'] ?? '';
        $partySize = filter_input(INPUT_POST, 'party_size', FILTER_VALIDATE_INT);

        if (!$resId || !$date || !$time || !$partySize || $partySize < 1) {
            $bookErr = 'Please fill in all fields.';
        } elseif ($date < date('Y-m-d')) {
            $bookErr = 'Booking date must be today or in the future.';
        } else {
            $db->prepare("
                INSERT INTO restaurant_bookings (TravID, ResID, BookingDate, BookingTime, PartySize)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$u['sub_id'], $resId, $date, $time, $partySize]);
            $bookMsg = 'Table booked successfully!';
        }
    }
}

$search = trim($_GET['search']      ?? '');
$city   = trim($_GET['city']        ?? '');
$sort   = trim($_GET['sort']        ?? 'name_asc');
$minRat = is_numeric($_GET['min_rating'] ?? '') ? (float)$_GET['min_rating'] : null;

$validSorts = ['name_asc', 'rating_desc', 'open_asc'];
if (!in_array($sort, $validSorts)) $sort = 'name_asc';

$sortMap = [
    'name_asc'    => 'to2.Name ASC',
    'rating_desc' => 'AvgRating DESC',
    'open_asc'    => 'res.TimeOpen ASC',
];
$orderBy = $sortMap[$sort];

$sql = "
    SELECT to2.TOID, to2.Name, to2.City, res.TimeOpen, res.TimeClose, res.ResID,
           d.Name AS DestName, ROUND(AVG(rev.Rating),1) AS AvgRating,
           COUNT(DISTINCT rev.RevID) AS ReviewCount,
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
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($city) {
    $sql .= " AND to2.City LIKE ?";
    $params[] = "%$city%";
}

$sql .= " GROUP BY res.ResID";

if ($minRat !== null) {
    $sql .= " HAVING AvgRating >= ?";
    $params[] = $minRat;
}

$sql .= " ORDER BY $orderBy";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$cities = $db->query("
    SELECT DISTINCT to2.City
    FROM restaurants res
    JOIN tourism_offerings to2 ON to2.TOID = res.TOID
    ORDER BY to2.City
")->fetchAll(PDO::FETCH_COLUMN);

$restaurants = $stmt->fetchAll();
$csrf = csrfToken();
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

        <?php if ($bookMsg): ?>
          <div class="alert alert-success"><?= htmlspecialchars($bookMsg) ?></div>
        <?php endif; ?>
        <?php if ($bookErr): ?>
          <div class="alert alert-error"><?= htmlspecialchars($bookErr) ?></div>
        <?php endif; ?>

        <form method="GET" class="filter-bar">
          <div class="form-group">
            <label>City</label>
            <select class="form-control" name="city">
              <option value="">All cities</option>
              <?php foreach ($cities as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $city === $c ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Min Rating</label>
            <select class="form-control" name="min_rating">
              <option value="">Any rating</option>
              <option value="3" <?= $minRat == 3 ? 'selected' : '' ?>>★★★ 3+</option>
              <option value="4" <?= $minRat == 4 ? 'selected' : '' ?>>★★★★ 4+</option>
              <option value="5" <?= $minRat == 5 ? 'selected' : '' ?>>★★★★★ 5 only</option>
            </select>
          </div>
          <div class="form-group">
            <label>Sort by</label>
            <select class="form-control" name="sort">
              <option value="name_asc"    <?= $sort === 'name_asc'    ? 'selected' : '' ?>>Name: A–Z</option>
              <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Top Rated</option>
              <option value="open_asc"    <?= $sort === 'open_asc'    ? 'selected' : '' ?>>Opens Earliest</option>
            </select>
          </div>
          <div class="form-group">
            <label>Search</label>
            <input class="form-control" name="search"
              value="<?= htmlspecialchars($search) ?>" placeholder="Restaurant or city…">
          </div>
          <div style="align-self:flex-end; display:flex; gap:6px">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="restaurants.php" class="btn btn-outline">Reset</a>
          </div>
        </form>

        <div class="package-grid">
          <?php foreach ($restaurants as $r): ?>
            <div class="pack-card">
              <div class="pack-card-img" style="font-size:48px">🍽️</div>
              <div class="pack-card-body">
                <div class="pack-title"><?= htmlspecialchars($r['Name']) ?></div>
                <div class="pack-dest">📍 <?= htmlspecialchars($r['City']) ?>, <?= htmlspecialchars($r['DestName']) ?></div>
                <?php if ($r['AvgRating']): ?>
                  <div class="text-sm" style="color:var(--gold); margin-bottom:6px">
                    <?= str_repeat('★', round($r['AvgRating'])) . str_repeat('☆', 5 - round($r['AvgRating'])) ?>
                    <?= $r['AvgRating'] ?>
                    <span class="text-muted">(<?= $r['ReviewCount'] ?>)</span>
                  </div>
                <?php endif; ?>
                <div class="text-sm text-muted">🕐 <?= substr($r['TimeOpen'], 0, 5) ?> – <?= substr($r['TimeClose'], 0, 5) ?></div>
                <?php if ($r['MenuSample']): ?>
                  <div class="text-sm text-muted" style="margin-top:6px">Menu:
                    <?= htmlspecialchars(mb_strimwidth($r['MenuSample'], 0, 80, '…')) ?></div>
                <?php endif; ?>

                <!-- Buttons -->
                <div style="margin-top:12px; border-top:1px solid var(--border); padding-top:10px; display:flex; gap:8px; flex-wrap:wrap">
                  <button class="btn btn-primary btn-sm"
                    onclick="openBook(<?= $r['ResID'] ?>, '<?= htmlspecialchars(addslashes($r['Name'])) ?>', '<?= substr($r['TimeOpen'],0,5) ?>', '<?= substr($r['TimeClose'],0,5) ?>')">
                    🍽️ Book Table
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Booking Modal -->
  <div class="modal-overlay" id="bookModal">
    <div class="modal-box">
      <h3>Book a Table</h3>
      <p id="bookModalText" class="text-sm text-muted" style="margin-bottom:16px"></p>
      <form method="POST">
        <input type="hidden" name="action" value="book">
        <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="res_id" id="bookResId">

        <div class="form-group" style="margin-bottom:12px">
          <label>Date</label>
          <input type="date" class="form-control" name="booking_date"
            min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group" style="margin-bottom:12px">
          <label>Time</label>
          <input type="time" class="form-control" name="booking_time"
            id="bookTime" required>
        </div>
        <div class="form-group" style="margin-bottom:16px">
          <label>Party Size</label>
          <input type="number" class="form-control" name="party_size"
            min="1" max="20" value="2" required>
        </div>
        <div class="modal-actions">
          <button type="submit" class="btn btn-primary">Confirm Booking</button>
          <button type="button" class="btn btn-outline" onclick="closeBook()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openBook(id, name, open, close) {
      document.getElementById('bookResId').value = id;
      document.getElementById('bookModalText').textContent = name;
      const timeInput = document.getElementById('bookTime');
      timeInput.min = open;
      timeInput.max = close;
      timeInput.value = open;
      document.getElementById('bookModal').classList.add('open');
    }
    function closeBook() {
      document.getElementById('bookModal').classList.remove('open');
    }
    document.getElementById('bookModal').addEventListener('click', function(e) {
      if (e.target === this) closeBook();
    });
  </script>
</body>
</html>