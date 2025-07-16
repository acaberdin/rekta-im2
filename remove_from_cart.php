<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

$detail_id = intval($_POST['detail_id']);

$stmt = $mysqli->prepare("DELETE FROM trans_details WHERE id = ?");
$stmt->bind_param("i", $detail_id);
$stmt->execute();

header("Location: cart.php");
exit;
?>
