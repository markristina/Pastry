<?php
session_start();

require_once __DIR__ . '/products.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'view';
$redirect = $_POST['redirect'] ?? 'cart.php';

function addToCart(string $productId, int $quantity = 1): void {
    if ($quantity < 1) {
        $quantity = 1;
    }

    $product = findProductById($productId);
    if (!$product) {
        return;
    }

    if (!isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = [
            'product' => $product,
            'quantity' => 0,
        ];
    }

    $_SESSION['cart'][$productId]['quantity'] += $quantity;
}

function removeFromCart(string $productId): void {
    unset($_SESSION['cart'][$productId]);
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);
    addToCart($productId, $quantity);

    header("Location: {$redirect}");
    exit;
}

if ($action === 'remove' && isset($_GET['product_id'])) {
    removeFromCart($_GET['product_id']);
    header('Location: cart.php');
    exit;
}

$cartItems = $_SESSION['cart'];
$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['product']['price'] * $item['quantity'];
}
$cartCount = 0;
foreach ($cartItems as $item) {
    $cartCount += (int)$item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | La Maria Bakehouse</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header>
    <div class="container">
        <nav>
            <a href="<?php echo isset($_SESSION['user']) ? 'index.php' : 'landing.php'; ?>" class="logo">
                <img src="assets/images/logolmb.png" alt="La Maria Bakehouse logo" class="logo-img">
                <span>La Maria Bakehouse</span>
            </a>
            <div class="nav-links">
                <a href="<?php echo isset($_SESSION['user']) ? 'index.php' : 'landing.php'; ?>">Home</a>
                <a href="menu.php">Menu</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact Us</a>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="cart.php" class="active btn btn-outline btn-sm"><i class="fas fa-shopping-cart"></i> Cart<?php if ($cartCount > 0): ?><span class="cart-badge"><?php echo (int)$cartCount; ?></span><?php endif; ?></a>
                    <a href="profile.php" class="btn btn-outline btn-sm">Account</a>
                    <a href="logout.php" class="btn btn-sm">Logout</a>
                <?php else: ?>
                    <a href="cart.php" class="active btn btn-outline btn-sm"><i class="fas fa-shopping-cart"></i> Cart</a>
                    <a href="login.php" class="btn btn-outline btn-sm">Login</a>
                    <a href="signup.php" class="btn btn-sm">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<section class="container" style="padding: 60px 0;">
    <h1>Your Cart</h1>
    <?php if (empty($cartItems)): ?>
        <p>Your cart is empty. <a href="menu.php">Browse pastries</a>.</p>
    <?php else: ?>
        <div class="admin-card admin-card--table" style="margin-top: 20px;">
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product']['name']); ?></td>
                            <td>₱<?php echo number_format($item['product']['price'], 2); ?></td>
                            <td><?php echo (int)$item['quantity']; ?></td>
                            <td>₱<?php echo number_format($item['product']['price'] * $item['quantity'], 2); ?></td>
                            <td>
                                <a href="cart.php?action=remove&product_id=<?php echo urlencode($item['product']['id']); ?>" class="btn btn-outline btn-sm" onclick="return confirm('Remove item?');">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin-top: 20px; display:flex; justify-content: space-between; align-items: center; flex-wrap:wrap; gap:12px;">
            <div>
                <h3>Total: ₱<?php echo number_format($cartTotal, 2); ?></h3>
                <p style="font-size: 0.9em; color: #666; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i> All prices in Philippine Peso (₱)
                </p>
            </div>
            <a href="checkout.php" class="btn">
                <i class="fas fa-lock"></i> Proceed to Checkout
            </a>
        </div>
    <?php endif; ?>
</section>

</body>
</html>
