<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$u = currentUser();
?>
<nav class="navbar">
  <a class="nav-logo" href="/agency/dashboard.php">Trip<em>istry</em> <span style="font-size:12px; background:var(--teal-pale); color:var(--teal); padding:2px 8px; border-radius:6px; font-family:'DM Sans',sans-serif; font-weight:600">Agency</span></a>
  <div class="nav-links">
    <a href="/agency/packages.php"  class="<?= $currentPage==='packages.php' ?'active':''?>">My Packages</a>
    <a href="/agency/clients.php"   class="<?= $currentPage==='clients.php'  ?'active':''?>">Clients</a>
    <a href="/agency/profile.php"   class="<?= $currentPage==='profile.php'  ?'active':''?>"><?= htmlspecialchars($u['username']) ?></a>
    <a href="/logout.php" class="btn-nav">Sign Out</a>
  </div>
</nav>
 