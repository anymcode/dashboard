<?php
require_once '../config/db.php';

// Check Authentication (Security)
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Set Headers for CSV Download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="transactions_export_' . date('Y-m-d_H-i') . '.csv"');

// Open Output Stream
$output = fopen('php://output', 'w');

// Add CSV Header Row
fputcsv($output, ['Transaction ID', 'Date', 'Customer Name', 'Customer Email', 'Status', 'Payment Method', 'Total Amount', 'Items']);

// Fetch Transactions with Filters (Reuse logic if needed, but for export usually we want all or filtered)
// For simplicity, let's export ALL transactions or respect current filters if passed
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$query = "SELECT t.*, u.name as user_name, u.email as user_email 
          FROM transactions t 
          LEFT JOIN users u ON t.user_id = u.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (t.transaction_code LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $query .= " AND t.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Fetch Items for this transaction to include in CSV
    $stmt_items = $pdo->prepare("SELECT p.name, ti.quantity FROM transaction_items ti JOIN products p ON ti.product_id = p.id WHERE ti.transaction_id = ?");
    $stmt_items->execute([$row['id']]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    
    $item_string = [];
    foreach ($items as $item) {
        $item_string[] = $item['name'] . " (x" . $item['quantity'] . ")";
    }
    $items_formatted = implode(", ", $item_string);

    // Write Row to CSV
    fputcsv($output, [
        $row['transaction_code'],
        $row['created_at'],
        $row['user_name'] ?? 'Guest',
        $row['user_email'] ?? '-',
        ucfirst($row['status']),
        $row['payment_method'],
        $row['total_amount'],
        $items_formatted
    ]);
}

fclose($output);
exit();
?>
