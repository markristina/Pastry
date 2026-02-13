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
            'image' => 'assets/images/10.jpg'
        ],
        [
            'id' => 'calamansi_honey_cheesecake',
            'name' => 'Calamansi Honey Cheesecake',
            'description' => 'Silky baked cheesecake brightened with calamansi curd and wildflower honey drizzle.',
            'price' => 120.00,
            'image' => 'assets/images/11.webp'
        ],
        [
            'id' => 'coconut_dulce_ensaymada',
            'name' => 'Coconut Dulce Ensaymada',
            'description' => 'Pillowy brioche swirl topped with muscovado butter, coconut flakes, and queso de bola.',
            'price' => 95.00,
            'image' => 'assets/images/12.webp'
        ],
        [
            'id' => 'classic_croissant',
            'name' => 'Classic Croissant',
            'description' => 'Buttery, flaky, and golden brown. The perfect start to your morning.',
            'price' => 65.00,
            'image' => 'assets/images/1.jpg'
        ],
        [
            'id' => 'berry_danishes',
            'name' => 'Berry Danishes',
            'description' => 'Sweet pastry filled with fresh vanilla custard and topped with seasonal berries.',
            'price' => 85.00,
            'image' => 'assets/images/2..jpg'
        ],
        [
            'id' => 'chocolate_eclair',
            'name' => 'Chocolate Eclair',
            'description' => 'Choux pastry filled with rich chocolate cream and topped with chocolate ganache.',
            'price' => 75.00,
            'image' => 'assets/images/3..avif'
        ],
        [
            'id' => 'pain_au_chocolat',
            'name' => 'Pain au Chocolat',
            'description' => 'Flaky laminated dough wrapped around bars of dark chocolate.',
            'price' => 70.00,
            'image' => 'assets/images/4..webp'
        ],
        [
            'id' => 'almond_tart',
            'name' => 'Almond Tart',
            'description' => 'Buttery tart crust filled with frangipane and toasted almond flakes.',
            'price' => 95.00,
            'image' => 'assets/images/5.jpg'
        ],
        [
            'id' => 'red_velvet_cupcake',
            'name' => 'Red Velvet Cupcake',
            'description' => 'Moist red velvet sponge topped with silky cream cheese frosting.',
            'price' => 60.00,
            'image' => 'assets/images/6.jpg'
        ],
        [
            'id' => 'ube_cheese_pandesal',
            'name' => 'Ube Cheese Pandesal',
            'description' => 'Soft purple yam pandesal filled with melty cheese—sweet, savory, and comforting.',
            'price' => 40.00,
            'image' => 'assets/images/7p.jpg'
        ],
        [
            'id' => 'mango_basil_tart',
            'name' => 'Mango Basil Tart',
            'description' => 'Buttery tart shell layered with mango curd and a bright basil-sugar finish.',
            'price' => 110.00,
            'image' => 'assets/images/8.webp'
        ],
        [
            'id' => 'dark_choco_banana_bread',
            'name' => 'Dark Choco Banana Bread',
            'description' => 'Moist banana loaf with cacao chunks and toasted walnuts—perfect with coffee.',
            'price' => 80.00,
            'image' => 'assets/images/9.jpg'
        ]
    ];
}

function ensureProductsSeeded(): void {
    ensureTables();
    $pdo = getPDO();

    $stmt = $pdo->query('SELECT COUNT(*) FROM products');
    $count = (int)$stmt->fetchColumn();
    if ($count > 0) {
        return;
    }

    $seed = defaultProductSeed();
    $insert = $pdo->prepare('INSERT INTO products (slug, name, description, price, image, category, badge, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
    foreach ($seed as $p) {
        $insert->execute([
            $p['id'],
            $p['name'],
            $p['description'],
            $p['price'],
            $p['image'],
            null,
            null,
        ]);
    }
}

function getFeaturedProducts(): array {
    ensureProductsSeeded();
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT slug AS id, name, description, price, image FROM products WHERE is_active = 1 ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function findProductById(string $id): ?array {
    ensureProductsSeeded();
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT slug AS id, name, description, price, image, category, badge, is_active FROM products WHERE slug = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getAllProducts(): array {
    ensureProductsSeeded();
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, slug, name, description, price, image, category, badge, is_active, created_at FROM products ORDER BY created_at DESC');
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

function createProduct(string $name, float $price, ?string $description, ?string $image, ?string $category, ?string $badge, bool $isActive = true): string {
    ensureTables();
    $pdo = getPDO();
    $slug = generateProductSlug($name);
    $stmt = $pdo->prepare('INSERT INTO products (slug, name, description, price, image, category, badge, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $slug,
        $name,
        $description,
        $price,
        $image,
        $category,
        $badge,
        $isActive ? 1 : 0,
    ]);
    return $slug;
}

function updateProduct(int $id, string $name, float $price, ?string $description, ?string $image, ?string $category, ?string $badge, bool $isActive): bool {
    ensureTables();
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE products SET name = ?, price = ?, description = ?, image = ?, category = ?, badge = ?, is_active = ? WHERE id = ?');
    return $stmt->execute([
        $name,
        $price,
        $description,
        $image,
        $category,
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
