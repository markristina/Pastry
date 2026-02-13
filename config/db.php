<?php
// PDO connection helper for MySQL (XAMPP default: root with no password)
// Adjust credentials if your MySQL setup differs

function getPDO(): PDO {
    $host = 'localhost';
    $db   = 'pastry_db';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    return new PDO($dsn, $user, $pass, $options);
}

function ensureTables(): void {
    $pdo = getPDO();

    // Create tables if they don't exist
    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS users (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS profiles (
        user_id BIGINT UNSIGNED PRIMARY KEY,
        phone VARCHAR(20) NULL,
        address TEXT NULL,
        city VARCHAR(80) NULL,
        postal_code VARCHAR(20) NULL,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS orders (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        total DECIMAL(10,2) NOT NULL DEFAULT 0,
        customer_name VARCHAR(100) NULL,
        customer_phone VARCHAR(20) NULL,
        customer_address TEXT NULL,
        customer_city VARCHAR(80) NULL,
        customer_postal VARCHAR(20) NULL,
        payment_method VARCHAR(50) NULL DEFAULT 'cash_on_delivery',
        payment_status VARCHAR(20) NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    // Add payment_method column if it doesn't exist (for existing tables)
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NULL DEFAULT 'cash_on_delivery' AFTER customer_postal");
    } catch (PDOException $e) {
        // Column may already exist, ignore error
    }

    // Add payment_status column if it doesn't exist (for existing tables)
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(20) NULL DEFAULT 'pending' AFTER payment_method");
    } catch (PDOException $e) {
        // Column may already exist, ignore error
    }

    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS order_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id BIGINT UNSIGNED NOT NULL,
        product_id VARCHAR(80) NOT NULL,
        product_name VARCHAR(200) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS products (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(100) NOT NULL UNIQUE,
        name VARCHAR(200) NOT NULL,
        description TEXT NULL,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255) NULL,
        category VARCHAR(100) NULL,
        badge VARCHAR(60) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);

    // Seed default bakery owner/admin account if none exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = (int)$stmt->fetchColumn();
    if ($adminCount === 0) {
        $defaultEmail = 'bakehouse@gmail.com';
        $defaultName = 'La Maria Owner';
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $seed = $pdo->prepare('INSERT IGNORE INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ? )');
        $seed->execute([$defaultName, $defaultEmail, $defaultPassword, 'admin']);
    }
}

// Basic user CRUD helpers
function findUserByEmail(string $email): ?array {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function findUserById(int $id): ?array {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function createUser(string $name, string $email, string $passwordHash, string $role = 'customer'): int {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, $passwordHash, $role]);
    return (int)$pdo->lastInsertId();
}

function updateUser(int $id, string $name, string $email, string $role): bool {
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
    return $stmt->execute([$name, $email, $role, $id]);
}

function updatePassword(int $userId, string $newPasswordHash): bool {
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    return $stmt->execute([$newPasswordHash, $userId]);
}

function deleteUser(int $id): bool {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    return $stmt->execute([$id]);
}


function getAllUsers(): array {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

// Profile helpers
function getProfileByUserId(int $userId): ?array {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM profiles WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function upsertProfile(int $userId, ?string $phone, ?string $address, ?string $city, ?string $postalCode): bool {
    $pdo = getPDO();
    // Upsert pattern: try update, if 0 rows affected then insert
    $stmt = $pdo->prepare('UPDATE profiles SET phone = ?, address = ?, city = ?, postal_code = ? WHERE user_id = ?');
    $stmt->execute([$phone, $address, $city, $postalCode, $userId]);
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare('INSERT INTO profiles (user_id, phone, address, city, postal_code) VALUES (?, ?, ?, ?, ?)');
        return $stmt->execute([$userId, $phone, $address, $city, $postalCode]);
    }
    return true;
}

// Order helpers
function createOrder(int $userId, float $total, ?string $customerName, ?string $customerPhone, ?string $customerAddress, ?string $customerCity, ?string $customerPostal, string $paymentMethod = 'cash_on_delivery', string $status = 'pending'): int {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO orders (user_id, total, customer_name, customer_phone, customer_address, customer_city, customer_postal, payment_method, status) VALUES (?, ROUND(?, 2), ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $userId, 
        $total, 
        $customerName, 
        $customerPhone, 
        $customerAddress, 
        $customerCity, 
        $customerPostal,
        $paymentMethod,
        $status
    ]);
    return (int)$pdo->lastInsertId();
}

