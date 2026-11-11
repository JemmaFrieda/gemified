<?php

session_start();

require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE email = ?"
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {

        // Store user information in session
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["role"] = $user["role"];

        // Send user to correct dashboard
        if ($user["role"] === "student") {

            header("Location: student/dashboard.php");
            exit;

        } elseif ($user["role"] === "teacher") {

            header("Location: teacher/dashboard.php");
            exit;

        } elseif ($user["role"] === "admin") {

            header("Location: admin/dashboard.php");
            exit;
        }

    } else {

        $message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Login</title>
</head>

<body>

    <h1>Login</h1>

    <?php if (!empty($message)): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

   
<body>

    <div class="auth-page">

        <div class="auth-card">

            <h1>Login
                 <form method="POST">

        <label>Email:</label><br>
        <input type="email" name="email" required>

        <br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required>

        <br><br>

        <button type="submit">
            Login
        </button>

    </form>
            </h1>

            <!-- your login form here -->

        </div>

    </div>

</body>
    <p>
        Don't have an account?
        <a href="register.php">Register</a>
    </p>

</body>

</html>