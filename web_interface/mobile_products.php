<?php
/**
 * Mobile Products Page - Android Style
 * Halaman daftar produk dengan UI/UX mobile-first
 */

require_once 'config.php';

// Session start
session_start();

$category = $_GET['category'] ?? 'all';
$brand = $_GET['brand'] ?? '';
$search_query = $_GET['search'] ?? '';

// Database connection
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Build query based on filters
    $where_conditions = [];
    $params = [];
    
    if ($category !== 'all') {
        $where_conditions[] = "category = ?";
        $params[] = $category;
    }
    
    if (!empty($brand)) {
        $where_conditions[] = "brand = ?";
        $params[] = $brand;
    }
    
    if (!empty($search_query)) {
        $where_conditions[] = "(name LIKE ? OR brand LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }
    
    $where_clause = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);
    $query = "SELECT * FROM products $where_clause ORDER BY category, brand, name LIMIT 50";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories
    $cat_stmt = $pdo->query("SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY count DESC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $products = [];
    $categories = [];
}

$category_names = [
    'pulsa' => 'Pulsa',
    'data' => 'Paket Data', 
    'games' => 'Voucher Game',
    'emoney' => 'E-Money',
    'pln' => 'Token PLN',
    'voucher' => 'Voucher',
    'lainnya' => 'Lainnya',
    'all' => 'Semua Produk'
];

