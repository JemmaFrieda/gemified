
<?php

session_start();

require_once "../config/database.php";

// Make sure the student is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// Make sure the user is a student
if ($_SESSION["role"] !== "student") {
    exit("Access denied.");
}

// Check quiz ID
if (!isset($_POST["quiz_id"])) {
    exit("Quiz ID is missing.");
}

$quiz_id = (int) $_POST["quiz_id"];

$student_id = $_SESSION["user_id"];

// Get submitted answers
$answers = $_POST["answers"] ?? [];


// --------------------------------------------------
// Get quiz information
// --------------------------------------------------

$stmt = $pdo->prepare(
    "SELECT id, title
     FROM quizzes
     WHERE id = ?"
);

$stmt->execute([$quiz_id]);

$quiz = $stmt->fetch();

if (!$quiz) {
    exit("Quiz not found.");
}


// --------------------------------------------------
// Get all questions
// --------------------------------------------------

$stmt = $pdo->prepare(
    "SELECT id, correct_answer, points
     FROM questions
     WHERE quiz_id = ?
     ORDER BY id ASC"
);

$stmt->execute([$quiz_id]);

$questions = $stmt->fetchAll();

if (count($questions) === 0) {
    exit("This quiz has no questions.");
}


// --------------------------------------------------
// Check whether the student already completed quiz
// --------------------------------------------------

$stmt = $pdo->prepare(
    "SELECT id
     FROM quiz_results
     WHERE student_id = ?
     AND quiz_id = ?
     LIMIT 1"
);

$stmt->execute([
    $student_id,
    $quiz_id
]);

$already_completed = $stmt->fetch();


// --------------------------------------------------
// Calculate score
// --------------------------------------------------

$correct_answers = 0;
$total_questions = count($questions);

foreach ($questions as $question) {

    $question_id = $question["id"];

    // If student did not answer the question
    // it will be treated as incorrect.
    $student_answer = $answers[$question_id] ?? "";

    if (
        strtoupper($student_answer)
        === strtoupper($question["correct_answer"])
    ) {

        $correct_answers++;
    }
}


// Calculate percentage
$percentage = ($correct_answers / $total_questions) * 100;


// --------------------------------------------------
// XP
// --------------------------------------------------

$xp_earned = 0;

if (!$already_completed) {

    // Completing a quiz gives 20 XP
    $xp_earned = 20;
}


// --------------------------------------------------
// Start database transaction
// --------------------------------------------------

$pdo->beginTransaction();

