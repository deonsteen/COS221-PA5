<?php
// login.php — place this in Task 5/ (root)
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
 
if (isLoggedIn()) {
    header('Location: ' . ($_SESSION['role'] === 'agency' ? 'agency/dashboard.php' : 'traveller/dashboard.php'));
    exit;
}
 
$error   = '';
$success = '';
$mode    = $_GET['mode'] ?? 'login';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
 
    // ── LOGIN ──
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
 
    // ── REGISTER ──
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
                // Shows the real error — remove this line after testing works
                $error = 'DB Error: ' . $e->getMessage();
                // Uncomment the line below and remove the line above once registration works:
                // $error = str_contains($e->getMessage(), 'Duplicate') ? 'Username or email already exists.' : 'Registration failed. Please try again.';
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
<link rel="stylesheet" href="css/style.css">
<style>
body { display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh; background: var(--sand); }
 
.hero {
  background: linear-gradient(155deg, #0a7373 0%, #065050 55%, #032828 100%);
  padding: 64px 56px;
  display: flex; flex-direction: column; justify-content: center;
  position: relative; overflow: hidden;
}
.hero::before {
  content: '';
  position: absolute; inset: 0; opacity: .06;
  background-image: radial-gradient(circle, #fff 1px, transparent 1px);
  background-size: 28px 28px;
}
.hero-brand { font-family: 'Fraunces', serif; font-size: 52px; font-weight: 700; color: #fff; letter-spacing: -2px; line-height: 1; margin-bottom: 20px; }
.hero-brand em { font-style: italic; color: #6ee7e7; }
.hero-sub { color: rgba(255,255,255,.7); font-size: 17px; line-height: 1.7; max-width: 380px; margin-bottom: 48px; }
.dest-pills { display: flex; flex-wrap: wrap; gap: 10px; }
.dest-pill { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); color: #fff; padding: 6px 16px; border-radius: 99px; font-size: 13px; }
 
.form-side { display: flex; align-items: center; justify-content: center; padding: 48px 56px; }
.form-wrap { width: 100%; max-width: 400px; }
.form-title { font-family: 'Fraunces', serif; font-size: 30px; font-weight: 500; margin-bottom: 6px; }
.form-subtitle { color: var(--muted); font-size: 14px; margin-bottom: 28px; }
 
.tabs { display: flex; border-bottom: 2px solid var(--border); margin-bottom: 28px; }
.tab { flex: 1; text-align: center; padding: 10px; font-size: 14px; font-weight: 500; color: var(--muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; text-decoration: none; transition: color .15s, border-color .15s; }
.tab.active { color: var(--teal); border-bottom-color: var(--teal); }
 
.role-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 18px; }
.role-radio { display: none; }
.role-label { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 14px 10px; border: 1.5px solid var(--border); border-radius: var(--radius); cursor: pointer; font-size: 13px; font-weight: 500; color: var(--muted); transition: all .15s; }
.role-label .icon { font-size: 26px; }
.role-radio:checked + .role-label { border-color: var(--teal); color: var(--teal); background: var(--teal-pale); }
 
@media (max-width: 768px) {
  body { grid-template-columns: 1fr; }
  .hero { display: none; }
  .form-side { padding: 32px 24px; }
}
</style>
</head>
<body>
 
<div class="hero">
  <div class="hero-brand">Trip<em>istry</em></div>
  <p class="hero-sub">Browse and compare travel packages from trusted agencies. Your next adventure starts here.</p>
  <div class="dest-pills">
    <span class="dest-pill">✈️ Bali</span>
    <span class="dest-pill">🏛 Rome</span>
    <span class="dest-pill">🌴 Maldives</span>
    <span class="dest-pill">🗼 Paris</span>
    <span class="dest-pill">🦁 Serengeti</span>
    <span class="dest-pill">🏙 Tokyo</span>
  </div>
</div>
 
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
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px">Sign In</button>
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
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px">Create Account</button>
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
</script>
</body>
</html>