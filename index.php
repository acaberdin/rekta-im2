<?php
session_start();
include 'config.php';

$is_logged_in = isset($_SESSION['loggedin']);
$filter = $_GET['category'] ?? 'all';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cycling E-commerce</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="navbar">
    <a href="index.php" class="logo">Cycling Gear</a>

    <div class="nav-links" style="flex: 1; text-align: center;">
        <a href="index.php?category=1">Clothing</a>
        <a href="index.php?category=2">Accessories</a>
        <a href="index.php">All</a>
    </div>

    <div class="icons">
        <div class="search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Search products...">
        </div>

        <a href="favorites.php"><i class="fa-solid fa-heart" title="Favorites"></i></a>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping" title="Cart"></i></a>
        <a href="<?= $is_logged_in ? 'logout.php' : 'login.php' ?>">
            <i class="fa-solid fa-user" title="<?= $is_logged_in ? 'Logout' : 'Login' ?>"></i>
        </a>
    </div>
</div>

<header>
    <?php if ($is_logged_in): ?>
        <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
        <h3>User Type: <?= htmlspecialchars($_SESSION['user_type_desc']) ?></h3>
    <?php else: ?>
        <p><a href="login.php">Login</a> to add products to your cart or favorites.</p>
    <?php endif; ?>
</header>

<?php
$categoryNames = [
    1 => "Clothing",
    2 => "Accessories"
];

if (isset($categoryNames[$filter])) {
    echo "<h2 style='text-align:center'>" . $categoryNames[$filter] . "</h2>";
}
?>

<div class="products" id="products">
    <?php
    $query = "SELECT * FROM product_inventory WHERE is_active = 1";
    if (is_numeric($filter)) {
        $query .= " AND category_id = " . intval($filter);
    }
    $result = $mysqli->query($query);

    while ($row = $result->fetch_assoc()) {
        echo "<div class='product' data-name='" . htmlspecialchars($row['product_name']) . "'>";
        echo "<h2>" . htmlspecialchars($row['product_name']) . "</h2>";
        echo "<p>Price: $" . number_format($row['unit_price'], 2) . "</p>";
        echo "<p>" . htmlspecialchars($row['product_description']) . "</p>";

        if ($is_logged_in) {
            echo "<a href='add_to_cart.php?product_id=" . $row['id'] . "'><button>Add to Cart</button></a>";
            echo "<a href='favorites.php?product_id=" . $row['id'] . "'><button>Add to Favorites</button></a>";
        } else {
            echo "<button class='disabled' disabled>Add to Cart</button>";
            echo "<button class='disabled' disabled>Add to Favorites</button>";
        }

        echo "</div>";
    }
    ?>
</div>

<script src="scripts/main.js"></script>

</body>
</html>
