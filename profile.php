<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: landing.php');
    exit;
}
require_once __DIR__ . '/config/db.php';
ensureTables();

$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }
}

$message = null;
$messageType = 'success'; // success or error
$userId = (int)($_SESSION['user']['id'] ?? 0);
$user = $userId > 0 ? findUserById($userId) : null;
if (!$user) {
    $email = $_SESSION['user']['email'] ?? null;
    if ($email) {
        $user = findUserByEmail($email);
        if ($user) {
            $_SESSION['user']['id'] = (int)$user['id'];
            $_SESSION['user']['name'] = $user['name'];
            $_SESSION['user']['role'] = $user['role'];
            $userId = (int)$user['id'];
        }
    }
}
if (!$user) {
    header('Location: logout.php');
    exit;
}
$profile = getProfileByUserId($userId) ?: [];

// Handle profile details update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_details') {
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal = trim($_POST['postal_code'] ?? '');
    try {
        upsertProfile($userId, $phone ?: null, $address ?: null, $city ?: null, $postal ?: null);
        $message = 'Delivery details saved successfully.';
        $profile = getProfileByUserId($userId);
    } catch (Throwable $e) {
        $message = 'Update failed. Please try again.';
        $messageType = 'error';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!password_verify($current, $user['password_hash'])) {
        $message = 'Current password is incorrect.';
        $messageType = 'error';
    } elseif (strlen($new) < 6) {
        $message = 'New password must be at least 6 characters.';
        $messageType = 'error';
    } elseif ($new !== $confirm) {
        $message = 'New passwords do not match.';
        $messageType = 'error';
    } else {
        try {
            updatePassword($userId, password_hash($new, PASSWORD_DEFAULT));
            $message = 'Password updated successfully.';
            $user = findUserById($userId);
        } catch (Throwable $e) {
            $message = 'Password update failed. Please try again.';
            $messageType = 'error';
        }
    }
}

