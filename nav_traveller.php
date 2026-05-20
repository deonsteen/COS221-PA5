<?php

$currentPage = basename($_SERVER['PHP_SELF']);
$u = currentUser();
?>
<nav class="navbar">
  <a class="nav-logo" href="../traveller/dashboard.php">Trip<em>istry</em></a>
  <div class="nav-links">
    <a href="../traveller/packages.php"     class="<?= $currentPage==='packages.php'     ? 'active':'' ?>">Packages</a>
    <a href="../traveller/destinations.php" class="<?= $currentPage==='destinations.php' ? 'active':'' ?>">Destinations</a>
    <a href="../traveller/bookings.php"     class="<?= $currentPage==='bookings.php'     ? 'active':'' ?>">My Bookings</a>
    <a href="../traveller/profile.php"      class="<?= $currentPage==='profile.php'      ? 'active':'' ?>"><?= htmlspecialchars($u['username']) ?></a>
    <a href="../logout.php" class="btn-nav">Sign Out</a>
  </div>
</nav>