<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "23UCS105");
require_once 'db_helper.php';

// Check if user is admin
$userResult = $conn->query("SELECT is_admin FROM users WHERE user_id = " . $_SESSION['user_id']);
$user = $userResult->fetch_assoc();

if (!$user || !$user['is_admin']) {
    header("Location: dashboard.php");
    exit;
}

// Handle delete course
if (isset($_GET['delete'])) {
    $courseId = intval($_GET['delete']);
    deleteAndResetAutoIncrement($conn, 'courses', 'id', $courseId);
    header("Location: admin.php");
    exit;
}

// Handle add/edit course
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = floatval($_POST['price']);
    $deal = $conn->real_escape_string($_POST['deal']);
    $offer = $conn->real_escape_string($_POST['offer']);
    
    if (isset($_POST['course_id']) && !empty($_POST['course_id'])) {
        // Update existing course
        $courseId = intval($_POST['course_id']);
        $conn->query("UPDATE courses SET title='$title', description='$description', price=$price, deal='$deal', offer='$offer' WHERE id=$courseId");
    } else {
        // Add new course
        $conn->query("INSERT INTO courses (title, description, price, deal, offer) VALUES ('$title', '$description', $price, '$deal', '$offer')");
    }
    header("Location: admin.php");
    exit;
}

// Get course data for editing
$editCourse = null;
if (isset($_GET['edit'])) {
    $courseId = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM courses WHERE id = $courseId");
    $editCourse = $result->fetch_assoc();
}

// Get all courses
$courseData = $conn->query("SELECT * FROM courses");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel - Course Management</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial;
    background: #f5f5f5;
}

/* APP BAR */
.appbar {
    background: #dc3545;
    color: white;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.appbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.appbar-right {
    display: flex;
    align-items: center;
    gap: 18px;
}

.icon {
    cursor: pointer;
    font-weight: bold;
    color: white;
    text-decoration: none;
}

/* CONTAINER */
.container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 20px;
    color: #333;
}

/* FORM STYLES */
.form-container {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

input, textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-family: Arial;
}

textarea {
    resize: vertical;
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.btn-submit {
    background: #28a745;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
}

.btn-submit:hover {
    background: #218838;
}

.btn-cancel {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
    margin-left: 10px;
}

.btn-cancel:hover {
    background: #5a6268;
}

/* COURSE TABLE */
.course-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.course-table th {
    background: #dc3545;
    color: white;
    padding: 12px;
    text-align: left;
    font-weight: bold;
}

.course-table td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
}

.course-table tr:hover {
    background: #f9f9f9;
}

.course-table .title {
    font-weight: bold;
    color: #333;
}

.course-table .price {
    color: green;
    font-weight: bold;
}

.course-table .actions {
    display: flex;
    gap: 8px;
}

.btn-edit {
    background: #007bff;
    color: white;
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    font-size: 12px;
}

.btn-edit:hover {
    background: #0056b3;
}

.btn-delete {
    background: #dc3545;
    color: white;
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
}

.btn-delete:hover {
    background: #c82333;
}

.btn-add {
    background: #28a745;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    margin-bottom: 20px;
}

.btn-add:hover {
    background: #218838;
}

