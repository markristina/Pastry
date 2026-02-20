<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: landing.php');
    exit;
}
if (($_SESSION['user']['role'] ?? 'customer') !== 'admin') {
    header('Location: index.php');
    exit;
}
require_once __DIR__ . '/config/db.php';
ensureTables();

require_once __DIR__ . '/products.php';

// Handle product create/update/delete actions
$productMessage = null;
$productAction = $_POST['product_action'] ?? $_GET['product_action'] ?? null;
try {
    if ($productAction === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['product_name'] ?? '');
        $price = (float)($_POST['product_price'] ?? 0);
        $quantity = (int)($_POST['product_quantity'] ?? 0);
        $description = trim($_POST['product_description'] ?? '');
        $image = trim($_POST['product_image'] ?? '');
        $categoryId = !empty($_POST['product_category_id']) ? (int)($_POST['product_category_id']) : null;
        $badge = trim($_POST['product_badge'] ?? '');
        $isActive = isset($_POST['product_active']);

        if ($name === '' || $price <= 0) {
            $productMessage = 'Please provide a valid product name and price.';
        } else {
            createProduct($name, $price, $description ?: null, $image ?: null, $categoryId, $badge ?: null, $isActive, $quantity);
            // Notify admin of new product
            createAdminNotification('product_added', 'New Product Added!', "Product '{$name}' has been added to the catalog.");
            $productMessage = 'Product created.';
        }
    } elseif ($productAction === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)($_POST['category_id']) : null;
        $badge = trim($_POST['badge'] ?? '');
        $isActive = isset($_POST['is_active']);
        $quantity = (int)($_POST['quantity'] ?? 0);

        // Get old product name before update
        $oldProduct = null;
        $products = getAllProducts();
        foreach ($products as $p) {
            if ((int)$p['id'] === $id) {
                $oldProduct = $p;
                break;
            }
        }

        if ($id <= 0 || $name === '' || $price <= 0) {
            $productMessage = 'Please provide valid product details.';
        } else {
            updateProduct($id, $name, $price, $description, $image, $categoryId, $badge, $isActive, $quantity);
            // Notify admin of product update
            createAdminNotification('product_updated', 'Product Updated!', "Product '{$name}' has been updated.");
            $productMessage = 'Product updated.';
        }
    } elseif ($productAction === 'archive' && isset($_GET['product_id'])) {
        $id = (int)($_GET['product_id'] ?? 0);
        // Get product name before archiving - need to get from all products including active ones
        $productName = '';
        $allProductsForArchive = getAllProducts(); // This gets active products before archiving
        foreach ($allProductsForArchive as $p) {
            if ((int)$p['id'] === $id) {
                $productName = $p['name'];
                break;
            }
        }
        if ($id > 0) {
            archiveProduct($id);
            // Notify admin of product archiving
            if ($productName) {
                createAdminNotification('product_archived', 'Product Archived!', "Product '{$productName}' has been archived and is no longer visible in the storefront.");
            }
            $productMessage = 'Product archived. You can restore it from the archive section.';
        }
    } elseif ($productAction === 'unarchive' && isset($_GET['product_id'])) {
        $id = (int)($_GET['product_id'] ?? 0);
        // Get product name before unarchiving - need to get from archived products
        $productName = '';
        $archivedProductsForRestore = getArchivedProducts();
        foreach ($archivedProductsForRestore as $p) {
            if ((int)$p['id'] === $id) {
                $productName = $p['name'];
                break;
            }
        }
        if ($id > 0) {
            unarchiveProduct($id);
            // Notify admin of product restoration
            if ($productName) {
                createAdminNotification('product_restored', 'Product Restored!', "Product '{$productName}' has been restored and is now visible in the storefront.");
            }
            $productMessage = 'Product restored to active catalog.';
        }
    } elseif ($productAction === 'delete' && isset($_GET['product_id'])) {
        $id = (int)($_GET['product_id'] ?? 0);
        // Get product name before deletion - need to get from active products
        $productName = '';
        $allProductsForDelete = getAllProducts();
        foreach ($allProductsForDelete as $p) {
            if ((int)$p['id'] === $id) {
                $productName = $p['name'];
                break;
            }
        }
        if ($id > 0) {
            // Permanently delete the product
            $pdo = getPDO();
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $result = $stmt->execute([$id]);
            
            if ($result) {
                // Notify admin of product deletion
                if ($productName) {
                    createAdminNotification('product_deleted', 'Product Deleted!', "Product '{$productName}' has been permanently deleted from the system.");
                }
                $productMessage = 'Product permanently deleted.';
            } else {
                $productMessage = 'Failed to delete product.';
            }
        }
    }
} catch (Throwable $e) {
    $productMessage = 'Product operation failed. Please try again.';
}

