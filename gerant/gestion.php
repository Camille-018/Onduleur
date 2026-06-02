<?php
// management.php: user management page reserved for the manager
require_once __DIR__ . '/../auth/authCheck.php';
include __DIR__ . '/../style/navbar.php';

// Verify that the user is the manager
if ($_SESSION['mail'] !== GESTIONNAIRE_EMAIL) {
    echo "<script>
            alert('Accès refusé : seuls les gestionnaires peuvent accéder à cette page.');
            window.location.href = '../index.php';
          </script>";
    exit;
}

// Process role and status modifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_POST['user_id'] ?? 0;
    $action = $_POST['action'] ?? '';

    if ($action === 'refuse') {
        // Mark the user as refused
        $stmt = $pdo->prepare("UPDATE users SET status = 'refused' WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $_SESSION['message'] = "Utilisateur marqué comme refusé.";
        header('Location: gestion.php?order=' . urlencode($_GET['order'] ?? 'default'));
        exit;
    } elseif ($action === 'reactivate') {
        // Reactivate the user (set to pending)
        $stmt = $pdo->prepare("UPDATE users SET status = 'pending' WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $_SESSION['message'] = "Utilisateur réactivé.";
        header('Location: gestion.php?order=' . urlencode($_GET['order'] ?? 'default'));
        exit;
    } else {
        // Modify the role and status
        $newRole = $_POST['new_role'] ?? '';
        $newStatus = $_POST['new_status'] ?? '';

        if ($userId > 0 && in_array($newRole, ['admin', 'user']) && in_array($newStatus, ['active', 'pending'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = :role, status = :status WHERE id = :id");
            $stmt->execute([
                ':role' => $newRole,
                ':status' => $newStatus,
                ':id' => $userId
            ]);
            $_SESSION['message'] = "Utilisateur mis à jour avec succès.";
            header('Location: gestion.php?order=' . urlencode($_GET['order'] ?? 'default'));
            exit;
        }
    }
}

// Retrieve order and pagination parameters
$order = $_GET['order'] ?? 'default';
$validOrders = ['default', 'refused', 'pending', 'admin', 'user'];
if (!in_array($order, $validOrders, true)) {
    $order = 'default';
}

$itemsPerPage = 15;
$refusedPage = isset($_GET['refused_page']) ? max(1, (int)$_GET['refused_page']) : 1;
$pendingPage = isset($_GET['pending_page']) ? max(1, (int)$_GET['pending_page']) : 1;
$adminPage = isset($_GET['admin_page']) ? max(1, (int)$_GET['admin_page']) : 1;
$userPage = isset($_GET['user_page']) ? max(1, (int)$_GET['user_page']) : 1;

// Count refused users
$refusedCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'refused'");
$refusedCount = $refusedCountStmt->fetchColumn();

// Count pending users
$pendingCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'");
$pendingCount = $pendingCountStmt->fetchColumn();

// Count administrators and users
$adminsCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status NOT IN ('pending', 'refused')");
$adminsCount = $adminsCountStmt->fetchColumn();
$usersCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status NOT IN ('pending', 'refused')");
$usersCount = $usersCountStmt->fetchColumn();

$refusedPages = ceil($refusedCount / $itemsPerPage) ?: 1;
$pendingPages = ceil($pendingCount / $itemsPerPage) ?: 1;
$adminsPages = ceil($adminsCount / $itemsPerPage) ?: 1;
$usersPages = ceil($usersCount / $itemsPerPage) ?: 1;

$refusedPage = min($refusedPage, $refusedPages);
$pendingPage = min($pendingPage, $pendingPages);
$adminPage = min($adminPage, $adminsPages);
$userPage = min($userPage, $usersPages);

$refusedOffset = ($refusedPage - 1) * $itemsPerPage;
$pendingOffset = ($pendingPage - 1) * $itemsPerPage;
$adminOffset = ($adminPage - 1) * $itemsPerPage;
$userOffset = ($userPage - 1) * $itemsPerPage;

// Retrieve refused users
$refusedStmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE status = 'refused'
    ORDER BY username ASC
    LIMIT :limit OFFSET :offset
");
$refusedStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$refusedStmt->bindValue(':offset', $refusedOffset, PDO::PARAM_INT);
$refusedStmt->execute();
$refuseds = $refusedStmt->fetchAll();

// Retrieve pending users
$pendingStmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE status = 'pending'
    ORDER BY username ASC
    LIMIT :limit OFFSET :offset
");
$pendingStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$pendingStmt->bindValue(':offset', $pendingOffset, PDO::PARAM_INT);
$pendingStmt->execute();
$pendings = $pendingStmt->fetchAll();

// Retrieve administrators
$adminsStmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE role = 'admin' AND status NOT IN ('pending', 'refused')
    ORDER BY username ASC
    LIMIT :limit OFFSET :offset
");
$adminsStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$adminsStmt->bindValue(':offset', $adminOffset, PDO::PARAM_INT);
$adminsStmt->execute();
$admins = $adminsStmt->fetchAll();

// Retrieve users
$usersStmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE role = 'user' AND status NOT IN ('pending', 'refused')
    ORDER BY username ASC
    LIMIT :limit OFFSET :offset
");
$usersStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$usersStmt->bindValue(':offset', $userOffset, PDO::PARAM_INT);
$usersStmt->execute();
$users = $usersStmt->fetchAll();

$sectionOrders = [
    'default' => ['admin', 'user', 'pending', 'refused'],
    'refused' => ['refused', 'pending', 'admin', 'user'],
    'pending' => ['pending', 'refused', 'admin', 'user'],
    'admin' => ['admin', 'user', 'pending', 'refused'],
    'user' => ['user', 'admin', 'pending', 'refused'],
];
$sectionOrder = $sectionOrders[$order];

// Session message
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="stylesheet" href="../style/style.css">
    <title>UPS - Gestion des Utilisateurs</title>
</head>
<body>
    <h1 class="title">Gestion des Utilisateurs</h1>
    <hr>

    <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Section order selector -->
    <div class="filter-actions">
        <form method="GET" class="order-form">
            <label for="order" class="order-label">Ordre des sections :</label>
            <select id="order" name="order" onchange="this.form.submit()" class="order-select">
                <option value="default" <?= $order === 'default' ? 'selected' : '' ?>>Admin, User, En attente, Refusés</option>
                <option value="refused" <?= $order === 'refused' ? 'selected' : '' ?>>Refusés, En attente, Admin, User</option>
                <option value="pending" <?= $order === 'pending' ? 'selected' : '' ?>>En attente, Refusés, Admin, User</option>
                <option value="admin" <?= $order === 'admin' ? 'selected' : '' ?>>Admin, User, En attente, Refusés</option>
                <option value="user" <?= $order === 'user' ? 'selected' : '' ?>>User, Admin, En attente, Refusés</option>
            </select>
        </form>
    </div>

    <?php foreach ($sectionOrder as $section): ?>
        <?php if ($section === 'refused'): ?>
            <h2 class="section-title">❌ Refusés (<?= $refusedCount ?>)</h2>
            <?php if (!empty($refuseds)): ?>
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($refuseds as $refused): ?>
                            <tr>
                                <td><?= htmlspecialchars($refused['username']) ?></td>
                                <td><?= htmlspecialchars($refused['mail']) ?></td>
                                <td><span class="role-<?= $refused['role'] ?>"><?= htmlspecialchars($refused['role']) ?></span></td>
                                <td><span class="status-refused"><?= htmlspecialchars($refused['status']) ?></span></td>
                                <td>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="user_id" value="<?= $refused['id'] ?>">
                                        <input type="hidden" name="action" value="reactivate">
                                        <button type="submit" class="action-button">Réactiver</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($refusedPages > 1): ?>
                    <div class="pagination">
                        <?php if ($refusedPage > 1): ?>
                            <a href="?order=<?= $order ?>&refused_page=1">&laquo;&laquo;</a>
                            <a href="?order=<?= $order ?>&refused_page=<?= $refusedPage - 1 ?>">&laquo;</a>
                        <?php endif; ?>

                        <span>Page <?= $refusedPage ?> / <?= $refusedPages ?></span>

                        <?php if ($refusedPage < $refusedPages): ?>
                            <a href="?order=<?= $order ?>&refused_page=<?= $refusedPage + 1 ?>">&raquo;</a>
                            <a href="?order=<?= $order ?>&refused_page=<?= $refusedPages ?>">&raquo;&raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p>Aucun utilisateur refusé.</p>
            <?php endif; ?>
        <?php elseif ($section === 'pending'): ?>
            <h2 class="section-title">⏳ En Attente (<?= $pendingCount ?>)</h2>
            <?php if (!empty($pendings)): ?>
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendings as $pending): ?>
                            <tr>
                                <td><?= htmlspecialchars($pending['username']) ?></td>
                                <td><?= htmlspecialchars($pending['mail']) ?></td>
                                <td><span class="role-<?= $pending['role'] ?>"><?= htmlspecialchars($pending['role']) ?></span></td>
                                <td><span class="status-<?= $pending['status'] ?>"><?= htmlspecialchars($pending['status']) ?></span></td>
                                <td>
                                    <div class="action-group">
                                        <form method="POST" class="edit-form">
                                            <input type="hidden" name="user_id" value="<?= $pending['id'] ?>">
                                            <select name="new_role">
                                                <option value="admin" <?= $pending['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                <option value="user" <?= $pending['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                            </select>
                                            <select name="new_status">
                                                <option value="active" <?= $pending['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="pending" <?= $pending['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            </select>
                                            <button type="submit">Mettre à jour</button>
                                        </form>
                                        <form method="POST" class="inline-form">
                                            <input type="hidden" name="user_id" value="<?= $pending['id'] ?>">
                                            <input type="hidden" name="action" value="refuse">
                                            <button type="submit" class="action-button">Refuser</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($pendingPages > 1): ?>
                    <div class="pagination">
                        <?php if ($pendingPage > 1): ?>
                            <a href="?order=<?= $order ?>&pending_page=1">&laquo;&laquo;</a>
                            <a href="?order=<?= $order ?>&pending_page=<?= $pendingPage - 1 ?>">&laquo;</a>
                        <?php endif; ?>

                        <span>Page <?= $pendingPage ?> / <?= $pendingPages ?></span>

                        <?php if ($pendingPage < $pendingPages): ?>
                            <a href="?order=<?= $order ?>&pending_page=<?= $pendingPage + 1 ?>">&raquo;</a>
                            <a href="?order=<?= $order ?>&pending_page=<?= $pendingPages ?>">&raquo;&raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p>Aucun utilisateur en attente.</p>
            <?php endif; ?>
        <?php elseif ($section === 'admin'): ?>
            <h2 class="section-title">👨‍💼 Administrateurs (<?= $adminsCount ?>)</h2>
            <?php if (!empty($admins)): ?>
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <?php $isManager = $admin['mail'] === GESTIONNAIRE_EMAIL; ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($admin['username']) ?>
                                    <?php if ($isManager): ?>
                                        <span class="manager-label">Gérant</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($admin['mail']) ?></td>
                                <td><span class="role-admin"><?= htmlspecialchars($admin['role']) ?></span></td>
                                <td><span class="status-<?= $admin['status'] ?>"><?= htmlspecialchars($admin['status']) ?></span></td>
                                <td>
                                    <?php if ($isManager): ?>
                                        <span class="manager-actions">Action impossible sur le gérant</span>
                                    <?php else: ?>
                                        <div class="action-group">
                                            <form method="POST" class="edit-form">
                                                <input type="hidden" name="user_id" value="<?= $admin['id'] ?>">
                                                <select name="new_role">
                                                    <option value="admin" <?= $admin['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                    <option value="user" <?= $admin['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                                </select>
                                                <select name="new_status">
                                                    <option value="active" <?= $admin['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                    <option value="pending" <?= $admin['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                </select>
                                                <button type="submit" >Mettre à jour</button>
                                            </form>
                                            <form method="POST" class="inline-form">
                                                <input type="hidden" name="user_id" value="<?= $admin['id'] ?>">
                                                <input type="hidden" name="action" value="refuse">
                                                <button type="submit" class="action-button">Refuser</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($adminsPages > 1): ?>
                    <div class="pagination">
                        <?php if ($adminPage > 1): ?>
                            <a href="?order=<?= $order ?>&admin_page=1">&laquo;&laquo;</a>
                            <a href="?order=<?= $order ?>&admin_page=<?= $adminPage - 1 ?>">&laquo;</a>
                        <?php endif; ?>

                        <span>Page <?= $adminPage ?> / <?= $adminsPages ?></span>

                        <?php if ($adminPage < $adminsPages): ?>
                            <a href="?order=<?= $order ?>&admin_page=<?= $adminPage + 1 ?>">&raquo;</a>
                            <a href="?order=<?= $order ?>&admin_page=<?= $adminsPages ?>">&raquo;&raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p>Aucun administrateur trouvé.</p>
            <?php endif; ?>
        <?php elseif ($section === 'user'): ?>
            <h2 class="section-title">👤 Users (<?= $usersCount ?>)</h2>
            <?php if (!empty($users)): ?>
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['mail']) ?></td>
                                <td><span class="role-user"><?= htmlspecialchars($user['role']) ?></span></td>
                                <td><span class="status-<?= $user['status'] ?>"><?= htmlspecialchars($user['status']) ?></span></td>
                                <td>
                                    <div class="action-group">
                                        <form method="POST" class="edit-form">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <select name="new_role">
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                            </select>
                                            <select name="new_status">
                                                <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="pending" <?= $user['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            </select>
                                            <button type="submit" >Mettre à jour</button>
                                        </form>
                                        <form method="POST" class="inline-form">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="action" value="refuse">
                                            <button type="submit" class="action-button">Refuser</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($usersPages > 1): ?>
                    <div class="pagination">
                        <?php if ($userPage > 1): ?>
                            <a href="?order=<?= $order ?>&user_page=1">&laquo;&laquo;</a>
                            <a href="?order=<?= $order ?>&user_page=<?= $userPage - 1 ?>">&laquo;</a>
                        <?php endif; ?>

                        <span>Page <?= $userPage ?> / <?= $usersPages ?></span>

                        <?php if ($userPage < $usersPages): ?>
                            <a href="?order=<?= $order ?>&user_page=<?= $userPage + 1 ?>">&raquo;</a>
                            <a href="?order=<?= $order ?>&user_page=<?= $usersPages ?>">&raquo;&raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p>Aucun user trouvé.</p>
            <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>



    </body>
<script src="../style/message.js"></script>
</html>
