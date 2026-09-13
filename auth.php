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
