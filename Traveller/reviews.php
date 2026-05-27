<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
requireRole('traveller');

$u  = currentUser();
$db = getDB();

// ---------------------------------------------------------------------------
// Handle new tourism-offering review submission
// ---------------------------------------------------------------------------
$successMsg = $errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_review') {
    $toid   = filter_input(INPUT_POST, 'toid',   FILTER_VALIDATE_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $desc   = trim($_POST['description'] ?? '');

    if (!$toid || !$rating || !$desc) {
        $errorMsg = 'Please fill in all fields.';
    } elseif ($rating < 1 || $rating > 5) {
        $errorMsg = 'Rating must be between 1 and 5.';
    } else {
        // Check the traveller actually visited this offering's destination
        $canReview = $db->prepare("
            SELECT 1
            FROM holidays h
            JOIN clients cl ON cl.ClientID = h.ClientID
            JOIN travellers t ON t.TravID = cl.TravID
            JOIN packages p   ON p.PackID  = h.PackID
            JOIN packinfo pi  ON pi.PackID  = p.PackID
            JOIN tourism_offerings to2 ON to2.TOID = ?
            JOIN destinations d ON d.DestID = to2.DestID
            WHERE t.TravID = ?
              AND h.To < CURDATE()
              AND pi.Destination LIKE CONCAT('%', d.City, '%')
            LIMIT 1
        ");
        $canReview->execute([$toid, $u['sub_id']]);

        if (!$canReview->fetchColumn()) {
            $errorMsg = 'You can only review offerings from destinations you have visited.';
        } else {
            // Prevent duplicates
            $dup = $db->prepare("SELECT RevID FROM reviews WHERE TravID=? AND TOID=?");
            $dup->execute([$u['sub_id'], $toid]);
            if ($dup->fetchColumn()) {
                $errorMsg = 'You have already reviewed this offering.';
            } else {
                $revNum = rand(2000, 99999);
                $db->prepare("INSERT INTO reviews (TravID, TOID, RevNum, Description, Rating) VALUES (?,?,?,?,?)")
                   ->execute([$u['sub_id'], $toid, $revNum, $desc, $rating]);
                $successMsg = 'Review submitted successfully!';
            }
        }
    }
}

// ---------------------------------------------------------------------------
// Handle delete
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_review') {
    $revId = filter_input(INPUT_POST, 'rev_id', FILTER_VALIDATE_INT);
    if ($revId) {
        $db->prepare("DELETE FROM reviews WHERE RevID=? AND TravID=?")->execute([$revId, $u['sub_id']]);
        $successMsg = 'Review deleted.';
    }
}

