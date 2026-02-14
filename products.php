<?php

require_once __DIR__ . '/config/db.php';

/**
 * Internal seed used to populate the products table the first time.
 */
function defaultProductSeed(): array {
    return [
        [
            'id' => 'tableya_sea_salt_croissant',
            'name' => 'Tableya Sea Salt Croissant',
            'description' => 'Flaky layers filled with dark tableya ganache, finished with Maldon sea salt.',
            'price' => 85.00,
            'image' => 'assets/images/10.jpg',
            'category' => 'croissants'
        ],
        [
            'id' => 'calamansi_honey_cheesecake',
            'name' => 'Calamansi Honey Cheesecake',
            'description' => 'Silky baked cheesecake brightened with calamansi curd and wildflower honey drizzle.',
            'price' => 120.00,
            'image' => 'assets/images/11.webp',
            'category' => 'cakes'
        ],
        [
            'id' => 'coconut_dulce_ensaymada',
            'name' => 'Coconut Dulce Ensaymada',
            'description' => 'Pillowy brioche swirl topped with muscovado butter, coconut flakes, and queso de bola.',
            'price' => 95.00,
            'image' => 'assets/images/12.webp',
            'category' => 'filipino-delights'
        ],
        [
            'id' => 'classic_croissant',
            'name' => 'Classic Croissant',
            'description' => 'Buttery, flaky, and golden brown. The perfect start to your morning.',
            'price' => 65.00,
            'image' => 'assets/images/1.jpg',
            'category' => 'croissants'
        ],
        [
            'id' => 'berry_danishes',
            'name' => 'Berry Danishes',
            'description' => 'Sweet pastry filled with fresh vanilla custard and topped with seasonal berries.',
            'price' => 85.00,
            'image' => 'assets/images/2..jpg',
            'category' => 'pastries'
        ],
        [
            'id' => 'chocolate_eclair',
            'name' => 'Chocolate Eclair',
            'description' => 'Choux pastry filled with rich chocolate cream and topped with chocolate ganache.',
            'price' => 75.00,
            'image' => 'assets/images/3..avif',
            'category' => 'pastries'
        ],
        [
            'id' => 'pain_au_chocolat',
            'name' => 'Pain au Chocolat',
            'description' => 'Flaky laminated dough wrapped around bars of dark chocolate.',
            'price' => 70.00,
            'image' => 'assets/images/4..webp',
            'category' => 'croissants'
        ],
        [
            'id' => 'almond_tart',
            'name' => 'Almond Tart',
            'description' => 'Buttery tart crust filled with frangipane and toasted almond flakes.',
            'price' => 95.00,
            'image' => 'assets/images/5.jpg',
            'category' => 'tarts'
        ],
        [
            'id' => 'red_velvet_cupcake',
            'name' => 'Red Velvet Cupcake',
            'description' => 'Moist red velvet sponge topped with silky cream cheese frosting.',
            'price' => 60.00,
            'image' => 'assets/images/6.jpg',
            'category' => 'cakes'
        ],
        [
            'id' => 'ube_cheese_pandesal',
            'name' => 'Ube Cheese Pandesal',
            'description' => 'Soft purple yam pandesal filled with melty cheese—sweet, savory, and comforting.',
            'price' => 40.00,
            'image' => 'assets/images/7p.jpg',
            'category' => 'filipino-delights'
        ],
        [
            'id' => 'mango_basil_tart',
            'name' => 'Mango Basil Tart',
            'description' => 'Buttery tart shell layered with mango curd and a bright basil-sugar finish.',
            'price' => 110.00,
            'image' => 'assets/images/8.webp',
            'category' => 'tarts'
        ],
        [
            'id' => 'dark_choco_banana_bread',
            'name' => 'Dark Choco Banana Bread',
            'description' => 'Moist banana loaf with cacao chunks and toasted walnuts—perfect with coffee.',
            'price' => 80.00,
            'image' => 'assets/images/9.jpg',
            'category' => 'breads'
        ]
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
        $stmt->execute([$p['id']]);
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
            $update = $pdo->prepare('UPDATE products SET name = ?, description = ?, price = ?, image = ?, category_id = ?, badge = ? WHERE slug = ?');
            $update->execute([
                $p['name'],
                $p['description'],
                $p['price'],
                $p['image'],
                $categoryId,
                null,
                $p['id'],
            ]);
        } else {
            // Insert new product
            $insert = $pdo->prepare('INSERT INTO products (slug, name, description, price, image, category_id, badge, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
            $insert->execute([
                $p['id'],
                $p['name'],
                $p['description'],
                $p['price'],
                $p['image'],
                $categoryId,
                null,
            ]);
        }
    }
}

function getFeaturedProducts(): array {
    ensureProductsSeeded();
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT p.slug AS id, p.name, p.description, p.price, p.image, c.name as category_name, c.icon as category_icon FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 AND p.is_archived = 0 ORDER BY p.created_at DESC');
    return $stmt->fetchAll();
}

function findProductById(string $id): ?array {
    ensureProductsSeeded();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT p.slug AS id, p.name, p.description, p.price, p.image, p.category_id, c.name as category_name, c.icon as category_icon, p.badge, p.is_active FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = ? LIMIT 1');
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

function createProduct(string $name, float $price, ?string $description, ?string $image, ?int $categoryId, ?string $badge, bool $isActive = true): string {
    ensureTables();
    $pdo = getPDO();
    $slug = generateProductSlug($name);
    $stmt = $pdo->prepare('INSERT INTO products (slug, name, description, price, image, category_id, badge, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $slug,
        $name,
        $description,
        $price,
        $image,
        $categoryId,
        $badge,
        $isActive ? 1 : 0,
    ]);
    return $slug;
}

function updateProduct(int $id, string $name, float $price, ?string $description, ?string $image, ?int $categoryId, ?string $badge, bool $isActive): bool {
    ensureTables();
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE products SET name = ?, price = ?, description = ?, image = ?, category_id = ?, badge = ?, is_active = ? WHERE id = ?');
    return $stmt->execute([
        $name,
        $price,
        $description,
        $image,
        $categoryId,
        $badge,
        $isActive ? 1 : 0,
        $id,
    ]);
}

function deleteProduct(int $id): bool {
    ensureTables();
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    return $stmt->execute([$id]);
}
