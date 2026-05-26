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
                        $_SESSION['role']      = 'agency';
                        $_SESSION['AgentID']   = $ag['AgentID'];
                        $_SESSION['sub_id']    = $ag['AgentID'];

                        // Fetch agency name
                        $nameStmt = $db->prepare("SELECT Name FROM agencies WHERE AgentID = ?");
                        $nameStmt->execute([$ag['AgentID']]);
                        $agencyRow = $nameStmt->fetch();
                        $_SESSION['AgencyName'] = $agencyRow['Name'] ?? 'Agency';

                        header('Location: Agency/agency_dashboard.php');
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
