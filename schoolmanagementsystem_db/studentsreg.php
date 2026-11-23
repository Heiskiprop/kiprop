<?php
session_start();

require_once "db.php";

// Only administrators can manage students
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

// REGISTER STUDENT
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )) {
        die("Invalid request. Refresh the page.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "register") {

        $full_name = trim($_POST["full_name"] ?? "");
        $gender = $_POST["gender"] ?? "";
        $dob = $_POST["date_of_birth"] ?? "";
        $phone = trim($_POST["phone"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $class_id = $_POST["class_id"] ?? "";

        if ($full_name === "" ||
            !in_array($gender, ["Male", "Female", "Other"], true)) {

            $error = "Enter a name and select a valid gender.";

        } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Enter a valid email address.";

        } else {

            try {
                // Generate a unique admission number
                $admission_no = "ADM-" .
                    date("Ymd") . "-" .
                    strtoupper(bin2hex(random_bytes(3)));

                $sql = "INSERT INTO students
                (admission_no, full_name, gender,
                 date_of_birth, phone, email, class_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    $admission_no,
                    $full_name,
                    $gender,
                    $dob ?: null,
                    $phone ?: null,
                    $email ?: null,
                    $class_id !== "" ? $class_id : null
                ]);

                $message = "Student registered successfully! Admission Number: " .
                    $admission_no;

            } catch (PDOException $e) {
                $error = "Could not register student. Please try again.";
            }
        }
    }

    // DELETE STUDENT
    if ($action === "delete") {

        $student_id = (int)($_POST["student_id"] ?? 0);

        $stmt = $conn->prepare(
            "DELETE FROM students WHERE student_id = ?"
        );

        $stmt->execute([$student_id]);

        $message = "Student record deleted successfully.";
    }
}

// GET CLASSES
$classes = $conn->query(
    "SELECT class_id, class_name, academic_year
     FROM classes ORDER BY class_name"
)->fetchAll(PDO::FETCH_ASSOC);

// GET STUDENTS
$students = $conn->query(
    "SELECT s.*, c.class_name
     FROM students s
     LEFT JOIN classes c ON s.class_id = c.class_id
     ORDER BY s.student_id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Management</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

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
            max-width: 1150px;
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
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-top: 7px;
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
            border-collapse: collapse;
            min-width: 750px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #54278f;
            color: white;
        }

        @media(max-width: 650px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header>
    <h2 style="color:white">SCHOOL MANAGEMENT SYSTEM</h2>
    <p>Student Registration and Management</p>
    <a href="dashboard.php" style="color:white">
        Back to Dashboard
    </a>
</header>

<div class="container">

    <div class="form-box">

        <h2>Register New Student</h2>

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

            <input type="hidden" name="csrf_token"
                   value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

            <input type="hidden" name="action" value="register">

            <div class="form-grid">

                <div>
                    <label>Full Name</label>
                    <input type="text" name="full_name"
                           placeholder="Enter student full name"
                           maxlength="150" required>
                </div>

                <div>
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div>
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth">
                </div>

                <div>
                    <label>Phone Number</label>
                    <input type="tel" name="phone"
                           placeholder="Enter phone number">
                </div>

                <div>
                    <label>Email Address</label>
                    <input type="email" name="email"
                           placeholder="Enter email address">
                </div>

                <div>
                    <label>Assign Class</label>
                    <select name="class_id">
                        <option value="">Select Class</option>

                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class["class_id"]; ?>">
                                <?php echo htmlspecialchars(
                                    $class["class_name"] . " - " .
                                    $class["academic_year"]
                                ); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

            </div>

            <br>

            <button type="submit">
                REGISTER STUDENT
            </button>

        </form>

    </div>


    <div class="table-box">

        <h2>Registered Students</h2>

        <p>Total Students:
            <strong><?php echo count($students); ?></strong>
        </p>

        <div class="table-scroll">

            <table>

                <tr>
                    <th>ID</th>
                    <th>Admission No.</th>
                    <th>Full Name</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Class</th>
                    <th>Action</th>
                </tr>

                <?php foreach ($students as $student): ?>

                    <tr>
                        <td><?php echo $student["student_id"]; ?></td>

                        <td><?php echo htmlspecialchars($student["admission_no"]); ?></td>

                        <td><?php echo htmlspecialchars($student["full_name"]); ?></td>

                        <td><?php echo htmlspecialchars($student["gender"]); ?></td>

                        <td><?php echo htmlspecialchars($student["phone"] ?? ""); ?></td>

                        <td><?php echo htmlspecialchars($student["class_name"] ?? "Not assigned"); ?></td>

                        <td>
                            <form method="POST"
                                  onsubmit="return confirm('Delete this student?');">

                                <input type="hidden" name="csrf_token"
                                       value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

                                <input type="hidden" name="action"
                                       value="delete">

                                <input type="hidden" name="student_id"
                                       value="<?php echo $student["student_id"]; ?>">

                                <button class="delete" type="submit">
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