.alert {
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.no-data {
    text-align: center;
    padding: 20px;
    color: #666;
}
</style>
</head>

<body>

<!-- APP BAR -->
<div class="appbar">
    <div class="appbar-left">
        <div class="icon">⚙️ Admin Panel</div>
    </div>

    <div class="appbar-right">
        <a href="dashboard.php" class="icon">📊 Dashboard</a>
        <a href="auth.php?logout=1" class="icon">🚪 Logout</a>
    </div>
</div>

<!-- CONTAINER -->
<div class="container">
    <!-- ADD/EDIT COURSE FORM -->
    <div class="section">
        <div class="section-title">
            <?php echo isset($editCourse) ? "Edit Course" : "Add New Course"; ?>
        </div>

        <div class="form-container">
            <form method="POST">
                <?php if (isset($editCourse)): ?>
                    <input type="hidden" name="course_id" value="<?= $editCourse['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="title">Course Title *</label>
                    <input type="text" id="title" name="title" required value="<?= isset($editCourse) ? htmlspecialchars($editCourse['title']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="description">Course Description *</label>
                    <textarea id="description" name="description" required><?= isset($editCourse) ? htmlspecialchars($editCourse['description']) : '' ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (₹) *</label>
                        <input type="number" id="price" name="price" step="0.01" required value="<?= isset($editCourse) ? $editCourse['price'] : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="deal">Deal (e.g., 20% OFF)</label>
                        <input type="text" id="deal" name="deal" value="<?= isset($editCourse) ? htmlspecialchars($editCourse['deal']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="offer">Offer (e.g., Free projects)</label>
                    <input type="text" id="offer" name="offer" value="<?= isset($editCourse) ? htmlspecialchars($editCourse['offer']) : '' ?>">
                </div>

                <button type="submit" class="btn-submit">
                    <?php echo isset($editCourse) ? "Update Course" : "Add Course"; ?>
                </button>

                <?php if (isset($editCourse)): ?>
                    <a href="admin.php" class="btn-cancel">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- COURSES LIST -->
    <!-- <div class="section">
        <div class="section-title">All Courses</div>

        <?php if ($courseData->num_rows > 0): ?>
            <table class="course-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Deal</th>
                        <th>Offer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $courseData->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td class="title"><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= htmlspecialchars(substr($row['description'], 0, 50)) ?>...</td>
                            <td class="price">₹<?= $row['price'] ?></td>
                            <td><?= $row['deal'] ? htmlspecialchars($row['deal']) : '-' ?></td>
                            <td><?= $row['offer'] ? htmlspecialchars($row['offer']) : '-' ?></td>
                            <td class="actions">
                                <a href="admin.php?edit=<?= $row['id'] ?>" class="btn-edit">✏️ Edit</a>
                                <button onclick="deleteConfirm(<?= $row['id'] ?>)" class="btn-delete">🗑️ Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">No courses found. <a href="admin.php">Add the first course</a></div>
        <?php endif; ?>
    </div> -->
</div>

<script>
function deleteConfirm(courseId) {
    if (confirm('Are you sure you want to delete this course?')) {
        window.location.href = 'admin.php?delete=' + courseId;
    }
}
</script>

</body>
</html>
<?php
session_start();

/* ================= LOGOUT ================= */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// If someone visits auth.php without logout parameter, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>
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

<!DOCTYPE html>
<html>
<head>
<title>My Cart</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f2f4f7;
    padding: 20px;
}

.cart-box {
    max-width: 750px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.cart-header h2 {
    margin: 0;
}

.back-link {
    text-decoration: none;
    color: #007bff;
    font-weight: bold;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    margin-bottom: 12px;
    border-radius: 8px;
    background: #f9fafb;
    border: 1px solid #e0e0e0;
}

.cart-title {
    font-weight: bold;
    font-size: 16px;
}

.cart-price {
    color: #28a745;
    font-weight: bold;
    margin-top: 5px;
}

.remove-link {
    text-decoration: none;
    color: white;
    background: #dc3545;
    padding: 6px 12px;
    border-radius: 5px;
    font-size: 14px;
}

.remove-link:hover {
    background: #b52a37;
}

.cart-total {
    margin-top: 25px;
    text-align: right;
}

.cart-total strong {
    font-size: 18px;
}

.pay-btn {
    margin-top: 10px;
    padding: 10px 18px;
    background: #007bff;
    border: none;
    color: white;
    font-size: 15px;
    border-radius: 6px;
    cursor: pointer;
}

.pay-btn:hover {
    background: #0056b3;
}

.empty {
    text-align: center;
    color: #777;
    margin-top: 40px;
}
</style>

</head>
<body>

<div class="cart-box">

    <div class="cart-header">
        <h2>🛒 My Cart</h2>
        <a href="dashboard.php" class="back-link">← Back</a>
    </div>

    <?php if ($data->num_rows > 0): ?>

        <?php while($row = $data->fetch_assoc()): ?>
            <?php $total += $row['price']; ?>

            <div class="cart-item">
                <div>
                    <div class="cart-title"><?= $row['title'] ?></div>
                    <div class="cart-price">₹<?= $row['price'] ?></div>
                </div>

                <!-- SAME REMOVE LOGIC -->
                <a href="?remove=<?= $row['id'] ?>" class="remove-link">
                    Remove
                </a>
            </div>

        <?php endwhile; ?>

        <div class="cart-total">
            <strong>Total: ₹<?= $total ?></strong><br>
            <a href="payment.php">
                <button class="pay-btn">Proceed to Payment</button>
            </a>
        </div>

    <?php else: ?>
        <div class="empty">
            <h3>Your cart is empty 😔</h3>
            <a href="dashboard.php" class="back-link">Browse Courses</a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
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
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "23UCS105");

// Check if user is admin
$userResult = $conn->query("SELECT is_admin FROM users WHERE user_id = " . $_SESSION['user_id']);
$user = $userResult->fetch_assoc();
$isAdmin = $user && $user['is_admin'];

$courseData = $conn->query("SELECT * FROM courses");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"> 
<title>Dashboard</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial;
    background: #f5f5f5;
}

