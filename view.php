<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET["id"])) {
    exit("Course not found.");
}

$course_id = (int) $_GET["id"];

// Get course
$stmt = $pdo->prepare(
    "SELECT courses.*, categories.name AS category_name
     FROM courses
     LEFT JOIN categories
     ON courses.category_id = categories.id
     WHERE courses.id = ?"
);

$stmt->execute([$course_id]);

$course = $stmt->fetch();

if (!$course) {
    exit("Course not found.");
}

// Get lessons
$stmt = $pdo->prepare(
    "SELECT *
     FROM lessons
     WHERE course_id = ?
     ORDER BY order_number ASC"
);

$stmt->execute([$course_id]);

$lessons = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
<link rel="stylesheet" href="../assets/css/style.css">
    <title>
        <?php echo htmlspecialchars($course["title"]); ?>
    </title>

</head>

<body>

    <h1>
        <?php echo htmlspecialchars($course["title"]); ?>
    </h1>

    <p>
        <?php echo htmlspecialchars($course["description"]); ?>
    </p>

    <p>
        Category:
        <?php echo htmlspecialchars($course["category_name"]); ?>
    </p>

    <hr>

    <h2>Lessons</h2>

    <?php if (count($lessons) > 0): ?>

        <?php foreach ($lessons as $lesson): ?>

            <div>

                <h3>
                    <?php echo htmlspecialchars($lesson["title"]); ?>
                </h3>

                <a href="../student/lesson.php?id=<?php echo $lesson["id"]; ?>">
                    Open Lesson
                </a>

            </div>

            <hr>

        <?php endforeach; ?>

    <?php else: ?>

        <p>No lessons available.</p>

    <?php endif; ?>

    <a href="index.php">Back to Courses</a>

</body>

</html>