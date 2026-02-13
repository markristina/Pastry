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

// Handle create/update/delete actions
$action = $_POST['action'] ?? $_GET['action'] ?? null;
try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'customer';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $message = 'Please provide valid name, email, and password.';
        } elseif (findUserByEmail($email)) {
            $message = 'Email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $newUserId = createUser($name, $email, $hash, $role === 'admin' ? 'admin' : 'customer');
            // Notify admin of new user
            if ($role === 'customer') {
                notifyAdminOfNewUser($name, $email);
            }
            $message = 'User created.';
        }
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $_POST['role'] ?? 'customer';
        if ($id <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please provide valid user details.';
        } else {
            updateUser($id, $name, $email, $role === 'admin' ? 'admin' : 'customer');
            $message = 'User updated.';
        }
    } elseif ($action === 'delete' && isset($_GET['id'])) {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            deleteUser($id);
            $message = 'User deleted.';
        }
    }
} catch (Throwable $e) {
    $message = 'Operation failed. Please try again.';
}

$users = getAllUsers();
$orders = getAllOrders();
$orderStatuses = ['pending', 'processing', 'ready', 'completed', 'cancelled'];

require_once __DIR__ . '/products.php';

// Handle product create/update/delete actions
$productMessage = null;
$productAction = $_POST['product_action'] ?? $_GET['product_action'] ?? null;
try {
    if ($productAction === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['product_name'] ?? '');
        $price = (float)($_POST['product_price'] ?? 0);
        $description = trim($_POST['product_description'] ?? '');
        $image = trim($_POST['product_image'] ?? '');
        $category = trim($_POST['product_category'] ?? '');
        $badge = trim($_POST['product_badge'] ?? '');
        $isActive = isset($_POST['product_active']);

        if ($name === '' || $price <= 0) {
            $productMessage = 'Please provide a valid product name and price.';
        } else {
            createProduct($name, $price, $description ?: null, $image ?: null, $category ?: null, $badge ?: null, $isActive);
            // Notify admin of new product
            notifyAdminOfNewProduct($name);
            $productMessage = 'Product created.';
        }
    } elseif ($productAction === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $badge = trim($_POST['badge'] ?? '');
        $isActive = isset($_POST['is_active']);

        // Get old product name before update
        $oldProduct = null;
        foreach ($products as $p) {
            if ((int)$p['id'] === $id) {
                $oldProduct = $p;
                break;
            }
        }

        if ($id <= 0 || $name === '' || $price <= 0) {
            $productMessage = 'Please provide valid product details.';
        } else {
            updateProduct($id, $name, $price, $description ?: null, $image ?: null, $category ?: null, $badge ?: null, $isActive);
            // Notify admin of product update
            notifyAdminOfProductUpdate($name);
            $productMessage = 'Product updated.';
        }
    } elseif ($productAction === 'delete' && isset($_GET['product_id'])) {
        $id = (int)($_GET['product_id'] ?? 0);
        // Get product name before deletion
        $productName = '';
        foreach ($products as $p) {
            if ((int)$p['id'] === $id) {
                $productName = $p['name'];
                break;
            }
        }
        if ($id > 0) {
            deleteProduct($id);
            // Notify admin of product deletion
            if ($productName) {
                notifyAdminOfProductDeletion($productName);
            }
            $productMessage = 'Product deleted.';
        }
    }
} catch (Throwable $e) {
    $productMessage = 'Product operation failed. Please try again.';
}

$products = getAllProducts();

// Simple dashboard metrics
$totalOrders = count($orders);
$totalRevenue = 0;
foreach ($orders as $o) {
    $totalRevenue += (float)($o['total'] ?? 0);
}

