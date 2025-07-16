<?php
session_start();
include 'config.php';

$is_logged_in = isset($_SESSION['loggedin']);
$filter = $_GET['category'] ?? 'all';
$size_filter = $_GET['size'] ?? '';
$color_filter = $_GET['color'] ?? '';
$sort_order = $_GET['sort'] ?? '';
$search_query = $_GET['search'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Rekta Online Shop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="styles/main.css">
  <style>
    .navbar .icons a:hover i,
    .navbar .icons button:hover i {
      outline: 2px solid black;
      border-radius: 5px;
    }
    .sidebar {
      min-width: 250px;
      padding: 20px;
      border-right: 1px solid #ccc;
    }
    .product-list {
      flex-grow: 1;
      padding: 20px;
    }
    .search-bar {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .search-bar input[type="text"] {
      border: 1px solid #ccc;
      border-radius: 4px;
      padding: 5px 10px;
    }
    .fav-btn {
      z-index: 10;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
  <a class="navbar-brand" href="index.php">REKTA</a>
  <div class="collapse navbar-collapse justify-content-center">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="index.php?category=1">Clothing</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php?category=2">Accessories</a></li>
      <li class="nav-item"><a class="nav-link" href="index.php">All</a></li>
    </ul>
  </div>

  <form class="search-bar me-3" method="GET" action="index.php">
    <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search_query) ?>">
    <button type="submit" class="btn btn-light p-1">
      <i class="fa-solid fa-magnifying-glass text-dark"></i>
    </button>
  </form>

  <div class="icons d-flex align-items-center">
    <a href="favorites.php" class="text-white me-3"><i class="fa-solid fa-heart"></i></a>
    <a href="cart.php" class="text-white me-3 position-relative">
      <i class="fa-solid fa-cart-shopping"></i>
      <span id="cart-count" class="badge bg-danger position-absolute top-0 start-100 translate-middle">0</span>
    </a>
    <a href="<?= $is_logged_in ? 'logout.php' : 'login.php' ?>" class="text-white">
      <i class="fa-solid fa-user"></i>
    </a>
  </div>
</nav>

<div class="container-fluid mt-4">
  <div class="row">
    <div class="col-md-3 sidebar">
      <form method="GET" action="index.php">
        <h5>Filter by Size</h5>
        <?php foreach (['S', 'M', 'L'] as $size): ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="size" value="<?= $size ?>" <?= $size_filter === $size ? 'checked' : '' ?>>
            <label class="form-check-label"><?= $size ?></label>
          </div>
        <?php endforeach; ?>

        <h5 class="mt-3">Filter by Color</h5>
        <?php foreach (['Black', 'White', 'Red'] as $color): ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="color" value="<?= $color ?>" <?= $color_filter === $color ? 'checked' : '' ?>>
            <label class="form-check-label"><?= $color ?></label>
          </div>
        <?php endforeach; ?>

        <h5 class="mt-3">Sort by Price</h5>
        <select name="sort" class="form-select">
          <option value="">Default</option>
          <option value="asc" <?= $sort_order === 'asc' ? 'selected' : '' ?>>Lowest to Highest</option>
          <option value="desc" <?= $sort_order === 'desc' ? 'selected' : '' ?>>Highest to Lowest</option>
        </select>

        <button type="submit" class="btn btn-dark mt-3 w-100">Apply Filters</button>
      </form>
    </div>

    <div class="col-md-9 product-list">
      <?php
      $query = "SELECT * FROM product_inventory WHERE is_active = 1";
      if (is_numeric($filter)) $query .= " AND category_id = " . intval($filter);
      if (!empty($size_filter)) $query .= " AND size = '" . $mysqli->real_escape_string($size_filter) . "'";
      if (!empty($color_filter)) $query .= " AND color = '" . $mysqli->real_escape_string($color_filter) . "'";
      if (!empty($search_query)) $query .= " AND product_name LIKE '%" . $mysqli->real_escape_string($search_query) . "%'";
      if ($sort_order === 'asc') $query .= " ORDER BY unit_price ASC";
      elseif ($sort_order === 'desc') $query .= " ORDER BY unit_price DESC";

      $result = $mysqli->query($query);
      echo '<div class="row">';
      while ($row = $result->fetch_assoc()) {
        echo "<div class='col-md-4 mb-4'>";
        echo "<div class='card h-100 position-relative'>";

        // Heart icon top-right
        if ($is_logged_in) {
          echo "<button class='btn btn-outline-danger fav-btn position-absolute top-0 end-0 m-2' data-product-id='" . $row['id'] . "'>";
          echo "<i class='fa-solid fa-heart'></i>";
          echo "</button>";
        }

        echo "<div class='card-body'>";
        echo "<h5 class='card-title'>" . htmlspecialchars($row['product_name']) . "</h5>";
        echo "<p class='card-text'>Price: $" . number_format($row['unit_price'], 2) . "</p>";
        echo "<p class='card-text'>" . htmlspecialchars($row['product_description']) . "</p>";

        if ($is_logged_in) {
          echo "<form class='add-to-cart-form' data-product-id='" . $row['id'] . "'>";
          echo "<input type='number' name='quantity' value='1' min='1' style='width:60px' class='form-control d-inline-block me-2'>";
          echo "<button type='submit' class='btn btn-dark me-2'>Add to Cart</button>";
          echo "</form>";
        } else {
          echo "<button class='btn btn-secondary' disabled>Add to Cart</button> ";
          echo "<button class='btn btn-secondary' disabled>Add to Favorites</button>";
        }

        echo "</div></div></div>";
      }
      echo '</div>';
      ?>
    </div>
  </div>
</div>

<script src="scripts/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
