<?php

session_start();

require_once "../config/database.php";

// 1. Make sure the student is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// 2. Make sure the user is a student
if ($_SESSION["role"] !== "student") {
    exit("Access denied.");
}

// 3. Get the lesson ID
if (!isset($_POST["lesson_id"])) {
    exit("Lesson ID is missing.");
}

$lesson_id = (int) $_POST["lesson_id"];

$student_id = $_SESSION["user_id"];


// 4. Check whether this lesson exists
$stmt = $pdo->prepare(
    "SELECT id
     FROM lessons
     WHERE id = ?"
);

$stmt->execute([$lesson_id]);

$lesson = $stmt->fetch();

if (!$lesson) {
    exit("Lesson not found.");
}


// 5. Check whether the student already completed this lesson
$stmt = $pdo->prepare(
    "SELECT id, completed
     FROM progress
     WHERE student_id = ?
     AND lesson_id = ?"
);

$stmt->execute([
    $student_id,
    $lesson_id
]);

$progress = $stmt->fetch();


// 6. If already completed, don't give XP again
if ($progress && $progress["completed"] == 1) {

    header(
        "Location: lesson.php?id=" .
        $lesson_id .
        "&message=already_completed"
    );

    exit;
}


// 7. Start a database transaction
$pdo->beginTransaction();

try {

    // 8. Save lesson completion
    if ($progress) {

        $stmt = $pdo->prepare(
            "UPDATE progress
             SET completed = 1,
                 completed_at = NOW()
             WHERE student_id = ?
             AND lesson_id = ?"
        );

        $stmt->execute([
            $student_id,
            $lesson_id
        ]);

    } else {

        $stmt = $pdo->prepare(
            "INSERT INTO progress
             (student_id, lesson_id, completed, completed_at)
             VALUES (?, ?, 1, NOW())"
        );

        $stmt->execute([
            $student_id,
            $lesson_id
        ]);
    }


    // 9. Give the student 10 XP
    $xp_earned = 10;

    $stmt = $pdo->prepare(
        "UPDATE users
         SET xp = xp + ?
         WHERE id = ?"
    );

    $stmt->execute([
        $xp_earned,
        $student_id
    ]);


    // 10. Record the XP transaction
    $stmt = $pdo->prepare(
        "INSERT INTO xp_transactions
         (student_id, xp_amount, reason)
         VALUES (?, ?, ?)"
    );

    $stmt->execute([
        $student_id,
        $xp_earned,
        "Completed a lesson"
    ]);


    // 11. Get the student's new XP
    $stmt = $pdo->prepare(
        "SELECT xp
         FROM users
         WHERE id = ?"
    );

    $stmt->execute([$student_id]);

    $user = $stmt->fetch();

    $xp = (int) $user["xp"];


    // 12. Calculate the student's level
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


    // 13. Update the student's level
    $stmt = $pdo->prepare(
        "UPDATE users
         SET level = ?
         WHERE id = ?"
    );

    $stmt->execute([
        $level,
        $student_id
    ]);
// Award First Lesson badge
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total
     FROM progress
     WHERE student_id = ?
     AND completed = 1"
);

$stmt->execute([$student_id]);

$result = $stmt->fetch();

$total_lessons = (int) $result["total"];

if ($total_lessons >= 1) {

    // Find First Lesson badge
    $stmt = $pdo->prepare(
        "SELECT id
         FROM badges
         WHERE name = 'First Lesson'"
    );

    $stmt->execute();

    $badge = $stmt->fetch();

    if ($badge) {

        // Give badge if student doesn't already have it
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO student_badges
             (student_id, badge_id)
             VALUES (?, ?)"
        );

        $stmt->execute([
            $student_id,
            $badge["id"]
        ]);
    }
}
$pdo->commit();

header(
    "Location: lesson.php?id=" .
    $lesson_id .
    "&message=completed"
);

exit;

    // 14. Commit all changes
    $pdo->commit();


    // 15. Return to the lesson
    header(
        "Location: lesson.php?id=" .
        $lesson_id .
        "&message=completed"
    );

    exit;


} catch (Exception $e) {

    // If something goes wrong, undo the changes
    $pdo->rollBack();

    exit(
        "Something went wrong: " .
        $e->getMessage()
    );
}