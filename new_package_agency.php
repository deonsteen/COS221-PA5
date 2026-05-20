<?php
require_once __DIR__ . '../db.php';
require_once __DIR__ . '../auth.php';
requireRole('agency');
$u = currentUser();
$db = getDB();
$agentId = $u['sub_id'];
 
$error = $success = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitise inputs
    $name     = trim($_POST['name']        ?? '');
    $dest     = trim($_POST['destination'] ?? '');
    $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT);
    $class    = in_array($_POST['class'] ?? '', ['Standard','Premium','Luxury']) ? $_POST['class'] : '';
    $price    = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $itiType  = trim($_POST['iti_type']   ?? '');
    $itiDate  = $_POST['iti_date']        ?? '';
    $itiActs  = trim($_POST['iti_acts']   ?? '');
    $itiDesc  = trim($_POST['iti_desc']   ?? '');
    // Discount
    $discDetails = trim($_POST['disc_details'] ?? '');
    $discFrom    = $_POST['disc_from'] ?? '';
    $discTo      = $_POST['disc_to']   ?? '';
 
    if (!$name || !$dest || !$duration || !$class || !$price) {
        $error = 'Please fill in all required package fields.';
    } elseif ($price < 0 || $duration < 1) {
        $error = 'Price and duration must be positive.';
    } else {
        try {
            $db->beginTransaction();
 
            // 1. Insert package
            $db->prepare("INSERT INTO packages (AgentID, Price) VALUES (?,?)")->execute([$agentId, $price]);
            $packId = $db->lastInsertId();
 
            // 2. Insert packinfo
            $db->prepare("INSERT INTO packinfo (PackID, Name, Destination, Duration, Class) VALUES (?,?,?,?,?)")
               ->execute([$packId, $name, $dest, $duration, $class]);
            $infoId = $db->lastInsertId();
 
            // 3. Insert itinerary if provided
            if ($itiType && $itiDate && $itiActs) {
                $db->prepare("INSERT INTO itinerary (InfoID,Type,DateTime,Activities,Description) VALUES (?,?,?,?,?)")
                   ->execute([$infoId, $itiType, $itiDate, $itiActs, $itiDesc]);
            }
 
            // 4. Insert discount if provided
            if ($discDetails && $discFrom && $discTo && $discTo > $discFrom) {
                $db->prepare("INSERT INTO discounts (PackID,`From`,`To`,Details) VALUES (?,?,?,?)")
                   ->execute([$packId, $discFrom, $discTo, $discDetails]);
            }
 
            $db->commit();
            $success = 'Package created successfully!';
            header("Location: packages.php?created=1");
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $error = 'Failed to create package. Please try again.';
        }
    }
}
 
// Destinations for datalist
$dests = $db->query("SELECT Name, City FROM destinations ORDER BY Name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Package – Tripistry</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/nav_agency.php'; ?>
<div class="container page-wrap">
  <div class="sidebar-layout">
    <?php include '../includes/sidebar_agency.php'; ?>
    <div>
      <div class="page-header"><h1>Create New Package</h1></div>
 
      <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
 
      <form method="POST">
        <!-- Package Info -->
        <div class="card" style="margin-bottom:20px">
          <div class="card-body">
            <h2 style="font-family:'Fraunces',serif; font-size:20px; margin-bottom:16px">📦 Package Details</h2>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
              <div class="form-group" style="grid-column:1/-1">
                <label>Package Name *</label>
                <input class="form-control" type="text" name="name" required maxlength="150" placeholder="e.g. Paris Romantic Escape">
              </div>
              <div class="form-group" style="grid-column:1/-1">
                <label>Destination *</label>
                <input class="form-control" type="text" name="destination" required list="dest-list" placeholder="e.g. Paris, France">
                <datalist id="dest-list">
                  <?php foreach ($dests as $d): ?>
                  <option value="<?= htmlspecialchars($d['Name'].', '.$d['City']) ?>">
                  <?php endforeach; ?>
                </datalist>
              </div>
              <div class="form-group">
                <label>Duration (days) *</label>
                <input class="form-control" type="number" name="duration" required min="1" max="365">
              </div>
              <div class="form-group">
                <label>Class *</label>
                <select class="form-control" name="class" required>
                  <option value="">Select…</option>
                  <option>Standard</option><option>Premium</option><option>Luxury</option>
                </select>
              </div>
              <div class="form-group" style="grid-column:1/-1">
                <label>Price (R) *</label>
                <input class="form-control" type="number" name="price" required min="0" step="0.01" placeholder="e.g. 25000">
              </div>
            </div>
          </div>
        </div>
 
        <!-- Itinerary -->
        <div class="card" style="margin-bottom:20px">
          <div class="card-body">
            <h2 style="font-family:'Fraunces',serif; font-size:20px; margin-bottom:16px">🗺️ Itinerary (optional)</h2>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
              <div class="form-group">
                <label>Type</label>
                <select class="form-control" name="iti_type">
                  <option value="">None</option>
                  <option>Tour</option><option>Safari</option><option>Leisure</option><option>Food</option><option>Adventure</option>
                </select>
              </div>
              <div class="form-group">
                <label>Date & Time</label>
                <input class="form-control" type="datetime-local" name="iti_date">
              </div>
              <div class="form-group" style="grid-column:1/-1">
                <label>Activities</label>
                <input class="form-control" type="text" name="iti_acts" placeholder="e.g. Eiffel Tower, Seine River Cruise">
              </div>
              <div class="form-group" style="grid-column:1/-1">
                <label>Description</label>
                <textarea class="form-control" name="iti_desc" placeholder="Describe the day's activities…"></textarea>
              </div>
            </div>
          </div>
        </div>
 
        <!-- Discount -->
        <div class="card" style="margin-bottom:20px">
          <div class="card-body">
            <h2 style="font-family:'Fraunces',serif; font-size:20px; margin-bottom:16px">🏷️ Discount (optional)</h2>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px">
              <div class="form-group" style="grid-column:1/-1">
                <label>Discount Details</label>
                <input class="form-control" type="text" name="disc_details" placeholder="e.g. 15% early bird discount">
              </div>
              <div class="form-group">
                <label>Valid From</label>
                <input class="form-control" type="date" name="disc_from">
              </div>
              <div class="form-group">
                <label>Valid To</label>
                <input class="form-control" type="date" name="disc_to">
              </div>
            </div>
          </div>
        </div>
 
        <div style="display:flex; gap:12px">
          <button type="submit" class="btn btn-primary">Create Package</button>
          <a href="packages.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>