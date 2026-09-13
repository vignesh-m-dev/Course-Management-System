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
