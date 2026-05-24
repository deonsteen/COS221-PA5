<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
requireRole('traveller');
 
$db = getDB();
 
// Sanitised filter inputs
$search   = trim($_GET['search']   ?? '');
$dest     = trim($_GET['dest']     ?? '');
$class    = trim($_GET['class']    ?? '');
$sort     = trim($_GET['sort']     ?? 'price_asc');
$min      = is_numeric($_GET['min'] ?? '') ? (float)$_GET['min'] : null;
$max      = is_numeric($_GET['max'] ?? '') ? (float)$_GET['max'] : null;
$dur_max  = is_numeric($_GET['dur'] ?? '') ? (int)$_GET['dur']   : null;
 
$validClasses = ['Standard','Premium','Luxury'];
$validSorts   = ['price_asc','price_desc','duration_asc','name_asc','rating_desc'];
if (!in_array($class, $validClasses)) $class = '';
if (!in_array($sort,  $validSorts))   $sort  = 'price_asc';
 
$sortMap = [
    'price_asc'    => 'p.Price ASC',
    'price_desc'   => 'p.Price DESC',
    'duration_asc' => 'pi.Duration ASC',
    'name_asc'     => 'pi.Name ASC',
    'rating_desc'  => 'avg_rating DESC',
];
$orderBy = $sortMap[$sort];
 
// Build query with bound params
$sql = "
    SELECT p.PackID, p.Price, p.AgentID,
           pi.Name, pi.Destination, pi.Duration, pi.Class,
           ag.Name AS AgencyName,
           ROUND(AVG(ae.Rating),1) AS avg_rating,
           COUNT(DISTINCT ae.ExpNum) AS review_count,
           d.DiscountID, d.Details AS DiscDetails
    FROM packages p
    JOIN packinfo pi   ON pi.PackID   = p.PackID
    JOIN agencies ag   ON ag.AgentID  = p.AgentID
    LEFT JOIN clients cl ON cl.AgentID = p.AgentID
    LEFT JOIN agency_experiences ae ON ae.AgentID = p.AgentID
    LEFT JOIN discounts d ON d.PackID = p.PackID
       AND CURDATE() BETWEEN d.From AND d.To
    WHERE 1=1
";
$params = [];
 
