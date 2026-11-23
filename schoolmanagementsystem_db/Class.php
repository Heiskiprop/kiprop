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

// ADD OR UPDATE CLASS
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )) {
        die("Invalid request. Refresh the page.");
    }

    $action = $_POST["action"] ?? "";
    $class_name = trim($_POST["class_name"] ?? "");
    $academic_year = trim($_POST["academic_year"] ?? "");

    if ($action === "save") {

        $class_id = (int)($_POST["class_id"] ?? 0);

        if ($class_name === "" || $academic_year === "") {
            $error = "Please fill in all class details.";

        } elseif (strlen($class_name) > 80 ||
                  strlen($academic_year) > 20) {
            $error = "Class name or academic year is too long.";

        } else {

            try {
                if ($class_id > 0) {

                    $stmt = $conn->prepare(
                        "UPDATE classes
                         SET class_name = ?, academic_year = ?
                         WHERE class_id = ?"
                    );

                    $stmt->execute([
                        $class_name,
                        $academic_year,
                        $class_id
                    ]);

                    $message = "Class updated successfully!";

                } else {

                    $stmt = $conn->prepare(
                        "INSERT INTO classes
                         (class_name, academic_year)
                         VALUES (?, ?)"
                    );

                    $stmt->execute([
                        $class_name,
                        $academic_year
                    ]);

                    $message = "New class added successfully!";
                }

            } catch (PDOException $e) {
                $error = "Unable to save class. Please try again.";
            }
        }
    }

    // DELETE CLASS
    if ($action === "delete") {

        $class_id = (int)($_POST["class_id"] ?? 0);

        try {
            $stmt = $conn->prepare(
                "DELETE FROM classes WHERE class_id = ?"
            );

            $stmt->execute([$class_id]);

            $message = "Class deleted successfully.";

        } catch (PDOException $e) {
            $error = "This class cannot be deleted.";
        }
    }
}

// FETCH CLASS FOR EDITING
$editClass = null;

if (isset($_GET["edit"])) {

    $id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM classes WHERE class_id = ?"
    );

    $stmt->execute([$id]);

    $editClass = $stmt->fetch(PDO::FETCH_ASSOC);
}

// GET ALL CLASSES AND STUDENT COUNTS
$classes = $conn->query(
    "SELECT c.class_id,
            c.class_name,
            c.academic_year,
            COUNT(s.student_id) AS total_students
     FROM classes c
     LEFT JOIN students s ON c.class_id = s.class_id
     GROUP BY c.class_id, c.class_name, c.academic_year
     ORDER BY c.class_id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>

    <title>Class Management</title>

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

        .box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px #ddd;
        }

        h2 {
            color: #54278f;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
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

        .edit {
            background: #198754;
        }

        .message {
            color: green;
        }

        .error {
            color: red;
        }

        .table-scroll {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 600px;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #54278f;
            color: white;
        }

        a {
            text-decoration: none;
        }

    </style>

</head>

<body>

<header>

    <h2 style="color:white">
        SCHOOL MANAGEMENT SYSTEM
    </h2>

    <p>Class Registration and Management</p>

    <a href="dashboard.php" style="color:white">
        Back to Dashboard
    </a>

</header>


<div class="container">

    <!-- ADD OR EDIT CLASS -->

    <div class="box">

        <h2>
            <?php echo $editClass ? "Edit Class" : "Add New Class"; ?>
        </h2>

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
                   value="save">

            <input type="hidden"
                   name="class_id"
                   value="<?php echo $editClass["class_id"] ?? 0; ?>">

            <label>Class Name</label>

            <input type="text"
                   name="class_name"
                   placeholder="Example: Form One"
                   maxlength="80"
                   value="<?php echo htmlspecialchars($editClass["class_name"] ?? ""); ?>"
                   required>

            <label>Academic Year</label>

            <input type="text"
                   name="academic_year"
                   placeholder="Example: 2026"
                   maxlength="20"
                   value="<?php echo htmlspecialchars($editClass["academic_year"] ?? ""); ?>"
                   required>

            <button type="submit">
                <?php echo $editClass ? "UPDATE CLASS" : "ADD CLASS"; ?>
            </button>

            <?php if ($editClass): ?>
                <a href="classes.php">Cancel Editing</a>
            <?php endif; ?>

        </form>

    </div>


    <!-- CLASS LIST -->

    <div class="box">

        <h2>Available Classes</h2>

        <p>
            Total Classes:
            <strong><?php echo count($classes); ?></strong>
        </p>

        <div class="table-scroll">

            <table>

                <tr>
                    <th>ID</th>
                    <th>Class Name</th>
                    <th>Academic Year</th>
                    <th>Students</th>
                    <th>Actions</th>
                </tr>

                <?php foreach ($classes as $class): ?>

                    <tr>

                        <td>
                            <?php echo $class["class_id"]; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($class["class_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($class["academic_year"]); ?>
                        </td>

                        <td>
                            <?php echo $class["total_students"]; ?>
                        </td>

                        <td>

                            <a class="edit"
                               href="classes.php?edit=<?php echo $class["class_id"]; ?>"
                               style="color:white;padding:8px;border-radius:5px;">
                                Edit
                            </a>

                            <form method="POST"
                                  style="display:inline;"
                                  onsubmit="return confirm('Delete this class? Students in this class will become unassigned.');">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

                                <input type="hidden"
                                       name="action"
                                       value="delete">

                                <input type="hidden"
                                       name="class_id"
                                       value="<?php echo $class["class_id"]; ?>">

                                <button type="submit" class="delete">
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