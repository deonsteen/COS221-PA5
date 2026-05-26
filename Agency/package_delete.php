<?php
// Agency/package_delete.php
// This file has no UI — it only handles the POST request from the delete modal
// and redirects back to packages.php with a success or error message.

session_start();

// ── Auth guard ──
if (!isset($_SESSION['AgentID']) || $_SESSION['role'] !== 'agency') {
    header('Location: ../login.php');
    exit;
}

// ── Only allow POST ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: packages.php');
    exit;
}

require_once '../db.php';
$pdo = getDB();

$agentID = (int) $_SESSION['AgentID'];
$packID  = isset($_POST['PackID']) ? (int) $_POST['PackID'] : 0;

if ($packID <= 0) {
    header('Location: packages.php?error=delete_failed');
    exit;
}

try {
    // Verify the package belongs to this agent before deleting
    $stmtCheck = $pdo->prepare("SELECT PackID FROM packages WHERE PackID = ? AND AgentID = ?");
    $stmtCheck->execute([$packID, $agentID]);

    if (!$stmtCheck->fetch()) {
        // Package doesn't exist or doesn't belong to this agent
        header('Location: packages.php?error=delete_failed');
        exit;
    }

    // Delete the package
    // packinfo is deleted automatically via ON DELETE CASCADE
    $stmtDel = $pdo->prepare("DELETE FROM packages WHERE PackID = ? AND AgentID = ?");
    $stmtDel->execute([$packID, $agentID]);

    header('Location: packages.php?success=deleted');
    exit;

} catch (Exception $e) {
    header('Location: packages.php?error=delete_failed');
    exit;
}
