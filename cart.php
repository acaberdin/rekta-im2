<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id'];

// Get cart header (trans_type_id = 1 for "cart")
$stmt = $mysqli->prepare("SELECT id FROM trans_header WHERE customer_id = ? AND trans_type_id = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo "<h2>Your cart is empty.</h2>";
    $stmt->close();
    exit;
}

$stmt->bind_result($trans_header_id);
$stmt->fetch();
$stmt->close();

// Handle form actions (update quantity / remove item)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_qty'])) {
        $detail_id = intval($_POST['detail_id']);
        $new_qty = max(1, intval($_POST['quantity'])); // minimum 1
        $stmt = $mysqli->prepare("UPDATE trans_details SET quantity = ? WHERE id = ? AND trans_header_id = ?");
        $stmt->bind_param("iii", $new_qty, $detail_id, $trans_header_id);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['remove_item'])) {
        $detail_id = intval($_POST['detail_id']);
        $stmt = $mysqli->prepare("DELETE FROM trans_details WHERE id = ? AND trans_header_id = ?");
        $stmt->bind_param("ii", $detail_id, $trans_header_id);
        $stmt->execute();
        $stmt->close();
    }

    // Prevent form resubmission
    header("Location: cart.php");
    exit;
}

// Fetch cart items
$stmt = $mysqli->prepare("
    SELECT 
        d.id AS detail_id,
        p.product_name,
        d.quantity,
        d.unit_price,
        (d.quantity * d.unit_price) AS total_price
    FROM trans_details d
    JOIN product_inventory p ON d.product_id = p.id
    WHERE d.trans_header_id = ?
");
$stmt->bind_param("i", $trans_header_id);
$stmt->execute();
$result = $stmt->get_result();

$total_amount = 0;

echo "<h2>Your Cart</h2>";
echo "<table border='1' cellpadding='10' cellspacing='0'>";
echo "<tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Actions</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";

    echo "<td>
        <form method='post' style='display:inline;'>
            <input type='hidden' name='detail_id' value='" . $row['detail_id'] . "'>
            <input type='number' name='quantity' value='" . $row['quantity'] . "' min='1' style='width:60px'>
            <button type='submit' name='update_qty'>Update</button>
        </form>
    </td>";

    echo "<td>$" . number_format($row['unit_price'], 2) . "</td>";
    echo "<td>$" . number_format($row['total_price'], 2) . "</td>";

    echo "<td>
        <form method='post' style='display:inline;'>
            <input type='hidden' name='detail_id' value='" . $row['detail_id'] . "'>
            <button type='submit' name='remove_item' style='color:red;'>Remove</button>
        </form>
    </td>";

    echo "</tr>";

    $total_amount += $row['total_price'];
}
echo "</table>";

echo "<h3>Total Order Amount: $" . number_format($total_amount, 2) . "</h3>";

$stmt->close();

// Update total in trans_header
$stmt = $mysqli->prepare("UPDATE trans_header SET total_order_amount = ? WHERE id = ?");
$stmt->bind_param("di", $total_amount, $trans_header_id);
$stmt->execute();
$stmt->close();
?>