/* APP BAR */
.appbar {
    background: #007bff;
    color: white;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.appbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.appbar-right {
    display: flex;
    align-items: center;
    gap: 18px;
}

.appbar input {
    padding: 7px;
    border-radius: 5px;
    border: none;
    width: 250px;
}

.icon {
    cursor: pointer;
    font-weight: bold;
    color: white;
    text-decoration: none;
}

/* COURSE LIST */
.container {
    padding: 20px;
}

.course-card {
    background: white;
    padding: 18px;
    margin-bottom: 15px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.course-title {
    font-size: 20px;
    font-weight: bold;
}

.course-desc {
    font-size: 14px;
    margin: 10px 0;
}

.price {
    font-size: 18px;
    font-weight: bold;
    color: green;
}

.buttons {
    margin-top: 12px;
    display: flex;
    gap: 10px;
}

button {
    padding: 8px 15px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.wishlist-btn {
    background: #ff4d4d;
}

.deal {
    color: #d35400;
    font-weight: bold;
}

.offer {
    color: #8e44ad;
    font-weight: bold;
}
</style>
</head>

<body>

<!-- APP BAR -->
<div class="appbar">
    <div class="appbar-left">
        <div class="icon">👤 <?= $_SESSION['user'] ?></div>
        <input type="text" placeholder="Search courses..." id="searchBox">
    </div>

    <div class="appbar-right">
        <?php if ($isAdmin): ?>
            <a href="admin.php" class="icon">⚙️ Admin Panel</a>
        <?php endif; ?>
        <a href="cart.php" class="icon">🛒 Cart</a>
        <a href="wishlist.php" class="icon">❤️ Wishlist</a>
        <a href="order_history.php" class="icon">📦 Orders</a>
        <a href="auth.php?logout=1" class="icon">🚪 Logout</a>
    </div>
</div>

<!-- COURSE LIST -->
<div class="container" id="courseList">
<?php while($row = $courseData->fetch_assoc()): ?>
    <div class="course-card">
        <div class="course-title"><?= $row['title'] ?></div>
        <div class="course-desc"><?= $row['description'] ?></div>

        <?php if (!empty($row['deal'])): ?>
            <div class="deal">Deal: <?= $row['deal'] ?></div>
        <?php endif; ?>

        <?php if (!empty($row['offer'])): ?>
            <div class="offer">Offer: <?= $row['offer'] ?></div>
        <?php endif; ?>

        <div class="price">₹<?= $row['price'] ?></div>

        <div class="buttons">
            <a href="cart.php?add=<?= $row['id'] ?>">
                <button>Add to Cart</button>
            </a>

            <a href="wishlist.php?add=<?= $row['id'] ?>">
                <button class="wishlist-btn">Wishlist</button>
            </a>
        </div>
    </div>
<?php endwhile; ?>
</div>

<script>
document.getElementById("searchBox").addEventListener("input", function() {
    let filter = this.value.toLowerCase();
    let cards = document.querySelectorAll(".course-card");

    cards.forEach(card => {
        let title = card.querySelector(".course-title").innerText.toLowerCase();
        card.style.display = title.includes(filter) ? "block" : "none";
    });
});
</script>

</body>
</html>
<?php
/**
 * Delete record and reset AUTO_INCREMENT
 * @param $conn - Database connection
 * @param $table - Table name
 * @param $idColumn - ID column name (e.g., 'id', 'user_id', 'cart_id')
 * @param $id - ID value to delete
 */
function deleteAndResetAutoIncrement($conn, $table, $idColumn, $id) {
    $id = intval($id);
    
    // Delete the record
    $conn->query("DELETE FROM $table WHERE $idColumn = $id");
    
    // Reset AUTO_INCREMENT to next available ID
    $maxIdResult = $conn->query("SELECT MAX($idColumn) AS max_id FROM $table");
    $maxIdRow = $maxIdResult->fetch_assoc();
    $nextId = ($maxIdRow['max_id'] ?? 0) + 1;
    
    // Reset AUTO_INCREMENT
    $conn->query("ALTER TABLE $table AUTO_INCREMENT = $nextId");
    
    return true;
}
?>
<?php
session_start();
$conn = new mysqli("localhost", "root", "", "23UCS105");

/* ================= REGISTER ================= */
$registerMessage = "";
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone']; // ✅ NEW
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $registerMessage = "Email already exists!";
    } else {
        $conn->query("
            INSERT INTO users (name, email, phone, password, is_admin)
            VALUES ('$name', '$email', '$phone', '$password', 0)
        ");
        $registerMessage = "Registration successful! You can login.";
    }
}

/* ================= LOGIN ================= */
$loginMessage = "";
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = $row['name'];
            $_SESSION['user_id'] = $row['user_id']; // 🔑

            header("Location: dashboard.php");
            exit;
        } else {
            $loginMessage = "Invalid password!";
        }
    } else {
        $loginMessage = "Email not found!";
    }
}

