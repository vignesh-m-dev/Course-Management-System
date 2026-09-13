<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "23UCS105");
$user_id = $_SESSION['user_id'];

$data = $conn->query("
    SELECT o.order_id, o.total_amount, o.payment_status, o.order_date,
           c.title, c.price
    FROM orders o
    JOIN courses c ON c.id = o.course_id
    WHERE o.user_id = $user_id
    ORDER BY o.order_id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Order History</title>
<style>
body{font-family:Arial;background:#f5f5f5;padding:20px}
.order{background:#fff;padding:15px;margin-bottom:15px;border-radius:8px}
.item{margin-left:15px}
</style>
</head>
<body>

<h2>Order History</h2>
<a href="dashboard.php">⬅ Back</a><br><br>

<?php
$prev = 0;
while ($row = $data->fetch_assoc()):
    if ($prev != $row['order_id']):
        if ($prev != 0) echo "</div>";
?>
<div class="order">
<b>Order #<?= $row['order_id'] ?></b><br>
Total: ₹<?= $row['total_amount'] ?><br>
Status: <?= $row['payment_status'] ?><br>
Date: <?= $row['order_date'] ?><br><br>
<?php endif; ?>

<div class="item">• <?= $row['title'] ?> – ₹<?= $row['price'] ?></div>

<?php
$prev = $row['order_id'];
endwhile;

if ($prev != 0) echo "</div>";
?>

</body>
</html>
