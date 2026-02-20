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

$message = null;

// Handle category create/update/delete actions
$categoryAction = $_POST['category_action'] ?? $_GET['category_action'] ?? null;
try {
    if ($categoryAction === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['category_description'] ?? '');
        $icon = trim($_POST['category_icon'] ?? '');
        
        if ($name === '') {
            $message = 'Please provide a category name.';
        } else {
            createCategory($name, $description ?: null, $icon ?: null);
            $message = 'Category created successfully.';
        }
    } elseif ($categoryAction === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        
        if ($id <= 0 || $name === '') {
            $message = 'Please provide valid category details.';
        } else {
            updateCategory($id, $name, $description ?: null, $icon ?: null);
            $message = 'Category updated successfully.';
        }
    } elseif ($categoryAction === 'delete' && isset($_GET['id'])) {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            // Check if category has products before deleting
            $pdo = getPDO();
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $stmt->execute([$id]);
            $productCount = (int)$stmt->fetchColumn();
            
            if ($productCount > 0) {
                $message = "Cannot delete category - it has {$productCount} associated products. Please reassign or delete the products first.";
            } else {
                deleteCategory($id);
                $message = 'Category deleted successfully.';
            }
        }
    }
} catch (Throwable $e) {
    $message = 'Category operation failed. Please try again.';
}

$categories = getAllCategories();

// Notifications
$unreadCount = getUnreadNotificationsCount();
$recentNotifications = getUnreadNotifications(5);
$allNotifications = getAllNotifications(20);

// Handle notification actions
$notificationAction = $_GET['notification_action'] ?? null;
if ($notificationAction === 'mark_read' && isset($_GET['notification_id'])) {
    $notifId = (int)$_GET['notification_id'];
    if ($notifId > 0) {
        markNotificationAsRead($notifId);
        header('Location: admin-categories.php#notifications');
        exit;
    }
} elseif ($notificationAction === 'mark_all_read') {
    markAllNotificationsAsRead();
    header('Location: admin-categories.php#notifications');
    exit;
}

// Simple dashboard metrics
$totalCategories = count($categories);
$usedCategories = 0;
foreach ($categories as $category) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
    $stmt->execute([$category['id']]);
    if ((int)$stmt->fetchColumn() > 0) {
        $usedCategories++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management | La Maria Bakehouse</title>
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
            <a href="admin-products.php"><i class="fa-solid fa-bread-slice"></i><span>Products</span></a>
            <a href="admin-categories.php" class="active"><i class="fa-solid fa-tags"></i><span>Categories</span></a>
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
                <p class="section-eyebrow">Catalog Organization</p>
                <h1>Categories Management</h1>
            </div>
            <div class="admin-topbar__right">
                <form class="admin-search" role="search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" placeholder="Search categories…" aria-label="Search categories">
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

        <?php if ($message): ?>
            <p class="admin-feedback admin-feedback--floating"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <section class="admin-kpis">
            <div class="admin-kpi-card">
                <p class="admin-kpi-label">Total Categories</p>
                <p class="admin-kpi-value"><?php echo number_format($totalCategories); ?></p>
                <p class="admin-kpi-meta">Available for products</p>
            </div>
            <div class="admin-kpi-card">
                <p class="admin-kpi-label">Used Categories</p>
                <p class="admin-kpi-value"><?php echo number_format($usedCategories); ?></p>
                <p class="admin-kpi-meta">Have products assigned</p>
            </div>
            <div class="admin-kpi-card">
                <p class="admin-kpi-label">Unused Categories</p>
                <p class="admin-kpi-value"><?php echo number_format($totalCategories - $usedCategories); ?></p>
                <p class="admin-kpi-meta">Available for new products</p>
            </div>
        </section>

        <section class="admin-section">
            <div class="admin-dashboard__header">
                <div>
                    <p class="section-eyebrow">Catalog Organization</p>
                    <h2>Categories</h2>
                </div>
            </div>

            <div class="admin-grid">
                <div class="admin-card">
                    <h3>Add Category</h3>
                    <form method="POST" action="admin-categories.php" class="admin-form">
                        <input type="hidden" name="category_action" value="create">
                        <div class="form-group">
                            <label for="category_name">Category Name</label>
                            <input type="text" id="category_name" name="category_name" required>
                        </div>
                        <div class="form-group">
                            <label for="category_description">Description</label>
                            <textarea id="category_description" name="category_description" rows="3" style="width:100%; border-radius:10px; border:1px solid #d7ccc8; padding:10px 12px;"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="category_icon">Icon Class (optional)</label>
                            <input type="text" id="category_icon" name="category_icon" placeholder="e.g., fa-bread-slice">
                            <small>Font Awesome icon class without "fa-" prefix</small>
                        </div>
                        <button type="submit" class="btn admin-submit">Add Category</button>
                    </form>
                </div>

                <div class="admin-card admin-card--table">
                    <div class="admin-table-header">
                        <h3>Category List</h3>
                        <div class="admin-table-info">
                            <span class="admin-table-count"><?php echo count($categories); ?> categories</span>
                        </div>
                    </div>
                    <div class="admin-table-container">
                        <table class="admin-table admin-table--fixed">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th style="width: 200px;">Name</th>
                                    <th style="width: 120px;">Icon</th>
                                    <th style="width: 300px;">Description</th>
                                    <th style="width: 100px;">Products</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($categories as $category): ?>
                                <?php
                                $pdo = getPDO();
                                $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
                                $stmt->execute([$category['id']]);
                                $productCount = (int)$stmt->fetchColumn();
                                ?>
                                <tr>
                                    <td><strong>#<?php echo (int)$category['id']; ?></strong></td>
                                    <td>
                                        <div class="admin-category-name">
                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($category['icon'])): ?>
                                            <i class="fa-solid <?php echo htmlspecialchars($category['icon']); ?> admin-category-icon"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-tag admin-category-icon"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="admin-category-description">
                                            <?php echo htmlspecialchars($category['description'] ?? '—'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="admin-product-count <?php echo $productCount === 0 ? 'admin-product-count--empty' : ''; ?>">
                                            <?php echo $productCount; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="admin-actions">
                                            <button type="button" class="admin-btn admin-btn--edit" onclick="editCategory(<?php echo (int)$category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($category['description'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($category['icon'] ?? '', ENT_QUOTES); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="admin-btn admin-btn--delete" onclick="if(confirm('Delete this category? <?php echo $productCount > 0 ? 'This will uncategorize ' . $productCount . ' products.' : ''; ?>')) window.location.href='admin-categories.php?category_action=delete&id=<?php echo (int)$category['id']; ?>'">
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
            </div>
        </section>

        <!-- Edit Category Modal -->
        <div id="editCategoryModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Category</h3>
                    <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
                </div>
                <form method="POST" action="admin-categories.php" class="admin-form">
                    <input type="hidden" name="category_action" value="update">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="form-group">
                        <label for="edit_name">Category Name</label>
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" rows="3" style="width:100%; border-radius:10px; border:1px solid #d7ccc8; padding:10px 12px;"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_icon">Icon Class (optional)</label>
                        <input type="text" id="edit_icon" name="icon">
                        <small>Font Awesome icon class without "fa-" prefix</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="btn">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
// Edit Category Modal
function editCategory(id, name, description, icon) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_icon').value = icon;
    document.getElementById('editCategoryModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editCategoryModal').style.display = 'none';
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
</script>
</body>
</html>
