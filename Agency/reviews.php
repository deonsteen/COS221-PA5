<?php
// Agency/reviews.php
session_start();

if (!isset($_SESSION['sub_id']) || $_SESSION['role'] !== 'agency') {
    header('Location: ../login.php');
    exit;
}

require_once '../db.php';
$pdo = getDB();

$agentID    = (int) $_SESSION['sub_id'];
$stmtName = $pdo->prepare("SELECT Name FROM agencies WHERE AgentID = ?");
$stmtName->execute([$agentID]);
$agencyName = htmlspecialchars($stmtName->fetchColumn() ?: 'Your Agency');
$words      = explode(' ', $agencyName);
$initials   = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

// ── Active tab ──
$tab = $_GET['tab'] ?? 'agency';
if (!in_array($tab, ['agency', 'tourism'])) $tab = 'agency';

// ── Filter ──
$filterRating = $_GET['rating'] ?? '';

// ── Agency experience reviews ──
$whereAE  = ['ae.AgentID = ?'];
$paramsAE = [$agentID];
if ($filterRating !== '') {
    $whereAE[]  = 'ae.Rating = ?';
    $paramsAE[] = (int) $filterRating;
}

$stmtAE = $pdo->prepare("
    SELECT
        ae.ExpNum,
        ae.Rating,
        ae.Description,
        u.Username
    FROM agency_experiences ae
    JOIN clients    c  ON c.ClientID = ae.ClientID
    JOIN travellers t  ON t.TravID   = c.TravID
    JOIN users      u  ON u.UserID   = t.UserID
    WHERE " . implode(' AND ', $whereAE) . "
    ORDER BY ae.ExpNum DESC
");
$stmtAE->execute($paramsAE);
$agencyReviews = $stmtAE->fetchAll(PDO::FETCH_ASSOC);

// ── Tourism offering reviews linked to this agent's packages ──
// Reviews are written about tourism_offerings (attractions, accommodation, restaurants)
// We show all reviews on offerings that appear in this agent's packages via packinfo/itinerary
// Since there's no direct link from reviews to packages, we show all TO reviews
// for offerings in destinations this agent operates in
$whereTO  = ['p.AgentID = ?'];
$paramsTO = [$agentID];
if ($filterRating !== '') {
    $whereTO[]  = 'r.Rating = ?';
    $paramsTO[] = (int) $filterRating;
}

$stmtTO = $pdo->prepare("
    SELECT DISTINCT
        r.RevID,
        r.Rating,
        r.Description,
        u.Username,
        to2.Name   AS OfferingName,
        to2.Type   AS OfferingType,
        to2.City   AS OfferingCity
    FROM reviews r
    JOIN travellers t   ON t.TravID  = r.TravID
    JOIN users      u   ON u.UserID  = t.UserID
    JOIN tourism_offerings to2 ON to2.TOID = r.TOID
    JOIN destinations  d   ON d.DestID = to2.DestID
    JOIN packinfo      pi  ON pi.Destination LIKE CONCAT('%', d.Name, '%')
    JOIN packages      p   ON p.PackID   = pi.PackID
    WHERE " . implode(' AND ', $whereTO) . "
    ORDER BY r.RevID DESC
");
$stmtTO->execute($paramsTO);
$tourismReviews = $stmtTO->fetchAll(PDO::FETCH_ASSOC);

// ── Average ratings ──
$stmtAvg = $pdo->prepare("SELECT AVG(Rating) FROM agency_experiences WHERE AgentID = ?");
$stmtAvg->execute([$agentID]);
$avgAgency = round((float) $stmtAvg->fetchColumn(), 1);

// ── Star string helper ──
function starString(float $rating): string {
    $full = (int) round($rating);
    return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
}

// ── Offering type icon ──
function offeringIcon(string $type): string {
    return match($type) {
        'ATTRACTION'    => '🏛',
        'ACCOMMODATION' => '🏨',
        'RESTAURANT'    => '🍽️',
        default         => '📍',
    };
}

// ── Rating distribution for agency reviews ──
$distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
foreach ($agencyReviews as $rev) {
    $distribution[(int)$rev['Rating']]++;
}
$totalAgency = count($agencyReviews);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reviews — <?= $agencyName ?> | Tripistry</title>
  <link rel="stylesheet" href="css/agency.css">
  <style>
    /* Tabs */
    .tab-bar {
      display: flex;
      border-bottom: 2px solid var(--border);
      margin-bottom: 24px;
      gap: 0;
    }
    .tab-btn {
      padding: 10px 20px;
      font-size: 14px; font-weight: 500;
      color: var(--muted); cursor: pointer;
      border-bottom: 2px solid transparent;
      margin-bottom: -2px;
      text-decoration: none;
      transition: color .15s, border-color .15s;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .tab-btn.active { color: var(--teal); border-bottom-color: var(--teal); }
    .tab-btn:hover  { color: var(--teal); }

    /* Rating summary card */
    .rating-summary {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 24px;
      margin-bottom: 24px;
      display: flex; align-items: center; gap: 32px;
      box-shadow: var(--shadow);
    }
    .rating-big {
      text-align: center; flex-shrink: 0;
    }
    .rating-big .number {
      font-family: 'Fraunces', serif;
      font-size: 52px; font-weight: 500;
      color: var(--ink); line-height: 1;
    }
    .rating-big .stars { font-size: 18px; color: var(--gold); margin: 4px 0; }
    .rating-big .count { font-size: 12px; color: var(--muted); }
    .rating-bars { flex: 1; }
    .bar-row {
      display: flex; align-items: center; gap: 8px;
      margin-bottom: 6px; font-size: 13px;
    }
    .bar-label { width: 14px; text-align: right; color: var(--muted); flex-shrink:0; }
    .bar-track {
      flex: 1; height: 8px; border-radius: 4px;
      background: var(--border); overflow: hidden;
    }
    .bar-fill {
      height: 100%; border-radius: 4px;
      background: var(--gold); transition: width .4s;
    }
    .bar-count { width: 24px; text-align: right; color: var(--muted); flex-shrink:0; }

    /* Review cards */
    .review-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 20px;
      margin-bottom: 14px;
      box-shadow: var(--shadow);
      transition: box-shadow .15s;
    }
    .review-card:hover { box-shadow: var(--shadow-md); }
    .review-card-header {
      display: flex; align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    .reviewer {
      display: flex; align-items: center; gap: 10px;
    }
    .reviewer-avatar {
      width: 36px; height: 36px; border-radius: 50%;
      background: var(--teal);
      color: #fff; display: flex; align-items:center;
      justify-content:center; font-size:13px; font-weight:700;
      flex-shrink: 0;
    }
    .reviewer-name { font-size:14px; font-weight:600; color:var(--ink); }
    .reviewer-sub  { font-size:12px; color:var(--muted); margin-top:1px; }
    .review-stars  { font-size:16px; color:var(--gold); letter-spacing:1px; }
    .review-body   { font-size:14px; color:var(--ink-2); line-height:1.6; }

    /* Offering badge */
    .offering-badge {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 11px; font-weight: 600;
      padding: 2px 8px; border-radius: 4px;
      margin-bottom: 8px;
    }
    .badge-attraction    { background:#eff6ff; color:#2563eb; }
    .badge-accommodation { background:#fffbeb; color:var(--gold); }
    .badge-restaurant    { background:#fdf4ff; color:#9333ea; }

    /* Filter pill row */
    .filter-pills { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
    .pill {
      padding: 5px 14px; border-radius: 99px;
      font-size: 13px; font-weight: 500;
      border: 1.5px solid var(--border);
      color: var(--muted); text-decoration: none;
      transition: all .15s;
    }
    .pill:hover, .pill.active {
      border-color: var(--teal); color: var(--teal);
      background: var(--teal-pale);
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="agency_dashboard.php" class="nav-logo">Trip<em>istry</em></a>
  <div class="nav-right">
    <div class="nav-agency-badge">
      <div class="avatar"><?= $initials ?></div>
      <?= $agencyName ?>
    </div>
    <a href="../logout.php" class="btn-logout">⎋ Logout</a>
  </div>
</nav>

<div class="dashboard-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-section">
      <span class="sidebar-label">Overview</span>
      <a href="agency_dashboard.php"><span class="icon">🏠</span> Dashboard</a>
    </div>
    <div class="sidebar-section">
      <span class="sidebar-label">Manage</span>
      <a href="packages.php"><span class="icon">📦</span> Packages</a>
      <a href="discounts.php"><span class="icon">🏷️</span> Discounts</a>
    </div>
    <div class="sidebar-section">
      <span class="sidebar-label">People</span>
      <a href="clients.php"><span class="icon">👥</span> Clients</a>
    </div>
    <div class="sidebar-section">
      <span class="sidebar-label">Feedback</span>
      <a href="reviews.php" class="active"><span class="icon">⭐</span> Reviews</a>
    </div>
    <hr class="sidebar-divider">
    <a href="../logout.php"><span class="icon">🚪</span> Logout</a>
  </aside>

  <!-- MAIN -->
  <main class="main-content">

    <div class="page-header">
      <h1>Reviews</h1>
      <p>See what travellers are saying about your agency and your destinations.</p>
    </div>

    <!-- Tabs -->
    <div class="tab-bar">
      <a href="reviews.php?tab=agency"
         class="tab-btn <?= $tab === 'agency' ? 'active' : '' ?>">
        ⭐ Agency Reviews
        <span style="background:var(--teal-pale);color:var(--teal);border-radius:99px;padding:1px 8px;font-size:11px;">
          <?= count($agencyReviews) ?>
        </span>
      </a>
      <a href="reviews.php?tab=tourism"
         class="tab-btn <?= $tab === 'tourism' ? 'active' : '' ?>">
        🏛 Tourism Offerings
        <span style="background:var(--teal-pale);color:var(--teal);border-radius:99px;padding:1px 8px;font-size:11px;">
          <?= count($tourismReviews) ?>
        </span>
      </a>
    </div>

    <!-- Rating filter pills -->
    <div class="filter-pills">
      <a href="reviews.php?tab=<?= $tab ?>"
         class="pill <?= $filterRating === '' ? 'active' : '' ?>">All Ratings</a>
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <a href="reviews.php?tab=<?= $tab ?>&rating=<?= $i ?>"
           class="pill <?= (int)$filterRating === $i ? 'active' : '' ?>">
          <?= str_repeat('★', $i) ?>
        </a>
      <?php endfor; ?>
    </div>

    <?php if ($tab === 'agency'): ?>
      <!-- ══ AGENCY REVIEWS TAB ══ -->

      <?php if ($totalAgency > 0 && $filterRating === ''): ?>
        <!-- Rating summary -->
        <div class="rating-summary">
          <div class="rating-big">
            <div class="number"><?= $avgAgency > 0 ? $avgAgency : '—' ?></div>
            <div class="stars"><?= starString($avgAgency) ?></div>
            <div class="count"><?= $totalAgency ?> review<?= $totalAgency !== 1 ? 's' : '' ?></div>
          </div>
          <div class="rating-bars">
            <?php for ($i = 5; $i >= 1; $i--):
              $count = $distribution[$i];
              $pct   = $totalAgency > 0 ? ($count / $totalAgency) * 100 : 0;
            ?>
              <div class="bar-row">
                <span class="bar-label"><?= $i ?></span>
                <div class="bar-track">
                  <div class="bar-fill" style="width:<?= round($pct) ?>%"></div>
                </div>
                <span class="bar-count"><?= $count ?></span>
              </div>
            <?php endfor; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (empty($agencyReviews)): ?>
        <div class="empty-state card" style="padding:40px;">
          <div class="empty-icon">⭐</div>
          <p><?= $filterRating ? 'No ' . $filterRating . '-star reviews found.' : 'No agency reviews yet.' ?></p>
        </div>
      <?php else: ?>
        <?php foreach ($agencyReviews as $rev): ?>
          <div class="review-card">
            <div class="review-card-header">
              <div class="reviewer">
                <div class="reviewer-avatar">
                  <?= strtoupper(substr($rev['Username'], 0, 1)) ?>
                </div>
                <div>
                  <div class="reviewer-name"><?= htmlspecialchars($rev['Username']) ?></div>
                  <div class="reviewer-sub">Verified Client</div>
                </div>
              </div>
              <div class="review-stars"><?= starString((float)$rev['Rating']) ?></div>
            </div>
            <div class="review-body"><?= htmlspecialchars($rev['Description']) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    <?php else: ?>
      <!-- ══ TOURISM OFFERINGS TAB ══ -->

      <?php if (empty($tourismReviews)): ?>
        <div class="empty-state card" style="padding:40px;">
          <div class="empty-icon">🏛</div>
          <p><?= $filterRating ? 'No ' . $filterRating . '-star reviews found.' : 'No tourism offering reviews yet.' ?></p>
        </div>
      <?php else: ?>
        <?php foreach ($tourismReviews as $rev):
          $typeLower = strtolower($rev['OfferingType']);
          $badgeClass = 'badge-' . $typeLower;
        ?>
          <div class="review-card">
            <div class="offering-badge <?= $badgeClass ?>">
              <?= offeringIcon($rev['OfferingType']) ?>
              <?= ucfirst($typeLower) ?> · <?= htmlspecialchars($rev['OfferingCity']) ?>
            </div>
            <div style="font-weight:600;font-size:14px;margin-bottom:10px;color:var(--ink);">
              <?= htmlspecialchars($rev['OfferingName']) ?>
            </div>
            <div class="review-card-header">
              <div class="reviewer">
                <div class="reviewer-avatar" style="background:var(--teal-d);">
                  <?= strtoupper(substr($rev['Username'], 0, 1)) ?>
                </div>
                <div>
                  <div class="reviewer-name"><?= htmlspecialchars($rev['Username']) ?></div>
                  <div class="reviewer-sub">Traveller</div>
                </div>
              </div>
              <div class="review-stars"><?= starString((float)$rev['Rating']) ?></div>
            </div>
            <div class="review-body"><?= htmlspecialchars($rev['Description']) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    <?php endif; ?>

  </main>
</div>

</body>
</html>