$products = getAllProducts();
$categories = getAllCategories();
$archivedProducts = getArchivedProducts();

// Notifications
$unreadCount = getUnreadAdminNotificationsCount();
$recentNotifications = getAdminNotifications(5);
$allNotifications = getAdminNotifications(20);

// Handle notification actions
$notificationAction = $_GET['notification_action'] ?? null;
if ($notificationAction === 'mark_read' && isset($_GET['notification_id'])) {
    $notifId = (int)$_GET['notification_id'];
    if ($notifId > 0) {
        markAdminNotificationAsRead($notifId);
        header('Location: admin-products.php#notifications');
        exit;
    }
} elseif ($notificationAction === 'mark_all_read') {
    markAllAdminNotificationsAsRead();
    header('Location: admin-products.php#notifications');
    exit;
}

// Simple dashboard metrics
$totalProducts = count($products);
$totalArchived = count($archivedProducts);
$totalCategories = count($categories);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management | La Maria Bakehouse</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-sidebar__brand">
            <a href="index.php" class="logo">
                <img src="assets/images/logolmb.png" alt="La Maria Bakehouse logo" class="logo-img">
                <span>La Maria Admin</span>
            </a>
        </div>
        <nav class="admin-sidebar__nav">
            <a href="admin.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
            <a href="admin-orders.php"><i class="fa-solid fa-receipt"></i><span>Orders</span></a>
            <a href="admin-products.php" class="active"><i class="fa-solid fa-bread-slice"></i><span>Products</span></a>
            <a href="admin-categories.php"><i class="fa-solid fa-tags"></i><span>Categories</span></a>
            <a href="admin.php#archive"><i class="fa-solid fa-archive"></i><span>Archive</span></a>
            <a href="admin.php#users"><i class="fa-solid fa-users"></i><span>Users</span></a>
        </nav>
        <a href="index.php" target="_blank"><i class="fa-solid fa-store"></i><span>View Storefront</span></a>
        <div class="admin-sidebar__footer">
            <a href="profile.php"><i class="fa-regular fa-user"></i><span>Account</span></a>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <p class="section-eyebrow">Catalog</p>
                <h1>Products Management</h1>
            </div>
            <div class="admin-topbar__right">
                <form class="admin-search" role="search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" placeholder="Search products…" aria-label="Search products">
                </form>
                
                <!-- Notification Bell -->
                <div class="admin-notification-wrapper">
                    <button type="button" class="admin-notification-btn" id="notificationBtn">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="admin-notification-badge"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="admin-notification-dropdown" id="notificationDropdown">
                        <div class="admin-notification-header">
                            <h4>Notifications</h4>
                            <?php if ($unreadCount > 0): ?>
                                <a href="?notification_action=mark_all_read" class="admin-notification-mark-all">Mark all read</a>
                            <?php endif; ?>
                        </div>
                        <div class="admin-notification-list">
                            <?php if (empty($allNotifications)): ?>
                                <div class="admin-notification-empty">No notifications yet</div>
                            <?php else: ?>
                                <?php foreach ($allNotifications as $notif): ?>
                                    <div class="admin-notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo (int)$notif['id']; ?>">
                                        <div class="admin-notification-icon">
                                            <?php 
                                            $icon = 'fa-bell';
                                            $iconClass = 'admin-notification-icon--default';
                                            switch($notif['type']) {
                                                case 'new_order':
                                                    $icon = 'fa-shopping-cart';
                                                    $iconClass = 'admin-notification-icon--order';
                                                    break;
                                                case 'product_added':
                                                    $icon = 'fa-plus-circle';
                                                    $iconClass = 'admin-notification-icon--product';
                                                    break;
                                                case 'product_updated':
                                                    $icon = 'fa-edit';
                                                    $iconClass = 'admin-notification-icon--product';
                                                    break;
                                                case 'product_deleted':
                                                    $icon = 'fa-trash';
                                                    $iconClass = 'admin-notification-icon--delete';
                                                    break;
                                                case 'new_user':
                                                    $icon = 'fa-user-plus';
                                                    $iconClass = 'admin-notification-icon--user';
                                                    break;
                                            }
                                            ?>
                                            <i class="fa-solid <?php echo $icon; ?> <?php echo $iconClass; ?>"></i>
                                        </div>
                                        <div class="admin-notification-content">
                                            <div class="admin-notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                            <div class="admin-notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                                            <div class="admin-notification-time"><?php echo date('M j, g:i A', strtotime($notif['created_at'])); ?></div>
                                        </div>
                                        <?php if (!$notif['is_read']): ?>
                                            <a href="?notification_action=mark_read&notification_id=<?php echo (int)$notif['id']; ?>" class="admin-notification-read-btn" title="Mark as read">
                                                <i class="fa-solid fa-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="admin-user-pill">
                    <?php
                    $adminName = $_SESSION['user']['name'] ?? 'Admin';
                    $adminInitial = strtoupper(substr($adminName, 0, 1));
                    ?>
                    <div class="admin-user-pill__avatar"><?php echo htmlspecialchars($adminInitial); ?></div>
                    <div class="admin-user-pill__meta">
                        <span class="admin-user-pill__name"><?php echo htmlspecialchars($adminName); ?></span>
                        <span class="admin-user-pill__role">Administrator</span>
                    </div>
                </div>
            </div>
        </header>

        <?php if ($productMessage): ?>
            <p class="admin-feedback admin-feedback--floating"><?php echo htmlspecialchars($productMessage); ?></p>
        <?php endif; ?>

        <section class="admin-kpis">
            <div class="admin-kpi-card">
                <p class="admin-kpi-label">Active Products</p>
                <p class="admin-kpi-value"><?php echo number_format($totalProducts); ?></p>
                <p class="admin-kpi-meta">Currently visible</p>
            </div>
            <div class="admin-kpi-card">
                <p class="admin-kpi-label">Archived Products</p>
                <p class="admin-kpi-value"><?php echo number_format($totalArchived); ?></p>
                <p class="admin-kpi-meta">Hidden from storefront</p>
            </div>
            <div class="admin-kpi-card">
                <p class="admin-kpi-label">Categories</p>
                <p class="admin-kpi-value"><?php echo number_format($totalCategories); ?></p>
                <p class="admin-kpi-meta">Product categories</p>
            </div>
        </section>

        <section class="admin-section">
            <div class="admin-dashboard__header">
                <div>
                    <p class="section-eyebrow">Catalog Management</p>
                    <h2>Products Management</h2>
                </div>
            </div>

            <!-- Test Button for Debugging -->
            <div class="admin-card">
                <h3>Debug Tools</h3>
                <button type="button" class="btn btn-outline" onclick="testEditModal()">Test Edit Modal</button>
                <button type="button" class="btn btn-outline" onclick="testConsoleLog()">Test Console Log</button>
            </div>

            <div class="admin-card admin-card--table admin-card--full">
                <div class="admin-table-header">
                    <h3>Product List</h3>
                    <div class="admin-table-info">
                        <span class="admin-table-count"><?php echo count($products); ?> products</span>
                    </div>
                </div>
                <div class="admin-table-container">
                    <table class="admin-table admin-table--fixed">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 80px;">Image</th>
                                <th style="width: 200px;">Name</th>
                                <th style="width: 100px;">Price</th>
                                <th style="width: 80px;">Qty</th>
                                <th style="width: 120px;">Category</th>
                                <th style="width: 100px;">Badge</th>
                                <th style="width: 80px;">Status</th>
                                <th style="width: 240px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><strong>#<?php echo (int)$p['id']; ?></strong></td>
                                <td>
                                    <div class="admin-product-image">
                                        <?php if (!empty($p['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="admin-product-thumbnail">
                                        <?php else: ?>
                                            <div class="admin-product-placeholder">
                                                <i class="fa-solid fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="admin-product-name">
                                        <?php echo htmlspecialchars($p['name']); ?>
                                        <?php if (!empty($p['badge'])): ?>
                                            <span class="admin-product-badge"><?php echo htmlspecialchars($p['badge']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><strong>₱<?php echo number_format((float)$p['price'], 2); ?></strong></td>
                                <td>
                                    <span class="admin-quantity <?php echo (int)$p['quantity'] <= 10 ? 'admin-quantity--low' : ''; ?>">
                                        <?php echo (int)$p['quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($p['category_name'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($p['badge'] ?? '—'); ?></td>
                                <td>
                                    <span class="admin-status admin-status--<?php echo !empty($p['is_active']) ? 'active' : 'inactive'; ?>">
                                        <?php echo !empty($p['is_active']) ? 'Visible' : 'Hidden'; ?>
                                    </span>
                                </td>
                                <td>
                                        <div class="admin-actions">
                                            <button type="button" class="admin-btn admin-btn--edit" onclick="editProduct(<?php echo (int)$p['id']; ?>, '<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>', <?php echo (float)$p['price']; ?>, '<?php echo htmlspecialchars($p['category_id'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['image'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['description'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['badge'] ?? '', ENT_QUOTES); ?>', <?php echo !empty($p['is_active']) ? 'true' : 'false'; ?>, <?php echo (int)($p['quantity'] ?? 0); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="admin-btn admin-btn--archive" onclick="archiveProduct(<?php echo (int)$p['id']; ?>)">
                                                <i class="fas fa-archive"></i>
                                            </button>
                                            <button type="button" class="admin-btn admin-btn--delete" onclick="deleteProduct(<?php echo (int)$p['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Add Product Section -->
        <section class="admin-section">
            <div class="admin-dashboard__header">
                <div>
                    <p class="section-eyebrow">Catalog Management</p>
                    <h2>Add New Product</h2>
                </div>
            </div>

            <div class="admin-card">
                <form method="POST" action="admin-products.php" class="admin-form">
                    <input type="hidden" name="product_action" value="create">
                    <div class="admin-form-grid">
                        <div class="form-group">
                            <label for="product_name">Product Name</label>
                            <input type="text" id="product_name" name="product_name" required>
                        </div>
                        <div class="form-group">
                            <label for="product_category_id">Category (optional)</label>
                            <select id="product_category_id" name="product_category_id">
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int)$category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="product_price">Price (₱)</label>
                            <input type="number" step="0.01" min="0" id="product_price" name="product_price" required>
                        </div>
                        <div class="form-group">
                            <label for="product_quantity">Quantity</label>
                            <input type="number" min="0" id="product_quantity" name="product_quantity" required placeholder="0">
                        </div>
                        <div class="form-group">
                            <label for="product_image">Image path</label>
                            <input type="text" id="product_image" name="product_image" placeholder="assets/images/example.jpg">
                        </div>
                        <div class="form-group">
                            <label for="product_badge">Badge (optional)</label>
                            <input type="text" id="product_badge" name="product_badge" placeholder="e.g., New, Bestseller">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="product_description">Description</label>
                        <textarea id="product_description" name="product_description" rows="3" style="width:100%; border-radius:10px; border:1px solid #d7ccc8; padding:10px 12px;"></textarea>
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" id="product_active" name="product_active" checked>
                        <label for="product_active" style="margin-bottom:0; text-transform:none; letter-spacing:normal;">Show in storefront</label>
                    </div>
                    <button type="submit" class="btn admin-submit">Add Product</button>
                </form>
            </div>
        </section>

        <!-- Archived Products Section -->
        <section class="admin-section">
            <div class="admin-dashboard__header">
                <div>
                    <p class="section-eyebrow">Catalog Management</p>
                    <h2>Archived Products</h2>
                </div>
            </div>

            <div class="admin-card admin-card--table admin-card--full">
                <div class="admin-table-header">
                    <h3>Archived Products</h3>
                    <div class="admin-table-info">
                        <span class="admin-table-count"><?php echo count($archivedProducts); ?> archived</span>
                        <a href="admin.php#archive" class="btn btn-outline btn-sm">View Full Archive</a>
                    </div>
                </div>
                <div class="admin-table-container">
                    <?php if (empty($archivedProducts)): ?>
                        <div class="admin-empty-state">
                            <i class="fas fa-archive" style="font-size: 3rem; color: rgba(141,110,99,0.2); margin-bottom: 16px;"></i>
                            <h3>No archived products</h3>
                            <p>Products that you archive will appear here for easy restoration.</p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table admin-table--fixed">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th style="width: 80px;">Image</th>
                                    <th style="width: 200px;">Name</th>
                                    <th style="width: 100px;">Price</th>
                                    <th style="width: 80px;">Qty</th>
                                    <th style="width: 120px;">Category</th>
                                    <th style="width: 100px;">Badge</th>
                                    <th style="width: 200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($archivedProducts as $p): ?>
                                <tr>
                                    <td><strong>#<?php echo (int)$p['id']; ?></strong></td>
                                    <td>
                                        <div class="admin-product-image">
                                            <?php if (!empty($p['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="admin-product-thumbnail">
                                            <?php else: ?>
                                                <div class="admin-product-placeholder">
                                                    <i class="fa-solid fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="admin-product-name">
                                            <?php echo htmlspecialchars($p['name']); ?>
                                            <?php if (!empty($p['badge'])): ?>
                                                <span class="admin-product-badge"><?php echo htmlspecialchars($p['badge']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><strong>₱<?php echo number_format((float)$p['price'], 2); ?></strong></td>
                                    <td>
                                        <span class="admin-quantity <?php echo (int)$p['quantity'] <= 10 ? 'admin-quantity--low' : ''; ?>">
                                            <?php echo (int)$p['quantity']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['category_name'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($p['badge'] ?? '—'); ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <button type="button" class="admin-btn admin-btn--restore" onclick="unarchiveProduct(<?php echo (int)$p['id']; ?>)">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            <button type="button" class="admin-btn admin-btn--delete" onclick="deleteArchivedProduct(<?php echo (int)$p['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Edit Product Modal -->
        <div id="editProductModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Product</h3>
                    <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
                </div>
                <form method="POST" action="admin-products.php" class="admin-form">
                    <input type="hidden" name="product_action" value="update">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="form-group">
                        <label for="edit_name">Product Name</label>
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_category_id">Category (optional)</label>
                        <select id="edit_category_id" name="category_id">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int)$category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_price">Price (₱)</label>
                        <input type="number" step="0.01" min="0" id="edit_price" name="price" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_quantity">Quantity</label>
                        <input type="number" min="0" id="edit_quantity" name="quantity" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_image">Image path</label>
                        <input type="text" id="edit_image" name="image">
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" rows="3" style="width:100%; border-radius:10px; border:1px solid #d7ccc8; padding:10px 12px;"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_badge">Badge (optional)</label>
                        <input type="text" id="edit_badge" name="badge">
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" id="edit_active" name="is_active">
                        <label for="edit_active" style="margin-bottom:0; text-transform:none; letter-spacing:normal;">Show in storefront</label>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="btn">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
// Test Functions
function testEditModal() {
    console.log('testEditModal called');
    // Test with sample data
    const testId = 1;
    const testName = 'Test Product';
    const testPrice = 99.99;
    const testCategoryId = '1';
    const testImage = 'assets/images/test.jpg';
    const testDescription = 'This is a test product description';
    const testBadge = 'Test';
    const testIsActive = true;
    const testQuantity = 50;
    
    console.log('Setting test data:', {testId, testName, testPrice, testCategoryId, testImage, testDescription, testBadge, testIsActive, testQuantity});
    
    document.getElementById('edit_id').value = testId;
    document.getElementById('edit_name').value = testName;
    document.getElementById('edit_price').value = testPrice;
    document.getElementById('edit_quantity').value = testQuantity;
    document.getElementById('edit_category_id').value = testCategoryId;
    document.getElementById('edit_image').value = testImage;
    document.getElementById('edit_description').value = testDescription;
    document.getElementById('edit_badge').value = testBadge;
    document.getElementById('edit_active').checked = testIsActive;
    document.getElementById('editProductModal').style.display = 'block';
    
    console.log('Modal should now be visible with test data');
}

function testConsoleLog() {
    console.log('testConsoleLog called');
    console.log('Current products data:', <?php echo json_encode($products); ?>);
    console.log('Modal element:', document.getElementById('editProductModal'));
    console.log('Edit form fields:', {
        id: document.getElementById('edit_id'),
        name: document.getElementById('edit_name'),
        price: document.getElementById('edit_price'),
        quantity: document.getElementById('edit_quantity'),
        category: document.getElementById('edit_category_id'),
        image: document.getElementById('edit_image'),
        description: document.getElementById('edit_description'),
        badge: document.getElementById('edit_badge'),
        active: document.getElementById('edit_active')
    });
}
function editProduct(id, name, price, categoryId, image, description, badge, isActive, quantity) {
    console.log('editProduct called with:', {id, name, price, categoryId, image, description, badge, isActive, quantity});
    
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_quantity').value = quantity;
    document.getElementById('edit_category_id').value = categoryId;
    document.getElementById('edit_image').value = image;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_badge').value = badge;
    document.getElementById('edit_active').checked = isActive;
    document.getElementById('editProductModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editProductModal').style.display = 'none';
}

// Archive Product
function archiveProduct(id) {
    if (confirm('Archive this product? You can restore it later from archive section.')) {
        window.location.href = 'admin-products.php?product_action=archive&product_id=' + id;
    }
}

// Unarchive Product
function unarchiveProduct(id) {
    if (confirm('Restore this product? It will be visible in the storefront again.')) {
        window.location.href = 'admin-products.php?product_action=unarchive&product_id=' + id;
    }
}

// Delete Active Product
function deleteProduct(id) {
    if (confirm('PERMANENTLY delete this product? This action cannot be undone and will remove all data permanently.')) {
        window.location.href = 'admin-products.php?product_action=delete&product_id=' + id;
    }
}

// Delete Archived Product
function deleteArchivedProduct(id) {
    if (confirm('PERMANENTLY delete this archived product? This action cannot be undone and will remove all data permanently.')) {
        window.location.href = 'admin-products.php?product_action=delete&product_id=' + id;
    }
}

// Notification dropdown functionality
document.getElementById('notificationBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
});

// Close notification dropdown when clicking outside
document.addEventListener('click', function() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.style.display = 'none';
});

// Prevent dropdown from closing when clicking inside it
document.getElementById('notificationDropdown').addEventListener('click', function(e) {
    e.stopPropagation();
});

// Check for success message and show notification
window.addEventListener('load', function() {
    <?php if ($productMessage): ?>
        const message = '<?php echo addslashes($productMessage); ?>';
        if (message && message.includes('updated')) {
            // Show success notification for update
            const notification = document.createElement('div');
            notification.className = 'admin-feedback admin-feedback--success';
            notification.textContent = 'Product updated successfully!';
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            document.body.appendChild(notification);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }
    <?php endif; ?>
});
</script>
</body>
</html>
