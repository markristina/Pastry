<?php
session_start();
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }
}
$isLoggedIn = isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | La Maria Bakehouse</title>
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
                    <a href="menu.php">Menu</a>
                    <a href="about.php" class="active">About</a>
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

    <!-- About Page Hero -->
    <section class="hero hero-contact">
        <div class="container">
            <div class="hero-content fade-up">
                <p class="hero-eyebrow">Our Story</p>
                <h1>About La Maria Bakehouse</h1>
                <p class="hero-subtitle">Bread that still tastes like home handcrafted in Davao with tradition, quality ingredients, and a whole lot of heart.</p>
            </div>
        </div>
    </section>

    <!-- Our Story -->
    <section class="about about-page">
        <div class="container">
            <div class="about-content fade-up">
                <div class="about-text">
                    <p class="section-eyebrow">Where It Started</p>
                    <h2 class="section-title">Baking traditions passed down</h2>
                    <div class="section-divider"></div>
                    <p>Founded by sisters Mara and Lia, La Maria Bakehouse began in a home oven in Davao. What started as care packages for neighbors grew into a bakehouse where every batch still carries a handwritten note, a quiet prayer, and the belief that bread should taste like home.</p>
                    <p>Every recipe is rooted in Lola Maria’s kitchen notes, elevated with modern patisserie skill. We believe in the magic of simple, high-quality ingredients—single-origin cacao, Bukidnon butter, cage-free eggs—and we start baking before the sun rises so you can enjoy pastries still warm to the touch.</p>
                    <p>Whether you’re picking up a box for merienda or planning a family celebration, our team is here to guide you through flavors, pairings, and perfect portions.</p>
                    <a href="contact.php" class="btn">Get in Touch</a>
                </div>
                <div class="about-img">
                    <img src="assets/images/La Maria.png" alt="Inside La Maria Bakehouse">
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="why-choose-us">
        <div class="container">
            <p class="section-eyebrow">Why Choose La Maria</p>
            <h2 class="section-title">Warmth in every layer</h2>
            <div class="section-divider"></div>
            <div class="features-grid fade-up">
                <div class="feature-card">
                    <i class="fas fa-book"></i>
                    <h3>Heritage Meets Craft</h3>
                    <p>Every recipe is rooted in Lola Maria’s kitchen notes, elevated with modern patisserie skill.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-leaf"></i>
                    <h3>Premium, Local Ingredients</h3>
                    <p>We choose single-origin cacao, Bukidnon butter, and cage-free eggs for honest flavor.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-bread-slice"></i>
                    <h3>Fresh Daily, Never Frozen</h3>
                    <p>Our bakers start at 3 a.m. so you can enjoy pastries still warm to the touch.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-heart"></i>
                    <h3>Made for Gathering</h3>
                    <p>Custom boxes, event platters, and café pick-up keep celebrations effortless.</p>
                </div>
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