if ($search) {
    $sql .= " AND (pi.Name LIKE ? OR pi.Destination LIKE ? OR ag.Name LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}
if ($dest) {
    $sql .= " AND pi.Destination LIKE ?";
    $params[] = "%$dest%";
}
if ($class) {
    $sql .= " AND pi.Class = ?";
    $params[] = $class;
}
if ($min !== null) {
    $sql .= " AND p.Price >= ?";
    $params[] = $min;
}
if ($max !== null) {
    $sql .= " AND p.Price <= ?";
    $params[] = $max;
}
if ($dur_max !== null) {
    $sql .= " AND pi.Duration <= ?";
    $params[] = $dur_max;
}
 
$sql .= " GROUP BY p.PackID, p.Price, p.AgentID, pi.Name, pi.Destination, pi.Duration, pi.Class, ag.Name, d.DiscountID, d.Details ORDER BY $orderBy";
 
$stmt = $db->prepare($sql);
$stmt->execute($params);
$packages = $stmt->fetchAll();
 
// Destination list for filter dropdown
$dests = $db->query("SELECT DISTINCT Destination FROM packinfo ORDER BY Destination")->fetchAll(PDO::FETCH_COLUMN);
 
$destIcons = [
    'Paris'=>'🗼','Bali'=>'🌴','Tokyo'=>'🏯','Cape Town'=>'🏔','Dubai'=>'🌆',
    'Maldives'=>'🏝','Rome'=>'🏛','Bangkok'=>'🛺','Santorini'=>'🌅','Serengeti'=>'🦁',
    'Marrakech'=>'🕌','Barcelona'=>'🎨','Singapore'=>'🌃','Zanzibar'=>'⛵',
    'Kyoto'=>'🌸','Cairo'=>'🪆','Mauritius'=>'🐠','Nairobi'=>'🦒',
    'Victoria Falls'=>'💧','New York'=>'🗽','Istanbul'=>'🕍','Lisbon'=>'🏰',
    'Kruger Park'=>'🐘','Phuket'=>'🤿','Amsterdam'=>'🚲','Johannesburg'=>'💎',
    'Durban'=>'🌊','Prague'=>'🏰','Miami'=>'🌴',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Packages – Tripistry</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include __DIR__ . '/../nav_traveller.php'; ?>

<div class="container page-wrap">
  <div class="sidebar-layout">
    <?php include __DIR__ . '/../sidebar_traveller.php'; ?>
 
    <div>
      <div class="page-header">
        <h1>Browse Packages</h1>
        <p><?= count($packages) ?> package<?= count($packages)!==1?'s':'' ?> found</p>
      </div>
 
      <!-- Filters -->
      <form method="GET" class="filter-bar">
        <div class="form-group">
          <label>Search</label>
          <input class="form-control" type="text" name="search" placeholder="Package, destination, agency…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="form-group">
          <label>Destination</label>
          <select class="form-control" name="dest">
            <option value="">All destinations</option>
            <?php foreach ($dests as $d): ?>
            <option value="<?= htmlspecialchars($d) ?>" <?= $dest===$d?'selected':'' ?>><?= htmlspecialchars($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Class</label>
          <select class="form-control" name="class">
            <option value="">Any class</option>
            <option value="Standard" <?= $class==='Standard'?'selected':'' ?>>Standard</option>
            <option value="Premium"  <?= $class==='Premium' ?'selected':'' ?>>Premium</option>
            <option value="Luxury"   <?= $class==='Luxury'  ?'selected':'' ?>>Luxury</option>
          </select>
        </div>
        <div class="form-group">
          <label>Min Price (R)</label>
          <input class="form-control" type="number" name="min" placeholder="0" value="<?= htmlspecialchars($_GET['min']??'') ?>">
        </div>
        <div class="form-group">
          <label>Max Price (R)</label>
          <input class="form-control" type="number" name="max" placeholder="Any" value="<?= htmlspecialchars($_GET['max']??'') ?>">
        </div>
        <div class="form-group">
          <label>Max Days</label>
          <input class="form-control" type="number" name="dur" placeholder="Any" value="<?= htmlspecialchars($_GET['dur']??'') ?>">
        </div>
        <div class="form-group">
          <label>Sort by</label>
          <select class="form-control" name="sort">
            <option value="price_asc"    <?= $sort==='price_asc'   ?'selected':''?>>Price: Low → High</option>
            <option value="price_desc"   <?= $sort==='price_desc'  ?'selected':''?>>Price: High → Low</option>
            <option value="duration_asc" <?= $sort==='duration_asc'?'selected':''?>>Duration: Shortest</option>
            <option value="name_asc"     <?= $sort==='name_asc'    ?'selected':''?>>Name: A–Z</option>
            <option value="rating_desc"  <?= $sort==='rating_desc' ?'selected':''?>>Top Rated</option>
          </select>
        </div>
        <div class="form-group" style="align-self:flex-end">
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="packages.php" class="btn btn-outline" style="margin-left:6px">Reset</a>
        </div>
      </form>
 
      <?php if (isset($_GET['compare_msg'])): ?>
      <div class="alert alert-info">Package added to compare. <a href="compare_packages.php">View comparison →</a></div>
      <?php endif; ?>
 
      <!-- Package Grid -->
      <?php if ($packages): ?>
      <div class="package-grid">
        <?php foreach ($packages as $pkg):
          $dest_raw = explode(',', $pkg['Destination'])[0];
          $icon = $destIcons[$dest_raw] ?? '✈️';
          $badgeClass = 'badge-' . strtolower($pkg['Class']);
          $stars = $pkg['avg_rating'] ? str_repeat('★', round($pkg['avg_rating'])) . str_repeat('☆', 5-round($pkg['avg_rating'])) : '☆☆☆☆☆';
        ?>
        <div class="pack-card">
          <div class="pack-card-img" style="font-size:56px"><?= $icon ?></div>
          <div class="pack-card-body">
            <span class="pack-badge <?= $badgeClass ?>"><?= $pkg['Class'] ?></span>
            <div class="pack-title"><?= htmlspecialchars($pkg['Name']) ?></div>
            <div class="pack-dest">📍 <?= htmlspecialchars($pkg['Destination']) ?></div>
            <div class="pack-meta">
              <span>🕐 <?= $pkg['Duration'] ?> days</span>
              <span>🏢 <?= htmlspecialchars($pkg['AgencyName']) ?></span>
            </div>
            <?php if ($pkg['avg_rating']): ?>
            <div class="text-sm" style="color:var(--gold); margin-bottom:8px">
              <?= $stars ?> <span class="text-muted">(<?= $pkg['review_count'] ?>)</span>
            </div>
            <?php endif; ?>
            <?php if ($pkg['DiscDetails']): ?>
            <div class="alert alert-success text-sm" style="padding:6px 10px; margin-bottom:8px">🏷️ <?= htmlspecialchars($pkg['DiscDetails']) ?></div>
            <?php endif; ?>
          </div>
          <div class="pack-footer">
            <div>
              <div class="pack-price">R<?= number_format($pkg['Price'],0) ?></div>
              <small class="text-muted">per person</small>
            </div>
            <div style="display:flex; gap:6px">
              <a href="?<?= http_build_query(array_merge($_GET, ['add_compare'=>$pkg['PackID']])) ?>"
                 class="btn btn-outline btn-sm" title="Add to compare">⚖️</a>
              <a href="/COS221-PA5/packages_detail.php?id=<?= $pkg['PackID'] ?>" class="btn btn-primary btn-sm">View →</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="card card-body text-muted" style="text-align:center; padding:48px">
        No packages match your filters. <a href="packages.php">Clear filters</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
 
<script>
// Add to compare via session (simple JS approach using URL param)
const url = new URL(window.location.href);
const addId = url.searchParams.get('add_compare');
if (addId) {
  let list = JSON.parse(sessionStorage.getItem('compare') || '[]');
  if (!list.includes(addId)) list.push(addId);
  if (list.length > 3) list = list.slice(-3);
  sessionStorage.setItem('compare', JSON.stringify(list));
  url.searchParams.delete('add_compare');
  url.searchParams.set('compare_msg', '1');
  history.replaceState({}, '', url);
}
</script>
</body>
</html>