<?php
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST["full_name"]);
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $role = $_POST["role"];

    // Only students and parents can self-register
    if (!in_array($role, ["student", "parent"], true)) {
        $message = "Invalid account type.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } else {

        // Check whether username already exists
        $check = $conn->prepare(
            "SELECT user_id FROM users WHERE username = ?"
        );
        $check->execute([$username]);

        if ($check->fetch()) {
            $message = "Username already exists!";
        } else {

            // Securely hash password
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $sql = "INSERT INTO users
            (username, password_hash, role, full_name, status)
            VALUES (?, ?, ?, ?, 'active')";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $username,
                $hashedPassword,
                $role,
                $fullname
            ]);

            header("Location: login.php?registered=1");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Account</title>

    <style>
        body {
            background: #eeeaf5;
            font-family: Arial;
        }

        .register-box {
            width: 350px;
            margin: 60px auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 15px #ccc;
        }

        h2 {
            text-align: center;
            color: #54278f;
        }

        input, select {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #54278f;
            color: white;
            border: none;
            cursor: pointer;
        }

        .message {
            color: red;
            text-align: center;
        }

        a {
            color: #54278f;
        }
    </style>
</head>

<body>

<div class="register-box">

    <h2>Create Your Account</h2>

    <p class="message">
        <?php echo htmlspecialchars($message); ?>
    </p>

    <form method="POST">

        <input type="text"
               name="full_name"
               placeholder="Full Name"
               required>

        <input type="text"
               name="username"
               placeholder="Create Username"
               required>

        <input type="password"
               name="password"
               placeholder="Create Password (8+ characters)"
               required minlength="8">

        <select name="role" required>
            <option value="">Select Account Type</option>
            <option value="student">Student</option>
            <option value="parent">Parent</option>
        </select>

        <button type="submit">REGISTER</button>

    </form>

    <p style="text-align:center">
        Already registered?
        <a href="login.php">Login Here</a>
    </p>

</div>

</body>
</html>