$totalCustomers = 0;
$totalAdmins = 0;
foreach ($users as $u) {
    if (($u['role'] ?? 'customer') === 'admin') {
        $totalAdmins++;
    } else {
        $totalCustomers++;
    }
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
        // Refresh the page to show updated notifications
        header('Location: admin.php#notifications');
        exit;
    }
} elseif ($notificationAction === 'mark_all_read') {
    markAllNotificationsAsRead();
    // Refresh the page to show updated notifications
    header('Location: admin.php#notifications');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | La Maria Bakehouse</title>
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
            <a href="#dashboard" class="active"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
            <a href="#orders"><i class="fa-solid fa-receipt"></i><span>Orders</span></a>
            <a href="#products"><i class="fa-solid fa-bread-slice"></i><span>Products</span></a>
            <a href="#users"><i class="fa-solid fa-users"></i><span>Users</span></a>
            <a href="index.php" target="_blank"><i class="fa-solid fa-store"></i><span>View Storefront</span></a>
        </nav>
        <div class="admin-sidebar__footer">
            <a href="profile.php"><i class="fa-regular fa-user"></i><span>Account</span></a>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar" id="dashboard">
            <div>
                <p class="section-eyebrow">Control Panel</p>
                <h1>Dashboard overview</h1>
            </div>
            <div class="admin-topbar__right">
                <form class="admin-search" role="search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" placeholder="Search orders, users, or products…" aria-label="Search admin data">
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
                <p class="admin-kpi-label">Total Customers</p>
                <p class="admin-kpi-value"><?php echo number_format($totalCustomers); ?></p>
                <p class="admin-kpi-meta">+<?php echo $totalAdmins; ?> admins</p>
            </div>
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
        <section class="admin-section" id="orders">
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
                    <p><strong>Total:</strong> $<?php echo number_format((float)$viewOrder['total'], 2); ?></p>
                    <form method="POST" action="admin.php?view_order=<?php echo (int)$viewOrder['id']; ?>#orders">
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
            <a href="admin.php#orders" class="btn btn-outline btn-sm" style="margin-top: 12px;">Back to orders list</a>
        </div>
        <?php endif; ?>

        <div class="admin-card admin-card--table admin-card--full">
            <?php if (empty($orders)): ?>
                <p style="padding: 24px; color: #666;">No orders yet. Customer orders will appear here.</p>
            <?php else: ?>
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $ord): ?>
                            <tr>
                                <td><strong>#<?php echo (int)$ord['id']; ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($ord['customer_name'] ?: ($ord['user_name'] ?? '—')); ?>
                                    <br><small><?php echo htmlspecialchars($ord['customer_phone'] ?: ($ord['user_email'] ?? '')); ?></small>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($ord['created_at'])); ?></td>
                                <td>₱<?php echo number_format((float)$ord['total'], 2); ?></td>
                                <td><span class="order-status order-status--<?php echo htmlspecialchars($ord['status']); ?>"><?php echo ucfirst($ord['status']); ?></span></td>
                                <td>
                                    <form method="POST" action="admin.php#orders" class="admin-order-form">
                                        <input type="hidden" name="order_action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$ord['id']; ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <?php foreach ($orderStatuses as $st): ?>
                                                <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ($ord['status'] === $st) ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                    <a href="admin.php?view_order=<?php echo (int)$ord['id']; ?>#orders" class="btn btn-outline btn-sm" style="margin-left: 8px;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        </section>

        <section class="admin-section" id="products">
            <div class="admin-dashboard__header">
                <div>
                    <p class="section-eyebrow">Catalog</p>
                    <h2>Products</h2>
                </div>
                <?php if ($productMessage): ?>
                    <p class="admin-feedback"><?php echo htmlspecialchars($productMessage); ?></p>
                <?php endif; ?>
            </div>

            <div class="admin-grid">
                <div class="admin-card">
                    <h3>Add Product</h3>
                    <form method="POST" action="admin.php#products" class="admin-form">
                        <input type="hidden" name="product_action" value="create">
                        <div class="form-group">
                            <label for="product_name">Product Name</label>
                            <input type="text" id="product_name" name="product_name" required>
                        </div>
                        <div class="form-group">
                            <label for="product_category">Category (optional)</label>
                            <input type="text" id="product_category" name="product_category" placeholder="e.g., Bread, Cake, Pastry">
                        </div>
                        <div class="form-group">
                            <label for="product_price">Price (₱)</label>
                            <input type="number" step="0.01" min="0" id="product_price" name="product_price" required>
                        </div>
                        <div class="form-group">
                            <label for="product_image">Image path</label>
                            <input type="text" id="product_image" name="product_image" placeholder="assets/images/example.jpg">
                        </div>
                        <div class="form-group">
                            <label for="product_description">Description</label>
                            <textarea id="product_description" name="product_description" rows="3" style="width:100%; border-radius:10px; border:1px solid #d7ccc8; padding:10px 12px;"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="product_badge">Badge (optional)</label>
                            <input type="text" id="product_badge" name="product_badge" placeholder="e.g., New, Bestseller">
                        </div>
                        <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" id="product_active" name="product_active" checked>
                            <label for="product_active" style="margin-bottom:0; text-transform:none; letter-spacing:normal;">Show in storefront</label>
                        </div>
                        <button type="submit" class="btn admin-submit">Add Product</button>
                    </form>
                </div>

                <div class="admin-card admin-card--table">
                    <h3>Product List</h3>
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Category</th>
                                    <th>Badge</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>#<?php echo (int)$p['id']; ?></td>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td>₱<?php echo number_format((float)$p['price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($p['category'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($p['badge'] ?? ''); ?></td>
                                    <td><?php echo !empty($p['is_active']) ? 'Visible' : 'Hidden'; ?></td>
                                    <td>
                                        <form method="POST" action="admin.php#products" class="admin-inline-form">
                                            <input type="hidden" name="product_action" value="update">
                                            <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                            <input type="text" name="name" value="<?php echo htmlspecialchars($p['name']); ?>" placeholder="Name" required>
                                            <input type="number" step="0.01" min="0" name="price" value="<?php echo htmlspecialchars($p['price']); ?>" placeholder="Price" required>
                                            <input type="text" name="category" value="<?php echo htmlspecialchars($p['category'] ?? ''); ?>" placeholder="Category">
                                            <input type="text" name="badge" value="<?php echo htmlspecialchars($p['badge'] ?? ''); ?>" placeholder="Badge">
                                            <input type="text" name="image" value="<?php echo htmlspecialchars($p['image'] ?? ''); ?>" placeholder="Image path">
                                            <input type="hidden" name="description" value="<?php echo htmlspecialchars($p['description'] ?? ''); ?>">
                                            <label style="display:flex;align-items:center;gap:4px;font-size:0.8rem;">
                                                <input type="checkbox" name="is_active" <?php echo !empty($p['is_active']) ? 'checked' : ''; ?>>
                                                Visible
                                            </label>
                                            <button type="submit" class="btn btn-sm">Save</button>
                                        </form>
                                        <a href="admin.php?product_action=delete&product_id=<?php echo (int)$p['id']; ?>#products" class="btn btn-outline btn-sm admin-delete" onclick="return confirm('Delete this product?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <section class="admin-section" id="users">
            <div class="admin-dashboard__header">
                <div>
                    <p class="section-eyebrow">Control Panel</p>
                    <h2>Users</h2>
                </div>
            </div>

            <div class="admin-grid">
                <div class="admin-card">
                    <h3>Add User</h3>
                    <form method="POST" action="admin.php" class="admin-form">
                        <input type="hidden" name="action" value="create">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role">
                                <option value="customer">Customer</option>
                                <option value="admin">Admin (Owner)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn admin-submit">Create</button>
                    </form>
                </div>

                <div class="admin-card admin-card--table">
                    <h3>Users</h3>
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo (int)$u['id']; ?></td>
                                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><span class="role-pill role-pill--<?php echo htmlspecialchars($u['role']); ?>"><?php echo htmlspecialchars($u['role']); ?></span></td>
                                    <td>
                                        <form method="POST" action="admin.php" class="admin-inline-form">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                            <input type="text" name="name" value="<?php echo htmlspecialchars($u['name']); ?>" placeholder="Name" required>
                                            <input type="email" name="email" value="<?php echo htmlspecialchars($u['email']); ?>" placeholder="Email" required>
                                            <select name="role">
                                                <option value="customer" <?php echo $u['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                                <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm">Save</button>
                                        </form>
                                        <a href="admin.php?action=delete&id=<?php echo (int)$u['id']; ?>" class="btn btn-outline btn-sm admin-delete" onclick="return confirm('Delete this user?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
<script src="assets/js/script.js"></script>
</body>
</html>