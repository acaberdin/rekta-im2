<?php
session_start();
require_once 'config.php';

$username = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $mysqli->prepare("SELECT id, user_name, user_type_id, password FROM user_login WHERE user_name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $stmt->store_result();
    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $name, $user_type_id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $name;
            $_SESSION['id'] = $id;
            $_SESSION['user_type_desc'] = $user_type_id;

            header("Location: index.php");
            exit;
        } else {
            $error_message = "Invalid password.";
        }
    } else {
        $error_message = "Username not found.";
    }

    $stmt->close();
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="styles/register.css">
</head>



<body>
<div class="container">
    <h2>Login</h2>

    <!-- Error messages -->

    <?php if (!empty($_GET['registered'])): ?>
    <p style="color: green;">Account created successfully. You can now log in.</p>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <ul>
                <li><?= htmlspecialchars($error_message) ?></li>
            </ul>
        <?php endif; ?>

    <form method="POST">
    <label>Username:</label>
    <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required />

    <label>Password:</label>
    <input type="password" name="password" required />

    <input type="submit" value="Login" />
    </form>

    <p>Not a member? <a href="register.php">Register Now</a></p>
</div>
</body>

</html>
