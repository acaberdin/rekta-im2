<?php
session_start();
require 'config.php';
if (!isset($_SESSION['loggedin'], $_GET['product_id'], $_POST['quantity'])) {
    header('Location: index.php');
    exit;
}
$userId = $_SESSION['id'];
$productId = intval($_GET['product_id']);
$qty = max(1, intval($_POST['quantity']));

// Fetch customer_id
$stmt = $mysqli->prepare("SELECT id FROM customer WHERE user_id = ?");
$stmt->bind_param("i",$userId);
$stmt->execute(); $stmt->bind_result($customerId); $stmt->fetch(); $stmt->close();

// Fetch or create cart
$stmt = $mysqli->prepare("SELECT id FROM trans_header WHERE customer_id = ? AND trans_type_id = 1 AND status = 'cart'");
$stmt->bind_param("i",$customerId);
$stmt->execute(); $stmt->bind_result($cartId);
if (!$stmt->fetch()) {
    $stmt->close();
    $stmt = $mysqli->prepare("INSERT INTO trans_header (trans_date, trans_type_id, customer_id, total_order_amount, amount_paid, payment_method_id, delivery_type_id, status) VALUES (NOW(),1,? ,0,0,NULL,NULL,'cart')");
    $stmt->bind_param("i",$customerId); $stmt->execute(); $cartId = $stmt->insert_id; $stmt->close();
} else {
    $stmt->close();
}

// Get price
$stmt = $mysqli->prepare("SELECT unit_price FROM product_inventory WHERE id = ?");
$stmt->bind_param("i",$productId);
$stmt->execute(); $stmt->bind_result($unitPrice); $stmt->fetch(); $stmt->close();

// Add/update item
$stmt = $mysqli->prepare("SELECT id, qty_in FROM trans_details WHERE trans_header_id = ? AND product_id = ?");
$stmt->bind_param("ii",$cartId,$productId);
$stmt->execute(); $stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($detailId,$existingQty); $stmt->fetch();
    $newQty = $existingQty + $qty;
    $amount = $unitPrice * $newQty;
    $stmt->close();
    $stmt = $mysqli->prepare("UPDATE trans_details SET qty_in = ?, amount = ? WHERE id = ?");
    $stmt->bind_param("idi",$newQty,$amount,$detailId);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt->close();
    $amount = $unitPrice * $qty;
    $stmt = $mysqli->prepare("INSERT INTO trans_details (trans_header_id, product_id, qty_in, qty_out, price, amount) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("iiiddi",$cartId,$productId,$qty, 0, $unitPrice, $amount);
    $stmt->execute();
    $stmt->close();
}

// Update total
$stmt = $mysqli->prepare("SELECT SUM(amount) FROM trans_details WHERE trans_header_id = ?");
$stmt->bind_param("i",$cartId);
$stmt->execute(); $stmt->bind_result($totalAmount); $stmt->fetch(); $stmt->close();

$stmt = $mysqli->prepare("UPDATE trans_header SET total_order_amount = ? WHERE id = ?");
$stmt->bind_param("di",$totalAmount, $cartId);
$stmt->execute(); $stmt->close();

echo json_encode(['success'=>true]);