/* ================= LOGOUT ================= */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login & Register</title>
<style>
body { font-family: Arial; background:#f2f2f2; display:flex; justify-content:center; align-items:center; height:100vh; }
.container { width:350px; background:white; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,.2); }
input, button { width:100%; padding:10px; margin:10px 0; }
button { background:#007bff; color:white; border:none; border-radius:5px; }
.toggle { text-align:center; color:#007bff; cursor:pointer; }
.msg { text-align:center; color:red; }
.success { color:green; }
</style>
</head>

<body>

<div class="container" id="loginBox">
    <h2>Login</h2>
    <form method="POST">
        <input type="email" name="email" required placeholder="Email">
        <input type="password" name="password" required placeholder="Password">
        <button name="login">Login</button>
    </form>
    <div class="msg"><?= $loginMessage ?></div>
    <div class="toggle" onclick="showRegister()">Don't have an account? Register</div>
</div>

<div class="container" id="registerBox" style="display:none;">
    <h2>Register</h2>
    <form method="POST">
        <input type="text" name="name" required placeholder="Full Name">
        <input type="email" name="email" required placeholder="Email">
        <input type="text" name="phone" required placeholder="Phone Number"> <!-- ✅ NEW -->
        <input type="password" name="password" required placeholder="Password">
        <button name="register">Register</button>
    </form>
    <div class="msg success"><?= $registerMessage ?></div>
    <div class="toggle" onclick="showLogin()">Already have an account? Login</div>
</div>

<script>
function showRegister() {
    document.getElementById("loginBox").style.display = "none";
    document.getElementById("registerBox").style.display = "block";
}
function showLogin() {
    document.getElementById("registerBox").style.display = "none";
    document.getElementById("loginBox").style.display = "block";
}
</script>

</body>
</html>
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

<h2>📦 Order History</h2>
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
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "23UCS105");
$user_id = $_SESSION['user_id'];

/* FETCH CART ITEMS */
$cart = $conn->query("
    SELECT c.id, c.price
    FROM cart ct
    JOIN courses c ON c.id = ct.course_id
    WHERE ct.user_id = $user_id
");

if ($cart->num_rows == 0) {
    echo "<h2>Your cart is empty</h2><a href='dashboard.php'>Go Back</a>";
    exit;
}

$total = 0;
$items = [];
while ($row = $cart->fetch_assoc()) {
    $total += $row['price'];
    $items[] = $row;
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
</style>
</head>
<body>

<div class="payment-box">
    <h2>💳 Payment</h2>

    <!-- PAY NOW FORM -->
    <form method="post" action="place_order.php">
        <input type="hidden" name="payment_status" value="PAID">
        
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
    <form method="post" action="place_order.php">
        <input type="hidden" name="payment_status" value="PENDING">
        <button class="pay-later">Pay Later (₹<?= $total ?>)</button>
    </form>

    <div class="note">
        * This is a demo payment page (educational purpose only)
    </div>
</div>

</body>
</html>
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "23UCS105");
$user_id = $_SESSION['user_id'];

/* READ PAYMENT STATUS FROM FORM */
$payment_status = $_POST['payment_status'] ?? 'PAID';

/* FETCH CART ITEMS */
$cart = $conn->query("
    SELECT c.id, c.price
    FROM cart ct
    JOIN courses c ON c.id = ct.course_id
    WHERE ct.user_id = $user_id
");

if ($cart->num_rows == 0) {
    header("Location: cart.php");
    exit;
}

/* GENERATE NEW ORDER ID */
$res = $conn->query("SELECT MAX(order_id) AS max_id FROM orders");
$row = $res->fetch_assoc();
$order_id = $row['max_id'] + 1;

$total = 0;
$items = [];

/* CALCULATE TOTAL AND COLLECT ITEMS */
while ($r = $cart->fetch_assoc()) {
    $total += $r['price'];
    $items[] = $r;
}

/* INSERT EACH COURSE AS ONE ROW IN orders TABLE */
foreach ($items as $item) {
    $conn->query("
        INSERT INTO orders ( user_id, course_id, total_amount, payment_status)
        VALUES ( $user_id, {$item['id']}, $total, '$payment_status')
    "); 
}

/* INSERT INTO payments TABLE */
$conn->query("
    INSERT INTO payments (user_id, order_id, amount, payment_status)
    VALUES ($user_id, $order_id, $total, '$payment_status')
");

/* CLEAR CART */
$conn->query("DELETE FROM cart WHERE user_id = $user_id");

/* REDIRECT TO ORDER HISTORY */
header("Location: order_history.php");
exit;
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

<h2>❤️ My Wishlist</h2>
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
