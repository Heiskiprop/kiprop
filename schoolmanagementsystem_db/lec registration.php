<?php
session_start();
require_once "db.php";

// Admin access only
if (!isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";

// HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )) {
        die("Invalid request. Refresh the page.");
    }

    $action = $_POST["action"] ?? "";

    // REGISTER TEACHER
    if ($action === "register") {

        $full_name = trim($_POST["full_name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $staff_no = trim($_POST["staff_no"] ?? "");

        if ($full_name === "") {
            $error = "Teacher name is required.";

        } elseif ($email !== "" &&
                  !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Enter a valid email address.";

        } else {

            try {
                // Generate staff number if not provided
                if ($staff_no === "") {
                    $staff_no = "TCH-" . date("Ymd") .
                        "-" . strtoupper(bin2hex(random_bytes(3)));
                }

                $sql = "INSERT INTO teachers
                (staff_no, full_name, email, phone)
                VALUES (?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    $staff_no,
                    $full_name,
                    $email ?: null,
                    $phone ?: null
                ]);

                $message = "Teacher registered successfully! Staff No: " .
                    $staff_no;

            } catch (PDOException $e) {
                if ($e->getCode() == "23000") {
                    $error = "That staff number already exists.";
                } else {
                    $error = "Unable to register teacher. Please try again.";
                }
            }
        }
    }

    // DELETE TEACHER
    if ($action === "delete") {

        $teacher_id = (int)($_POST["teacher_id"] ?? 0);

        $stmt = $conn->prepare(
            "DELETE FROM teachers WHERE teacher_id = ?"
        );

        $stmt->execute([$teacher_id]);

        $message = "Teacher record deleted successfully.";
    }
}

// FETCH TEACHERS
$stmt = $conn->query(
    "SELECT * FROM teachers ORDER BY teacher_id DESC"
);

$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>

    <title>Teacher Management System</title>

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

        header {
            background: #54278f;
            color: white;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: 25px auto;
            padding: 15px;
        }

        .form-box, .table-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px #ddd;
        }

        h2 {
            color: #54278f;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 7px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            padding: 12px 20px;
            background: #54278f;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .delete {
            background: #dc3545;
        }

        .message {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }

        .table-scroll {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #54278f;
            color: white;
        }

        @media(max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>

<body>

<header>

    <h2 style="color:white">
        SCHOOL MANAGEMENT SYSTEM
    </h2>

    <p>Teacher Registration and Management</p>

    <a href="dashboard.php" style="color:white">
        Back to Dashboard
    </a>

</header>


<div class="container">

    <!-- TEACHER REGISTRATION -->

    <div class="form-box">

        <h2>Register New Teacher</h2>

        <?php if ($message): ?>
            <p class="message">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <form method="POST">

            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

            <input type="hidden"
                   name="action"
                   value="register">

            <div class="form-grid">

                <div>
                    <label>Full Name</label>

                    <input type="text"
                           name="full_name"
                           placeholder="Enter teacher full name"
                           maxlength="150"
                           required>
                </div>

                <div>
                    <label>Staff Number (Optional)</label>

                    <input type="text"
                           name="staff_no"
                           placeholder="Automatic if left blank">
                </div>

                <div>
                    <label>Email Address</label>

                    <input type="email"
                           name="email"
                           placeholder="Enter email address">
                </div>

                <div>
                    <label>Phone Number</label>

                    <input type="tel"
                           name="phone"
                           placeholder="Enter phone number">
                </div>

            </div>

            <br>

            <button type="submit">
                REGISTER TEACHER
            </button>

        </form>

    </div>


    <!-- TEACHER RECORDS -->

    <div class="table-box">

        <h2>Registered Teachers</h2>

        <p>
            Total Teachers:
            <strong><?php echo count($teachers); ?></strong>
        </p>

        <div class="table-scroll">

            <table>

                <tr>
                    <th>ID</th>
                    <th>Staff Number</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Action</th>
                </tr>

                <?php foreach ($teachers as $teacher): ?>

                    <tr>

                        <td>
                            <?php echo $teacher["teacher_id"]; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                $teacher["staff_no"] ?? ""
                            ); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                $teacher["full_name"]
                            ); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                $teacher["email"] ?? ""
                            ); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(
                                $teacher["phone"] ?? ""
                            ); ?>
                        </td>

                        <td>

                            <form method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this teacher?');">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

                                <input type="hidden"
                                       name="action"
                                       value="delete">

                                <input type="hidden"
                                       name="teacher_id"
                                       value="<?php echo $teacher["teacher_id"]; ?>">

                                <button type="submit"
                                        class="delete">
                                    Delete
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

    </div>

</div>

</body>
</html>