<?php
/**
 * Redistribute products from "Lainnya" category to proper categories
 * for better organization and cleaner interface
 */

// Database connection
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully.\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

echo "Starting category redistribution...\n";

// Define redistribution rules
$redistribution_rules = [
    // E-Money category
    ['from_brand' => 'GO PAY', 'to_category' => 'E-Money'],
    ['from_brand' => 'GRAB', 'to_category' => 'E-Money'],
    ['from_brand' => 'MAXIM', 'to_category' => 'E-Money'],
    ['from_brand' => 'Indriver', 'to_category' => 'E-Money'],
    ['from_brand' => 'TAPCASH BNI', 'to_category' => 'E-Money'],
    
    // Pulsa category
    ['from_brand' => 'TRI', 'to_category' => 'Pulsa'],
    ['from_brand' => 'XL', 'to_category' => 'Pulsa'],
    
    // Game category
    ['from_brand' => 'POINT BLANK', 'to_category' => 'Game'],
    ['from_brand' => 'Super Sus', 'to_category' => 'Game'],
    
    // Data category
    ['from_brand' => 'WIFI ID', 'to_category' => 'Data'],
    
    // Streaming category
    ['from_brand' => 'Nex Parabola', 'to_category' => 'Streaming'],
    ['from_brand' => 'K-VISION dan GOL', 'to_category' => 'Streaming'],
    ['from_brand' => 'Transvision', 'to_category' => 'Streaming'],
    
    // Lita is a game currency
    ['from_brand' => 'Lita', 'to_category' => 'Game']
];

$total_moved = 0;

// Execute redistribution
foreach ($redistribution_rules as $rule) {
    try {
        $sql = "UPDATE products SET category = ? WHERE category = 'Lainnya' AND brand = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$rule['to_category'], $rule['from_brand']]);
        
        $moved_count = $stmt->rowCount();
        $total_moved += $moved_count;
        
        echo "Moved {$moved_count} products from '{$rule['from_brand']}' to '{$rule['to_category']}' category\n";
        
    } catch (PDOException $e) {
        echo "Error moving {$rule['from_brand']}: " . $e->getMessage() . "\n";
    }
}

echo "\nRedistribution completed! Total products moved: {$total_moved}\n";

// Show new category distribution
echo "\n=== New Category Distribution ===\n";
try {
    $sql = "SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY count DESC";
    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categories as $cat) {
        echo sprintf("%-20s: %d products\n", $cat['category'], $cat['count']);
    }
    
    // Show remaining items in Lainnya
    echo "\n=== Remaining in 'Lainnya' category ===\n";
    $sql = "SELECT brand, COUNT(*) as count FROM products WHERE category = 'Lainnya' GROUP BY brand ORDER BY count DESC";
    $stmt = $pdo->query($sql);
    $remaining = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($remaining) {
        foreach ($remaining as $item) {
            echo sprintf("%-20s: %d products\n", $item['brand'], $item['count']);
        }
    } else {
        echo "No products remaining in 'Lainnya' category\n";
    }
    
} catch (PDOException $e) {
    echo "Error getting distribution: " . $e->getMessage() . "\n";
}

echo "\nCategory redistribution completed successfully!\n";
?>