<?php
// C:\xampp\htdocs\pos\index.php

// If already logged in, redirect to dashboard
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS System</title>
    <!-- Use Bootstrap latest stable from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

<div class="login-container">
    <div class="premium-card login-card text-center p-5">
        <div class="brand-logo mb-4">
            <i class="bi bi-cart3 fs-1 text-primary"></i>
            <h2 class="fw-bold-700 mt-2 text-dark">Xentra<span class="text-primary">POS</span></h2>
        </div>
        <h4 class="fw-bold mb-1">Welcome Back</h4>
        <p class="text-muted mb-4 small">Sign in to manage your business</p>
        
        <div id="loginAlertContainer"></div>
        
        <form id="loginForm">
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="username" required autofocus placeholder="Enter your username">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control border-start-0 ps-0" id="password" required placeholder="••••••••">
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" id="loginBtn" class="btn btn-primary btn-lg fw-bold">Login</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script src="assets/js/auth.js"></script>
</body>
</html>
