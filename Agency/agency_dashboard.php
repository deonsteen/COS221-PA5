<<?php
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

// ── Initials for avatar ──
$words    = explode(' ', $agencyName);
$initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

// ── Stats queries ──

// Total packages
$stmtPacks = $pdo->prepare("SELECT COUNT(*) FROM packages WHERE AgentID = ?");
$stmtPacks->execute([$agentID]);
$totalPackages = (int) $stmtPacks->fetchColumn();

// Total unique clients
$stmtClients = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE AgentID = ?");
$stmtClients->execute([$agentID]);
$totalClients = (int) $stmtClients->fetchColumn();

// Average agency rating (from agency_experiences)
$stmtRating = $pdo->prepare("SELECT AVG(Rating) FROM agency_experiences WHERE AgentID = ?");
$stmtRating->execute([$agentID]);
$avgRating = round((float) $stmtRating->fetchColumn(), 1);

// Total reviews received
$stmtReviews = $pdo->prepare("SELECT COUNT(*) FROM agency_experiences WHERE AgentID = ?");
$stmtReviews->execute([$agentID]);
$totalReviews = (int) $stmtReviews->fetchColumn();

// ── Recent packages (last 5) ──
$stmtRecentPacks = $pdo->prepare("
    SELECT p.PackID, p.Price, pi.Name, pi.Destination, pi.Duration, pi.Class
    FROM packages p
    JOIN packinfo pi ON pi.PackID = p.PackID
    WHERE p.AgentID = ?
    ORDER BY p.PackID DESC
    LIMIT 5
");
$stmtRecentPacks->execute([$agentID]);
$recentPackages = $stmtRecentPacks->fetchAll(PDO::FETCH_ASSOC);

// ── Recent reviews (last 4) ──
$stmtRecentReviews = $pdo->prepare("
    SELECT ae.Rating, ae.Description, u.Username
    FROM agency_experiences ae
    JOIN clients c  ON c.ClientID = ae.ClientID
    JOIN travellers t ON t.TravID = c.TravID
    JOIN users u    ON u.UserID = t.UserID
    WHERE ae.AgentID = ?
    ORDER BY ae.ExpNum DESC
    LIMIT 4
");
$stmtRecentReviews->execute([$agentID]);
$recentReviews = $stmtRecentReviews->fetchAll(PDO::FETCH_ASSOC);

// ── Helper: star string ──
function starString(float $rating): string {
    $full  = (int) round($rating);
    $stars = str_repeat('★', $full) . str_repeat('☆', 5 - $full);
    return $stars;
}

// ── Helper: class badge ──
function classBadge(string $class): string {
    $map = [
        'Standard' => 'badge-standard',
        'Premium'  => 'badge-premium',
        'Luxury'   => 'badge-luxury',
    ];
    $cls = $map[$class] ?? 'badge-standard';
    return "<span class=\"badge {$cls}\">" . htmlspecialchars($class) . "</span>";
}

// ── Helper: destination emoji ──
function destEmoji(string $dest): string {
    $map = [
        'Paris'       => '🗼', 'Bali'        => '🌴', 'Tokyo'       => '🗾',
        'Cape Town'   => '🏔', 'Dubai'       => '🏙', 'Rome'        => '🏛',
        'Maldives'    => '🏝', 'Safari'      => '🦁', 'Zanzibar'    => '🌊',
        'Santorini'   => '🌅', 'Bangkok'     => '🛕', 'Kyoto'       => '🌸',
        'Kruger'      => '🦁', 'Mauritius'   => '🌺', 'Singapore'   => '✨',
        'New York'    => '🗽', 'Barcelona'   => '🎨', 'Lisbon'      => '🌞',
        'Istanbul'    => '🕌', 'Nairobi'     => '🌍', 'Cairo'       => '🏺',
        'Serengeti'   => '🦒', 'Phuket'      => '🏖', 'Marrakech'   => '🕌',
        'Prague'      => '🏰', 'Victoria'    => '💧', 'Durban'      => '🌊',
        'Johannesburg'=> '🌆', 'Amsterdam'   => '🌷', 'Miami'       => '🌴',
    ];
    foreach ($map as $keyword => $emoji) {
        if (stripos($dest, $keyword) !== false) return $emoji;
    }
    return '✈️';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — <?= $agencyName ?> | Tripistry</title>
  <link rel="stylesheet" href="css/agency.css">
</head>
<body>

<!-- ══════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════ -->
<nav class="navbar">
  <a href="agency_dashboard.php" class="nav-logo">Trip<em>istry</em></a>
  <div class="nav-right">
    <div class="nav-agency-badge">
      <div class="avatar"><?= $initials ?></div>
      <?= $agencyName ?>
    </div>
    <a href="../logout.php" class="btn-logout">
      <span>⎋</span> Logout
    </a>
  </div>
</nav>

<!-- ══════════════════════════════════════════
LAYOUT
══════════════════════════════════════════ -->
<div class="dashboard-layout">

  <!-- ── Sidebar ── -->
  <aside class="sidebar">
    <div class="sidebar-section">
      <span class="sidebar-label">Overview</span>
      <a href="agency_dashboard.php" class="active">
        <span class="icon">🏠</span> Dashboard
      </a>
    </div>
    <div class="sidebar-section">
      <span class="sidebar-label">Manage</span>
      <a href="packages.php">
        <span class="icon">📦</span> Packages
      </a>
      <a href="discounts.php">
        <span class="icon">🏷️</span> Discounts
      </a>
    </div>
    <div class="sidebar-section">
      <span class="sidebar-label">People</span>
      <a href="clients.php">
        <span class="icon">👥</span> Clients
      </a>
    </div>
    <div class="sidebar-section">
      <span class="sidebar-label">Feedback</span>
      <a href="reviews.php">
        <span class="icon">⭐</span> Reviews
      </a>
    </div>
    <hr class="sidebar-divider">
    <div class="sidebar-section">
      <a href="../logout.php">
        <span class="icon">🚪</span> Logout
      </a>
    </div>
  </aside>

  <!-- ── Main ── -->
  <main class="main-content">

    <!-- Welcome banner -->
    <div class="welcome-banner">
      <div class="welcome-text">
        <h2>Welcome back, <?= $agencyName ?> 👋</h2>
        <p>Here's a snapshot of your agency's performance today.</p>
      </div>
      <div class="welcome-actions">
        <a href="packages.php" class="btn-white">📦 My Packages</a>
        <a href="package_form.php" class="btn-white btn-white-solid">+ New Package</a>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon icon-teal">📦</div>
        <div class="stat-value"><?= $totalPackages ?></div>
        <div class="stat-label">Total Packages</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon icon-green">👥</div>
        <div class="stat-value"><?= $totalClients ?></div>
        <div class="stat-label">Clients</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon icon-gold">⭐</div>
        <div class="stat-value"><?= $avgRating > 0 ? $avgRating : '—' ?></div>
        <div class="stat-label">Avg Rating</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon icon-purple">💬</div>
        <div class="stat-value"><?= $totalReviews ?></div>
        <div class="stat-label">Reviews</div>
      </div>
    </div>

    <!-- Recent packages + reviews side by side -->
    <div class="two-col">

      <!-- Recent packages -->
      <div>
        <div class="section-heading">
          <h2>Recent Packages</h2>
          <a href="packages.php">View all →</a>
        </div>
        <div class="card">
          <div class="card-body">
            <?php if (empty($recentPackages)): ?>
              <div class="empty-state">
                <div class="empty-icon">📦</div>
                <p>No packages yet.<br>
                  <a href="package_form.php">Create your first package</a>
                </p>
              </div>
            <?php else: ?>
              <?php foreach ($recentPackages as $pack): ?>
                <div class="pack-row">
                  <div class="pack-thumb">
                    <?= destEmoji($pack['Destination']) ?>
                  </div>
                  <div class="pack-info">
                    <div class="name"><?= htmlspecialchars($pack['Name']) ?></div>
                    <div class="meta">
                      <?= htmlspecialchars($pack['Destination']) ?> &middot;
                      <?= $pack['Duration'] ?> days &middot;
                      <?= classBadge($pack['Class']) ?>
                    </div>
                  </div>
                  <div class="pack-price-pill">
                    R<?= number_format($pack['Price'], 0, '.', ' ') ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Recent reviews -->
      <div>
        <div class="section-heading">
          <h2>Recent Reviews</h2>
          <a href="reviews.php">View all →</a>
        </div>
        <div class="card">
          <div class="card-body">
            <?php if (empty($recentReviews)): ?>
              <div class="empty-state">
                <div class="empty-icon">⭐</div>
                <p>No reviews yet.</p>
              </div>
            <?php else: ?>
              <?php foreach ($recentReviews as $rev): ?>
                <div class="review-row">
                  <div class="review-header">
                    <span class="reviewer-name">
                      <?= htmlspecialchars($rev['Username']) ?>
                    </span>
                    <span class="stars"><?= starString((float)$rev['Rating']) ?></span>
                  </div>
                  <div class="review-text">
                    <?= htmlspecialchars($rev['Description']) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div><!-- /.two-col -->

  </main><!-- /.main-content -->
</div><!-- /.dashboard-layout -->

</body>
</html>