function addOrderItem(int $orderId, string $productId, string $productName, float $price, int $quantity): void {
    if ($quantity <= 0) {
        throw new InvalidArgumentException('Quantity must be greater than 0');
    }
    if ($price < 0) {
        throw new InvalidArgumentException('Price cannot be negative');
    }
    
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ROUND(?, 2), ?)');
    $stmt->execute([$orderId, $productId, $productName, $price, $quantity]);
}

function getOrdersByUserId(int $userId): array {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getAllOrders(): array {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT o.*, u.name as user_name, u.email as user_email FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC');
    return $stmt->fetchAll();
}

function getOrderById(int $orderId): ?array {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT o.*, u.name as user_name, u.email as user_email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getOrderItems(int $orderId): array {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function updateOrderStatus(int $orderId, string $status): bool {
    $allowed = ['pending', 'processing', 'ready', 'completed', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
    return $stmt->execute([$status, $orderId]);
}

// ========================
// Notification System
// ========================

// Create notifications table
function ensureNotificationTable(): void {
    $pdo = getPDO();
    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS notifications (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        type ENUM('new_order', 'product_added', 'product_updated', 'product_deleted', 'new_user') NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        reference_id VARCHAR(100) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL);
}

// Create a notification
function createNotification(string $type, string $title, string $message, ?string $referenceId = null): int {
    ensureNotificationTable();
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO notifications (type, title, message, reference_id) VALUES (?, ?, ?, ?)');
    $stmt->execute([$type, $title, $message, $referenceId]);
    return (int)$pdo->lastInsertId();
}

// Get all notifications (for admin)
function getAllNotifications(int $limit = 50): array {
    ensureNotificationTable();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM notifications ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get unread notifications count
function getUnreadNotificationsCount(): int {
    ensureNotificationTable();
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT COUNT(*) FROM notifications WHERE is_read = 0');
    return (int)$stmt->fetchColumn();
}

// Get recent unread notifications
function getUnreadNotifications(int $limit = 10): array {
    ensureNotificationTable();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Mark notification as read
function markNotificationAsRead(int $id): bool {
    ensureNotificationTable();
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?');
    return $stmt->execute([$id]);
}

// Mark all notifications as read
function markAllNotificationsAsRead(): bool {
    ensureNotificationTable();
    $pdo = getPDO();
    $stmt = $pdo->query('UPDATE notifications SET is_read = 1 WHERE is_read = 0');
    return $stmt->rowCount() > 0;
}

// Delete old notifications (keep last 100)
function cleanupOldNotifications(): void {
    ensureNotificationTable();
    $pdo = getPDO();
    $stmt = $pdo->query('DELETE FROM notifications WHERE id NOT IN (SELECT id FROM (SELECT id FROM notifications ORDER BY created_at DESC LIMIT 100) AS recent)');
}

// Helper function to notify admin of new order
function notifyAdminOfNewOrder(int $orderId, string $customerName, float $total): void {
    $message = "New order #{$orderId} from {$customerName} - ₱" . number_format($total, 2);
    createNotification('new_order', 'New Order Received!', $message, (string)$orderId);
}

// Helper function to notify admin of new product
function notifyAdminOfNewProduct(string $productName): void {
    createNotification('product_added', 'New Product Added!', "Product '{$productName}' has been added to the catalog.");
}

// Helper function to notify admin of product update
function notifyAdminOfProductUpdate(string $productName): void {
    createNotification('product_updated', 'Product Updated!', "Product '{$productName}' has been updated.");
}

// Helper function to notify admin of product deletion
function notifyAdminOfProductDeletion(string $productName): void {
    createNotification('product_deleted', 'Product Deleted!', "Product '{$productName}' has been removed from the catalog.");
}

// Helper function to notify admin of new user registration
function notifyAdminOfNewUser(string $userName, string $userEmail): void {
    $message = "New customer registered: {$userName} ({$userEmail})";
    createNotification('new_user', 'New Customer!', $message);
}
