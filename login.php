<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/config/db.php';
ensureTables();
$loginMessage = null;
$signupSuccess = (isset($_GET['signup']) && $_GET['signup'] === 'success');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $user = findUserByEmail($email);
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role']
        ];
        $redirect = $_GET['redirect'] ?? 'index';
        if ($redirect === 'checkout') {
            header('Location: checkout.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $loginMessage = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | La Maria Bakehouse</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
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
                        <a href="profile.php" class="btn btn-outline btn-sm">Account</a>
                        <a href="logout.php" class="btn btn-sm">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="active">Login</a>
                        <a href="signup.php" class="btn btn-sm">Sign Up</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Login Form Section -->
    <section class="auth-container">
        <div class="auth-box">
            <h2>Welcome Back</h2>
            <p class="welcome-message">It's sweet to see you again! Your daily dose of happiness is just a click away.</p>
            <?php if ($signupSuccess): ?>
                <p class="success" style="margin-bottom:12px; color:#2e7d32;">Account created. Please log in.</p>
            <?php endif; ?>
            <?php if ($loginMessage): ?>
                <p class="error" style="margin-bottom:12px; color:#b00020;"><?php echo htmlspecialchars($loginMessage); ?></p>
            <?php endif; ?>
            <form method="POST" action="login.php" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn" style="width: 100%;">Login</button>
                <div class="form-footer">
                    <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                </div>
            </form>
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
