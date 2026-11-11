<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION["role"] !== "student") {
    exit("Access denied.");
}

if (!isset($_GET["id"])) {
    exit("Lesson not found.");
}

$lesson_id = (int) $_GET["id"];

$stmt = $pdo->prepare(
    "SELECT lessons.*, courses.title AS course_title
     FROM lessons
     JOIN courses
     ON lessons.course_id = courses.id
     WHERE lessons.id = ?"
);

$stmt->execute([$lesson_id]);

$lesson = $stmt->fetch();

if (!$lesson) {
    exit("Lesson not found.");
}

$message = "";

if (isset($_GET["message"])) {

    if ($_GET["message"] === "completed") {

        $message = "🎉 Congratulations, " .
                   htmlspecialchars($_SESSION["name"]) .
                   "! You completed the lesson and earned 10 XP! ⭐";

    }

    if ($_GET["message"] === "already_completed") {

        $message = "ℹ️ You have already completed this lesson.";

    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
<link rel="stylesheet" href="../assets/css/style.css">
    <title>
        <?php echo htmlspecialchars($lesson["title"]); ?>
    </title>

</head>

<body>

    <h1>
        <?php echo htmlspecialchars($lesson["title"]); ?>
    </h1>

    <p>
        Course:
        <?php echo htmlspecialchars($lesson["course_title"]); ?>
    </p>

    <hr>

    <?php if (!empty($message)): ?>

    <div style="
        padding: 15px;
        margin: 20px 0;
        border: 2px solid green;
        background-color: #e8f5e9;
        border-radius: 10px;
        text-align: center;
    ">

        <h2>🎉 Congratulations!</h2>

        <p>
            <?php echo $message; ?>
        </p>

    </div>

<?php endif; ?>

    <div>

        <?php
        echo nl2br(
            htmlspecialchars($lesson["content"])
        );
        ?>

    </div>

    <hr>

    <form method="POST" action="complete_lesson.php">

        <input
            type="hidden"
            name="lesson_id"
            value="<?php echo (int)$lesson["id"]; ?>"
        >

        <button type="submit">
            ✅ Complete Lesson (+10 XP)
        </button>

    </form>
<?php

// Find quiz belonging to this lesson
$stmt = $pdo->prepare(
    "SELECT id, title
     FROM quizzes
     WHERE lesson_id = ?
     LIMIT 1"
);

$stmt->execute([$lesson_id]);

$quiz = $stmt->fetch();

?>

<?php if ($quiz): ?>

    <hr>

    <h3>📝 Quiz</h3>

    <p>
        Test your understanding of this lesson.
    </p>

    <p>
        <strong>
            <?php echo htmlspecialchars($quiz["title"]); ?>
        </strong>
    </p>

    <a href="quiz.php?id=<?php echo $quiz["id"]; ?>">
        🎯 Take Quiz
    </a>

<?php endif; ?>
    <br>

    <a href="../student/dashboard.php">
        🎮 Back to Dashboard
    </a>

    <br><br>

    <a href="../courses/index.php">
        📚 Back to Courses
    </a>

</body>

</html>