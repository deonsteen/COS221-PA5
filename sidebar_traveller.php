<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
  <div class="sidebar-title">Traveller</div>
  <a href="/COS221-PA5/traveller/dashboard.php"                 class="<?= $currentPage==='dashboard.php'                 ?'active':''?>">🏠 Dashboard</a>
  <a href="/COS221-PA5/traveller/Traveller_Loby.php"            class="<?= $currentPage==='Traveller_Loby.php'            ?'active':''?>">🎫 My Lobby</a>
  <a href="/COS221-PA5/traveller/packages.php"                  class="<?= $currentPage==='packages.php'                  ?'active':''?>">🧳 Browse Packages</a>
  <a href="/COS221-PA5/traveller/compare_packages.php"          class="<?= $currentPage==='compare_packages.php'          ?'active':''?>">⚖️ Compare</a>
  <a href="/COS221-PA5/traveller/destinations.php"              class="<?= $currentPage==='destinations.php'              ?'active':''?>">🌍 Destinations</a>
  <a href="/COS221-PA5/traveller/flights_accom_attractions.php" class="<?= $currentPage==='flights_accom_attractions.php' ?'active':''?>">✈️ Flights</a>
  <a href="/COS221-PA5/traveller/attractions.php"               class="<?= $currentPage==='attractions.php'               ?'active':''?>">🎡 Attractions</a>
  <a href="/COS221-PA5/traveller/restaurants.php"               class="<?= $currentPage==='restaurants.php'               ?'active':''?>">🍽️ Restaurants</a>
  <a href="/COS221-PA5/traveller/bookings.php"                  class="<?= $currentPage==='bookings.php'                  ?'active':''?>">📋 My Bookings</a>
  <a href="/COS221-PA5/traveller/profile.php"                   class="<?= $currentPage==='profile.php'                   ?'active':''?>">👤 Profile</a>
</div>
 