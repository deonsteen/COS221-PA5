<?php
// Agency/clients.php
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

// ── Filter ──
$filterSearch = trim($_GET['search'] ?? '');

$where  = ['c.AgentID = ?'];
$params = [$agentID];

if ($filterSearch !== '') {
    $where[]  = '(u.Username LIKE ? OR cd.Email LIKE ?)';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
}

// ── Query: clients with traveller + user + contact details ──
$sql = "
    SELECT
        c.ClientID,
        u.Username,
        cd.Email,
        cn.Number     AS Phone,
        t.DoB,
        COUNT(DISTINCT ae.ExpNum) AS ReviewCount
    FROM clients c
    JOIN travellers    t  ON t.TravID  = c.TravID
    JOIN users         u  ON u.UserID  = t.UserID
    LEFT JOIN contactdetails  cd ON cd.UserID = u.UserID
    LEFT JOIN contact_numbers cn ON cn.CDID   = cd.CDID
    LEFT JOIN agency_experiences ae ON ae.ClientID = c.ClientID AND ae.AgentID = c.AgentID
    WHERE " . implode(' AND ', $where) . "
    GROUP BY c.ClientID, u.Username, cd.Email, cn.Number, t.DoB
    ORDER BY u.Username ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Age helper ──
function calcAge(string $dob): int {
    return (int) (new DateTime($dob))->diff(new DateTime())->y;
}

// ── Initials avatar colour helper ──
function avatarColour(string $name): string {
    $colours = [
        '#0a7373','#065858','#0fa8a8','#9333ea',
        '#d97706','#16a34a','#dc2626','#2563eb',
    ];
    return $colours[ord($name[0]) % count($colours)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clients — <?= $agencyName ?> | Tripistry</title>
  <link rel="stylesheet" href="css/agency.css">
  <style>
    .client-avatar {
      width: 38px; height: 38px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; font-weight: 700; color: #fff;
      flex-shrink: 0;
    }
    .client-info .name  { font-size:14px; font-weight:500; color:var(--ink); }
    .client-info .meta  { font-size:12px; color:var(--muted); margin-top:1px; }
    .review-count {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 12px; font-weight: 600;
      background: var(--teal-pale); color: var(--teal);
      border-radius: 6px; padding: 2px 8px;
    }
    .review-count.zero { background:#f9fafb; color:var(--muted); }
    .search-wrap {
      display: flex; gap: 10px; align-items: flex-end;
      margin-bottom: 20px;
    }
    .search-wrap .form-group { margin-bottom: 0; flex: 1; max-width: 360px; }
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
      <a href="clients.php" class="active"><span class="icon">👥</span> Clients</a>
    </div>
    <div class="sidebar-section">
      <span class="sidebar-label">Feedback</span>
      <a href="reviews.php"><span class="icon">⭐</span> Reviews</a>
    </div>
    <hr class="sidebar-divider">
    <a href="../logout.php"><span class="icon">🚪</span> Logout</a>
  </aside>

  <!-- MAIN -->
  <main class="main-content">

    <div class="page-header">
      <h1>Clients</h1>
      <p><?= count($clients) ?> client<?= count($clients) !== 1 ? 's' : '' ?> registered with <?= $agencyName ?></p>
    </div>

    <!-- Search bar -->
    <form method="GET" action="clients.php">
      <div class="search-wrap">
        <div class="form-group">
          <label>Search by username or email</label>
          <input
            type="text"
            name="search"
            class="form-control"
            placeholder="e.g. james_olivier"
            value="<?= htmlspecialchars($filterSearch) ?>"
          >
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($filterSearch): ?>
          <a href="clients.php" class="btn btn-outline">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <!-- Clients table -->
    <div class="card">
      <?php if (empty($clients)): ?>
        <div class="empty-state">
          <div class="empty-icon">👥</div>
          <p>
            <?= $filterSearch ? 'No clients match your search.' : 'No clients yet.' ?>
          </p>
        </div>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Client</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Age</th>
              <th>Reviews Left</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clients as $client):
              $name    = htmlspecialchars($client['Username']);
              $initial = strtoupper(substr($client['Username'], 0, 1));
              $colour  = avatarColour($client['Username']);
              $age     = $client['DoB'] ? calcAge($client['DoB']) : '—';
              $reviews = (int) $client['ReviewCount'];
            ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div class="client-avatar" style="background:<?= $colour ?>">
                      <?= $initial ?>
                    </div>
                    <div class="client-info">
                      <div class="name"><?= $name ?></div>
                      <div class="meta">Traveller</div>
                    </div>
                  </div>
                </td>
                <td class="text-sm text-muted">
                  <?= $client['Email'] ? htmlspecialchars($client['Email']) : '—' ?>
                </td>
                <td class="text-sm text-muted">
                  <?= $client['Phone'] ? htmlspecialchars($client['Phone']) : '—' ?>
                </td>
                <td class="text-sm">
                  <?= $age ?> <?= is_numeric($age) ? 'yrs' : '' ?>
                </td>
                <td>
                  <span class="review-count <?= $reviews === 0 ? 'zero' : '' ?>">
                    <?= $reviews === 0 ? '—' : '⭐ ' . $reviews ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </main>
</div>

</body>
</html>
