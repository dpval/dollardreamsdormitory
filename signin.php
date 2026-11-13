<?php
// Fix session handling for hosting environment
session_start();

include "db.php";

// Redirect to dashboard if already logged in
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if (!empty($user) && !empty($pass)) {

        // Check against the 'admin' database table shown in phpMyAdmin
        $stmt = $conn->prepare("SELECT id, username, password FROM admin WHERE username = ? LIMIT 1");

        if ($stmt) {
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                
                // Compare entered password with database password
                if ($pass === $row['password']) {
                    
                    $_SESSION['admin'] = true;
                    $_SESSION['admin_id'] = $row['id'];
                    
                    header("Location: dashboard.php");
                    exit();

                } else {
                    $error = "Invalid username or password!";
                }

            } else {
                $error = "Invalid username or password!";
            }

            $stmt->close();
        } else {
            // Fallback check if database connection fails
            if ($user === "admin" && $pass === "admin123") {
                $_SESSION['admin'] = true;
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password!";
            }
        }

    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - Dollar Dreams Dormitory</title>

<style>
*{ margin:0; padding:0; box-sizing:border-box; }
body{
    font-family:Arial, sans-serif;
    background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('image/hero.jpg');
    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;
    background-attachment:fixed;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.login-container{
    width:100%;
    max-width:420px;
    background:rgba(255,255,255,0.96);
    padding:35px;
    border-radius:18px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    text-align:center;
}

.login-container h2{ color:#183823; margin-bottom:25px; font-size:30px; }
form{ width:100%; }

input{
    width:100%;
    padding:14px 16px;
    margin-bottom:15px;
    border:1px solid #d9d9d9;
    border-radius:10px;
    font-size:16px;
    outline:none;
}

input:focus{ border-color:#183823; }

.login-btn{
    width:100%;
    padding:14px;
    background:#183823;
    color:white;
    border:none;
    border-radius:10px;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

.login-btn:hover{ background:#245336; }

.back-btn{ display:inline-block; margin-top:18px; }

.back-btn img{
    width:80px;
    height:auto;
    border-radius:50%;
    cursor:pointer;
    transition:0.3s;
}

.back-btn img:hover{ transform:scale(1.08); }

.error{ margin-top:15px; color:red; font-weight:bold; }

@media (max-width:768px){
    .login-container{ max-width:500px; padding:35px 25px; }
    .login-container h2{ font-size:28px; }
    input, .login-btn{ padding:15px; font-size:16px; }
    .back-btn img{ width:90px; }
}

@media (max-width:480px){
    body{ padding:15px; }
    .login-container{ padding:30px 20px; border-radius:16px; }
    .login-container h2{ font-size:26px; margin-bottom:20px; }
    input{ margin-bottom:12px; }
    .back-btn img{ width:85px; }
}
</style>
</head>

<body>

<div class="login-container">

    <h2>Admin Login</h2>

    <form method="POST" action="signin.php">
        <input type="text" name="username" placeholder="Username" required autocomplete="off">
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="login-btn">Login</button>
    </form>

    <a href="index.php" class="back-btn">
        <img src="image/dddbackbutton.jpg" alt="Back">
    </a>

    <?php if (!empty($error)) { ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php } ?>

</div>

</body>
</html>