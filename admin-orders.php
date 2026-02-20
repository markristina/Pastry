<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: landing.php');
    exit;
}
if (($_SESSION['user']['role'] ?? 'customer') !== 'admin') {
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/config/db.php';
ensureTables();

$message = null;

// Handle order status update
$orderAction = $_POST['order_action'] ?? null;
if ($orderAction === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    if ($orderId > 0 && $newStatus !== '') {
        try {
            updateOrderStatus($orderId, $newStatus);
            $message = 'Order #' . $orderId . ' updated to ' . ucfirst($newStatus);
        } catch (Throwable $e) {
            $message = 'Failed to update order.';
        }
    }
}

$orders = getAllOrders();
$orderStatuses = ['pending', 'processing', 'ready', 'completed', 'cancelled'];

$viewOrderId = isset($_GET['view_order']) ? (int)$_GET['view_order'] : 0;
$viewOrder = $viewOrderId > 0 ? getOrderById($viewOrderId) : null;
$viewOrderItems = $viewOrder ? getOrderItems($viewOrderId) : [];

// Notifications
$unreadCount = getUnreadNotificationsCount();
$recentNotifications = getUnreadNotifications(5);
$allNotifications = getAllNotifications(20);

// Handle notification actions
$notificationAction = $_GET['notification_action'] ?? null;
if ($notificationAction === 'mark_read' && isset($_GET['notification_id'])) {
    $notifId = (int)$_GET['notification_id'];
    if ($notifId > 0) {
        markNotificationAsRead($notifId);
        header('Location: admin-orders.php#notifications');
        exit;
    }
} elseif ($notificationAction === 'mark_all_read') {
    markAllNotificationsAsRead();
    header('Location: admin-orders.php#notifications');
    exit;
}

// Simple dashboard metrics
$totalOrders = count($orders);
$totalRevenue = 0;
foreach ($orders as $o) {
    $totalRevenue += (float)($o['total'] ?? 0);
}

// Orders by status for mini chart
$statusStats = [];
foreach ($orderStatuses as $st) {
    $statusStats[$st] = 0;
}
foreach ($orders as $o) {
    $st = $o['status'] ?? 'pending';
    if (!isset($statusStats[$st])) {
        $statusStats[$st] = 0;
    }
    $statusStats[$st]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management | La Maria Bakehouse</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-sidebar__brand">
            <a href="index.php" class="logo">
                <img src="assets/images/logolmb.png" alt="La Maria Bakehouse logo" class="logo-img">
                <span>La Maria Admin</span>
            </a>
        </div>
        <nav class="admin-sidebar__nav">
            <a href="admin.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
            <a href="admin-orders.php" class="active"><i class="fa-solid fa-receipt"></i><span>Orders</span></a>
            <a href="admin-products.php"><i class="fa-solid fa-bread-slice"></i><span>Products</span></a>
            <a href="admin-categories.php"><i class="fa-solid fa-tags"></i><span>Categories</span></a>
            <a href="admin.php#archive"><i class="fa-solid fa-archive"></i><span>Archive</span></a>
            <a href="admin.php#users"><i class="fa-solid fa-users"></i><span>Users</span></a>
        </nav>
        <a href="index.php" target="_blank"><i class="fa-solid fa-store"></i><span>View Storefront</span></a>
        <div class="admin-sidebar__footer">
            <a href="profile.php"><i class="fa-regular fa-user"></i><span>Account</span></a>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <p class="section-eyebrow">E-commerce</p>
                <h1>Orders Management</h1>
            </div>
            <div class="admin-topbar__right">
                <form class="admin-search" role="search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" placeholder="Search orders…" aria-label="Search orders">
                </form>
                
                <!-- Notification Bell -->
                <div class="admin-notification-wrapper">
                    <button type="button" class="admin-notification-btn" id="notificationBtn">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="admin-notification-badge"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="admin-notification-dropdown" id="notificationDropdown">
                        <div class="admin-notification-header">
                            <h4>Notifications</h4>
                            <?php if ($unreadCount > 0): ?>
                                <a href="?notification_action=mark_all_read" class="admin-notification-mark-all">Mark all read</a>
                            <?php endif; ?>
                        </div>
                        <div class="admin-notification-list">
                            <?php if (empty($allNotifications)): ?>
                                <div class="admin-notification-empty">No notifications yet</div>
                            <?php else: ?>
                                <?php foreach ($allNotifications as $notif): ?>
                                    <div class="admin-notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo (int)$notif['id']; ?>">
                                        <div class="admin-notification-icon">
                                            <?php 
                                            $icon = 'fa-bell';
                                            $iconClass = 'admin-notification-icon--default';
                                            switch($notif['type']) {
                                                case 'new_order':
                                                    $icon = 'fa-shopping-cart';
                                                    $iconClass = 'admin-notification-icon--order';
                                                    break;
                                                case 'product_added':
                                                    $icon = 'fa-plus-circle';
                                                    $iconClass = 'admin-notification-icon--product';
                                                    break;
                                                case 'product_updated':
                                                    $icon = 'fa-edit';
                                                    $iconClass = 'admin-notification-icon--product';
                                                    break;
                                                case 'product_deleted':
                                                    $icon = 'fa-trash';
                                                    $iconClass = 'admin-notification-icon--delete';
                                                    break;
                                                case 'new_user':
                                                    $icon = 'fa-user-plus';
                                                    $iconClass = 'admin-notification-icon--user';
                                                    break;
                                            }
                                            ?>
                                            <i class="fa-solid <?php echo $icon; ?> <?php echo $iconClass; ?>"></i>
                                        </div>
                                        <div class="admin-notification-content">
                                            <div class="admin-notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                            <div class="admin-notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                                            <div class="admin-notification-time"><?php echo date('M j, g:i A', strtotime($notif['created_at'])); ?></div>
                                        </div>
                                        <?php if (!$notif['is_read']): ?>
                                            <a href="?notification_action=mark_read&notification_id=<?php echo (int)$notif['id']; ?>" class="admin-notification-read-btn" title="Mark as read">
                                                <i class="fa-solid fa-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="admin-user-pill">
                    <?php
                    $adminName = $_SESSION['user']['name'] ?? 'Admin';
                    $adminInitial = strtoupper(substr($adminName, 0, 1));
                    ?>
                    <div class="admin-user-pill__avatar"><?php echo htmlspecialchars($adminInitial); ?></div>
                    <div class="admin-user-pill__meta">
                        <span class="admin-user-pill__name"><?php echo htmlspecialchars($adminName); ?></span>
                        <span class="admin-user-pill__role">Administrator</span>
                    </div>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
            <p class="admin-feedback admin-feedback--floating"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <section class="admin-kpis">
            <div class="admin-kpi-card">
                <p class="admin-kpi-label">Total Orders</p>
                <p class="admin-kpi-value"><?php echo number_format($totalOrders); ?></p>
                <p class="admin-kpi-meta">Across all time</p>
            </div>
            <div class="admin-kpi-card">
                <p class="admin-kpi-label">Total Revenue</p>
                <p class="admin-kpi-value">₱<?php echo number_format($totalRevenue, 2); ?></p>
                <p class="admin-kpi-meta">Completed + in progress</p>
            </div>
            <div class="admin-kpi-card admin-kpi-card--status">
                <p class="admin-kpi-label">Orders by status</p>
                <div class="admin-status-bars">
                    <?php foreach ($orderStatuses as $st): ?>
                        <?php $count = $statusStats[$st] ?? 0; ?>
                        <div class="admin-status-bar">
                            <span class="admin-status-bar__label"><?php echo ucfirst($st); ?></span>
                            <div class="admin-status-bar__track">
                                <div class="admin-status-bar__fill admin-status-bar__fill--<?php echo htmlspecialchars($st); ?>"
                                     style="--value: <?php echo $totalOrders > 0 ? ($count / max($totalOrders, 1)) : 0; ?>"></div>
                            </div>
                            <span class="admin-status-bar__count"><?php echo (int)$count; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Orders section -->
        <section class="admin-section">
            <div class="admin-dashboard__header">
                <div>
                    <p class="section-eyebrow">E-commerce</p>
                    <h2>Orders</h2>
                </div>
            </div>

        <?php if ($viewOrder): ?>
        <div class="admin-card admin-order-detail">
            <h3>Order #<?php echo (int)$viewOrder['id']; ?> <span class="order-status order-status--<?php echo htmlspecialchars($viewOrder['status']); ?>"><?php echo ucfirst($viewOrder['status']); ?></span></h3>
            <div class="admin-order-detail__grid">
                <div>
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($viewOrder['customer_name'] ?: $viewOrder['user_name'] ?? '—'); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($viewOrder['user_email'] ?? '—'); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($viewOrder['customer_phone'] ?? '—'); ?></p>
                    <?php
                        $addr = array_filter([
                            $viewOrder['customer_address'] ?? '',
                            $viewOrder['customer_city'] ?? '',
                            $viewOrder['customer_postal'] ?? ''
                        ]);
                        ?>
                    <p><strong>Address:</strong> <?php echo $addr ? htmlspecialchars(implode(', ', $addr)) : '—'; ?></p>
                </div>
                <div>
                    <p><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($viewOrder['created_at'])); ?></p>
                    <p><strong>Total:</strong> ₱<?php echo number_format((float)$viewOrder['total'], 2); ?></p>
                    <form method="POST" action="admin-orders.php?view_order=<?php echo (int)$viewOrder['id']; ?>">
                        <input type="hidden" name="order_action" value="update_status">
                        <input type="hidden" name="order_id" value="<?php echo (int)$viewOrder['id']; ?>">
                        <label>Update status:</label>
                        <select name="status" onchange="this.form.submit()">
                            <?php foreach ($orderStatuses as $st): ?>
                                <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ($viewOrder['status'] === $st) ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
            <table class="admin-table" style="margin-top: 16px;">
                <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($viewOrderItems as $oi): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($oi['product_name']); ?></td>
                        <td>₱<?php echo number_format((float)$oi['price'], 2); ?></td>
                        <td><?php echo (int)$oi['quantity']; ?></td>
                        <td>₱<?php echo number_format((float)$oi['price'] * (int)$oi['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <a href="admin-orders.php" class="btn btn-outline btn-sm" style="margin-top: 12px;">Back to orders list</a>
        </div>
        <?php endif; ?>

        <div class="admin-card admin-card--table admin-card--full">
            <?php if (empty($orders)): ?>
                <div class="admin-empty-state">
                    <i class="fas fa-receipt" style="font-size: 3rem; color: rgba(141,110,99,0.2); margin-bottom: 16px;"></i>
                    <h3>No orders yet</h3>
                    <p>Customer orders will appear here once they start placing orders.</p>
                </div>
            <?php else: ?>
                <div class="admin-table-header">
                    <h3>Recent Orders</h3>
                    <div class="admin-table-info">
                        <span class="admin-table-count"><?php echo count($orders); ?> orders</span>
                        <span class="admin-table-revenue">₱<?php echo number_format($totalRevenue, 2); ?></span>
                    </div>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table admin-table--fixed">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Order #</th>
                                <th style="width: 200px;">Customer</th>
                                <th style="width: 140px;">Date</th>
                                <th style="width: 100px;">Total</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $ord): ?>
                            <tr>
                                <td><strong>#<?php echo (int)$ord['id']; ?></strong></td>
                                <td>
                                    <div class="admin-customer-info">
                                        <strong><?php echo htmlspecialchars($ord['customer_name'] ?: ($ord['user_name'] ?? '—')); ?></strong>
                                        <small><?php echo htmlspecialchars($ord['customer_phone'] ?: ($ord['user_email'] ?? '')); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="admin-date-info">
                                        <?php echo date('M j, Y', strtotime($ord['created_at'])); ?>
                                        <small><?php echo date('g:i A', strtotime($ord['created_at'])); ?></small>
                                    </div>
                                </td>
                                <td><strong>₱<?php echo number_format((float)$ord['total'], 2); ?></strong></td>
                                <td>
                                    <span class="order-status order-status--<?php echo htmlspecialchars($ord['status']); ?>">
                                        <?php echo ucfirst($ord['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-actions">
                                        <form method="POST" action="admin-orders.php" style="display: inline;">
                                            <input type="hidden" name="order_action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?php echo (int)$ord['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="admin-status-select">
                                                <?php foreach ($orderStatuses as $st): ?>
                                                    <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ($ord['status'] === $st) ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                        <button type="button" class="admin-btn admin-btn--view" onclick="window.location.href='admin-orders.php?view_order=<?php echo (int)$ord['id']; ?>'">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        </section>
    </main>
</div>

<script>
// Notification dropdown functionality
document.getElementById('notificationBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
});

// Close notification dropdown when clicking outside
document.addEventListener('click', function() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.style.display = 'none';
});

// Prevent dropdown from closing when clicking inside it
document.getElementById('notificationDropdown').addEventListener('click', function(e) {
    e.stopPropagation();
});
</script>
</body>
</html>
