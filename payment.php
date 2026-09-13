<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

require_once 'db_helper.php';

$conn = new mysqli("localhost", "root", "", "23UCS105");
$user_id = $_SESSION['user_id'];

$total = 0;
$originalTotal = 0;
$items = [];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$fromMyCourses = false;

if ($course_id > 0) {
    // Fetch single course for payment
    $courseResult = $conn->query("SELECT id, price, deal FROM courses WHERE id = $course_id");
    if ($courseResult && $courseResult->num_rows > 0) {
        $course = $courseResult->fetch_assoc();
        $discountedPrice = getDiscountedPrice($course['price'], $course['deal']);
        $total = $discountedPrice;
        $originalTotal = $course['price'];
        $items[] = array(
            'id' => $course['id'],
            'price' => $course['price'],
            'deal' => $course['deal'],
            'discounted_price' => $discountedPrice
        );
        $fromMyCourses = true;
    } else {
        echo "<h2>Course not found</h2><a href='my_courses.php'>Go Back</a>";
        exit;
    }
} else {
    /* FETCH CART ITEMS */
    $cart = $conn->query("
        SELECT c.id, c.price, c.deal
        FROM cart ct
        JOIN courses c ON c.id = ct.course_id
        WHERE ct.user_id = $user_id
    ");

    if ($cart->num_rows == 0) {
        echo "<h2>Your cart is empty</h2><a href='dashboard.php'>Go Back</a>";
        exit;
    }

    while ($row = $cart->fetch_assoc()) {
        $discountedPrice = getDiscountedPrice($row['price'], $row['deal']);
        $total += $discountedPrice;
        $originalTotal += $row['price'];
        $row['discounted_price'] = $discountedPrice;
        $items[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Payment</title>
<style>
body {
    font-family: Arial;
    background: #f5f5f5;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.payment-box {
    background: white;
    padding: 25px;
    width: 360px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.2);
}
.payment-box h2 {
    text-align: center;
    margin-bottom: 20px;
}

.payment-summary {
    background: #f9fafb;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 15px;
    font-size: 14px;
}

.payment-summary-original {
    color: #999;
    text-decoration: line-through;
    margin-bottom: 5px;
}

.payment-summary-total {
    font-size: 16px;
    font-weight: bold;
    color: #28a745;
}
input {
    width: 100%;
    padding: 8px;
    margin-bottom: 12px;
    border-radius: 5px;
    border: 1px solid #ccc;
}
.row {
    display: flex;
    gap: 10px;
}
button {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    color: white;
    font-size: 15px;
}
.pay-now {
    background: green;
    margin-bottom: 10px;
}
.pay-later {
    background: gray;
}
.note {
    font-size: 12px;
    color: #666;
    margin-top: 10px;
    text-align: center;
}

.back-link {
    color: #007bff;
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 15px;
    display: inline-block;
}

.back-link:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<div class="payment-box">
    <h2>Payment</h2>
    
    <div>
        <a href="<?php echo $fromMyCourses ? 'my_courses.php' : 'cart.php'; ?>" class="back-link">← Back</a>
    </div>

    <div class="payment-summary">
        <?php if ($originalTotal > $total): ?>
            <div class="payment-summary-original">Original Total: ₹<?= $originalTotal ?></div>
        <?php endif; ?>
        <div class="payment-summary-total">Pay: ₹<?= $total ?></div>
    </div>

    <!-- PAY NOW FORM -->
    <form method="post" action="place_order.php">
        <input type="hidden" name="payment_status" value="PAID">
        <input type="hidden" name="total_amount" value="<?= $total ?>">
        <?php if ($fromMyCourses && $course_id > 0): ?>
            <input type="hidden" name="course_id" value="<?= $course_id ?>">
        <?php endif; ?>>
        
        <label>Card Holder Name</label>
        <input type="text" name="card_name" placeholder="John Doe" required>
        
        <label>Card Number</label>
        <input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="16" required>
        
        <div class="row">
            <div style="width:50%">
                <label>Expiry</label>
                <input type="text" name="expiry" placeholder="MM/YY" required>
            </div>
            <div style="width:50%">
                <label>CVV</label>
                <input type="password" name="cvv" maxlength="3" required>
            </div>
        </div>

        <button class="pay-now">Pay Now (₹<?= $total ?>)</button>
    </form>

    <!-- PAY LATER FORM -->
    <form method="post" action="order_history.php">
        <input type="hidden" name="payment_status" value="PENDING">
        <input type="hidden" name="total_amount" value="<?= $total ?>">
        <?php if ($fromMyCourses && $course_id > 0): ?>
            <input type="hidden" name="course_id" value="<?= $course_id ?>">
        <?php endif; ?>
        <button class="pay-later">Pay Later (₹<?= $total ?>)</button>
    </form>

    <div class="note">
        * This is a demo payment page (educational purpose only)
    </div>
</div>

</body>
</html>
