<?php
session_start();
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }
}
if (!isset($_SESSION['user'])) {
    header('Location: landing.php');
    exit;
}

require_once __DIR__ . '/products.php';
$featuredProducts = array_slice(getFeaturedProducts(), 0, 6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Maria Bakehouse | Artisan Pastries</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header / Navigation -->
    <header>
        <div class="container">
            <nav>
                <a href="index.php" class="logo">
                    <img src="assets/images/logolmb.png" alt="La Maria Bakehouse logo" class="logo-img">
                    <span>La Maria Bakehouse</span>
                </a>
                <div class="nav-links">
                    <a href="index.php" class="active">Home</a>
                    <a href="menu.php">Menu</a>
                    <a href="about.php">About</a>
                    <a href="contact.php">Contact Us</a>
                    <?php if (isset($_SESSION['user'])): ?>
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

    <!-- Hero Section -->
    <section class="hero hero-home">
        <div class="container">
            <div class="hero-content fade-up">
                <h1>Where Every Bite Feels Warm and Familiar</h1>
                <p class="hero-lead">Handcrafted with care and the finest ingredients, our pastries offer a delightful crunch, delicate flakiness, and a soft, buttery finish.</p>
                <div class="hero-btns">
                    <a href="signup.php" class="btn">Order Now</a>
                    <a href="#featured" class="btn btn-outline hero-outline">View Menu</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Pastries -->
    <section id="featured" class="featured">
        <div class="container">
            <p class="section-eyebrow">Signature Picks</p>
            <h2 class="section-title">Featured Delights</h2>
            <div class="section-divider"></div>
            <div class="pastry-grid fade-up">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="pastry-card">
                        <div class="pastry-img">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <div class="pastry-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p><?php echo htmlspecialchars($product['description']); ?></p>
                            <span class="price">₱<?php echo number_format($product['price'], 2); ?></span>
                            <form method="POST" action="cart.php" class="add-to-cart-form">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                <input type="hidden" name="redirect" value="cart.php">
                                
                                <div class="quantity-selector">
                                    <button type="button" class="qty-btn minus"><i class="fas fa-minus"></i></button>
                                    <input type="number" name="quantity" value="1" min="1" readonly>
                                    <button type="button" class="qty-btn plus"><i class="fas fa-plus"></i></button>
                                </div>
                                
                                <button type="submit" class="btn">Order Now</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- About & Contact CTA -->
    <section class="contact-cta">
        <div class="container">
            <div class="contact-cta__inner fade-up">
                <p class="section-eyebrow">Learn more & get in touch</p>
                <h2 class="section-title">Our story and how to reach us</h2>
                <p>Discover La Maria’s heritage and find our location, hours, and contact details.</p>
                <a href="about.php" class="btn">About Us</a>
                <a href="contact.php" class="btn btn-outline" style="margin-left: 10px;">Contact Us</a>
            </div>
        </div>
    </section>

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
