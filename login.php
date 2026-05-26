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
 
}