// ---------------------------------------------------------------------------
// My existing reviews
// ---------------------------------------------------------------------------
$myReviews = $db->prepare("
    SELECT r.RevID, r.Description, r.Rating, r.TOID,
           to2.Name AS offering_name, to2.Type AS offering_type,
           d.Name   AS destination
    FROM reviews r
    JOIN tourism_offerings to2 ON to2.TOID = r.TOID
    JOIN destinations d ON d.DestID = to2.DestID
    WHERE r.TravID = ?
    ORDER BY r.RevID DESC
");
$myReviews->execute([$u['sub_id']]);
$myReviews = $myReviews->fetchAll();

// ---------------------------------------------------------------------------
// Offerings the traveller can still review (visited but not yet reviewed)
// ---------------------------------------------------------------------------
$reviewable = $db->prepare("
    SELECT DISTINCT to2.TOID, to2.Name, to2.Type, to2.City, d.Name AS destination
    FROM holidays h
    JOIN clients  cl ON cl.ClientID = h.ClientID
    JOIN travellers t ON t.TravID  = cl.TravID
    JOIN packages p  ON p.PackID   = h.PackID
    JOIN packinfo pi ON pi.PackID  = p.PackID
    JOIN destinations d ON pi.Destination LIKE CONCAT('%', d.City, '%')
    JOIN tourism_offerings to2 ON to2.DestID = d.DestID
    WHERE t.TravID = ?
      AND h.To < CURDATE()
      AND to2.TOID NOT IN (
            SELECT TOID FROM reviews WHERE TravID = ?
      )
    ORDER BY to2.Type, to2.Name
");
$reviewable->execute([$u['sub_id'], $u['sub_id']]);
$reviewableOfferings = $reviewable->fetchAll();

$typeIcon  = ['ATTRACTION' => '🎡', 'ACCOMMODATION' => '🏨', 'RESTAURANT' => '🍽️'];
$typeLabel = ['ATTRACTION' => 'Attraction', 'ACCOMMODATION' => 'Accommodation', 'RESTAURANT' => 'Restaurant'];

// Highlight a booking if redirected from past_bookings.php
$highlight = filter_input(INPUT_GET, 'highlight', FILTER_VALIDATE_INT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews – Tripistry</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: #fff;
            border-radius: var(--radius-lg);
            padding: 36px 40px;
            margin-bottom: 28px;
        }
        .page-header h1 { font-family: 'Fraunces', serif; font-size: 30px; margin-bottom: 6px; }

        .tabs { display: flex; gap: 4px; margin-bottom: 24px; border-bottom: 2px solid var(--border); }
        .tab-btn {
            padding: 10px 20px; font-size: 14px; font-weight: 600;
            border: none; background: none; cursor: pointer;
            color: var(--text-muted); border-bottom: 2px solid transparent;
            margin-bottom: -2px; border-radius: var(--radius) var(--radius) 0 0;
            transition: color .2s, border-color .2s;
        }
        .tab-btn.active { color: var(--teal); border-bottom-color: var(--teal); }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .review-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px 20px;
            margin-bottom: 14px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }
        .review-card .icon-badge {
            width: 44px; height: 44px;
            border-radius: 50%;
            background: var(--surface-alt);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .stars-gold { color: var(--gold, #f59e0b); font-size: 16px; }

        .write-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 14px;
        }
        .write-card-header {
            background: var(--surface-alt);
            padding: 14px 18px;
            display: flex; align-items: center; justify-content: space-between;
            cursor: pointer;
        }
        .write-card-body { padding: 18px; }

        .star-row { display: flex; gap: 6px; font-size: 24px; cursor: pointer; margin-bottom: 12px; }
        .star-row span { color: #d1d5db; transition: color .1s; }
        .star-row span.lit { color: var(--gold, #f59e0b); }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-state .icon { font-size: 56px; margin-bottom: 12px; }

        .delete-btn {
            background: none; border: none; color: #ef4444; cursor: pointer;
            font-size: 12px; padding: 2px 6px; border-radius: 4px;
        }
        .delete-btn:hover { background: #fef2f2; }
    </style>
</head>
<body>
<?php include '../nav_traveller.php'; ?>

<div class="container page-wrap" style="display:grid; grid-template-columns:220px 1fr; gap:24px; align-items:start">
    <?php include '../sidebar_traveller.php'; ?>

    <main>
        <div class="page-header">
            <h1>⭐ My Reviews</h1>
            <p style="opacity:.85;color:#fff">Share your experiences and help the Tripistry community.</p>
        </div>

        <?php if ($successMsg): ?>
            <div class="alert alert-success" style="margin-bottom:16px"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert alert-error" style="margin-bottom:16px"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn <?= empty($reviewableOfferings) ? '' : 'active' ?>"
                    onclick="showTab('write')">
                ✏️ Write a Review
                <?php if ($reviewableOfferings): ?>
                    <span style="background:var(--teal);color:#fff;font-size:11px;padding:1px 7px;border-radius:999px;margin-left:4px"><?= count($reviewableOfferings) ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-btn <?= empty($reviewableOfferings) ? 'active' : '' ?>"
                    onclick="showTab('mine')">
                📋 My Reviews (<?= count($myReviews) ?>)
            </button>
        </div>

        <!-- Write a Review -->
        <div class="tab-panel <?= empty($reviewableOfferings) ? '' : 'active' ?>" id="tab-write">
            <?php if (empty($reviewableOfferings)): ?>
                <div class="empty-state">
                    <div class="icon">🗺️</div>
                    <h3 style="font-family:'Fraunces',serif; margin-bottom:8px">Nothing to review yet</h3>
                    <p>Complete a trip first — offerings at your visited destinations will appear here.</p>
                    <a href="packages.php" class="btn btn-primary" style="margin-top:14px">Browse Packages</a>
                </div>
            <?php else: ?>
                <p class="text-sm" style="color:var(--text-muted); margin-bottom:16px">
                    You have <strong><?= count($reviewableOfferings) ?></strong> offering<?= count($reviewableOfferings) !== 1 ? 's' : '' ?> you can review from your past trips.
                </p>
                <?php foreach ($reviewableOfferings as $o):
                    $uid = 'o' . $o['TOID'];
                ?>
                <div class="write-card" id="card-<?= $uid ?>">
                    <div class="write-card-header" onclick="toggleCard('<?= $uid ?>')">
                        <div style="display:flex; align-items:center; gap:10px">
                            <span style="font-size:22px"><?= $typeIcon[$o['Type']] ?? '📌' ?></span>
                            <div>
                                <div style="font-weight:600"><?= htmlspecialchars($o['Name']) ?></div>
                                <div style="font-size:12px; color:var(--text-muted)"><?= $typeLabel[$o['Type']] ?> · <?= htmlspecialchars($o['destination']) ?></div>
                            </div>
                        </div>
                        <span id="arrow-<?= $uid ?>" style="font-size:18px; color:var(--text-muted)">＋</span>
                    </div>
                    <div class="write-card-body" id="body-<?= $uid ?>" style="display:none">
                        <form method="POST">
                            <input type="hidden" name="action" value="submit_review">
                            <input type="hidden" name="toid"   value="<?= $o['TOID'] ?>">
                            <input type="hidden" name="rating" id="rating-<?= $uid ?>" value="">

                            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px">Your Rating</label>
                            <div class="star-row" id="stars-<?= $uid ?>">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span data-val="<?= $i ?>"
                                          onclick="setRating('<?= $uid ?>', <?= $i ?>)"
                                          onmouseover="hoverStars('<?= $uid ?>', <?= $i ?>)"
                                          onmouseout="resetHover('<?= $uid ?>')">★</span>
                                <?php endfor; ?>
                            </div>

                            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px">Your Review</label>
                            <textarea class="form-control" name="description" rows="3"
                                      placeholder="What did you think of <?= htmlspecialchars($o['Name']) ?>?"
                                      required style="margin-bottom:12px"></textarea>

                            <button class="btn btn-primary btn-sm">Submit Review</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- My Reviews -->
        <div class="tab-panel <?= empty($reviewableOfferings) ? 'active' : '' ?>" id="tab-mine">
            <?php if (empty($myReviews)): ?>
                <div class="empty-state">
                    <div class="icon">⭐</div>
                    <h3 style="font-family:'Fraunces',serif; margin-bottom:8px">No reviews yet</h3>
                    <p>Head to the "Write a Review" tab to share your experiences.</p>
                </div>
            <?php else: ?>
                <?php foreach ($myReviews as $r): ?>
                <div class="review-card">
                    <div class="icon-badge"><?= $typeIcon[$r['offering_type']] ?? '📌' ?></div>
                    <div style="flex:1">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px">
                            <div>
                                <strong><?= htmlspecialchars($r['offering_name']) ?></strong>
                                <span style="font-size:12px; color:var(--text-muted); margin-left:8px"><?= htmlspecialchars($r['destination']) ?></span>
                            </div>
                            <form method="POST" style="margin:0"
                                  onsubmit="return confirm('Delete this review?')">
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="rev_id" value="<?= $r['RevID'] ?>">
                                <button class="delete-btn">🗑 Delete</button>
                            </form>
                        </div>
                        <div class="stars-gold" style="margin-bottom:6px">
                            <?= str_repeat('★', $r['Rating']) . str_repeat('☆', 5 - $r['Rating']) ?>
                            <span style="font-size:13px; color:var(--text-muted); margin-left:4px"><?= $r['Rating'] ?>/5</span>
                        </div>
                        <p style="font-size:14px; color:var(--text); margin:0"><?= htmlspecialchars($r['Description']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
// Tab switching
function showTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.currentTarget.classList.add('active');
}

// Accordion cards
function toggleCard(uid) {
    const body  = document.getElementById('body-'  + uid);
    const arrow = document.getElementById('arrow-' + uid);
    const open  = body.style.display !== 'none';
    body.style.display  = open ? 'none' : 'block';
    arrow.textContent   = open ? '＋' : '－';
}

// Star rating
const ratings = {};
function setRating(uid, val) {
    ratings[uid] = val;
    document.getElementById('rating-' + uid).value = val;
    renderStars(uid, val, true);
}
function hoverStars(uid, val) { renderStars(uid, val, false); }
function resetHover(uid)       { renderStars(uid, ratings[uid] || 0, true); }
function renderStars(uid, val, permanent) {
    document.querySelectorAll('#stars-' + uid + ' span').forEach((s, i) => {
        s.classList.toggle('lit', i < val);
    });
}

// Auto-open the write panel if redirected from past_bookings with highlight
<?php if ($highlight): ?>
    // scroll to the write tab and open first card automatically
    showTab('write');
<?php endif; ?>
</script>
</body>
</html>