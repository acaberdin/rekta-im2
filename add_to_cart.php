<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to add to cart.']);
    exit;
}

$user_id = $_SESSION['id'];

// Get product ID from GET or POST
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
    exit;
}

// Get product price from DB
$stmt = $mysqli->prepare("SELECT unit_price FROM product_inventory WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->bind_result($unit_price);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}
$stmt->close();

// Check if cart (trans_header) exists for the user
$stmt = $mysqli->prepare("SELECT id FROM trans_header WHERE customer_id = ? AND trans_type_id = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($trans_header_id);
$stmt->fetch();
$stmt->close();

// If no cart, create one
if (empty($trans_header_id)) {
    $stmt = $mysqli->prepare("INSERT INTO trans_header (trans_date, trans_type_id, customer_id, total_order_amount) VALUES (NOW(), 1, ?, 0)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $trans_header_id = $stmt->insert_id;
    $stmt->close();
}

// Check if product already in cart
$stmt = $mysqli->prepare("SELECT id, quantity FROM trans_details WHERE trans_header_id = ? AND product_id = ?");
$stmt->bind_param("ii", $trans_header_id, $product_id);
$stmt->execute();
$stmt->bind_result($detail_id, $existing_qty);
if ($stmt->fetch()) {
    // Update quantity if already in cart
    $stmt->close();
    $new_qty = $existing_qty + 1;
    $stmt = $mysqli->prepare("UPDATE trans_details SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_qty, $detail_id);
    $stmt->execute();
    $stmt->close();
} else {
    // Insert new item
    $stmt->close();
    $stmt = $mysqli->prepare("INSERT INTO trans_details (trans_header_id, product_id, quantity, unit_price) VALUES (?, ?, 1, ?)");
    $stmt->bind_param("iid", $trans_header_id, $product_id, $unit_price);
    $stmt->execute();
    $stmt->close();
}

// Optional: update total_order_amount in trans_header
$stmt = $mysqli->prepare("SELECT SUM(quantity * unit_price) FROM trans_details WHERE trans_header_id = ?");
$stmt->bind_param("i", $trans_header_id);
$stmt->execute();
$stmt->bind_result($total_order_amount);
$stmt->fetch();
$stmt->close();

$stmt = $mysqli->prepare("UPDATE trans_header SET total_order_amount = ? WHERE id = ?");
$stmt->bind_param("di", $total_order_amount, $trans_header_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Product added to cart.']);
?>
