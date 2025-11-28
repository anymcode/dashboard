<?php
require_once 'config/db.php';

// Configuration
$numberOfUsers = 10;
$numberOfCategories = 5;
$numberOfProducts = 20;
$numberOfTransactions = 15;

echo "<pre>";
echo "Starting Database Seeder...\n";
echo "---------------------------\n";

try {
    // 1. Seed Categories
    echo "Seeding Categories...\n";
    $categories = [
        'Electronics', 'Fashion', 'Home & Living', 'Gadgets', 'Sports', 
        'Books', 'Automotive', 'Beauty', 'Toys', 'Groceries'
    ];
    
    $categoryIds = [];
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
    
    // Get existing categories first to avoid duplicates or just add new ones
    foreach (array_slice($categories, 0, $numberOfCategories) as $catName) {
        $slug = strtolower(str_replace(' ', '-', $catName)) . '-' . rand(100, 999);
        $desc = "Best quality $catName products.";
        
        $stmt->execute([$catName, $slug, $desc]);
        $categoryIds[] = $pdo->lastInsertId();
        echo " - Created Category: $catName\n";
    }

    // 2. Seed Users
    echo "\nSeeding Users...\n";
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $password = password_hash('password123', PASSWORD_DEFAULT); // Default password for all
    
    $userIds = [];
    for ($i = 1; $i <= $numberOfUsers; $i++) {
        $name = "User Test $i";
        $email = "user$i" . rand(1000,9999) . "@example.com";
        $role = 'user';
        
        $stmt->execute([$name, $email, $password, $role]);
        $userIds[] = $pdo->lastInsertId();
        echo " - Created User: $email (Pass: password123)\n";
    }

    // 3. Seed Products
    echo "\nSeeding Products...\n";
    $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $productIds = [];
    for ($i = 1; $i <= $numberOfProducts; $i++) {
        if (empty($categoryIds)) break;
        
        $catId = $categoryIds[array_rand($categoryIds)];
        $name = "Product Test Item $i";
        $slug = "product-test-item-$i-" . rand(1000, 9999);
        $desc = "This is a dummy description for product $i.";
        $price = rand(10000, 5000000);
        $stock = rand(0, 100);
        $image = null; // No image for now
        
        $stmt->execute([$catId, $name, $slug, $desc, $price, $stock, $image]);
        $productIds[] = $pdo->lastInsertId();
        echo " - Created Product: $name (Rp " . number_format($price) . ")\n";
    }

    // 4. Seed Transactions
    echo "\nSeeding Transactions...\n";
    $stmtTx = $pdo->prepare("INSERT INTO transactions (user_id, transaction_code, total_amount, status, payment_method, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtItem = $pdo->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    $statuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];
    $paymentMethods = ['Bank Transfer', 'E-Wallet', 'Credit Card'];
    
    for ($i = 1; $i <= $numberOfTransactions; $i++) {
        if (empty($userIds) || empty($productIds)) break;
        
        $userId = $userIds[array_rand($userIds)];
        $code = "TRX-" . date('Ymd') . "-" . rand(10000, 99999);
        $status = $statuses[array_rand($statuses)];
        $method = $paymentMethods[array_rand($paymentMethods)];
        
        // Random date within last 30 days
        $daysAgo = rand(0, 30);
        $date = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));
        
        // Create Transaction first with 0 amount
        $stmtTx->execute([$userId, $code, 0, $status, $method, $date]);
        $txId = $pdo->lastInsertId();
        
        // Add Items
        $totalAmount = 0;
        $numberOfItems = rand(1, 5);
        
        for ($j = 0; $j < $numberOfItems; $j++) {
            $prodId = $productIds[array_rand($productIds)];
            
            // Get product price
            $pStmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
            $pStmt->execute([$prodId]);
            $price = $pStmt->fetchColumn();
            
            $qty = rand(1, 3);
            $subtotal = $price * $qty;
            $totalAmount += $subtotal;
            
            $stmtItem->execute([$txId, $prodId, $qty, $price]);
        }
        
        // Update Total Amount
        $updateStmt = $pdo->prepare("UPDATE transactions SET total_amount = ? WHERE id = ?");
        $updateStmt->execute([$totalAmount, $txId]);
        
        echo " - Created Transaction: $code ($status) - Rp " . number_format($totalAmount) . "\n";
    }

    echo "\n---------------------------\n";
    echo "Seeding Completed Successfully!";

} catch (PDOException $e) {
    echo "\nError: " . $e->getMessage();
}
?>
