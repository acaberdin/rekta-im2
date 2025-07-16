<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id'];

// Get customer ID
$stmt = $mysqli->prepare("SELECT id FROM customer WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($customer_id);
if (!$stmt->fetch()) {
    echo "<div class='container mt-5'><h3>Customer not found.</h3></div>";
    exit;
}
$stmt->close();

// Get cart header (trans_type_id = 1 and status = 'cart')
$stmt = $mysqli->prepare("SELECT id FROM trans_header WHERE customer_id = ? AND trans_type_id = 1 AND status = 'cart' LIMIT 1");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo "<div class='container mt-5'><h3>Your cart is empty.</h3></div>";
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
        $new_qty = max(1, intval($_POST['quantity']));
        $stmt = $mysqli->prepare("UPDATE trans_details SET qty_in = ?, amount = price * ? WHERE id = ? AND trans_header_id = ?");
        $stmt->bind_param("iiii", $new_qty, $new_qty, $detail_id, $trans_header_id);
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

    // Refresh page
    header("Location: cart.php");
    exit;
}

// Fetch cart items
$stmt = $mysqli->prepare("
    SELECT 
        d.id AS detail_id,
        p.product_name,
        d.qty_in,
        d.price,
        (d.qty_in * d.price) AS total_price
    FROM trans_details d
    JOIN product_inventory p ON d.product_id = p.id
    WHERE d.trans_header_id = ?
");
$stmt->bind_param("i", $trans_header_id);
$stmt->execute();
$result = $stmt->get_result();

$total_amount = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2 class="mb-4">🛒 Your Cart</h2>

    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>Product</th>
                <th width="130">Quantity</th>
                <th width="120">Unit Price</th>
                <th width="120">Total</th>
                <th width="100">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['product_name']) ?></td>

                <td>
                    <form method="post" class="d-flex">
                        <input type="hidden" name="detail_id" value="<?= $row['detail_id'] ?>">
                        <input type="number" name="quantity" value="<?= $row['qty_in'] ?>" min="1" class="form-control me-2" style="width: 80px;">
                        <button type="submit" name="update_qty" class="btn btn-primary btn-sm">Update</button>
                    </form>
                </td>

                <td>$<?= number_format($row['price'], 2) ?></td>
                <td>$<?= number_format($row['total_price'], 2) ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="detail_id" value="<?= $row['detail_id'] ?>">
                        <button type="submit" name="remove_item" class="btn btn-danger btn-sm">Remove</button>
                    </form>
                </td>
            </tr>
            <?php $total_amount += $row['total_price']; ?>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div class="text-end">
        <h4>Total: $<?= number_format($total_amount, 2) ?></h4>
        <a href="checkout.php" class="btn btn-success mt-3">Proceed to Checkout</a>
    </div>
</div>

<?php
$stmt->close();

// Update total in trans_header
$stmt = $mysqli->prepare("UPDATE trans_header SET total_order_amount = ? WHERE id = ?");
$stmt->bind_param("di", $total_amount, $trans_header_id);
$stmt->execute();
$stmt->close();
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
