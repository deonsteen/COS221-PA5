<?php

$currentPage = basename($_SERVER['PHP_SELF']);
$u = currentUser();
?>
<nav class="navbar">
  <a class="nav-logo" href="/COS221-PA5/traveller/dashboard.php">Trip<em>istry</em></a>
  <div class="nav-links">
    <a href="/COS221-PA5/traveller/packages.php"
      class="<?= $currentPage === 'packages.php' ? 'active' : '' ?>">Packages</a>
    <a href="/COS221-PA5/traveller/destinations.php"
      class="<?= $currentPage === 'destinations.php' ? 'active' : '' ?>">Destinations</a>
    <a href="/COS221-PA5/traveller/bookings.php" class="<?= $currentPage === 'bookings.php' ? 'active' : '' ?>">My
      Bookings</a>
    <a href="/COS221-PA5/traveller/profile.php"
      class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>"><?= htmlspecialchars($u['username']) ?></a>
    <a href="/COS221-PA5/logout.php" >Sign Out</a>
  </div>
</nav>