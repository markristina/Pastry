<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php?redirect=checkout');
    exit;
}

require_once __DIR__ . '/products.php';
require_once __DIR__ . '/config/db.php';
ensureTables();

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$cartItems = $_SESSION['cart'];
$cartTotal = 0.00;
$cartCount = 0;

// Validate and calculate cart total
foreach ($cartItems as $item) {
    $price = (float)$item['product']['price'];
    $quantity = (int)$item['quantity'];
    
    if ($price < 0) {
        die('Invalid price for product: ' . htmlspecialchars($item['product']['name']));
    }
    if ($quantity <= 0) {
        die('Invalid quantity for product: ' . htmlspecialchars($item['product']['name']));
    }
    
    $itemTotal = $price * $quantity;
    $cartTotal += $itemTotal;
    $cartCount += $quantity;
}

// Round to 2 decimal places to avoid floating point precision issues
$cartTotal = round($cartTotal, 2);

$userId = (int)$_SESSION['user']['id'];
$profile = getProfileByUserId($userId) ?: [];
$user = findUserById($userId) ?: [];

$orderPlaced = false;
$orderId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    try {
        $customerName = $user['name'] ?? $_SESSION['user']['name'] ?? null;
        $customerPhone = $profile['phone'] ?? null;
        $customerAddress = $profile['address'] ?? null;
        $customerCity = $profile['city'] ?? null;
        $customerPostal = $profile['postal_code'] ?? null;
        $paymentMethod = $_POST['payment_method'] ?? 'cash_on_delivery';
        $paymentStatus = ($paymentMethod === 'cash_on_delivery') ? 'pending' : 'completed';

        // Process card payment if selected
        if ($paymentMethod === 'credit_card') {
            // In a real application, you would integrate with a payment processor here
            // This is a simplified example - in production, use a proper payment gateway
            $cardNumber = str_replace(' ', '', $_POST['card_number'] ?? '');
            $expiry = $_POST['expiry_date'] ?? '';
            $cvv = $_POST['cvv'] ?? '';
            $cardName = $_POST['card_name'] ?? '';

            // Basic validation
            if (empty($cardNumber) || empty($expiry) || empty($cvv) || empty($cardName)) {
                throw new Exception('Please fill in all card details');
            }

            // In a real application, you would call the payment processor API here
            // For example: $paymentResult = $paymentProcessor->charge($cartTotal, $cardNumber, $expiry, $cvv, $cardName);
            // if (!$paymentResult->success) { throw new Exception('Payment failed: ' . $paymentResult->message); }
        }

        // Start transaction to ensure data consistency
$pdo = getPDO();
$pdo->beginTransaction();

try {
    // Create the order
    $orderId = createOrder($userId, $cartTotal, $customerName, $customerPhone, $customerAddress, $customerCity, $customerPostal, $paymentMethod, $paymentStatus);
    
    // Add order items
    foreach ($cartItems as $item) {
        addOrderItem(
            $orderId,
            $item['product']['id'],
            $item['product']['name'],
            (float)$item['product']['price'],
            (int)$item['quantity']
        );
    }
    
    // Commit the transaction
    $pdo->commit();
    
    // Clear the cart only after successful order creation
    $_SESSION['cart'] = [];
    $orderPlaced = true;
} catch (Throwable $e) {
    // Rollback the transaction on error
    $pdo->rollBack();
    $orderError = 'Could not place order. ' . $e->getMessage();
}

        foreach ($cartItems as $item) {
            addOrderItem(
                $orderId,
                $item['product']['id'],
                $item['product']['name'],
                (float)$item['product']['price'],
                (int)$item['quantity']
            );
        }

        $_SESSION['cart'] = [];
        $orderPlaced = true;
    } catch (Throwable $e) {
        $orderError = 'Could not place order. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | La Maria Bakehouse</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .checkout-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        .checkout-summary {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            height: fit-content;
        }
        .checkout-details {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }
        .checkout-title {
            font-family: 'Playfair Display', serif;
            color: #5c4036;
            font-size: 2rem;
            margin-bottom: 5px;
            text-align: center;
        }
        .checkout-subtitle {
            color: #8D6E63;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        .order-summary-title {
            font-size: 1.3rem;
            color: #5c4036;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-total {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #eee;
            font-size: 1.2rem;
            font-weight: 600;
            color: #5c4036;
        }
        .delivery-info {
            background: #f9f7f5;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 0.95rem;
        }
        .payment-method {
            margin: 20px 0;
        }
        .payment-option {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .payment-option:hover {
            border-color: #8D6E63;
        }
        .payment-option input[type="radio"] {
            margin-right: 12px;
        }
        .payment-option label {
            display: flex;
            align-items: center;
            width: 100%;
            cursor: pointer;
            margin: 0;
        }
        .payment-option .payment-icon {
            margin-right: 10px;
            font-size: 1.4rem;
            color: #5c4036;
            width: 30px;
            text-align: center;
        }
        .payment-details {
            display: none;
            padding: 15px;
            margin-top: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .payment-option input[type="radio"]:checked + label + .payment-details {
            display: block;
        }
        .card-element {
            margin-top: 15px;
        }
        .card-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #5c4036;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: #8D6E63;
            box-shadow: 0 0 0 2px rgba(141, 110, 99, 0.2);
        }
        .checkout-btn {
            width: 100%;
            padding: 14px;
            font-size: 1.1rem;
            margin-top: 10px;
            background: #8D6E63;
            border: none;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .checkout-btn:hover {
            background: #7a5c52;
        }
        .back-to-cart {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #8D6E63;
            text-decoration: none;
        }
        .back-to-cart:hover {
            text-decoration: underline;
        }
        .success-message {
            text-align: center;
            padding: 40px 20px;
        }
        .success-icon {
            font-size: 4rem;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            .checkout-container {
                padding: 20px 15px;
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
                <a href="contact.php">Contact Us</a>
                <a href="cart.php" class="btn btn-outline btn-sm"><i class="fas fa-shopping-cart"></i> Cart</a>
                <a href="profile.php" class="btn btn-outline btn-sm">Account</a>
                <a href="logout.php" class="btn btn-sm">Logout</a>
            </div>
        </nav>
    </div>
</header>

<section class="checkout-container">
    <?php if (!empty($orderError)): ?>
        <div class="profile-message profile-message--error">
            <?php echo htmlspecialchars($orderError); ?>
        </div>
        <a href="cart.php" class="btn">Back to Cart</a>
    <?php elseif ($orderPlaced): ?>
        <div class="success-message">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="checkout-title">Order Confirmed!</h2>
            <p class="checkout-subtitle">Your order #<?php echo (int)$orderId; ?> has been received.</p>
            <p style="margin: 20px 0; color: #555; max-width: 600px; margin: 0 auto 30px; line-height: 1.6;">
                We've started preparing your order with care. You'll receive a confirmation email shortly. 
                For delivery orders, we'll contact you to confirm the delivery time.
            </p>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="profile.php?tab=orders" class="btn" style="min-width: 200px;">
                    <i class="fas fa-box-open" style="margin-right: 8px;"></i>View My Orders
                </a>
                <a href="menu.php" class="btn btn-outline" style="min-width: 200px;">
                    <i class="fas fa-utensils" style="margin-right: 8px;"></i>Continue Shopping
                </a>
            </div>
        </div>
    <?php else: ?>
        <h1 class="checkout-title">Checkout</h1>
        <p class="checkout-subtitle">Review your order and confirm your purchase</p>
        
        <div class="checkout-grid">
            <div class="checkout-details">
                <h3 class="order-summary-title">Order Summary</h3>
                <?php foreach ($cartItems as $item): ?>
                    <div class="order-item">
                        <div>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($item['product']['name']); ?></div>
                            <div style="font-size: 0.9em; color: #777;">₱<?php echo number_format($item['product']['price'], 2); ?> × <?php echo (int)$item['quantity']; ?></div>
                        </div>
                        <div style="font-weight: 500;">
                            ₱<?php echo number_format($item['product']['price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-total">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Total:</span>
                        <span>₱<?php echo number_format($cartTotal, 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="checkout-summary">
                <h3 class="order-summary-title">Delivery Information</h3>
                <div class="delivery-info">
                    <?php if (!empty($profile['address'])): ?>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name'] ?? ''); ?></p>
                        <p><strong>Phone:</strong> <?php echo !empty($profile['phone']) ? htmlspecialchars($profile['phone']) : 'Not provided'; ?></p>
                        <p><strong>Address:</strong> 
                            <?php 
                                echo htmlspecialchars($profile['address']);
                                if (!empty($profile['city'])) echo ', ' . htmlspecialchars($profile['city']);
                                if (!empty($profile['postal_code'])) echo ', ' . htmlspecialchars($profile['postal_code']);
                            ?>
                        </p>
                    <?php else: ?>
                        <p>Please <a href="profile.php" style="color: #8D6E63; text-decoration: underline;">update your profile</a> with your delivery information before placing an order.</p>
                    <?php endif; ?>
                </div>

                <div class="payment-method">
                    <h3 class="order-summary-title">Payment Method</h3>
                    <div class="payment-option">
                        <input type="radio" id="cash_on_delivery" name="payment_method" value="cash_on_delivery" checked>
                        <label for="cash_on_delivery">
                            <span class="payment-icon"><i class="fas fa-money-bill-wave"></i></span>
                            <span>Cash on Delivery</span>
                        </label>
                        <div class="payment-details">
                            <p>Pay with cash upon delivery. An invoice will be provided with your order.</p>
                        </div>
                    </div>
                    
                    <div class="payment-option">
                        <input type="radio" id="credit_card" name="payment_method" value="credit_card">
                        <label for="credit_card">
                            <span class="payment-icon"><i class="far fa-credit-card"></i></span>
                            <span>Credit/Debit Card</span>
                        </label>
                        <div class="payment-details">
                            <div class="card-element">
                                <div class="form-group">
                                    <label for="card_number">Card Number</label>
                                    <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" pattern="[0-9\s]{13,19}">
                                </div>
                                <div class="card-row">
                                    <div class="form-group">
                                        <label for="expiry_date">Expiry Date</label>
                                        <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5" pattern="(0[1-9]|1[0-2])\/[0-9]{2}">
                                    </div>
                                    <div class="form-group">
                                        <label for="cvv">CVV</label>
                                        <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4" pattern="[0-9]{3,4}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="card_name">Name on Card</label>
                                    <input type="text" id="card_name" name="card_name" placeholder="John Doe">
                                </div>
                            </div>
                            <div style="margin-top: 15px; font-size: 0.8rem; color: #666; display: flex; align-items: center;">
                                <i class="fas fa-lock" style="margin-right: 8px; color: #4CAF50;"></i>
                                <span>Your payment is secure and encrypted</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($profile['address'])): ?>
                    <form method="POST" action="checkout.php">
                        <button type="submit" name="confirm_order" value="1" class="checkout-btn">
                            <i class="fas fa-lock" style="margin-right: 8px;"></i>Confirm Order
                        </button>
                        <a href="cart.php" class="back-to-cart">
                            <i class="fas fa-arrow-left" style="margin-right: 5px;"></i>Back to Cart
                        </a>
                    </form>
                <?php else: ?>
                    <a href="profile.php" class="checkout-btn" style="text-align: center; text-decoration: none; display: block;">
                        <i class="fas fa-user-edit" style="margin-right: 8px;"></i>Complete Your Profile
                    </a>
                <?php endif; ?>
                
                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; font-size: 0.85rem; color: #777; text-align: center;">
                    <p><i class="fas fa-lock" style="margin-right: 5px;"></i>Secure Checkout</p>
                    <p style="margin-top: 5px;">Your payment information is secure and encrypted</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<footer class="site-footer">
    <div class="container">
        <div class="footer-bottom">
            <p>&copy; 2026 La Maria Bakehouse. All rights reserved.</p>
        </div>
    </div>
</footer>
</body>
</html>
