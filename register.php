<?php
require_once 'config.php';

$username = $email = $contact_number = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);

    if (strlen($password) < 6 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
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

        $stmt = $mysqli->prepare("INSERT INTO user_login (user_name, password, email, contact_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $hashed_password, $email, $contact_number);

        if ($stmt->execute()) {
            header("Location: login.php?registered=1"); // Redirect after success
            exit;
        } else {
            $errors[] = "Signup failed: " . $stmt->error;
        }

        $stmt->close();
        $mysqli->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up Form</title>
    <link rel="stylesheet" href="styles/register.css">
</head>



<body>
<div class="container">
    <h2>Sign Up</h2>

    <!-- Error messages here -->
     <?php if (!empty($errors)): ?>
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    <form method="POST">
        <label>Username:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required />

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required />

        <label>Contact Number:</label>
        <input type="text" name="contact_number" value="<?= htmlspecialchars($contact_number) ?>" required />

        <label>Password:</label>
        <input type="password" name="password" required />

        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required />

        <input type="submit" value="Sign Up" />
    </form>


    <p>Already have an account? <a href="login.php">Log in</a></p>
</div>
</body>