$userName = htmlspecialchars($user['name'] ?? ($_SESSION['user']['name'] ?? ''));
$userEmail = htmlspecialchars($user['email'] ?? ($_SESSION['user']['email'] ?? ''));
$userRole = $user['role'] ?? 'customer';
$memberSince = !empty($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : '—';

$initialSource = $user['name'] ?? ($_SESSION['user']['name'] ?? '');
$userInitial = strtoupper(substr(trim($initialSource), 0, 1));
if ($userInitial === '') {
    $userInitial = 'U';
}

$myOrders = $userRole === 'customer' ? getOrdersByUserId($userId) : [];

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
        header('Location: profile.php#notifications');
        exit;
    }
} elseif ($notificationAction === 'mark_all_read') {
    markAllNotificationsAsRead();
    // Refresh the page to show updated notifications
    header('Location: profile.php#notifications');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | La Maria Bakehouse</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <div class="container">
        <nav>
            <a href="index.php" class="logo">
                <img src="assets/images/logolmb.png" alt="La Maria Bakehouse logo" class="logo-img">
                <span>La Maria Bakehouse</span>
            </a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="menu.php">Menu</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact Us</a>
                <a href="cart.php" class="btn btn-outline btn-sm"><i class="fas fa-shopping-cart"></i> Cart<?php if ($cartCount > 0): ?><span class="cart-badge"><?php echo (int)$cartCount; ?></span><?php endif; ?></a>
                <a href="profile.php" class="active btn btn-outline btn-sm">Account</a>
                <?php if ($userRole === 'admin'): ?>
                    <a href="admin.php" class="btn btn-sm">Admin</a>
                <?php endif; ?>
                
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
                
                <a href="logout.php" class="btn btn-sm">Logout</a>
            </div>
        </nav>
    </div>
</header>

<!-- Profile dashboard -->
<main class="profile-dashboard">
    <div class="container">
        <!-- Welcome header -->
        <section class="profile-header fade-up">
            <div class="profile-header__identity">
                <div class="profile-header__avatar"><?php echo htmlspecialchars($userInitial); ?></div>
                <div class="profile-header__content">
                    <h1>My Account</h1>
                    <p class="profile-header__welcome">Welcome back, <strong><?php echo $userName; ?></strong></p>
                    <p class="profile-header__sub">Manage your details, delivery address, and password. Your information is used for orders and pickup.</p>
                </div>
            </div>
            <div class="profile-header__quick">
                <a href="menu.php" class="profile-quick-link"><i class="fas fa-utensils"></i> Order</a>
                <a href="cart.php" class="profile-quick-link"><i class="fas fa-shopping-cart"></i> Cart<?php if ($cartCount > 0): ?> (<?php echo (int)$cartCount; ?>)<?php endif; ?></a>
                <a href="contact.php" class="profile-quick-link"><i class="fas fa-envelope"></i> Contact</a>
            </div>
        </section>

        <?php if ($message): ?>
            <div class="profile-message profile-message--<?php echo $messageType; ?> fade-up">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Account overview -->
            <section class="profile-card fade-up">
                <h2 class="profile-card__title"><i class="fas fa-user"></i> Account overview</h2>
                <dl class="profile-dl">
                    <dt>Name</dt>
                    <dd><?php echo $userName; ?></dd>
                    <dt>Email</dt>
                    <dd><a href="mailto:<?php echo $userEmail; ?>"><?php echo $userEmail; ?></a></dd>
                    <dt>Member since</dt>
                    <dd><?php echo $memberSince; ?></dd>
                    <dt>Account type</dt>
                    <dd><span class="profile-role profile-role--<?php echo $userRole; ?>"><?php echo ucfirst($userRole); ?></span></dd>
                </dl>
            </section>

            <!-- Delivery & contact details -->
            <section class="profile-card profile-card--form fade-up">
                <h2 class="profile-card__title"><i class="fas fa-truck"></i> Delivery & contact details</h2>
                <p class="profile-card__desc">We use this for orders, pickup, and delivery. Keep it up to date.</p>
                <form method="POST" action="profile.php" class="profile-form">
                    <input type="hidden" name="action" value="save_details">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" placeholder="e.g. +63 917 123 4567">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" placeholder="Street, Barangay, Building"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>" placeholder="City">
                        </div>
                        <div class="form-group">
                            <label for="postal_code">Postal code</label>
                            <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($profile['postal_code'] ?? ''); ?>" placeholder="Postal code">
                        </div>
                    </div>
                    <button type="submit" class="btn">Save delivery details</button>
                </form>
            </section>

            <!-- Change password -->
            <section class="profile-card profile-card--form fade-up">
                <h2 class="profile-card__title"><i class="fas fa-lock"></i> Change password</h2>
                <p class="profile-card__desc">Choose a strong password you don’t use elsewhere.</p>
                <form method="POST" action="profile.php" class="profile-form">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label for="current_password">Current password</label>
                        <input type="password" id="current_password" name="current_password" required placeholder="Enter current password">
                    </div>
                    <div class="form-group">
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="At least 6 characters">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm new password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Confirm new password">
                    </div>
                    <button type="submit" class="btn">Update password</button>
                </form>
            </section>

            <?php if ($userRole === 'admin'): ?>
            <!-- Admin: Process orders (link to admin dashboard) -->
            <section class="profile-card profile-card--admin-cta fade-up">
                <h2 class="profile-card__title"><i class="fas fa-boxes"></i> Process orders</h2>
                <p class="profile-card__desc">View and process customer orders. Update status from Pending → Processing → Ready → Completed.</p>
                <a href="admin.php#orders" class="btn">Go to Order Management</a>
            </section>
            <?php else: ?>
            <!-- User: Order history -->
            <section class="profile-card profile-card--orders fade-up">
                <h2 class="profile-card__title"><i class="fas fa-receipt"></i> Order history</h2>
                <?php if (empty($myOrders)): ?>
                    <div class="profile-orders-placeholder">
                        <p>You haven’t placed any orders yet.</p>
                        <a href="menu.php" class="btn btn-outline">Browse menu</a>
                    </div>
                <?php else: ?>
                    <div class="profile-order-list">
                        <?php foreach ($myOrders as $ord): ?>
                            <div class="profile-order-item">
                                <div class="profile-order-item__head">
                                    <span class="profile-order-item__id">Order #<?php echo (int)$ord['id']; ?></span>
                                    <span class="profile-order-item__date"><?php echo date('M j, Y g:i A', strtotime($ord['created_at'])); ?></span>
                                    <span class="order-status order-status--<?php echo htmlspecialchars($ord['status']); ?>"><?php echo ucfirst($ord['status']); ?></span>
                                </div>
                                <div class="profile-order-item__total">Total: ₱<?php echo number_format((float)$ord['total'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="order-history.php" class="btn" style="margin-top: 12px; margin-right: 8px;">View Full Order History</a>
                    <a href="menu.php" class="btn btn-outline" style="margin-top: 12px;">Browse menu</a>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="index.php" class="logo">
                    <img src="assets/images/logolmb.png" alt="La Maria Bakehouse logo" class="logo-img">
                    <span>La Maria Bakehouse</span>
                </a>
                <p>Bringing sweetness to your life, one pastry at a time.</p>
                <div class="social-icons">
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="mailto:hello@lamariabakehouse.com" aria-label="Email"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h4>Explore</h4>
                <ul>
                    <li><a href="menu.php">Menu</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">FAQs</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h4>Contact</h4>
                <ul>
                    <li>123 Bakery Lane, Davao City</li>
                    <li>+63 917 555 1234</li>
                    <li><a href="contact.php">Contact page</a></li>
                </ul>
            </div>
            <div class="footer-column newsletter">
                <h4>Stay in the loop</h4>
                <form id="newsletterForm" onsubmit="return handleNewsletter(event)">
                    <input type="email" id="newsletterEmail" placeholder="Email address" required>
                    <button type="submit" class="btn btn-sm">Subscribe</button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 La Maria Bakehouse. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="assets/js/script.js"></script>
</body>
</html>
