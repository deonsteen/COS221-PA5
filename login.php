<?php
// login.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
 
if (isLoggedIn()) {
    header('Location: ' . ($_SESSION['role'] === 'agency' ? 'Agency/agency_dashboard.php' : 'traveller/dashboard.php'));
    exit;
}
 
$error   = '';
$success = '';
$mode    = $_GET['mode'] ?? 'login';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
 
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
 
        if (!$username || !$password) {
            $error = 'Please enter your username and password.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare("SELECT UserID, Username, Password FROM users WHERE Username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
 
            if ($user && password_verify($password, $user['Password'])) {
                $isTrav = $db->prepare("SELECT TravID FROM travellers WHERE UserID = ?");
                $isTrav->execute([$user['UserID']]);
                $trav = $isTrav->fetch();
 
                $isAg = $db->prepare("SELECT AgentID FROM agencies WHERE UserID = ?");
                $isAg->execute([$user['UserID']]);
                $ag = $isAg->fetch();
 
                if (!$trav && !$ag) {
                    $error = 'Account not properly set up. Contact support.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id']  = $user['UserID'];
                    $_SESSION['username'] = $user['Username'];
 
                    if ($trav) {
                        $_SESSION['role']   = 'traveller';
                        $_SESSION['sub_id'] = $trav['TravID'];
                        header('Location: traveller/dashboard.php');
                    } else {
                        $_SESSION['role']   = 'agency';
                        $_SESSION['sub_id'] = $ag['AgentID'];
                        header('Location: agency/dashboard.php');
                    }
                    exit;
                }
            } else {
                $error = 'Incorrect username or password.';
            }
        }
    }
 
    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $email    = trim($_POST['email'] ?? '');
        $role     = in_array($_POST['role'] ?? '', ['traveller','agency']) ? $_POST['role'] : 'traveller';
        $name     = trim($_POST['name'] ?? '');
        $dob      = trim($_POST['dob'] ?? '');
 
        if (!$username || !$password || !$email) {
            $error = 'Username, email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($role === 'traveller' && !$dob) {
            $error = 'Date of birth is required for travellers.';
        } elseif ($role === 'agency' && !$name) {
            $error = 'Agency name is required.';
        } else {
            $db = null;
            try {
                $db   = getDB();
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->beginTransaction();
                $stmt = $db->prepare("INSERT INTO users (Username, Password) VALUES (?, ?)");
                $stmt->execute([$username, $hash]);
                $uid = $db->lastInsertId();
                $db->prepare("INSERT INTO contactdetails (UserID, Email) VALUES (?, ?)")
                   ->execute([$uid, $email]);
                if ($role === 'traveller') {
                    $db->prepare("INSERT INTO travellers (UserID, DoB) VALUES (?, ?)")
                       ->execute([$uid, $dob]);
                } else {
                    $db->prepare("INSERT INTO agencies (UserID, Name) VALUES (?, ?)")
                       ->execute([$uid, $name]);
                }
                $db->commit();
                $success = 'Account created successfully! Please sign in.';
                $mode    = 'login';
            } catch (PDOException $e) {
                if ($db) $db->rollBack();
                $error = 'DB Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tripistry – <?= $mode === 'register' ? 'Create Account' : 'Sign In' ?></title>
<link rel="stylesheet" href="css/login.css">
</head>
<body>

<!-- LEFT — slideshow with Tripistry brand overlay -->
<div class="hero">

  <div class="slide active">
    <img src="img/London.jpg" alt="London">
    <div class="slide-overlay"></div>
  </div>
  <div class="slide">
    <img src="img/vatican3.jpg" alt="Vatican City">
    <div class="slide-overlay"></div>
  </div>
  <div class="slide">
    <img src="img/Netherlands.jpg" alt="Amsterdam">
    <div class="slide-overlay"></div>
  </div>
  <div class="slide">
    <img src="img/Durham.jpg" alt="Durham">
    <div class="slide-overlay"></div>
  </div>
  <div class="slide">
    <img src="img/Japan.jpg" alt="Japan">
    <div class="slide-overlay"></div>
  </div>

  <div class="slide-location active">London, Great Britain</div>
  <div class="slide-location">Vatican City</div>
  <div class="slide-location">Amsterdam, Holland</div>
  <div class="slide-location">Durham, Great Britain</div>
  <div class="slide-location">Mount Fuji, Japan</div>

  <div class="hero-content">
    <div class="hero-brand">Trip<em>istry</em></div>
    <p class="hero-sub">Browse and compare travel packages from trusted agencies. Your next adventure starts here.</p>
  
    <div class="slide-dots">
      <button class="dot active" aria-label="Slide 1"></button>
      <button class="dot" aria-label="Slide 2"></button>
      <button class="dot" aria-label="Slide 3"></button>
      <button class="dot" aria-label="Slide 4"></button>
      <button class="dot" aria-label="Slide 5"></button>
    </div>
  </div>

</div>

<!-- RIGHT — form -->
<div class="form-side">
  <div class="form-wrap">

    <?php if ($mode === 'login'): ?>
    <div class="form-title">Welcome back</div>
    <div class="form-subtitle">Sign in to your Tripistry account</div>
    <?php else: ?>
    <div class="form-title">Create account</div>
    <div class="form-subtitle">Join Tripistry today — it's free</div>
    <?php endif; ?>

    <div class="tabs">
      <a href="?mode=login"    class="tab <?= $mode==='login'    ? 'active' : '' ?>">Sign In</a>
      <a href="?mode=register" class="tab <?= $mode==='register' ? 'active' : '' ?>">Register</a>
    </div>

    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if ($mode === 'login'): ?>
    <form method="POST">
      <input type="hidden" name="action" value="login">
      <div class="form-group">
        <label>Username</label>
        <input class="form-control" type="text" name="username" required autocomplete="username">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input class="form-control" type="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-primary">Sign In</button>
    </form>

    <?php else: ?>
    <form method="POST" id="regForm">
      <input type="hidden" name="action" value="register">
      <div class="form-group">
        <label>I am a…</label>
        <div class="role-grid">
          <input type="radio" name="role" value="traveller" id="r_trav" class="role-radio" checked>
          <label for="r_trav" class="role-label"><span class="icon">🧳</span>Traveller</label>
          <input type="radio" name="role" value="agency" id="r_agency" class="role-radio">
          <label for="r_agency" class="role-label"><span class="icon">🏢</span>Agency</label>
        </div>
      </div>
      <div class="form-group">
        <label>Username *</label>
        <input class="form-control" type="text" name="username" required minlength="3" maxlength="50">
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input class="form-control" type="email" name="email" required>
      </div>
      <div class="form-group" id="field-dob">
        <label>Date of Birth *</label>
        <input class="form-control" type="date" name="dob" max="<?= date('Y-m-d', strtotime('-18 years')) ?>">
      </div>
      <div class="form-group" id="field-name" style="display:none">
        <label>Agency Name *</label>
        <input class="form-control" type="text" name="name" maxlength="100">
      </div>
      <div class="form-group">
        <label>Password * <span class="text-muted text-sm">(min 8 characters)</span></label>
        <input class="form-control" type="password" name="password" required minlength="8">
      </div>
      <button type="submit" class="btn-primary">Create Account</button>
    </form>
    <?php endif; ?>

  </div>
</div>

<script>
document.querySelectorAll('.role-radio').forEach(r => {
  r.addEventListener('change', () => {
    const isAgency = document.getElementById('r_agency').checked;
    document.getElementById('field-dob').style.display  = isAgency ? 'none' : '';
    document.getElementById('field-name').style.display = isAgency ? ''     : 'none';
  });
});

const slides    = document.querySelectorAll('.hero .slide');
const locations = document.querySelectorAll('.hero .slide-location');
const dots      = document.querySelectorAll('.dot');
let current     = 0;

function goTo(n) {
  slides[current].classList.remove('active');
  locations[current].classList.remove('active');
  dots[current].classList.remove('active');
  current = (n + slides.length) % slides.length;
  slides[current].classList.add('active');
  locations[current].classList.add('active');
  dots[current].classList.add('active');
}

dots.forEach((dot, i) => dot.addEventListener('click', () => goTo(i)));
setInterval(() => goTo(current + 1), 5000);
</script>
</body>
</html>