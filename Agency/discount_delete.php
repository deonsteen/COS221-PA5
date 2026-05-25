<?php
// Agency/discount_delete.php
// No UI — handles POST from delete modal and redirects back to discounts.php

session_start();

if (!isset($_SESSION['sub_id']) || $_SESSION['role'] !== 'agency') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: discounts.php');
    exit;
}

require_once '../db.php';
$pdo = getDB();

$agentID    = (int) $_SESSION['sub_id'];
$discountID = isset($_POST['DiscountID']) ? (int) $_POST['DiscountID'] : 0;

if ($discountID <= 0) {
    header('Location: discounts.php?error=delete_failed');
    exit;
}

try {
    // Verify the discount belongs to this agent's package before deleting
    $stmtCheck = $pdo->prepare("
        SELECT d.DiscountID
        FROM discounts d
        JOIN packages p ON p.PackID = d.PackID
        WHERE d.DiscountID = ? AND p.AgentID = ?
    ");
    $stmtCheck->execute([$discountID, $agentID]);

    if (!$stmtCheck->fetch()) {
        header('Location: discounts.php?error=delete_failed');
        exit;
    }

    // individual_discount is deleted automatically via ON DELETE CASCADE
    $stmtDel = $pdo->prepare("DELETE FROM discounts WHERE DiscountID = ?");
    $stmtDel->execute([$discountID]);

    header('Location: discounts.php?success=deleted');
    exit;

} catch (Exception $e) {
    header('Location: discounts.php?error=delete_failed');
    exit;
}
