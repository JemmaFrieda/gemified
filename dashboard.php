<?php

session_start();

require_once "../config/database.php";

// Make sure user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// Make sure user is a student
if ($_SESSION["role"] !== "student") {
    exit("Access denied.");
}

// Get current student's information
$stmt = $pdo->prepare(
    "SELECT name, email, xp, level, streak
     FROM users
     WHERE id = ?"
);

$stmt->execute([$_SESSION["user_id"]]);

$user = $stmt->fetch();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
<link rel="stylesheet" href="../assets/css/style.css">
    <title>Student Dashboard</title>

</head>

<body>

    <h1>Welcome, <?php echo htmlspecialchars($user["name"]); ?>!</h1>

    <h2>Student Dashboard</h2>

    <hr>

    <h3>🎮 Your Gamification</h3>

    <p>
        <strong>XP:</strong>
        <?php echo $user["xp"]; ?>
    </p>

    <p>
        <strong>Level:</strong>
        <?php echo $user["level"]; ?>
    </p>

    <p>
        <strong>Streak:</strong>
        <?php echo $user["streak"]; ?> days
    </p>

    <hr>

    <p><strong>Email:</strong> <?php echo htmlspecialchars($user["email"]); ?></p>

<hr>

<h3>📚 Learning</h3>

<p>
    <a href="../courses/index.php">📖 View Courses & Learn Lessons</a>
</p>

<hr>

<p>
    <a href="../logout.php">🚪 Logout</a>
</p>

</body>

</html>