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

$userName = htmlspecialchars($user['name'] ?? ($_SESSION['user']['name'] ?? ''));
$userEmail = htmlspecialchars($user['email'] ?? ($_SESSION['user']['email'] ?? ''));
$userRole = $user['role'] ?? 'customer';

$initialSource = $user['name'] ?? ($_SESSION['user']['name'] ?? '');
$userInitial = strtoupper(substr(trim($initialSource), 0, 1));
if ($userInitial === '') {
    $userInitial = 'U';
}

// Get all orders for this user
$myOrders = getOrdersByUserId($userId);

// Get order items for all orders (lazy load on expand)
$orderItems = [];
foreach ($myOrders as $order) {
    $orderId = (int)$order['id'];
    $orderItems[$orderId] = getOrderItems($orderId);
}

// Handle AJAX request for order details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'order_details') {
    $orderId = (int)($_GET['order_id'] ?? 0);
    if ($orderId > 0) {
        $order = getOrderById($orderId);
        $items = getOrderItems($orderId);
        if ($order && (int)$order['user_id'] === $userId) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items
            ]);
            exit;
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | La Maria Bakehouse</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Additional styles for Order History Page */
        .order-history-page {
            padding: 40px 0 80px;
            min-height: 60vh;
            background: 
                radial-gradient(circle at 10% 0%, rgba(255,255,255,0.9), transparent 55%),
                radial-gradient(circle at 90% 0%, rgba(255,180,140,0.18), transparent 60%),
                linear-gradient(180deg, #fff8e9 0%, #ffffff 42%);
        }

        .order-history-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(141, 110, 99, 0.15);
        }

        .order-history-header__identity {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .order-history-header__avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8d6e63, #ff7043);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.3rem;
            box-shadow: 0 10px 20px rgba(141, 110, 99, 0.35);
        }

        .order-history-header__content h1 {
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            margin-bottom: 8px;
            color: var(--primary-color);
        }

        .order-history-header__content p {
            font-size: 1rem;
            color: #666;
        }

        .order-history-header__back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: var(--white);
            border: 1px solid rgba(141, 110, 99, 0.2);
            border-radius: 10px;
            color: var(--text-color);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .order-history-header__back:hover {
            background: rgba(141, 110, 99, 0.06);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .order-history-header__back i {
            color: var(--accent-color);
        }

        /* Order Cards */
        .orders-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(141, 110, 99, 0.08);
            border: 1px solid rgba(141, 110, 99, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            box-shadow: 0 8px 30px rgba(141, 110, 99, 0.12);
        }

        .order-card__header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 20px 24px;
            background: rgba(141, 110, 99, 0.03);
            border-bottom: 1px solid rgba(141, 110, 99, 0.08);
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .order-card__header:hover {
            background: rgba(141, 110, 99, 0.06);
        }

        .order-card__info {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 20px;
        }

        .order-card__id {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .order-card__date {
            font-size: 0.9rem;
            color: #666;
        }

        .order-card__status-wrapper {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .order-card__total {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-color);
        }

        .order-card__toggle {
            color: var(--accent-color);
            font-size: 1rem;
            transition: transform 0.3s ease;
        }

        .order-card--expanded .order-card__toggle {
            transform: rotate(180deg);
        }

        /* Progress Tracker */
        .order-progress {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 24px;
            background: rgba(141, 110, 99, 0.02);
            border-bottom: 1px solid rgba(141, 110, 99, 0.08);
        }

        .order-progress__step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .order-progress__step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 16px;
            left: calc(50% + 20px);
            width: calc(100% - 40px);
            height: 3px;
            background: #e0e0e0;
            z-index: 0;
        }

        .order-progress__step--completed:not(:last-child)::after {
            background: #4caf50;
        }

        .order-progress__icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e0e0e0;
            color: #999;
            font-size: 0.85rem;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .order-progress__step--completed .order-progress__icon {
            background: #4caf50;
            color: white;
        }

        .order-progress__step--current .order-progress__icon {
            background: var(--accent-color);
            color: white;
            box-shadow: 0 0 0 4px rgba(255, 112, 67, 0.2);
        }

        .order-progress__label {
            margin-top: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #999;
            text-transform: capitalize;
            text-align: center;
        }

        .order-progress__step--completed .order-progress__label,
        .order-progress__step--current .order-progress__label {
            color: var(--text-color);
        }

        .order-progress__step--current .order-progress__label {
            font-weight: 600;
            color: var(--accent-color);
        }

        /* Order Details */
        .order-details {
            display: none;
            padding: 24px;
            animation: slideDown 0.3s ease;
        }

        .order-card--expanded .order-details {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .order-details__section {
            margin-bottom: 24px;
        }

        .order-details__section:last-child {
            margin-bottom: 0;
        }

        .order-details__title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-details__title i {
            color: var(--accent-color);
        }

        .order-details__grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .order-details__item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .order-details__label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
        }

        .order-details__value {
            font-size: 0.95rem;
            color: var(--text-color);
            font-weight: 500;
        }

        /* Order Items */
        .order-items-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 16px;
            background: rgba(141, 110, 99, 0.04);
            border-radius: 10px;
            border: 1px solid rgba(141, 110, 99, 0.08);
        }

        .order-item__image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: rgba(141, 110, 99, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }

        .order-item__image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .order-item__image i {
            font-size: 1.5rem;
            color: #aaa;
        }

        .order-item__info {
            flex: 1;
            min-width: 0;
        }

        .order-item__name {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .order-item__meta {
            font-size: 0.85rem;
            color: #666;
        }

        .order-item__price {
            text-align: right;
        }

        .order-item__unit-price {
            font-size: 0.85rem;
            color: #666;
        }

        .order-item__total-price {
            font-weight: 600;
            color: var(--text-color);
        }

        /* Empty State */
        .orders-empty {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(141, 110, 99, 0.08);
        }

        .orders-empty__icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .orders-empty__title {
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .orders-empty__text {
            color: #666;
            margin-bottom: 24px;
        }

        /* Loading State */
        .order-details__loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: #888;
        }

        .order-details__loading i {
            margin-right: 8px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .order-progress {
                flex-direction: column;
                gap: 16px;
            }

            .order-progress__step {
                flex-direction: row;
                gap: 16px;
            }

            .order-progress__step:not(:last-child)::after {
                display: none;
            }

            .order-progress__label {
                margin-top: 0;
                text-align: left;
            }

            .order-card__header {
                padding: 16px;
            }

            .order-card__info {
                gap: 12px;
            }

            .order-card__status-wrapper {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
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
                    <a href="profile.php" class="btn btn-outline btn-sm">Account</a>
                    <?php if ($userRole === 'admin'): ?>
                        <a href="admin.php" class="btn btn-sm">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-sm">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="order-history-page">
        <div class="container">
            <!-- Header -->
            <section class="order-history-header fade-up">
                <div class="order-history-header__identity">
                    <div class="order-history-header__avatar"><?php echo htmlspecialchars($userInitial); ?></div>
                    <div class="order-history-header__content">
                        <h1>My Orders</h1>
                        <p>Track and manage your order history, <?php echo $userName; ?></p>
                    </div>
                </div>
                <a href="profile.php" class="order-history-header__back">
                    <i class="fas fa-arrow-left"></i> Back to Account
                </a>
            </section>

            <!-- Orders List -->
            <?php if (empty($myOrders)): ?>
                <div class="orders-empty fade-up">
                    <div class="orders-empty__icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h2 class="orders-empty__title">No orders yet</h2>
                    <p class="orders-empty__text">You haven't placed any orders yet. Start browsing our delicious pastries!</p>
                    <a href="menu.php" class="btn">Browse Menu</a>
                </div>
            <?php else: ?>
                <div class="orders-container">
                    <?php foreach ($myOrders as $index => $order): 
                        $orderId = (int)$order['id'];
                        $orderStatus = $order['status'];
                        $items = $orderItems[$orderId] ?? [];
                    ?>
                        <div class="order-card fade-up" data-order-id="<?php echo $orderId; ?>" style="animation-delay: <?php echo $index * 0.1; ?>s">
                            <!-- Order Header (Clickable) -->
                            <div class="order-card__header" onclick="toggleOrderDetails(<?php echo $orderId; ?>)">
                                <div class="order-card__info">
                                    <span class="order-card__id">Order #<?php echo $orderId; ?></span>
                                    <span class="order-card__date">
                                        <i class="fas fa-calendar-alt" style="margin-right: 6px; color: #999;"></i>
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="order-card__status-wrapper">
                                    <span class="order-card__total">₱<?php echo number_format((float)$order['total'], 2); ?></span>
                                    <span class="order-status order-status--<?php echo htmlspecialchars($orderStatus); ?>">
                                        <?php echo ucfirst($orderStatus); ?>
                                    </span>
                                    <span class="order-card__toggle">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Tracker -->
                            <div class="order-progress">
                                <?php
                                $statuses = ['pending', 'processing', 'ready', 'completed'];
                                $statusLabels = [
                                    'pending' => 'Order Placed',
                                    'processing' => 'Processing',
                                    'ready' => 'Ready for Pickup',
                                    'completed' => 'Completed'
                                ];
                                
                                $currentIndex = array_search($orderStatus, $statuses);
                                if ($currentIndex === false) $currentIndex = -1;
                                
                                foreach ($statuses as $index => $status): 
                                    $isCompleted = $index < $currentIndex;
                                    $isCurrent = $index === $currentIndex;
                                ?>
                                    <div class="order-progress__step <?php echo $isCompleted ? 'order-progress__step--completed' : ''; ?> <?php echo $isCurrent ? 'order-progress__step--current' : ''; ?>">
                                        <div class="order-progress__icon">
                                            <?php if ($isCompleted): ?>
                                                <i class="fas fa-check"></i>
                                            <?php elseif ($isCurrent): ?>
                                                <i class="fas fa-clock"></i>
                                            <?php else: ?>
                                                <i class="fas fa-circle"></i>
                                            <?php endif; ?>
                                        </div>
                                        <span class="order-progress__label"><?php echo $statusLabels[$status]; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Order Details (Expandable) -->
                            <div class="order-details" id="order-details-<?php echo $orderId; ?>">
                                <div class="order-details__loading">
                                    <i class="fas fa-spinner"></i> Loading order details...
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
    <script>
        // Toggle order details
        function toggleOrderDetails(orderId) {
            const card = document.querySelector(`.order-card[data-order-id="${orderId}"]`);
            const detailsContainer = document.getElementById(`order-details-${orderId}`);
            
            if (!card || !detailsContainer) return;
            
            const isExpanded = card.classList.contains('order-card--expanded');
            
            if (isExpanded) {
                card.classList.remove('order-card--expanded');
            } else {
                // Check if details are already loaded
                if (detailsContainer.querySelector('.order-details__section')) {
                    card.classList.add('order-card--expanded');
                } else {
                    // Load details via AJAX
                    loadOrderDetails(orderId);
                    card.classList.add('order-card--expanded');
                }
            }
        }

        // Load order details via AJAX
        async function loadOrderDetails(orderId) {
            const detailsContainer = document.getElementById(`order-details-${orderId}`);
            if (!detailsContainer) return;

            try {
                const response = await fetch(`order-history.php?ajax=order_details&order_id=${orderId}`);
                const data = await response.json();

                if (data.success) {
                    renderOrderDetails(orderId, data.order, data.items);
                } else {
                    detailsContainer.innerHTML = '<p style="padding: 20px; color: #666;">Unable to load order details.</p>';
                }
            } catch (error) {
                console.error('Error loading order details:', error);
                detailsContainer.innerHTML = '<p style="padding: 20px; color: #666;">Error loading order details.</p>';
            }
        }

        // Render order details
        function renderOrderDetails(orderId, order, items) {
            const detailsContainer = document.getElementById(`order-details-${orderId}`);
            if (!detailsContainer) return;

            const paymentMethodLabels = {
                'cash_on_delivery': 'Cash on Delivery',
                'card': 'Credit/Debit Card',
                'gcash': 'GCash',
                'paymaya': 'PayMaya'
            };

            const paymentStatusLabels = {
                'pending': 'Pending',
                'paid': 'Paid',
                'failed': 'Failed',
                'refunded': 'Refunded'
            };

            // Build items HTML
            let itemsHtml = '';
            if (items && items.length > 0) {
                items.forEach(item => {
                    const itemTotal = (parseFloat(item.price) * parseInt(item.quantity)).toFixed(2);
                    itemsHtml += `
                        <div class="order-item">
                            <div class="order-item__image">
                                <i class="fas fa-cookie-bite"></i>
                            </div>
                            <div class="order-item__info">
                                <div class="order-item__name">${escapeHtml(item.product_name)}</div>
                                <div class="order-item__meta">Qty: ${item.quantity} × ₱${parseFloat(item.price).toFixed(2)}</div>
                            </div>
                            <div class="order-item__price">
                                <div class="order-item__total-price">₱${itemTotal}</div>
                            </div>
                        </div>
                    `;
                });
            } else {
                itemsHtml = '<p style="color: #666; padding: 10px 0;">No items found.</p>';
            }

            // Build details HTML
            const html = `
                <div class="order-details__section">
                    <h3 class="order-details__title"><i class="fas fa-info-circle"></i> Order Information</h3>
                    <div class="order-details__grid">
                        <div class="order-details__item">
                            <span class="order-details__label">Order ID</span>
                            <span class="order-details__value">#${order.id}</span>
                        </div>
                        <div class="order-details__item">
                            <span class="order-details__label">Order Date</span>
                            <span class="order-details__value">${new Date(order.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                        </div>
                        <div class="order-details__item">
                            <span class="order-details__label">Payment Method</span>
                            <span class="order-details__value">${paymentMethodLabels[order.payment_method] || order.payment_method || 'Cash on Delivery'}</span>
                        </div>
                        <div class="order-details__item">
                            <span class="order-details__label">Payment Status</span>
                            <span class="order-details__value">${paymentStatusLabels[order.payment_status] || order.payment_status || 'Pending'}</span>
                        </div>
                    </div>
                </div>

                <div class="order-details__section">
                    <h3 class="order-details__title"><i class="fas fa-truck"></i> Delivery Details</h3>
                    <div class="order-details__grid">
                        <div class="order-details__item">
                            <span class="order-details__label">Name</span>
                            <span class="order-details__value">${escapeHtml(order.customer_name || '-')}</span>
                        </div>
                        <div class="order-details__item">
                            <span class="order-details__label">Phone</span>
                            <span class="order-details__value">${escapeHtml(order.customer_phone || '-')}</span>
                        </div>
                        <div class="order-details__item" style="grid-column: 1 / -1;">
                            <span class="order-details__label">Address</span>
                            <span class="order-details__value">
                                ${escapeHtml(order.customer_address || '')}${order.customer_address && order.customer_city ? ', ' : ''}${escapeHtml(order.customer_city || '')}${(order.customer_address || order.customer_city) && order.customer_postal ? ', ' : ''}${escapeHtml(order.customer_postal || '')}
                                ${!order.customer_address && !order.customer_city && !order.customer_postal ? '-' : ''}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="order-details__section">
                    <h3 class="order-details__title"><i class="fas fa-shopping-basket"></i> Ordered Items (${items.length})</h3>
                    <div class="order-items-list">
                        ${itemsHtml}
                    </div>
                </div>

                <div class="order-details__section" style="background: rgba(141, 110, 99, 0.04); padding: 16px; border-radius: 10px; margin-top: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 600; color: var(--primary-color); font-size: 1.1rem;">Order Total</span>
                        <span style="font-weight: 700; font-size: 1.3rem; color: var(--accent-color);">₱${parseFloat(order.total).toFixed(2)}</span>
                    </div>
                </div>
            `;

            detailsContainer.innerHTML = html;
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialize fade-up animations
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-up');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, { threshold: 0.1 });

            fadeElements.forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>