try {

    // --------------------------------------------------
    // Save quiz result
    // --------------------------------------------------

    $stmt = $pdo->prepare(
        "INSERT INTO quiz_results
        (
            student_id,
            quiz_id,
            score,
            total_questions,
            xp_earned
        )
        VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $student_id,
        $quiz_id,
        $correct_answers,
        $total_questions,
        $xp_earned
    ]);


    // --------------------------------------------------
    // Give XP only if this is the first attempt
    // --------------------------------------------------

    if ($xp_earned > 0) {

        $stmt = $pdo->prepare(
            "UPDATE users
             SET xp = xp + ?
             WHERE id = ?"
        );

        $stmt->execute([
            $xp_earned,
            $student_id
        ]);


        // Save XP transaction
        $stmt = $pdo->prepare(
            "INSERT INTO xp_transactions
            (
                student_id,
                xp_amount,
                reason
            )
            VALUES (?, ?, ?)"
        );

        $stmt->execute([
            $student_id,
            $xp_earned,
            "Completed a quiz"
        ]);
    }


    // --------------------------------------------------
    // Get student's new XP
    // --------------------------------------------------

    $stmt = $pdo->prepare(
        "SELECT xp
         FROM users
         WHERE id = ?"
    );

    $stmt->execute([$student_id]);

    $user = $stmt->fetch();

    $xp = (int) $user["xp"];


    // --------------------------------------------------
    // Calculate level
    // --------------------------------------------------

    if ($xp >= 1000) {

        $level = 5;

    } elseif ($xp >= 500) {

        $level = 4;

    } elseif ($xp >= 250) {

        $level = 3;

    } elseif ($xp >= 100) {

        $level = 2;

    } else {

        $level = 1;
    }


    // Update level
    $stmt = $pdo->prepare(
        "UPDATE users
         SET level = ?
         WHERE id = ?"
    );

    $stmt->execute([
        $level,
        $student_id
    ]);


    // --------------------------------------------------
    // PERFECT SCORE BADGE
    // --------------------------------------------------

    if ($percentage == 100) {

        $stmt = $pdo->prepare(
            "SELECT id
             FROM badges
             WHERE name = 'Perfect Score'"
        );

        $stmt->execute();

        $badge = $stmt->fetch();

        if ($badge) {

            $stmt = $pdo->prepare(
                "INSERT IGNORE INTO student_badges
                (
                    student_id,
                    badge_id
                )
                VALUES (?, ?)"
            );

            $stmt->execute([
                $student_id,
                $badge["id"]
            ]);
        }
    }


    // --------------------------------------------------
    // QUIZ MASTER BADGE
    // --------------------------------------------------

    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT quiz_id) AS total
         FROM quiz_results
         WHERE student_id = ?"
    );

    $stmt->execute([$student_id]);

    $result = $stmt->fetch();

    $completed_quizzes = (int) $result["total"];


    if ($completed_quizzes >= 5) {

        $stmt = $pdo->prepare(
            "SELECT id
             FROM badges
             WHERE name = 'Quiz Master'"
        );

        $stmt->execute();

        $badge = $stmt->fetch();

        if ($badge) {

            $stmt = $pdo->prepare(
                "INSERT IGNORE INTO student_badges
                (
                    student_id,
                    badge_id
                )
                VALUES (?, ?)"
            );

            $stmt->execute([
                $student_id,
                $badge["id"]
            ]);
        }
    }


    // --------------------------------------------------
    // Finish transaction
    // --------------------------------------------------

    $pdo->commit();

} catch (Exception $e) {

    $pdo->rollBack();

    exit(
        "Something went wrong: " .
        $e->getMessage()
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
<link rel="stylesheet" href="../assets/css/style.css">
    <title>Quiz Result</title>

</head>

<body>

    <h1>🎉 Quiz Result</h1>

    <hr>

    <h2>
        <?php echo htmlspecialchars($quiz["title"]); ?>
    </h2>

    <p>
        <strong>
            Correct Answers:
        </strong>

        <?php echo $correct_answers; ?>
        /
        <?php echo $total_questions; ?>
    </p>

    <p>
        <strong>
            Score:
        </strong>

        <?php echo number_format($percentage, 0); ?>%
    </p>

    <?php if ($xp_earned > 0): ?>

        <p>
            🎮 <strong>XP Earned:</strong>
            +<?php echo $xp_earned; ?> XP
        </p>

    <?php else: ?>

        <p>
            ℹ️ You already completed this quiz,
            so no additional XP was awarded.
        </p>

    <?php endif; ?>


    <?php if ($percentage == 100): ?>

        <p>
            💯 <strong>Perfect Score!</strong>
        </p>
    <?php endif; ?>

<?php if ($percentage >= 50): ?>

    <h2>🎉 Congratulations!</h2>

    <p>
        Congratulations,
        <?php echo htmlspecialchars($_SESSION["name"]); ?>!
    </p>

    <p>
        You passed the quiz successfully.
        Keep learning and continue improving! 🌟
    </p>

<?php else: ?>

    <h2>📚 Keep Learning!</h2>

    <p>
        Don't give up!
        Review the lesson and try the quiz again.
    </p>

<?php endif; ?>
    <hr>

    <p>
        <a href="dashboard.php">
            🏠 Back to Dashboard
        </a>
    </p>

    <p>
        <a href="../courses/index.php">
            📚 View Courses
        </a>
    </p>

</body>

</html>
