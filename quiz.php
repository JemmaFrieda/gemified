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
if (!isset($_GET["id"])) {
    exit("Quiz ID is missing.");
}

$quiz_id = (int) $_GET["id"];

// Get quiz information
$stmt = $pdo->prepare(
    "SELECT id, title, description
     FROM quizzes
     WHERE id = ?"
);

$stmt->execute([$quiz_id]);

$quiz = $stmt->fetch();

if (!$quiz) {
    exit("Quiz not found.");
}

// Get quiz questions
$stmt = $pdo->prepare(
    "SELECT id,
            question,
            option_a,
            option_b,
            option_c,
            option_d,
            points
     FROM questions
     WHERE quiz_id = ?
     ORDER BY id ASC"
);

$stmt->execute([$quiz_id]);

$questions = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/css/style.css">
    <title>
        <?php echo htmlspecialchars($quiz["title"]); ?>
    </title>

</head>

<body>

    <h1>
        <?php echo htmlspecialchars($quiz["title"]); ?>
    </h1>

    <p>
        <?php echo htmlspecialchars($quiz["description"]); ?>
    </p>

    <hr>

    <form method="POST" action="submit_quiz.php">

        <input
            type="hidden"
            name="quiz_id"
            value="<?php echo $quiz_id; ?>"
        >

        <?php foreach ($questions as $index => $question): ?>

            <div>

                <h3>
                    Question <?php echo $index + 1; ?>
                </h3>

                <p>
                    <strong>
                        <?php echo htmlspecialchars($question["question"]); ?>
                    </strong>
                </p>

                <p>
                    <label>
                        <input
                            type="radio"
                            name="answers[<?php echo $question["id"]; ?>]"
                            value="A"
                        >
                        <?php echo htmlspecialchars($question["option_a"]); ?>
                    </label>
                </p>

                <p>
                    <label>
                        <input
                            type="radio"
                            name="answers[<?php echo $question["id"]; ?>]"
                            value="B"
                        >
                        <?php echo htmlspecialchars($question["option_b"]); ?>
                    </label>
                </p>

                <p>
                    <label>
                        <input
                            type="radio"
                            name="answers[<?php echo $question["id"]; ?>]"
                            value="C"
                        >
                        <?php echo htmlspecialchars($question["option_c"]); ?>
                    </label>
                </p>

                <p>
                    <label>
                        <input
                            type="radio"
                            name="answers[<?php echo $question["id"]; ?>]"
                            value="D"
                        >
                        <?php echo htmlspecialchars($question["option_d"]); ?>
                    </label>
                </p>

                <!-- Clear choice button -->
                <button
                    type="button"
                    onclick="clearChoice(<?php echo $question['id']; ?>)"
                >
                    Clear Choice
                </button>

                <hr>

            </div>

        <?php endforeach; ?>

        <button type="submit">
            📝 Submit Quiz
        </button>

    </form>

    <hr>

    <p>
        <a href="dashboard.php">
            ← Back to Dashboard
        </a>
    </p>


    <script>

        function clearChoice(questionId) {

            const choices = document.querySelectorAll(
                'input[name="answers[' + questionId + ']"]'
            );

            choices.forEach(function(choice) {
                choice.checked = false;
            });

        }

    </script>

</body>

</html>