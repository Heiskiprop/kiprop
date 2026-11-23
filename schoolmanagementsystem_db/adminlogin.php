<?php
require_once "db.php";

$adminName = "Qalicha";
$adminUsername = "Qalicha";
$adminPassword = "9090";

$check = $conn->prepare(
    "SELECT user_id FROM users WHERE username = ?"
);
$check->execute([$adminUsername]);

if ($check->fetch()) {
    die("Admin account already exists.");
}

$hashedPassword = password_hash(
    $adminPassword,
    PASSWORD_DEFAULT
);

$sql = "INSERT INTO users
(username, password_hash, role, full_name, status)
VALUES (?, ?, 'admin', ?, 'active')";

$stmt = $conn->prepare($sql);

$stmt->execute([
    $adminUsername,
    $hashedPassword,
    $adminName
]);

echo "Administrator account created successfully!";
?>