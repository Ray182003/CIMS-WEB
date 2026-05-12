<?php
session_start();
require "../config/db.php";

if (isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = '" . BASE_URL . "/dashboard/';</script>";
    exit();
}


$redirect = false;
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);

    $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    if ($user && password_verify($password, $user->password)) {
        $_SESSION["user_id"] = $user->id;
        $_SESSION["username"] = $user->username;
        $_SESSION["role"] = $user->role ?? 'staff';
        $_SESSION["logged_in"] = true;
        $redirect = true;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Church Admin Login</title>
    <link href="<?php echo BASE_URL; ?>/public/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/auth/css/styles.css" rel="stylesheet">
</head>

<body>

    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-church"></i>
            <h4>Parish Admin Login</h4>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <div class="footer-note">
            For authorized parish staff only
        </div>
    </div>


    <div class="loader-overlay" id="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text text-center">
            <i class="fas fa-church"></i>
            <span>Loading dashboard...</span>
        </div>
    </div>

    <script>
        const loginForm = document.querySelector("form");
        const loader = document.getElementById("loader");

        loginForm.addEventListener("submit", function() {
            loader.style.display = "flex";
        });

        <?php if ($redirect): ?>

            document.querySelector(".login-container").style.display = "none";
            loader.style.display = "flex";
            setTimeout(() => {
                window.location.href = "<?php echo BASE_URL; ?>/dashboard/";
            }, 1000);
        <?php endif; ?>
    </script>

</body>

</html>