<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
requireRole('traveller');

$u   = currentUser();
$db  = getDB();

// Fetch all holidays whose end date has passed
$stmt = $db->prepare("
    SELECT
        h.HolidayID,
        h.From    AS travel_from,
        h.To      AS travel_to,
        pi.Name   AS package_name,
        pi.Destination,
        pi.Duration,
        pi.Class,
        p.Price,
        p.PackID,
        ag.Name   AS agency_name,
        ag.AgentID,
        f.DepDateTime,
        f.ArrDateTime,
        dep.City  AS dep_city,
        arr.City  AS arr_city,
        f.Class   AS flight_class
    FROM holidays h
    JOIN clients  cl ON cl.ClientID = h.ClientID
    JOIN travellers t ON t.TravID   = cl.TravID
    JOIN packages  p  ON p.PackID   = h.PackID
    JOIN packinfo  pi ON pi.PackID  = p.PackID
    JOIN agencies  ag ON ag.AgentID = p.AgentID
    JOIN flights   f  ON f.FlightID = h.FlightID
    JOIN airports  dep ON dep.PortID = f.DepPortID
    JOIN airports  arr ON arr.PortID = f.ArrPortID
    WHERE t.TravID = ?
      AND h.To < CURDATE()
    ORDER BY h.To DESC
");
$stmt->execute([$u['sub_id']]);
$pastBookings = $stmt->fetchAll();

// Per-booking: check whether the traveller has already reviewed the agency
$reviewedAgencies = [];
$raStmt = $db->prepare("
    SELECT ae.AgentID
    FROM agency_experiences ae
    JOIN clients cl ON ae.ClientID = cl.ClientID
    JOIN travellers t ON cl.TravID = t.TravID
    WHERE t.TravID = ?
");
$raStmt->execute([$u['sub_id']]);
foreach ($raStmt->fetchAll() as $r) {
    $reviewedAgencies[$r['AgentID']] = true;
}

// Handle quick agency-review POST from this page
$successMsg = $errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'agency_review') {
    $agentId  = filter_input(INPUT_POST, 'agent_id', FILTER_VALIDATE_INT);
    $clientId = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
    $rating   = filter_input(INPUT_POST, 'rating',    FILTER_VALIDATE_INT);
    $desc     = trim($_POST['description'] ?? '');

    if (!$agentId || !$clientId || !$rating || !$desc) {
        $errorMsg = 'Please fill in all fields.';
    } elseif ($rating < 1 || $rating > 5) {
        $errorMsg = 'Rating must be between 1 and 5.';
    } else {
        $db->prepare("INSERT INTO agency_experiences (AgentID, ClientID, Description, Rating) VALUES (?,?,?,?)")
           ->execute([$agentId, $clientId, $desc, $rating]);
        $reviewedAgencies[$agentId] = true;
        $successMsg = 'Agency review submitted — thanks!';
    }
}

$classIcon = ['Standard' => '🎒', 'Premium' => '💼', 'Luxury' => '👑'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Past Bookings – Tripistry</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, var(--teal-d), var(--teal));
            color: #fff;
            border-radius: var(--radius-lg);
            padding: 36px 40px;
            margin-bottom: 28px;
        }
        .page-header h1 {
            font-family: 'Fraunces', serif;
            font-size: 30px;
            margin-bottom: 6px;
        }
        .booking-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .booking-card-header {
            background: var(--surface-alt);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .booking-card-body {
            padding: 20px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }
        .meta-item { font-size: 13px; }
        .meta-item .label { color: var(--text-muted); margin-bottom: 2px; }
        .meta-item .value { font-weight: 600; }
        .review-panel {
            background: var(--surface-alt);
            border-radius: var(--radius);
            padding: 16px;
            margin-top: 12px;
        }
        .star-row { display: flex; gap: 6px; font-size: 22px; cursor: pointer; margin-bottom: 10px; }
        .star-row span { color: #d1d5db; transition: color .1s; }
        .star-row span.lit { color: var(--gold, #f59e0b); }
        .done-badge {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            font-size: 12px;
            padding: 2px 10px;
            border-radius: 999px;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; }
    </style>
</head>
<body>
<?php include '../nav_traveller.php'; ?>

<div class="container page-wrap" style="display:grid; grid-template-columns:220px 1fr; gap:24px; align-items:start">
    <?php include '../sidebar_traveller.php'; ?>

    <main>
        <div class="page-header">
            <h1>🗓️ Past Bookings</h1>
            <p style="opacity:.85; color:#fff">Trips you've completed — leave a review to help other travellers.</p>
        </div>

        <?php if ($successMsg): ?>
            <div class="alert alert-success" style="margin-bottom:16px"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert alert-error" style="margin-bottom:16px"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <?php if (empty($pastBookings)): ?>
            <div class="empty-state">
                <div class="icon">✈️</div>
                <h2 style="font-family:'Fraunces',serif; margin-bottom:8px">No past trips yet</h2>
                <p>Once your booked holidays are over, they'll show up here.</p>
                <a href="packages.php" class="btn btn-primary" style="margin-top:16px">Browse Packages</a>
            </div>
        <?php else: ?>
            <?php foreach ($pastBookings as $b):
                // Fetch the ClientID for this agency so we can post a review
                $clStmt = $db->prepare("SELECT ClientID FROM clients WHERE TravID=? AND AgentID=?");
                $clStmt->execute([$u['sub_id'], $b['AgentID']]);
                $clientId = $clStmt->fetchColumn();
                $alreadyReviewed = !empty($reviewedAgencies[$b['AgentID']]);
            ?>
            <div class="booking-card">
                <div class="booking-card-header">
                    <div>
                        <span style="font-size:18px; margin-right:6px"><?= $classIcon[$b['Class']] ?? '🧳' ?></span>
                        <strong style="font-size:16px"><?= htmlspecialchars($b['package_name']) ?></strong>
                        <span class="pack-badge badge-<?= strtolower($b['Class']) ?>" style="margin-left:8px; font-size:11px"><?= $b['Class'] ?></span>
                    </div>
                    <div style="font-size:13px; color:var(--text-muted)">
                        Booking #<?= $b['HolidayID'] ?>
                    </div>
                </div>

                <div class="booking-card-body">
                    <div class="meta-grid">
                        <div class="meta-item">
                            <div class="label">Destination</div>
                            <div class="value">📍 <?= htmlspecialchars($b['Destination']) ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Travel Dates</div>
                            <div class="value"><?= date('d M Y', strtotime($b['travel_from'])) ?> – <?= date('d M Y', strtotime($b['travel_to'])) ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Duration</div>
                            <div class="value">⏱ <?= $b['Duration'] ?> days</div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Price Paid</div>
                            <div class="value">R<?= number_format($b['Price'], 0) ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Agency</div>
                            <div class="value">🏢 <?= htmlspecialchars($b['agency_name']) ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Flight</div>
                            <div class="value">✈️ <?= htmlspecialchars($b['dep_city']) ?> → <?= htmlspecialchars($b['arr_city']) ?> (<?= $b['flight_class'] ?>)</div>
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; flex-wrap:wrap">
                        <a href="reviews.php?highlight=<?= $b['HolidayID'] ?>" class="btn btn-outline btn-sm">📝 Write Offering Review</a>
                        <?php if ($alreadyReviewed): ?>
                            <span class="done-badge">✅ Agency reviewed</span>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline"
                                    onclick="toggleReview(<?= $b['HolidayID'] ?>)">
                                ⭐ Review <?= htmlspecialchars($b['agency_name']) ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (!$alreadyReviewed && $clientId): ?>
                    <div class="review-panel" id="rp-<?= $b['HolidayID'] ?>" style="display:none">
                        <div style="font-weight:600; margin-bottom:10px">Rate your experience with <?= htmlspecialchars($b['agency_name']) ?></div>
                        <form method="POST">
                            <input type="hidden" name="action"    value="agency_review">
                            <input type="hidden" name="agent_id"  value="<?= $b['AgentID'] ?>">
                            <input type="hidden" name="client_id" value="<?= $clientId ?>">
                            <input type="hidden" name="rating"    id="rating-<?= $b['HolidayID'] ?>" value="">

                            <div class="star-row" id="stars-<?= $b['HolidayID'] ?>">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span data-val="<?= $i ?>" onclick="setRating(<?= $b['HolidayID'] ?>, <?= $i ?>)">★</span>
                                <?php endfor; ?>
                            </div>

                            <textarea class="form-control" name="description"
                                      placeholder="How was the service? Would you book through them again?"
                                      rows="3" required style="margin-bottom:10px"></textarea>

                            <button class="btn btn-primary btn-sm">Submit Review</button>
                            <button type="button" class="btn btn-outline btn-sm"
                                    onclick="toggleReview(<?= $b['HolidayID'] ?>)">Cancel</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

<script>
function toggleReview(id) {
    const panel = document.getElementById('rp-' + id);
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function setRating(bookingId, val) {
    document.getElementById('rating-' + bookingId).value = val;
    const stars = document.querySelectorAll('#stars-' + bookingId + ' span');
    stars.forEach((s, i) => {
        s.classList.toggle('lit', i < val);
    });
}
</script>
</body>
</html>