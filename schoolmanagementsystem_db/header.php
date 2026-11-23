<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protect pages from unauthorized access
if (!isset($_SESSION['user_id'])) {
    header("Location: userlogin.php");
    exit;
}

$role = $_SESSION['role'] ?? 'student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>School Management System</title>

    <!-- Connect CSS -->
    <link rel="stylesheet" href="AssetsSDB/style.css">
</head>

<body>

<header class="topbar">

    <button class="menu-btn" onclick="toggleMenu()">
        ☰
    </button>

    <h2>🏫 SCHOOL MANAGEMENT SYSTEM</h2>

    <div class="user-info">
        Welcome,
        <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?>
    </div>

</header>

<div class="layout">

    <!-- SIDEBAR MENU -->
    <nav class="sidebar" id="sidebar">

        <h3>MAIN MENU</h3>

        <a href="dashboard.php">🏠 Dashboard</a>

        <?php if ($role === 'admin'): ?>

            <a href="studentsreg.php">👨‍🎓 Students</a>

            <a href="Class.php">🏫 Classes</a>

            <a href="subjectmanagement.php">📚 Subjects</a>

            <a href="teachers.php">👨‍🏫 Teachers</a>

            <a href="attendance.php">📅 Attendance</a>

            <a href="results.php">📊 Results</a>

            <a href="fees.php">💰 School Fees</a>

        <?php elseif ($role === 'teacher'): ?>

            <a href="Class.php">My Classes</a>
            <a href="attendance.php">Attendance</a>
            <a href="results.php">Enter Results</a>

        <?php elseif ($role === 'student'): ?>

            <a href="results.php">My Results</a>
            <a href="attendance.php">My Attendance</a>
            <a href="fees.php">My Fees</a>

        <?php elseif ($role === 'parent'): ?>

            <a href="results.php">Children's Results</a>
            <a href="attendance.php">Children's Attendance</a>
            <a href="fees.php">School Fees</a>

        <?php endif; ?>

        <a href="logout.php" class="logout"
           onclick="return confirm('Are you sure you want to log out?')">
            🚪 Logout
        </a>

    </nav>

    <!-- MAIN CONTENT -->
    <main class="content">