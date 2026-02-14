<?php

require_once __DIR__ . '/config/db.php';

/**
 * Internal seed used to populate the products table the first time.
 */
function defaultProductSeed(): array {
    return [
        [
            'slug' => 'tableya-sea-salt-croissant',
            'name' => 'Tableya Sea Salt Croissant',
            'description' => 'Flaky layers filled with dark tableya ganache, finished with Maldon sea salt.',
            'price' => 85.00,
            'quantity' => 50,
            'image' => 'assets/images/10.jpg',
            'category' => 'croissants',
            'badge' => 'Bestseller',
            'is_active' => 1,
        ],
        [
            'slug' => 'calamansi-honey-cheesecake',
            'name' => 'Calamansi Honey Cheesecake',
            'description' => 'Silky cream cheese infused with calamansi zest, topped with local honey.',
            'price' => 120.00,
            'quantity' => 30,
            'image' => 'assets/images/11.jpg',
            'category' => 'cakes',
            'badge' => 'New',
            'is_active' => 1,
        ],
        [
            'slug' => 'coconut-dulce-ensaymada',
            'name' => 'Coconut Dulce Ensaymada',
            'description' => 'Soft brioche buns with coconut dulce de leche frosting and toasted coconut flakes.',
            'price' => 45.00,
            'quantity' => 75,
            'image' => 'assets/images/12.jpg',
            'category' => 'filipino-delights',
            'badge' => null,
            'is_active' => 1,
        ],
        [
            'slug' => 'classic-croissant',
            'name' => 'Classic Croissant',
            'description' => 'Buttery, flaky croissant baked to golden perfection.',
            'price' => 65.00,
            'quantity' => 60,
            'image' => 'assets/images/13.jpg',
            'category' => 'croissants',
            'badge' => null,
            'is_active' => 1,
        ],
        [
            'slug' => 'berry-danishes',
            'name' => 'Berry Danishes',
            'description' => 'Light pastry with mixed berries and cream cheese filling.',
            'price' => 75.00,
            'quantity' => 40,
            'image' => 'assets/images/14.jpg',
            'category' => 'pastries',
            'badge' => null,
            'is_active' => 1,
        ],
        [
            'slug' => 'chocolate-eclair',
            'name' => 'Chocolate Eclair',
            'description' => 'Choux pastry filled with vanilla cream and topped with rich chocolate glaze.',
            'price' => 80.00,
            'quantity' => 35,
            'image' => 'assets/images/15.jpg',
            'category' => 'pastries',
            'badge' => null,
            'is_active' => 1,
        ],
        [
            'slug' => 'pain-au-chocolat',
            'name' => 'Pain au Chocolat',
            'description' => 'Classic French pastry with dark chocolate batons.',
            'price' => 70.00,
            'quantity' => 55,
            'image' => 'assets/images/16.jpg',
            'category' => 'croissants',
            'badge' => null,
            'is_active' => 1,
        ],
        [
            'slug' => 'almond-tart',
            'name' => 'Almond Tart',
            'description' => 'Buttery tart shell with frangipane almond filling and sliced almonds.',
            'price' => 95.00,
            'quantity' => 25,
            'image' => 'assets/images/17.jpg',
            'category' => 'tarts',
            'badge' => null,
            'is_active' => 1,
        ],
        [
            'slug' => 'red-velvet-cupcake',
            'name' => 'Red Velvet Cupcake',
            'description' => 'Moist red velvet cake with cream cheese frosting.',
            'price' => 55.00,
            'quantity' => 45,
            'image' => 'assets/images/18.jpg',
            'category' => 'cakes',
            'badge' => null,
            'is_active' => 1,
        ],
        [
            'slug' => 'ube-cheese-pandesal',
            'name' => 'Ube Cheese Pandesal',
            'description' => 'Filipino bread with ube filling and cheese topping.',
            'price' => 35.00,
            'quantity' => 80,
            'image' => 'assets/images/19.jpg',
            'category' => 'filipino-delights',
            'badge' => null,
            'is_active' => 1,
        ],
        [
            'slug' => 'mango-basil-tart',
            'name' => 'Mango Basil Tart',
            'description' => 'Fresh mango with basil-infused custard in a buttery tart shell.',
            'price' => 110.00,
            'quantity' => 20,
            'image' => 'assets/images/20.jpg',
            'category' => 'tarts',
            'badge' => 'Seasonal',
            'is_active' => 1,
        ],
        [
            'slug' => 'dark-choco-banana-bread',
            'name' => 'Dark Choco Banana Bread',
            'description' => 'Moist banana bread with dark chocolate chunks and walnuts.',
            'price' => 60.00,
            'quantity' => 50,
            'image' => 'assets/images/21.jpg',
            'category' => 'breads',
            'badge' => null,
            'is_active' => 1,
        ],
        [
            'slug' => 'pandesal',
            'name' => 'Pandesal',
            'description' => 'Classic Filipino bread rolls, slightly sweet and perfect for breakfast.',
            'price' => 25.00,
            'quantity' => 100,
            'image' => 'assets/images/22.jpg',
            'category' => 'filipino-delights',
            'badge' => null,
            'is_active' => 1,
        ],
    ];
}

