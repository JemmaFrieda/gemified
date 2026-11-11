<?php

session_start();

require_once "../config/database.php";

// Make sure the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// Get all courses
$stmt = $pdo->query(
    "SELECT courses.*, categories.name AS category_name
     FROM courses
     LEFT JOIN categories
     ON courses.category_id = categories.id
     ORDER BY courses.created_at DESC"
);

$courses = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
<link rel="stylesheet" href="../assets/css/style.css">
    <title>Courses</title>

</head>

<body>

    <h1>Available Courses</h1>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION["name"]); ?>!
    </p>

    <hr>

    <?php if (count($courses) > 0): ?>

        <?php foreach ($courses as $course): ?>

            <div>

                <h2>
                    <?php echo htmlspecialchars($course["title"]); ?>
                </h2>

                <p>
                    <?php echo htmlspecialchars($course["description"]); ?>
                </p>

                <p>
                    Category:
                    <?php echo htmlspecialchars($course["category_name"]); ?>
                </p>

                <a href="view.php?id=<?php echo $course["id"]; ?>">
                    View Course
                </a>

            </div>

            <hr>

        <?php endforeach; ?>

    <?php else: ?>

        <p>No courses available.</p>

    <?php endif; ?>

    <p>
        <a href="../student/dashboard.php">
            Back to Dashboard
        </a>
    </p>

</body>

</html>