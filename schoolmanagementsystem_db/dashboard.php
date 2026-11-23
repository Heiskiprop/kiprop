<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Prevent cached dashboard after logout
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

require_once "db.php";

$name = $_SESSION["full_name"];
$role = $_SESSION["role"];

// Get dashboard statistics for administrators
$students = 0;
$teachers = 0;
$classes = 0;
$subjects = 0;

if ($role === "admin") {
    $students = $conn->query(
        "SELECT COUNT(*) FROM students"
    )->fetchColumn();

    $teachers = $conn->query(
        "SELECT COUNT(*) FROM teachers"
    )->fetchColumn();

    $classes = $conn->query(
        "SELECT COUNT(*) FROM classes"
    )->fetchColumn();

    $subjects = $conn->query(
        "SELECT COUNT(*) FROM subjects"
    )->fetchColumn();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Management Dashboard</title>

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f1f9;
        }

        .sidebar {
            width: 240px;
            height: 100vh;
            background: #32145e;
            color: white;
            position: fixed;
            padding: 20px;
            overflow-y: auto;
        }

        .sidebar h2 {
            font-size: 20px;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            padding: 14px;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .sidebar a:hover {
            background: #7045a8;
        }

        .main {
            margin-left: 240px;
        }

        .topbar {
            background: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 5px #ddd;
        }

        .content {
            padding: 25px;
        }

        .cards {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(190px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px #ddd;
        }

        .card h3 {
            color: #54278f;
        }

        .number {
            font-size: 30px;
            font-weight: bold;
            color: #32145e;
        }

        .logout {
            background: #dc3545;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
        }

        @media(max-width: 700px) {
            .sidebar {
                width: 180px;
            }

            .main {
                margin-left: 180px;
            }

            .topbar {
                flex-wrap: wrap;
            }

            .content {
                padding: 12px;
            }
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <h2>SCHOOL PORTAL</h2>

    <a href="dashboard.php">Dashboard</a>

    <?php if ($role === "admin"): ?>

        <a href="#">Students</a>
        <a href="#">Teachers</a>
        <a href="#">Classes</a>
        <a href="#">Subjects</a>
        <a href="#">Attendance</a>
        <a href="#">Examinations</a>
        <a href="#">Results</a>
        <a href="#">School Fees</a>
        <a href="#">Announcements</a>
        <a href="#">Users</a>

    <?php elseif ($role === "teacher"): ?>

        <a href="#">My Classes</a>
        <a href="#">Attendance</a>
        <a href="#">Enter Results</a>

    <?php elseif ($role === "student"): ?>

        <a href="#">My Profile</a>
        <a href="#">My Attendance</a>
        <a href="#">My Results</a>
        <a href="#">My Fees</a>

    <?php elseif ($role === "parent"): ?>

        <a href="#">My Children</a>
        <a href="#">Attendance</a>
        <a href="#">Results</a>
        <a href="#">School Fees</a>

    <?php endif; ?>

    <a href="logout.php">Logout</a>

</div>


<!-- MAIN CONTENT -->
<div class="main">

    <div class="topbar">

        <h3>School Management System</h3>

        <div>
            Welcome,
            <strong>
                <?php echo htmlspecialchars($name); ?>
            </strong>

            (<?php echo htmlspecialchars($role); ?>)

            <a href="logout.php" class="logout">
                Logout
            </a>
        </div>

    </div>


    <div class="content">

        <h1>
            <?php echo ucfirst(htmlspecialchars($role)); ?>
            Dashboard
        </h1>

        <p>
            Welcome to your School Management System.
        </p>


        <?php if ($role === "admin"): ?>

            <div class="cards">

                <div class="card">
                    <h3>Total Students</h3>
                    <div class="number">
                        <?php echo $students; ?>
                    </div>
                </div>

                <div class="card">
                    <h3>Total Teachers</h3>
                    <div class="number">
                        <?php echo $teachers; ?>
                    </div>
                </div>

                <div class="card">
                    <h3>Total Classes</h3>
                    <div class="number">
                        <?php echo $classes; ?>
                    </div>
                </div>

                <div class="card">
                    <h3>Total Subjects</h3>
                    <div class="number">
                        <?php echo $subjects; ?>
                    </div>
                </div>

            </div>

        <?php elseif ($role === "teacher"): ?>

            <div class="cards">
                <div class="card">
                    <h3>Teacher Portal</h3>
                    <p>Manage your classes, attendance and results.</p>
                </div>
            </div>

        <?php elseif ($role === "student"): ?>

            <div class="cards">
                <div class="card">
                    <h3>Student Portal</h3>
                    <p>View your academic results and attendance.</p>
                </div>
            </div>

        <?php elseif ($role === "parent"): ?>

            <div class="cards">
                <div class="card">
                    <h3>Parent Portal</h3>
                    <p>Monitor your children's school progress.</p>
                </div>
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>