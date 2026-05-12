<?php
session_start();
require "../config/db.php";
require_once __DIR__ . "/../security_helper.php";


global $security;
$security = new AaaSecurity($conn);

function columnExists(PDO $connection, string $table, string $column): bool
{
    try {
        $stmt = $connection->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

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

    $statusColumnExists = columnExists($conn, 'users', 'status');

    if ($user && $statusColumnExists && isset($user->status) && $user->status !== 'active') {
        logActivity('LOGIN_BLOCKED', 'auth', $user->id, null, [
            'username' => $user->username,
            'reason' => 'inactive_status',
            'attempt_time' => date('Y-m-d H:i:s')
        ]);
        $error = "Your account is inactive. Please contact the administrator.";
    } elseif ($user && password_verify($password, $user->password)) {
        $_SESSION["user_id"] = $user->id;
        $_SESSION["username"] = $user->username;
        $_SESSION["role"] = $user->role ?? 'staff';
        $_SESSION["logged_in"] = true;
        $_SESSION["login_time"] = time(); 

      
        logActivity('LOGIN', 'auth', $user->id, null, [
            'username' => $user->username,
            'login_time' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        $redirect = true;
    } else {
       
        logActivity('LOGIN_FAILED', 'auth', null, null, [
            'username' => $username,
            'attempt_time' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        $error = "Invalid username or password.";
    }
}
?>

<head>
    <meta charset="UTF-8">
    <title>Church Admin Login</title>
    <link href="<?php echo BASE_URL; ?>/public/css/log/bootstrap.min.css" rel="stylesheet">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <!-- <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script> Replace with local or real kit -->
    <link href="<?php echo BASE_URL; ?>/auth/css/style.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/public/css/bootstrap.min.css" rel="stylesheet">



</head>

<body>

    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-church fa-2x" style="color: #007bff;"></i>
            <h4>Parish Admin Login</h4>
        </div>

        <form method="POST" class="form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">


            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?php echo $error; ?></div>
            <?php endif; ?>

            <input class="input" type="text" name="username" placeholder="Username" required>
            <input class="input" type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn">Submit</button>
        </form>


        <div class="footer-note">For authorized parish staff only</div>
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