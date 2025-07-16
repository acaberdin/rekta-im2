<?php
session_start();
include 'config.php';

$is_logged_in = isset($_SESSION['loggedin']);
$filter = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekta Cycling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="styles/main.css">
    <style>
        .navbar .icons a:hover i {
            outline: 2px solid black;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">REKTA</a>
        <div class="collapse navbar-collapse justify-content-center">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="index.php?category=1">Clothing</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?category=2">Accessories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php">All</a>
                </li>
            </ul>
        </div>
        <div class="d-flex align-items-center icons">
            <form class="d-flex me-3" role="search" method="GET" action="index.php">
                <input class="form-control me-2" type="search" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-light" type="submit"><i class="fa-solid fa-magnifying-glass text-dark"></i></button>
            </form>
            <a href="favorites.php" class="text-white me-3"><i class="fa-solid fa-heart"></i></a>
            <a href="add_to_cart.php" class="text-white me-3"><i class="fa-solid fa-cart-shopping"></i></a>
            <a href="<?= $is_logged_in ? 'logout.php' : 'login.php' ?>" class="text-white">
                <i class="fa-solid fa-user"></i>
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if ($is_logged_in): ?>
        <div class="alert alert-success text-center">
            Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!<br>
            User Type: <?= htmlspecialchars($_SESSION['user_type_desc']) ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">
            <a href="login.php">Login</a> to add products to your cart or favorites.
        </div>
    <?php endif; ?>

    <?php
    $categoryNames = [
        1 => "Clothing",
        2 => "Accessories"
    ];

    if (isset($categoryNames[$filter])) {
        echo "<h2 class='text-center'>" . $categoryNames[$filter] . "</h2>";
    }
    ?>

    <div class="row" id="products">
        <?php
        $query = "SELECT * FROM product_inventory WHERE is_active = 1";
        if (is_numeric($filter)) {
            $query .= " AND category_id = " . intval($filter);
        }
        if (!empty($search)) {
            $safe_search = $mysqli->real_escape_string($search);
            $query .= " AND product_name LIKE '%$safe_search%'";
        }

        $result = $mysqli->query($query);

        while ($row = $result->fetch_assoc()) {
            echo "<div class='col-md-4 mb-4'>";
            echo "<div class='card h-100'>";
            echo "<div class='card-body'>";
            echo "<h5 class='card-title'>" . htmlspecialchars($row['product_name']) . "</h5>";
            echo "<p class='card-text'>Price: $" . number_format($row['unit_price'], 2) . "</p>";
            echo "<p class='card-text'>" . htmlspecialchars($row['product_description']) . "</p>";

            if ($is_logged_in) {
                echo "<a href='add_to_cart.php?product_id=" . $row['id'] . "' class='btn btn-dark me-2'>Add to Cart</a>";
                echo "<a href='favorites.php?product_id=" . $row['id'] . "' class='btn btn-outline-secondary'>Add to Favorites</a>";
            } else {
                echo "<button class='btn btn-secondary' disabled>Add to Cart</button> ";
                echo "<button class='btn btn-secondary' disabled>Add to Favorites</button>";
            }

            echo "</div></div></div>";
        }
        ?>
    </div>
</div>

<script src="scripts/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
