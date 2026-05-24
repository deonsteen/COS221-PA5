<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
requireRole('traveller');
$db = getDB();

// IDs come from URL (added server-side via GET) or from JS sessionStorage via hidden field
$ids = [];
if (isset($_GET['ids'])) {
  foreach (explode(',', $_GET['ids']) as $id) {
    $v = filter_var(trim($id), FILTER_VALIDATE_INT);
    if ($v)
      $ids[] = $v;
  }
}
$ids = array_unique(array_slice($ids, 0, 3));

$packages = [];
if ($ids) {
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $db->prepare("
        SELECT p.PackID, p.Price,
              pi.Name, pi.Destination, pi.Duration, pi.Class,
              ag.Name AS AgencyName,
              ROUND(AVG(ae.Rating),1) AS AgencyRating,
              d.Details AS Discount
        FROM packages p
        JOIN packinfo pi  ON pi.PackID  = p.PackID
        JOIN agencies ag  ON ag.AgentID = p.AgentID
        LEFT JOIN agency_experiences ae ON ae.AgentID = p.AgentID
        LEFT JOIN discounts d ON d.PackID = p.PackID AND CURDATE() BETWEEN d.`From` AND d.`To`
        WHERE p.PackID IN ($placeholders)
        GROUP BY p.PackID
    ");
  $stmt->execute($ids);
  $packages = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Compare Packages – Tripistry</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .compare-table {
      width: 100%;
      border-collapse: collapse;
    }

    .compare-table th {
      padding: 16px;
      font-family: 'Fraunces', serif;
      font-size: 16px;
      text-align: left;
      background: var(--teal-pale);
      color: var(--teal);
      border: 1px solid var(--border);
    }

    .compare-table td {
      padding: 14px 16px;
      border: 1px solid var(--border);
      vertical-align: top;
      font-size: 14px;
    }

    .compare-table tr:nth-child(even) td {
      background: #fafafa;
    }

    .compare-table .row-label {
      font-weight: 600;
      color: var(--ink-2);
      background: var(--sand);
      white-space: nowrap;
    }

    .best {
      background: #f0fdf4 !important;
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/../nav_traveller.php'; ?>
  <?php include __DIR__ . '/../sidebar_traveller.php'; ?>

  <div class="container page-wrap">
    <div class="page-header">
      <h1>Compare Packages</h1>
      <p>Compare up to 3 packages side by side.</p>
    </div>

    <!-- Load from sessionStorage -->
    <form method="GET" id="compareForm"
      style="margin-bottom:20px; display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap">
      <div class="form-group" style="margin:0">
        <label>Add Package ID</label>
        <input class="form-control" type="number" id="addId" placeholder="Package ID">
      </div>
      <button type="button" onclick="addToCompare()" class="btn btn-outline">Add</button>
      <button type="button" onclick="clearCompare()" class="btn btn-danger btn-sm">Clear All</button>
      <input type="hidden" name="ids" id="idsField" value="<?= htmlspecialchars(implode(',', $ids)) ?>">
      <button type="submit" class="btn btn-primary">Compare</button>
    </form>

    <?php if (count($packages) < 2): ?>
      <div class="alert alert-info">Add at least 2 packages to compare. Browse <a href="packages.php">packages</a> and
        click ⚖️ to add them.</div>
    <?php else: ?>

      <?php
      // Find best price and duration
      $prices = array_column($packages, 'Price');
      $durations = array_column($packages, 'Duration');
      $ratings = array_column($packages, 'AgencyRating');
      $minPrice = min($prices);
      $minDur = min($durations);
      $maxRat = max($ratings);
      ?>

      <div style="overflow-x:auto">
        <table class="compare-table">
          <thead>
            <tr>
              <th style="width:160px">Feature</th>
              <?php foreach ($packages as $pkg): ?>
                <th><?= htmlspecialchars($pkg['Name']) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="row-label">Destination</td>
              <?php foreach ($packages as $pkg): ?>
                <td><?= htmlspecialchars($pkg['Destination']) ?></td><?php endforeach; ?>
            </tr>
            <tr>
              <td class="row-label">Agency</td>
              <?php foreach ($packages as $pkg): ?>
                <td><?= htmlspecialchars($pkg['AgencyName']) ?></td><?php endforeach; ?>
            </tr>
            <tr>
              <td class="row-label">Price</td>
              <?php foreach ($packages as $pkg): ?>
                <td class="<?= $pkg['Price'] == $minPrice ? 'best' : '' ?>">
                  <strong>R<?= number_format($pkg['Price'], 0) ?></strong>
                  <?= $pkg['Price'] == $minPrice ? ' 🏆 Best price' : '' ?>
                </td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="row-label">Duration</td>
              <?php foreach ($packages as $pkg): ?>
                <td class="<?= $pkg['Duration'] == $minDur ? 'best' : '' ?>">
                  <?= $pkg['Duration'] ?> days<?= $pkg['Duration'] == $minDur ? ' 🏆 Shortest' : '' ?>
                </td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="row-label">Class</td>
              <?php foreach ($packages as $pkg): ?>
                <td><span class="pack-badge badge-<?= strtolower($pkg['Class']) ?>"><?= $pkg['Class'] ?></span></td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="row-label">Agency Rating</td>
              <?php foreach ($packages as $pkg): ?>
                <td class="<?= $pkg['AgencyRating'] == $maxRat ? 'best' : '' ?>">
                  <?php if ($pkg['AgencyRating']): ?>
                    <span
                      class="stars"><?= str_repeat('★', round($pkg['AgencyRating'])) . str_repeat('☆', 5 - round($pkg['AgencyRating'])) ?></span>
                    <?= $pkg['AgencyRating'] ?>/5 <?= $pkg['AgencyRating'] == $maxRat ? ' 🏆 Top rated' : '' ?>
                  <?php else: ?><span class="text-muted">No reviews</span><?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="row-label">Active Discount</td>
              <?php foreach ($packages as $pkg): ?>
                <td>
                  <?= $pkg['Discount'] ? '<span class="alert alert-success" style="padding:4px 8px; font-size:12px">🏷️ ' . htmlspecialchars($pkg['Discount']) . '</span>' : '<span class="text-muted">None</span>' ?>
                </td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td class="row-label">Book</td>
              <?php foreach ($packages as $pkg): ?>
                <td><a href="/COS221-PA5/packages_detail.php?id=<?= $pkg['PackID'] ?>" class="btn btn-primary btn-sm">View &
                    Book →</a></td>
              <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <script>
    function getIds() { return JSON.parse(sessionStorage.getItem('compare') || '[]'); }
    function saveIds(ids) { sessionStorage.setItem('compare', JSON.stringify(ids)); }
    function syncForm() { document.getElementById('idsField').value = getIds().join(','); }

    function addToCompare() {
      const id = document.getElementById('addId').value.trim();
      if (!id) return;
      let ids = getIds();
      if (!ids.includes(id)) ids.push(id);
      if (ids.length > 3) ids = ids.slice(-3);
      saveIds(ids); syncForm();
      document.getElementById('compareForm').submit();
    }
    function clearCompare() { saveIds([]); syncForm(); document.getElementById('compareForm').submit(); }

    // On load, sync from sessionStorage if no GET ids
    window.addEventListener('load', () => {
      const urlIds = '<?= implode(',', $ids) ?>';
      if (!urlIds) {
        const stored = getIds();
        if (stored.length) {
          document.getElementById('idsField').value = stored.join(',');
          document.getElementById('compareForm').submit();
        }
      } else {
        saveIds(urlIds.split(','));
      }
    });
  </script>
</body>

</html>