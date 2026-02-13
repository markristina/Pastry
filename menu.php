<?php
session_start();
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }
}
require_once __DIR__ . '/products.php';
$products = getFeaturedProducts();
$isLoggedIn = isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu | La Maria Bakehouse</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <div class="container">
        <nav>
            <a href="<?php echo $isLoggedIn ? 'index.php' : 'landing.php'; ?>" class="logo">
                <img src="assets/images/logolmb.png" alt="La Maria Bakehouse logo" class="logo-img">
                <span>La Maria Bakehouse</span>
            </a>
            <div class="nav-links">
                <a href="<?php echo $isLoggedIn ? 'index.php' : 'landing.php'; ?>">Home</a>
                <a href="menu.php" class="active">Menu</a>
                <a href="about.php">About</a>
                    <a href="contact.php">Contact Us</a>
                <?php if ($isLoggedIn): ?>
                    <?php if (($_SESSION['user']['role'] ?? 'customer') === 'admin'): ?>
                        <a href="admin.php" class="btn btn-sm">Admin</a>
                    <?php endif; ?>
                    <a href="cart.php" class="btn btn-outline btn-sm"><i class="fas fa-shopping-cart"></i> Cart<?php if ($cartCount > 0): ?><span class="cart-badge"><?php echo (int)$cartCount; ?></span><?php endif; ?></a>
                    <a href="profile.php" class="btn btn-outline btn-sm">Account</a>
                    <a href="logout.php" class="btn btn-sm">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline btn-sm">Login</a>
                    <a href="signup.php" class="btn btn-sm">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<section class="hero hero-menu">
    <div class="container">
        <div class="hero-content fade-up">
            <p class="hero-eyebrow">Our Menu</p>
            <h1>Handmade pastries, ready when you are</h1>
            <p class="hero-subtitle">Browse the full bakehouse selection—from buttery morning viennoiserie to celebration-ready cakes. Tap Order to send items straight to your cart.</p>
            <div class="hero-btns">
                <a href="#menu-grid" class="btn">View Pastries</a>
                <a href="cart.php" class="btn btn-outline hero-outline">Go to Cart</a>
            </div>
        </div>
    </div>
</section>

<section class="menu-section" id="menu-grid">
    <div class="container">
        <div class="menu-header">
            <p class="section-eyebrow">Bakehouse Selection</p>
            <h2 class="section-title">Curated classics & seasonal treats</h2>
        </div>
        <div class="pastry-grid">
            <?php foreach ($products as $product): ?>
                <article class="pastry-card" id="<?php echo htmlspecialchars($product['id']); ?>">
                    <div class="pastry-img">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="pastry-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <span class="price">₱<?php echo number_format($product['price'], 2); ?></span>
                        <?php if ($isLoggedIn): ?>
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="redirect" value="menu.php">
                                <button type="submit" class="btn">Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <div class="pastry-action">
                                <a href="login.php" class="btn">Order Now</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="<?php echo $isLoggedIn ? 'index.php' : 'landing.php'; ?>" class="logo">
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
                    <li><a href="mailto:hello@lamariabakehouse.com">hello@lamariabakehouse.com</a></li>
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