// Determine page title
if (!empty($brand)) {
    $current_category_name = "Produk $brand";
} else {
    $current_category_name = $category_names[$category] ?? 'Produk';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?php echo $current_category_name; ?> - Bot Pulsa Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: #f5f5f5;
            height: 100vh;
            overflow-x: hidden;
        }
        
        /* App Container */
        .app-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Status Bar */
        .status-bar {
            height: 24px;
            background: rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 16px;
            font-size: 12px;
            color: white;
            font-weight: 500;
        }
        
        /* App Header */
        .app-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .back-btn {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }
        
        .back-btn:active {
            transform: scale(0.95);
            background: rgba(255,255,255,0.3);
        }
        
        .header-title {
            flex: 1;
            color: white;
        }
        
        .header-title h1 {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 2px;
        }
        
        .header-title p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .search-btn {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }
        
        .search-btn:active {
            transform: scale(0.95);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            background: white;
            border-radius: 24px 24px 0 0;
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Categories Filter */
        .categories-filter {
            padding: 16px 0 8px;
            background: white;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .categories-scroll {
            display: flex;
            gap: 8px;
            padding: 0 16px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .categories-scroll::-webkit-scrollbar {
            display: none;
        }
        
        .category-chip {
            flex-shrink: 0;
            background: #f5f5f5;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #666;
            font-weight: 500;
        }
        
        .category-chip.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .category-chip:active {
            transform: scale(0.98);
        }
        
        /* Products List */
        .products-container {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background: #fafafa;
        }
        
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding: 0 4px;
        }
        
        .products-count {
            font-size: 14px;
            color: #666;
        }
        
        .sort-btn {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            color: #666;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .product-card {
            background: white;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(102, 126, 234, 0.05) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }
        
        .product-card:active {
            transform: scale(0.98);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }
        
        .product-card:active::before {
            transform: translateX(100%);
        }
        
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .product-category {
            background: #667eea20;
            color: #667eea;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .product-name {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        
        .product-desc {
            font-size: 12px;
            color: #666;
            margin-bottom: 12px;
            line-height: 1.3;
        }
        
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .product-price {
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }
        
        .product-brand {
            font-size: 11px;
            color: #999;
            background: #f5f5f5;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        /* Loading State */
        .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 16px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            color: #666;
            text-align: center;
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            transition: all 0.2s ease;
            z-index: 1000;
        }
        
        .fab:active {
            transform: scale(0.95);
        }
        
        /* Search Overlay */
        .search-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: white;
            z-index: 1001;
            display: none;
            flex-direction: column;
        }
        
        .search-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 24px 16px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .search-input {
            flex: 1;
            background: rgba(255,255,255,0.2);
            border: none;
            padding: 12px 16px;
            border-radius: 24px;
            color: white;
            font-size: 14px;
            outline: none;
            backdrop-filter: blur(10px);
        }
        
        .search-input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .close-search {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Status Bar -->
        <div class="status-bar">
            <span id="currentTime"></span>
            <span>🔋 100% 📶</span>
        </div>
        
        <!-- App Header -->
        <div class="app-header">
            <button class="back-btn" onclick="goBack()">
                <span class="material-icons" style="font-size: 20px;">arrow_back</span>
            </button>
            <div class="header-title">
                <h1><?php echo $current_category_name; ?></h1>
                <p><?php echo count($products); ?> produk tersedia</p>
            </div>
            <button class="search-btn" onclick="openSearch()">
                <span class="material-icons" style="font-size: 20px;">search</span>
            </button>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Categories Filter -->
            <div class="categories-filter">
                <div class="categories-scroll">
                    <button class="category-chip <?php echo $category === 'all' ? 'active' : ''; ?>" 
                            onclick="filterCategory('all')">Semua</button>
                    <?php foreach ($categories as $cat): ?>
                        <button class="category-chip <?php echo $category === $cat['category'] ? 'active' : ''; ?>"
                                onclick="filterCategory('<?php echo $cat['category']; ?>')">
                            <?php echo $category_names[$cat['category']] ?? ucfirst($cat['category']); ?>
                            <span style="opacity: 0.7; margin-left: 4px;">(<?php echo $cat['count']; ?>)</span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Products Container -->
            <div class="products-container">
                <?php if (count($products) > 0): ?>
                    <div class="products-header">
                        <div class="products-count">
                            Menampilkan <?php echo count($products); ?> produk
                        </div>
                        <button class="sort-btn" onclick="toggleSort()">
                            <span class="material-icons" style="font-size: 16px;">sort</span>
                            Urutkan
                        </button>
                    </div>
                    
                    <div class="products-grid" id="productsGrid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card" onclick="selectProduct('<?php echo htmlspecialchars($product['digiflazz_code']); ?>', '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>)">
                                <div class="product-header">
                                    <div class="product-category">
                                        <?php echo $category_names[$product['category']] ?? ucfirst($product['category']); ?>
                                    </div>
                                </div>
                                <div class="product-name">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </div>
                                <?php if (!empty($product['description'])): ?>
                                    <div class="product-desc">
                                        <?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...
                                    </div>
                                <?php endif; ?>
                                <div class="product-footer">
                                    <div class="product-price">
                                        Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                                    </div>
                                    <?php if (!empty($product['brand'])): ?>
                                        <div class="product-brand">
                                            <?php echo htmlspecialchars($product['brand']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📱</div>
                        <h3>Produk Tidak Ditemukan</h3>
                        <p>Tidak ada produk dalam kategori ini.<br>Coba pilih kategori lain.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Floating Action Button -->
        <button class="fab" onclick="goToCart()">
            <span class="material-icons" style="font-size: 24px;">shopping_cart</span>
        </button>
        
        <!-- Search Overlay -->
        <div class="search-overlay" id="searchOverlay">
            <div class="search-header">
                <input type="text" class="search-input" placeholder="Cari produk..." id="searchInput">
                <button class="close-search" onclick="closeSearch()">
                    <span class="material-icons" style="font-size: 20px;">close</span>
                </button>
            </div>
            <div class="products-container">
                <div class="products-grid" id="searchResults">
                    <!-- Search results will be populated here -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        updateTime();
        setInterval(updateTime, 1000);
        
        // Navigation functions
        function goBack() {
            window.history.back();
        }
        
        function filterCategory(category) {
            window.location.href = `mobile_products.php?category=${category}`;
        }
        
        function selectProduct(code, name, price) {
            if (confirm(`Pilih produk: ${name}\nHarga: Rp ${price.toLocaleString('id-ID')}\n\nLanjutkan ke pembelian?`)) {
                window.location.href = `mobile_purchase.php?product=${code}`;
            }
        }
        
        function goToCart() {
            alert('🛒 Fitur keranjang belanja akan segera hadir!');
        }
        
        // Search functions
        function openSearch() {
            document.getElementById('searchOverlay').style.display = 'flex';
            document.getElementById('searchInput').focus();
        }
        
        function closeSearch() {
            document.getElementById('searchOverlay').style.display = 'none';
            document.getElementById('searchInput').value = '';
        }
        
        // Search functionality
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            if (query.length > 2) {
                searchProducts(query);
            }
        });
        
        function searchProducts(query) {
            // Simulate search (in real implementation, this would be AJAX call)
            const allProducts = document.querySelectorAll('.product-card');
            const resultsContainer = document.getElementById('searchResults');
            resultsContainer.innerHTML = '';
            
            let found = 0;
            allProducts.forEach(product => {
                const name = product.querySelector('.product-name').textContent.toLowerCase();
                if (name.includes(query) && found < 10) {
                    resultsContainer.appendChild(product.cloneNode(true));
                    found++;
                }
            });
            
            if (found === 0) {
                resultsContainer.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">🔍</div>
                        <h3>Tidak ditemukan</h3>
                        <p>Coba kata kunci lain</p>
                    </div>
                `;
            }
        }
        
        // Sort functionality
        let sortOrder = 'name';
        function toggleSort() {
            sortOrder = sortOrder === 'name' ? 'price' : 'name';
            sortProducts();
        }
        
        function sortProducts() {
            const grid = document.getElementById('productsGrid');
            const products = Array.from(grid.children);
            
            products.sort((a, b) => {
                if (sortOrder === 'price') {
                    const priceA = parseInt(a.querySelector('.product-price').textContent.replace(/\D/g, ''));
                    const priceB = parseInt(b.querySelector('.product-price').textContent.replace(/\D/g, ''));
                    return priceA - priceB;
                } else {
                    const nameA = a.querySelector('.product-name').textContent;
                    const nameB = b.querySelector('.product-name').textContent;
                    return nameA.localeCompare(nameB);
                }
            });
            
            grid.innerHTML = '';
            products.forEach(product => grid.appendChild(product));
        }
        
        // Prevent zoom on double tap
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
</body>
</html>