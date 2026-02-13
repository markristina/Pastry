<?php
session_start();
require_once __DIR__ . '/config/db.php';
ensureTables();

$signupMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['fullname'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm-password'] ?? '';
    $role = 'customer';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $signupMessage = 'Please fill in all required fields with valid information.';
    } elseif ($password !== $confirm) {
        $signupMessage = 'Passwords do not match.';
    } else {
        $existing = findUserByEmail($email);
        if ($existing) {
            $signupMessage = 'Email already registered. Please login.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $userId = createUser($name, $email, $passwordHash, $role);
                header('Location: login.php?signup=success');
                exit;
            } catch (Throwable $e) {
                $signupMessage = 'Signup failed. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | La Maria Bakehouse</title>
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
                        <a href="login.php">Login</a>
                        <a href="signup.php" class="btn btn-sm active">Sign Up</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Signup Form Section -->
    <section class="auth-container">
        <div class="auth-box">
            <h2>Create Account</h2>
            <p class="welcome-message">Create your account to place orders, save your details, and enjoy fresh pastries from La Maria Bakehouse.</p>
            <?php if ($signupMessage): ?>
                <p class="error" style="margin-bottom:12px; color:#b00020;"><?php echo htmlspecialchars($signupMessage); ?></p>
            <?php endif; ?>
            <form method="POST" action="signup.php" id="signupForm">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="offers" name="offers">
                    <label for="offers">Send me special offers and updates (optional)</label>
                </div>
                <button type="submit" class="btn" style="width: 100%;">Sign Up</button>
                <div class="form-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
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
