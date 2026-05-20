<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
requireRole('traveller');
$u = currentUser();
$db = getDB();
 
$packId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$packId) { header('Location: packages.php'); exit; }
 
// Full package details
$stmt = $db->prepare("
    SELECT p.PackID, p.Price, p.AgentID,
           pi.InfoID, pi.Name, pi.Destination, pi.Duration, pi.Class,
           ag.Name AS AgencyName, ag.AgentID,
           ROUND(AVG(ae.Rating),1) AS AgencyRating,
           COUNT(DISTINCT ae.ExpNum) AS AgencyReviews
    FROM packages p
    JOIN packinfo pi  ON pi.PackID  = p.PackID
    JOIN agencies ag  ON ag.AgentID = p.AgentID
    LEFT JOIN agency_experiences ae ON ae.AgentID = p.AgentID
    WHERE p.PackID = ?
    GROUP BY p.PackID
");
$stmt->execute([$packId]);
$pkg = $stmt->fetch();
if (!$pkg) { header('Location: packages.php'); exit; }
 
// Itinerary
$iti = $db->prepare("SELECT * FROM itinerary WHERE InfoID=? ORDER BY DateTime");
$iti->execute([$pkg['InfoID']]);
$itinerary = $iti->fetchAll();
 
// Discounts
$discs = $db->prepare("SELECT * FROM discounts WHERE PackID=? AND CURDATE() BETWEEN `From` AND `To`");
$discs->execute([$packId]);
$discounts = $discs->fetchAll();
 
// Agency reviews
$revs = $db->prepare("
    SELECT ae.Description, ae.Rating, u.Username
    FROM agency_experiences ae
    JOIN clients cl ON ae.ClientID = cl.ClientID
    JOIN travellers t ON cl.TravID = t.TravID
    JOIN users u ON t.UserID = u.UserID
    WHERE ae.AgentID = ?
    ORDER BY ae.ExpNum DESC LIMIT 5
");
$revs->execute([$pkg['AgentID']]);
$agencyReviews = $revs->fetchAll();
 
// Available flights (to destination airport)
$flights = $db->prepare("
    SELECT f.FlightID, f.DepDateTime, f.ArrDateTime, f.Class, f.Type,
           dep.Name AS DepAirport, dep.City AS DepCity,
           arr.Name AS ArrAirport, arr.City AS ArrCity,
           pl.Name AS PlaneName
    FROM flights f
    JOIN airports dep ON dep.PortID = f.DepPortID
    JOIN airports arr ON arr.PortID = f.ArrPortID
    JOIN airplanes pl  ON pl.PlaneID = f.PlaneID
    WHERE arr.City LIKE ?
    ORDER BY f.DepDateTime
    LIMIT 10
");
$destCity = explode(',', $pkg['Destination'])[0];
$flights->execute(["%$destCity%"]);
$availFlights = $flights->fetchAll();
 
// Check if already a client of this agency
$clientCheck = $db->prepare("SELECT ClientID FROM clients WHERE TravID=? AND AgentID=?");
$clientCheck->execute([$u['sub_id'], $pkg['AgentID']]);
$client = $clientCheck->fetch();
 
// Handle booking
$bookingError = $bookingSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    $flightId = filter_input(INPUT_POST, 'flight_id', FILTER_VALIDATE_INT);
    $fromDate  = $_POST['from_date'] ?? '';
    $toDate    = $_POST['to_date']   ?? '';
 
    if (!$flightId || !$fromDate || !$toDate) {
        $bookingError = 'Please select a flight and travel dates.';
    } elseif ($toDate <= $fromDate) {
        $bookingError = 'Return date must be after departure date.';
    } else {
        try {
            $db->beginTransaction();
            // Create client relationship if not exists
            if (!$client) {
                $db->prepare("INSERT IGNORE INTO clients (TravID, AgentID) VALUES (?,?)")->execute([$u['sub_id'], $pkg['AgentID']]);
                $cid = $db->lastInsertId() ?: (function() use ($db, $u, $pkg) {
                    $s = $db->prepare("SELECT ClientID FROM clients WHERE TravID=? AND AgentID=?");
                    $s->execute([$u['sub_id'], $pkg['AgentID']]);
                    return $s->fetchColumn();
                })();
            } else {
                $cid = $client['ClientID'];
            }
            $db->prepare("INSERT INTO holidays (ClientID,PackID,FlightID,`From`,`To`) VALUES (?,?,?,?,?)")
               ->execute([$cid, $packId, $flightId, $fromDate, $toDate]);
            $db->commit();
            $bookingSuccess = 'Booking confirmed! Check My Bookings for details.';
        } catch (PDOException $e) {
            $db->rollBack();
            $bookingError = 'Booking failed. Please try again.';
        }
    }
}
 
// Handle review submission
$reviewError = $reviewSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review') {
    $toid   = filter_input(INPUT_POST, 'toid',   FILTER_VALIDATE_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $desc   = trim($_POST['description'] ?? '');
    if (!$toid || !$rating || !$desc) {
        $reviewError = 'Please fill in all review fields.';
    } elseif ($rating < 1 || $rating > 5) {
        $reviewError = 'Rating must be 1–5.';
    } else {
        $revNum = rand(2000, 9999);
        $db->prepare("INSERT INTO reviews (TravID,TOID,RevNum,Description,Rating) VALUES (?,?,?,?,?)")
           ->execute([$u['sub_id'], $toid, $revNum, $desc, $rating]);
        $reviewSuccess = 'Review submitted!';
    }
}
 
// Handle agency experience review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'agency_review') {
    $rating = filter_input(INPUT_POST, 'ag_rating', FILTER_VALIDATE_INT);
    $desc   = trim($_POST['ag_description'] ?? '');
    if ($client && $rating && $desc && $rating >= 1 && $rating <= 5) {
        $db->prepare("INSERT INTO agency_experiences (AgentID,ClientID,Description,Rating) VALUES (?,?,?,?)")
           ->execute([$pkg['AgentID'], $client['ClientID'], $desc, $rating]);
        $reviewSuccess = 'Agency review submitted!';
    }
}
 
// Tourism offerings at destination
$offerings = $db->prepare("
    SELECT to2.TOID, to2.Name, to2.Type, to2.City,
           a.Price AS AttrPrice, a.TimeOpen, a.TimeClose,
           acc.Attribute,
           res.TimeOpen AS ResOpen, res.TimeClose AS ResClose
    FROM tourism_offerings to2
    JOIN destinations d ON d.DestID = to2.DestID
    LEFT JOIN attractions a ON a.TOID = to2.TOID
    LEFT JOIN accomodation acc ON acc.TOID = to2.TOID
    LEFT JOIN restaurants res ON res.TOID = to2.TOID
    WHERE d.City LIKE ?
    LIMIT 12
");
$offerings->execute(["%$destCity%"]);
$offeringsData = $offerings->fetchAll();
 
$typeIcon = ['ATTRACTION'=>'🎡','ACCOMMODATION'=>'🏨','RESTAURANT'=>'🍽️'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pkg['Name']) ?> – Tripistry</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.detail-hero {
  background: linear-gradient(135deg, var(--teal-d), var(--teal));
  color: #fff; border-radius: var(--radius-lg); padding: 40px;
  margin-bottom: 28px; position: relative; overflow: hidden;
}
.detail-hero::before {
  content: attr(data-icon);
  position: absolute; right: 40px; top: 50%; transform: translateY(-50%);
  font-size: 120px; opacity: .15;
}
.detail-hero h1 { font-family: 'Fraunces',serif; font-size: 34px; margin-bottom: 8px; }
.section-title { font-family: 'Fraunces',serif; font-size: 22px; margin-bottom: 16px; }
.star-input { display:flex; gap:6px; font-size:24px; cursor:pointer; }
.star-input span { color: #d1d5db; transition: color .1s; }
.star-input span.active { color: var(--gold); }
</style>
</head>
<body>
<?php include '../traveller/nav_traveller.php'; ?>
 
<div class="container page-wrap">
 
  <!-- Hero -->
  <div class="detail-hero" data-icon="✈️">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px">
      <span class="pack-badge badge-<?= strtolower($pkg['Class']) ?>" style="color:inherit; border:1px solid rgba(255,255,255,.5); background:rgba(255,255,255,.15)"><?= $pkg['Class'] ?></span>
    </div>
    <h1><?= htmlspecialchars($pkg['Name']) ?></h1>
    <p style="opacity:.8; font-size:16px">📍 <?= htmlspecialchars($pkg['Destination']) ?> &nbsp;·&nbsp; 🕐 <?= $pkg['Duration'] ?> days &nbsp;·&nbsp; 🏢 <?= htmlspecialchars($pkg['AgencyName']) ?></p>
    <div style="margin-top:20px; font-size:32px; font-weight:700">R<?= number_format($pkg['Price'],0) ?> <small style="font-size:16px; opacity:.7">per person</small></div>
  </div>
 
  <?php if ($bookingSuccess): ?><div class="alert alert-success"><?= htmlspecialchars($bookingSuccess) ?></div><?php endif; ?>
  <?php if ($bookingError):   ?><div class="alert alert-error"><?= htmlspecialchars($bookingError) ?></div><?php endif; ?>
  <?php if ($reviewSuccess):  ?><div class="alert alert-success"><?= htmlspecialchars($reviewSuccess) ?></div><?php endif; ?>
 
  <div style="display:grid; grid-template-columns: 1fr 340px; gap:28px; align-items:start">
    <div>
 
      <!-- Itinerary -->
      <?php if ($itinerary): ?>
      <div class="card" style="margin-bottom:24px">
        <div class="card-body">
          <div class="section-title">🗺️ Itinerary</div>
          <?php foreach ($itinerary as $i): ?>
          <div style="padding:12px 0; border-bottom:1px solid var(--border)">
            <div style="display:flex; justify-content:space-between; margin-bottom:4px">
              <strong><?= htmlspecialchars($i['Activities']) ?></strong>
              <span class="pack-badge badge-premium" style="font-size:11px"><?= htmlspecialchars($i['Type']) ?></span>
            </div>
            <div class="text-sm text-muted">📅 <?= date('D d M Y, H:i', strtotime($i['DateTime'])) ?></div>
            <?php if ($i['Description']): ?>
            <div class="text-sm" style="margin-top:4px"><?= htmlspecialchars($i['Description']) ?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
 
      <!-- Active Discounts -->
      <?php if ($discounts): ?>
      <div class="card" style="margin-bottom:24px">
        <div class="card-body">
          <div class="section-title">🏷️ Active Discounts</div>
          <?php foreach ($discounts as $d): ?>
          <div class="alert alert-success" style="margin-bottom:8px">
            <?= htmlspecialchars($d['Details']) ?>
            <span class="text-sm"> — valid <?= $d['From'] ?> to <?= $d['To'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
 
      <!-- At this destination -->
      <?php if ($offeringsData): ?>
      <div class="card" style="margin-bottom:24px">
        <div class="card-body">
          <div class="section-title">📍 At this Destination</div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
            <?php foreach ($offeringsData as $o): ?>
            <div style="padding:12px; border:1px solid var(--border); border-radius:var(--radius)">
              <div style="font-size:20px; margin-bottom:4px"><?= $typeIcon[$o['Type']] ?? '📌' ?></div>
              <div style="font-weight:500; font-size:14px"><?= htmlspecialchars($o['Name']) ?></div>
              <div class="text-sm text-muted"><?= $o['City'] ?></div>
              <?php if ($o['AttrPrice'] !== null): ?>
              <div class="text-sm" style="color:var(--teal)">R<?= number_format($o['AttrPrice'],0) ?></div>
              <?php endif; ?>
              <form method="POST" style="margin-top:8px">
                <input type="hidden" name="action" value="review">
                <input type="hidden" name="toid"   value="<?= $o['TOID'] ?>">
                <a href="#review-<?= $o['TOID'] ?>" class="text-sm" style="color:var(--teal)">Write a review ↓</a>
              </form>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
 
      <!-- Agency Reviews -->
      <div class="card" style="margin-bottom:24px">
        <div class="card-body">
          <div class="section-title">⭐ Agency Reviews</div>
          <?php if ($pkg['AgencyRating']): ?>
          <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px">
            <div style="font-size:40px; font-weight:700; color:var(--teal)"><?= $pkg['AgencyRating'] ?></div>
            <div>
              <div class="stars"><?= str_repeat('★', round($pkg['AgencyRating'])) . str_repeat('☆', 5-round($pkg['AgencyRating'])) ?></div>
              <div class="text-sm text-muted"><?= $pkg['AgencyReviews'] ?> review<?= $pkg['AgencyReviews']!==1?'s':'' ?></div>
            </div>
          </div>
          <?php endif; ?>
          <?php foreach ($agencyReviews as $r): ?>
          <div style="padding:12px 0; border-bottom:1px solid var(--border)">
            <div style="display:flex; justify-content:space-between">
              <strong class="text-sm"><?= htmlspecialchars($r['Username']) ?></strong>
              <span class="stars text-sm"><?= str_repeat('★',$r['Rating']) . str_repeat('☆',5-$r['Rating']) ?></span>
            </div>
            <p class="text-sm" style="margin-top:4px"><?= htmlspecialchars($r['Description']) ?></p>
          </div>
          <?php endforeach; ?>
 
          <!-- Agency review form -->
          <?php if ($client): ?>
          <div style="margin-top:16px">
            <div style="font-weight:500; margin-bottom:8px">Leave an Agency Review</div>
            <form method="POST">
              <input type="hidden" name="action" value="agency_review">
              <div class="star-input" id="agStar" style="margin-bottom:8px">
                <?php for ($i=1;$i<=5;$i++): ?><span data-val="<?=$i?>">★</span><?php endfor; ?>
              </div>
              <input type="hidden" name="ag_rating" id="agRatingInput" value="">
              <textarea class="form-control" name="ag_description" placeholder="Share your experience with <?= htmlspecialchars($pkg['AgencyName']) ?>…" style="margin-bottom:8px"></textarea>
              <button class="btn btn-outline btn-sm">Submit Review</button>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>
 
      <!-- Review Tourism Offering -->
      <div class="card" id="review-form">
        <div class="card-body">
          <div class="section-title">📝 Review an Attraction / Hotel / Restaurant</div>
          <?php if ($reviewError): ?><div class="alert alert-error"><?= htmlspecialchars($reviewError) ?></div><?php endif; ?>
          <form method="POST">
            <input type="hidden" name="action" value="review">
            <div class="form-group">
              <label>Select Offering</label>
              <select class="form-control" name="toid" required>
                <option value="">Choose…</option>
                <?php foreach ($offeringsData as $o): ?>
                <option value="<?= $o['TOID'] ?>" id="review-<?= $o['TOID'] ?>"><?= $typeIcon[$o['Type']] ?> <?= htmlspecialchars($o['Name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Rating</label>
              <div class="star-input" id="toStar">
                <?php for ($i=1;$i<=5;$i++): ?><span data-val="<?=$i?>">★</span><?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="toRatingInput">
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea class="form-control" name="description" placeholder="Tell others about your experience…" required></textarea>
            </div>
            <button class="btn btn-primary">Submit Review</button>
          </form>
        </div>
      </div>
 
    </div>
 
    <!-- Sidebar: Book -->
    <div>
      <div class="card" style="position:sticky; top:80px">
        <div class="card-body">
          <div style="font-family:'Fraunces',serif; font-size:20px; margin-bottom:16px">Book this Package</div>
          <form method="POST">
            <input type="hidden" name="action" value="book">
            <div class="form-group">
              <label>Select Flight</label>
              <select class="form-control" name="flight_id" required>
                <option value="">Choose a flight…</option>
                <?php foreach ($availFlights as $f): ?>
                <option value="<?= $f['FlightID'] ?>">
                  <?= $f['DepCity'] ?> → <?= $f['ArrCity'] ?> | <?= date('d M, H:i', strtotime($f['DepDateTime'])) ?> | <?= $f['Class'] ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Travel From</label>
              <input class="form-control" type="date" name="from_date" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
              <label>Travel To</label>
              <input class="form-control" type="date" name="to_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            <div style="border-top:1px solid var(--border); padding-top:14px; margin-bottom:14px">
              <div style="display:flex; justify-content:space-between">
                <span>Package Price</span>
                <strong>R<?= number_format($pkg['Price'],0) ?></strong>
              </div>
            </div>
            <button class="btn btn-primary btn-full">Confirm Booking</button>
          </form>
        </div>
      </div>
    </div>
 
  </div>
</div>
 
<script>
function initStars(containerId, inputId) {
  const stars = document.querySelectorAll('#' + containerId + ' span');
  const input = document.getElementById(inputId);
  stars.forEach(s => {
    s.addEventListener('mouseover', () => stars.forEach((x,i) => x.style.color = i <= parseInt(s.dataset.val)-1 ? 'var(--gold)' : '#d1d5db'));
    s.addEventListener('click', () => { input.value = s.dataset.val; stars.forEach(x => x.classList.remove('active')); for(let i=0;i<parseInt(s.dataset.val);i++) stars[i].classList.add('active'); });
  });
}
initStars('toStar', 'toRatingInput');
initStars('agStar', 'agRatingInput');
</script>
</body>
</html>