function ensureProductsSeeded(): void {
    ensureTables();
    seedDefaultCategories();
    $pdo = getPDO();

    $seed = defaultProductSeed();
    
    foreach ($seed as $p) {
        // Check if product already exists
        $stmt = $pdo->prepare('SELECT id FROM products WHERE slug = ?');
        $stmt->execute([$p['slug']]);
        $existing = $stmt->fetch();
        
        // Get category ID from category slug
        $categoryId = null;
        if (isset($p['category'])) {
            $categoryStmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
            $categoryStmt->execute([$p['category']]);
            $categoryRow = $categoryStmt->fetch();
            if ($categoryRow) {
                $categoryId = $categoryRow['id'];
            }
        }
        
        if ($existing) {
            // Update existing product with new image and other details
            $update = $pdo->prepare('UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, image = ?, category_id = ?, badge = ? WHERE slug = ?');
            $update->execute([
                $p['name'],
                $p['description'],
                $p['price'],
                $p['quantity'],
                $p['image'],
                $categoryId,
                $p['badge'],
                $p['slug'],
            ]);
        } else {
            // Insert new product
            $insert = $pdo->prepare('INSERT INTO products (slug, name, description, price, quantity, image, category_id, badge, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
            $insert->execute([
                $p['slug'],
                $p['name'],
                $p['description'],
                $p['price'],
                $p['quantity'],
                $p['image'],
                $categoryId,
                $p['badge'],
            ]);
        }
    }
}

function getFeaturedProducts(): array {
    ensureProductsSeeded();
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT p.slug AS id, p.name, p.description, p.price, p.quantity, p.image, c.name as category_name, c.icon as category_icon FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 AND p.is_archived = 0 ORDER BY p.created_at DESC');
    return $stmt->fetchAll();
}

function findProductById(string $id): ?array {
    ensureProductsSeeded();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT p.slug AS id, p.name, p.description, p.price, p.quantity, p.image, p.category_id, c.name as category_name, c.icon as category_icon, p.badge, p.is_active FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getAllProducts(): array {
    ensureProductsSeeded();
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_archived = 0 ORDER BY p.created_at DESC');
    return $stmt->fetchAll();
}

function generateProductSlug(string $name): string {
    $base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    $base = trim($base, '-');
    if ($base === '') {
        $base = 'product';
    }

    $pdo = getPDO();
    $slug = $base;
    $suffix = 1;
    while (true) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ?');
        $stmt->execute([$slug]);
        if ((int)$stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . $suffix;
        $suffix++;
    }
}

function createProduct(string $name, float $price, ?string $description, ?string $image, ?int $categoryId, ?string $badge, bool $isActive = true, int $quantity = 0): string {
    ensureTables();
    $pdo = getPDO();
    $slug = generateProductSlug($name);
    $stmt = $pdo->prepare('INSERT INTO products (slug, name, description, price, quantity, image, category_id, badge, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $slug,
        $name,
        $description,
        $price,
        $quantity,
        $image,
        $categoryId,
        $badge,
        $isActive ? 1 : 0,
    ]);
    return $slug;
}

function updateProduct(int $id, string $name, float $price, ?string $description, ?string $image, ?int $categoryId, ?string $badge, bool $isActive, int $quantity): bool {
    ensureTables();
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE products SET name = ?, price = ?, description = ?, image = ?, category_id = ?, badge = ?, is_active = ?, quantity = ? WHERE id = ?');
    return $stmt->execute([
        $name,
        $price,
        $description,
        $image,
        $categoryId,
        $badge,
        $isActive ? 1 : 0,
        $quantity,
        $id,
    ]);
}

function deleteProduct(int $id): bool {
    ensureTables();
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    return $stmt->execute([$id]);
}
