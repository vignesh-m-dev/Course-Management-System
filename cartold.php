<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "23UCS105");
$user_id = $_SESSION['user_id'];

/* ADD TO CART */
if (isset($_GET['add'])) {
    $course_id = (int)$_GET['add'];

    $check = $conn->query("
        SELECT * FROM cart 
        WHERE user_id=$user_id AND course_id=$course_id
    ");

    if ($check->num_rows == 0) {
        $conn->query("
            INSERT INTO cart (user_id, course_id)
            VALUES ($user_id, $course_id)
        ");
    }
    header("Location: cart.php");
    exit;
}

/* REMOVE */
if (isset($_GET['remove'])) {
    $conn->query("
        DELETE FROM cart 
        WHERE user_id=$user_id AND course_id=".$_GET['remove']
    );
    header("Location: cart.php");
    exit;
}

/* FETCH CART */
$data = $conn->query("
    SELECT c.id, c.title, c.price
    FROM cart ct
    JOIN courses c ON c.id = ct.course_id
    WHERE ct.user_id=$user_id
");

$total = 0;
?>

<h2>My Cart</h2>
<a href="dashboard.php">Back</a><br><br>

<?php while($row = $data->fetch_assoc()): ?>
<?php $total += $row['price']; ?>
<div>
    <?= $row['title'] ?> - ₹<?= $row['price'] ?>
    <a href="?remove=<?= $row['id'] ?>">cancel</a>
</div>
<?php endwhile; ?>

<?php if ($total > 0): ?>
<hr>
<b>Total: ₹<?= $total ?></b><br><br>
<a href="payment.php"><button>Proceed to Payment</button></a>
<?php endif; ?>
