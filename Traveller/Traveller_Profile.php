<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
requireRole('traveller');
$u = currentUser();
$db = getDB();

$success = '';
$error = '';

// Fetch current user info
$stmt = $db->prepare("
    SELECT u.UserID, u.Username,
           cd.Email,
           t.DoB,
           t.TravID
    FROM users u
    JOIN travellers t  ON t.UserID  = u.UserID
    LEFT JOIN contactdetails cd ON cd.UserID = u.UserID
    WHERE u.UserID = ?
");
$stmt->execute([$u['user_id']]);
$profile = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newPhone = trim($_POST['phone'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (!$newUsername || !$newEmail) {
        $error = 'Username and email are required.';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($newPassword && strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($newPassword && $newPassword !== $confirmPass) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $db->beginTransaction();

            // Update username
            $db->prepare("UPDATE users SET Username = ? WHERE UserID = ?")
                ->execute([$newUsername, $u['user_id']]);

            // Update password if provided
            if ($newPassword) {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare("UPDATE users SET Password = ? WHERE UserID = ?")
                    ->execute([$hash, $u['user_id']]);
            }

            // Update or insert contact details
            $existing = $db->prepare("SELECT CDID FROM contactdetails WHERE UserID = ?");
            $existing->execute([$u['user_id']]);
            if ($existing->fetch()) {
                $db->prepare("UPDATE contactdetails SET Email = ? WHERE UserID = ?")
                    ->execute([$newEmail, $u['user_id']]);
            } else {
                $db->prepare("INSERT INTO contactdetails (UserID, Email, Number) VALUES (?, ?, ?)")
                    ->execute([$u['user_id'], $newEmail]);
            }

            $db->commit();

            // Refresh session username
            $_SESSION['username'] = $newUsername;
            $u['username'] = $newUsername;

            // Re-fetch profile
            $stmt->execute([$u['user_id']]);
            $profile = $stmt->fetch();

            $success = 'Profile updated successfully!';
        } catch (PDOException $e) {
            $db->rollBack();
            $error = 'DB Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – Tripistry</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--teal-d), var(--teal));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #fff;
            font-family: 'Fraunces', serif;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 32px;
            padding: 28px;
            background: linear-gradient(135deg, var(--teal-d), var(--teal));
            border-radius: var(--radius-lg);
            color: #fff;
        }

        .profile-header-info h2 {
            font-family: 'Fraunces', serif;
            font-size: 24px;
            margin-bottom: 4px;
        }

        .profile-header-info p {
            opacity: 0.8;
            font-size: 14px;
        }

        .profile-avatar-large {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #fff;
            font-family: 'Fraunces', serif;
            font-weight: 700;
            flex-shrink: 0;
            border: 3px solid rgba(255, 255, 255, 0.4);
        }

        .section-title {
            font-family: 'Fraunces', serif;
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--ink);
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }
        }

        .password-section {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../nav_traveller.php'; ?>

    <div class="container page-wrap">
        <div class="sidebar-layout">
            <?php include __DIR__ . '/../sidebar_traveller.php'; ?>

            <div>
                <!-- Profile Header Banner -->
                <div class="profile-header">
                    <div class="profile-avatar-large">
                        <?= strtoupper(substr($profile['Username'] ?? 'T', 0, 1)) ?>
                    </div>
                    <div class="profile-header-info">
                        <h2><?= htmlspecialchars($profile['Username']) ?></h2>
                        <p>✉️ <?= htmlspecialchars($profile['Email'] ?? 'No email set') ?></p>
                        <?php if ($profile['DoB']): ?>
                            <p>🎂 <?= date('d M Y', strtotime($profile['DoB'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin-bottom:20px"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom:20px"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Edit Form -->
                <div class="card">
                    <div class="card-body">
                        <div class="section-title">Edit Profile</div>

                        <form method="POST">
                            <!-- Account Info -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Username *</label>
                                    <input class="form-control" type="text" name="username"
                                        value="<?= htmlspecialchars($profile['Username']) ?>" required minlength="3"
                                        maxlength="50">
                                </div>
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input class="form-control" type="email" name="email"
                                        value="<?= htmlspecialchars($profile['Email'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input class="form-control" type="text"
                                        value="<?= $profile['DoB'] ? date('d M Y', strtotime($profile['DoB'])) : 'Not set' ?>"
                                        disabled style="background:var(--sand); color:var(--ink-2)">
                                    <small class="text-muted">Date of birth cannot be changed.</small>
                                </div>
                            </div>

                            <!-- Change Password -->
                            <div class="password-section">
                                <div class="section-title" style="margin-bottom:16px">Change Password <span
                                        class="text-muted text-sm"
                                        style="font-family:inherit; font-size:13px; font-weight:400">(leave blank to
                                        keep current)</span></div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input class="form-control" type="password" name="password"
                                            placeholder="Min 8 characters" minlength="8">
                                    </div>
                                    <div class="form-group">
                                        <label>Confirm New Password</label>
                                        <input class="form-control" type="password" name="confirm_password"
                                            placeholder="Repeat new password">
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; gap:12px; margin-top:24px">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>

</html>