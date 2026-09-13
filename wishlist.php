<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "23UCS105");
$user_id = $_SESSION['user_id'];

/* ADD TO WISHLIST */
if (isset($_GET['add'])) {
    $course_id = $_GET['add'];

    $conn->query("
        INSERT INTO wishlist (user_id, course_id)
        VALUES ($user_id, $course_id)
    ");

    header("Location: wishlist.php");
    exit;
}

/* REMOVE FROM WISHLIST */
if (isset($_GET['remove'])) {
    $course_id = $_GET['remove'];

    $conn->query("
        DELETE FROM wishlist
        WHERE user_id = $user_id AND course_id = $course_id
    ");

    header("Location: wishlist.php");
    exit;
}

/* ADD TO CART */
if (isset($_GET['cart'])) {
    $course_id = $_GET['cart'];

    // Avoid duplicate in cart
    $exists = $conn->query("SELECT * FROM cart WHERE user_id=$user_id AND course_id=$course_id");
    if ($exists->num_rows == 0) {
        $conn->query("INSERT INTO cart (user_id, course_id) VALUES ($user_id, $course_id)");
    }

    header("Location: wishlist.php");
    exit;
}

/* FETCH WISHLIST ITEMS */
$data = $conn->query("
    SELECT c.id, c.title, c.price
    FROM courses c
    JOIN wishlist w ON c.id = w.course_id
    WHERE w.user_id = $user_id
");
?>

<!DOCTYPE html>
<html>
<head>
<title>My Wishlist</title>
<style>
body { font-family: Arial; background:#f5f5f5; padding:20px; }
.course-card {
    background:white;
    padding:15px;
    margin-bottom:10px;
    border-radius:8px;
}
button {
    padding:6px 12px;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
}
.remove-btn { background:#ff4d4d; }
.cart-btn { background:green; margin-left:10px; }
</style>
</head>
<body>

<h2>My Wishlist</h2>
<a href="dashboard.php">⬅ Back to Dashboard</a><br><br>

<?php if ($data->num_rows == 0): ?>
    <p>Your wishlist is empty.</p>
<?php endif; ?>

<?php while($row = $data->fetch_assoc()): ?>
<div class="course-card">
    <b><?= $row['title'] ?></b><br>
    ₹<?= $row['price'] ?><br><br>

    <a href="wishlist.php?remove=<?= $row['id'] ?>">
        <button class="remove-btn">Remove</button>
    </a>

    <a href="wishlist.php?cart=<?= $row['id'] ?>">
        <button class="cart-btn">Add to Cart</button>
    </a>
</div>
<?php endwhile; ?>

</body>
</html>
