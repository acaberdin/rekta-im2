<?php
require_once 'config.php';

$username = $email = $contact_number = '';
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);

    // Check for duplicate username
    $stmt = $mysqli->prepare("SELECT id FROM user_login WHERE user_name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Username already taken.";
    }
    $stmt->close();

    // Check for duplicate email
    $stmt = $mysqli->prepare("SELECT id FROM user_login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Email already registered.";
    }
    $stmt->close();

    // Password validation
    if (strlen($password) < 6 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\\d/', $password)) {
        $errors[] = "Password must be at least 6 characters and include both letters and numbers.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into user_login
        $stmt = $mysqli->prepare("INSERT INTO user_login (user_name, password, email, contact_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $hashed_password, $email, $contact_number);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            // Insert into customer (date_registered is auto set by DB)
            $customer_stmt = $mysqli->prepare("INSERT INTO customer (id, customer_name, contact_number) VALUES (?, ?, ?)");
            $customer_stmt->bind_param("iss", $user_id, $username, $contact_number);

            $customer_stmt->execute();
            $customer_stmt->close();

            $success_message = "Sign-up successful! <a href='login.php'>Login here</a>";
        } else {
            $errors[] = "Sign-up failed: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 500px;
            margin: 80px auto;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="form-container">
        <h2 class="text-center mb-4">Sign Up</h2>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success text-center">
                <?= $success_message ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="mb-3">
                <label for="contact_number" class="form-label">Contact Number</label>
                <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?= htmlspecialchars($contact_number) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Sign Up</button>
        </form>

        <p class="mt-3 text-center">Already have an account? <a href="login.php">Log in here</a></p>
    </div>
</div>
</body>
</html>
