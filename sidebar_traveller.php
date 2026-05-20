<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
  <div class="sidebar-title">Traveller</div>
  <a href="/traveller/dashboard.php"    class="<?= $currentPage==='dashboard.php'    ?'active':''?>">🏠 Dashboard</a>
  <a href="/traveller/packages.php"     class="<?= $currentPage==='packages.php'     ?'active':''?>">🧳 Browse Packages</a>
  <a href="/traveller/compare.php"      class="<?= $currentPage==='compare.php'      ?'active':''?>">⚖️ Compare</a>
  <a href="/traveller/destinations.php" class="<?= $currentPage==='destinations.php' ?'active':''?>">🌍 Destinations</a>
  <a href="/traveller/flights.php"      class="<?= $currentPage==='flights.php'      ?'active':''?>">✈️ Flights</a>
  <a href="/traveller/accommodation.php"class="<?= $currentPage==='accommodation.php'?'active':''?>">🏨 Accommodation</a>
  <a href="/traveller/attractions.php"  class="<?= $currentPage==='attractions.php'  ?'active':''?>">🎡 Attractions</a>
  <a href="/traveller/restaurants.php"  class="<?= $currentPage==='restaurants.php'  ?'active':''?>">🍽️ Restaurants</a>
  <a href="/traveller/bookings.php"     class="<?= $currentPage==='bookings.php'      ?'active':''?>">📋 My Bookings</a>
  <a href="/traveller/profile.php"      class="<?= $currentPage==='profile.php'       ?'active':''?>">👤 Profile</a>
</div>
 