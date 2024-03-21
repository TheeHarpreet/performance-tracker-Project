<?php
require_once("includes/config.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $firstname = $_POST['fname'];
    $surname = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $emailCheck = $mysqli->prepare("SELECT * FROM users WHERE email = ?");
    $emailCheck->bind_param('s', $email);
    $emailCheck->execute();
    $emailResult = $emailCheck->get_result();

    if (mysqli_num_rows($emailResult) == 0) {
        $query = $mysqli->prepare("INSERT INTO users (fname, lname, email, password, jobRole) VALUES (?, ?, ?, ?, 'None')");
        $query->bind_param('ssss', $firstname, $surname, $email, $password);
        $query->execute();
        $_SESSION['user_id'] = mysqli_insert_id($mysqli);
        $_SESSION['signup'] = "successful";
        
        header("Location: index.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up | MIROS</title>
    <link rel="stylesheet" href="css/mobile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/desktop.css" media="only screen and (min-width : 790px)"/>
</head>
<body>
    <?php include_once("includes/simplified-header.php") ?>
        <div class="signup-container">
            <form class="signup-form" method="post">
                <h1>Sign Up</h1>
                <h3>First name</h3>
                <input type="text" name="fname">
                <h3>Surname</h3>
                <input type="text" name="lname">
                <h3>Email</h3>
                <input type="text" name="email">
                <h3>Password</h3>
                <input type="password" name="password">
                <button type="submit" id="signup-button">Signup</button>
                <p>Have an account? <a href="login.php" class="login-change">Login</a></p>
            </form>
        </div>
    <?php include_once("includes/footer.php") ?>
</body>
</html>