<?php session_start();
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | La Maria Bakehouse</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
</head>
<body>
    <!-- Header / Navigation -->
    <header>
        <div class="container">
            <nav>
                <a href="landing.php" class="logo">
                    <img src="assets/images/logolmb.png" alt="La Maria Bakehouse logo" class="logo-img">
                    <span>La Maria Bakehouse</span>
                </a>
                <div class="nav-links">
                    <a href="landing.php">Home</a>
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

    <!-- Landing Hero -->
    <section class="hero">
        <div class="hero-content fade-up">
            <p class="hero-eyebrow">La Maria Bakehouse</p>
            <h1>Handcrafted breads and pastries, baked fresh each morning</h1>
            <p class="hero-subtitle">From slow-fermented sourdough to buttery, flaky croissants — we use time-honored techniques, premium ingredients, and a whole lot of heart.</p>
            <div class="hero-btns">
                <a href="signup.php" class="btn">Order Now</a>
                <a href="menu.php" class="btn btn-outline hero-outline">View Menu</a>
                <a href="contact.php" class="btn btn-light">Visit Us</a>
            </div>
            <?php if (!isset($_SESSION['user'])): ?>
                <p class="hero-note">New here? <a href="signup.php">Create an account</a> or <a href="login.php">log in</a>.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="why-choose-us" id="why">
        <div class="container">
            <p class="section-eyebrow">Why Choose La Maria Bakehouse</p>
            <h2 class="section-title">Warmth in every layer</h2>
            <div class="section-divider"></div>
            <div class="features-grid fade-up">
                <article class="feature-card">
                    <h3>Heritage Meets Craft</h3>
                    <p>Every recipe is rooted in Lola Maria&rsquo;s kitchen notes, elevated with modern patisserie skill.</p>
                </article>
                <article class="feature-card">
                    <h3>Premium, Local Ingredients</h3>
                    <p>We choose single-origin cacao, Bukidnon butter, and cage-free eggs for honest flavor.</p>
                </article>
                <article class="feature-card">
                    <h3>Fresh Daily, Never Frozen</h3>
                    <p>Our bakers start at 3&nbsp;a.m. so you can enjoy pastries still warm to the touch.</p>
                </article>
                <article class="feature-card">
                    <h3>Made for Gathering</h3>
                    <p>Custom boxes, event platters, and café pick-up keep celebrations effortless.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Best Sellers -->
    <section class="best-sellers" id="best-sellers">
        <div class="container">
            <div class="section-header">
                <p class="section-eyebrow">Best Sellers</p>
                <h2 class="section-title">House Favorites Locals Crave</h2>
                <div class="section-divider"></div>
                <p class="section-subtitle">Discover our most-loved pastries, crafted with premium ingredients and traditional recipes</p>
            </div>
            
            <div class="pastry-grid fade-up">
                <article class="pastry-card">
                    <div class="pastry-badge">Bestseller</div>
                    <div class="pastry-image">
                        <img src="assets/images/10.jpg" alt="Tableya Sea Salt Croissant" loading="lazy">
                    </div>
                    <div class="pastry-info">
                        <h3>Tableya Sea Salt Croissant</h3>
                        <p>Flaky layers filled with dark tableya ganache, finished with Maldon sea salt.</p>
                        <div class="pastry-cta">
                            <a href="menu.php#tableya_sea_salt_croissant" class="btn btn-sm btn-outline">View Details</a>
                        </div>
                    </div>
                </article>
                
                <article class="pastry-card">
                    <div class="pastry-badge">Customer Favorite</div>
                    <div class="pastry-image">
                        <img src="assets/images/11.webp" alt="Calamansi Honey Cheesecake" loading="lazy">
                    </div>
                    <div class="pastry-info">
                        <h3>Calamansi Honey Cheesecake</h3>
                        <p>Silky baked cheesecake brightened with calamansi curd and wildflower honey drizzle.</p>
                        <div class="pastry-cta">
                            <a href="menu.php#calamansi_honey_cheesecake" class="btn btn-sm btn-outline">View Details</a>
                        </div>
                    </div>
                </article>
                
                <article class="pastry-card">
                    <div class="pastry-badge">Chef's Special</div>
                    <div class="pastry-image">
                        <img src="assets/images/12.webp" alt="Coconut Dulce Ensaymada" loading="lazy">
                    </div>
                    <div class="pastry-info">
                        <h3>Coconut Dulce Ensaymada</h3>
                        <p>Pillowy brioche swirl topped with muscovado butter, coconut flakes, and queso de bola.</p>
                        <div class="pastry-cta">
                            <a href="menu.php#coconut_dulce_ensaymada" class="btn btn-sm btn-outline">View Details</a>
                        </div>
                    </div>
                </article>
            </div>
            
            <div class="text-center mt-5">
                <a href="menu.php" class="btn">View Full Menu</a>
            </div>
        </div>
    </section>

    <!-- About teaser + CTA -->
    <section class="about about-landing" id="about">
        <div class="container">
            <div class="about-text fade-up">
                <p class="section-eyebrow">About La Maria</p>
                <h2>Bread that still tastes like home</h2>
                <div class="section-divider"></div>
                <p>Founded by sisters Mara and Lia in Davao, La Maria Bakehouse brings heritage recipes and premium local ingredients to every pastry. Fresh daily, never frozen—with a belief that bread should taste like home.</p>
                <div class="hero-btns" style="margin-top: 24px;">
                    <a href="about.php" class="btn">Our Story</a>
                    <a href="contact.php" class="btn btn-outline">Visit & Contact</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Strip -->
    <section class="cta-strip">
        <div class="container cta-container fade-up">
            <div class="cta-text">
                <p class="section-eyebrow">Ready to order?</p>
                <h2>Reserve your pastries today</h2>
                <p>Create an account, browse the menu, and pick up warm bakes or arrange delivery.</p>
            </div>
            <div class="cta-buttons">
                <a href="signup.php" class="btn">Order Now</a>
                <a href="menu.php" class="btn btn-outline">View Menu</a>
            </div>
        </div>
    </section>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="landing.php" class="logo">
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
