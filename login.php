<?php
require_once("includes/config.php");
session_start();

$errors = array();
$_SESSION['user_id'] = 0;
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = "en";
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (!isset($_POST['lang'])) {
        $email = $_POST["email"];
        $password = $_POST["password"];
    
        $sql = "SELECT userID, password FROM users WHERE email = ?";
        $stmt = $mysqli->prepare($sql);
        
        // Bind parameters and execute statement
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        $result = $stmt->get_result();

        // Check if user exists
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $userID = $row['userID'];
            $passwordHash = $row['password'];

            // Verify password
            if (password_verify($password, $passwordHash)) {
                $_SESSION['user_id'] = $userID;
                header("Location: index.php");
                exit();
            } 
            else {
                array_push($errors, "Invalid password");
            }
        } else {
            array_push($errors, "User not found");
        }
        
        // Close statement
        $stmt->close();
    }
}
?>
<?php include("includes/lang-config.php");?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate("Login"); ?> | MIROS</title>
    <link rel="stylesheet" href="css/mobile.css" />
    <link rel="stylesheet" href="css/desktop.css" media="only screen and (min-width : 790px)"/>
</head>
<body>
    <?php include_once("includes/simplified-header.php") ?>
    <h1 class="segment-header"><?php echo translate("Log In"); ?></h1>
    <div class="segment-container login-container">
        <form class="login-form" method="post">
            <div class="login-input">
                <div class="login-seperate">
                    <h3><?php echo translate("Email"); ?></h3>
                    <input type="email" name="email" required>
                    <h3><?php echo translate("Password"); ?></h3>
                    <input type="password" name="password" required>
                    <button type="submit" id="signup-button"><?php echo translate("Login"); ?></button>
                </div>
            </div>
            <p class="account-link"><?php echo translate("Don't have an account?"); ?></p>
            <a href="signup.php" class="login-change"><?php echo translate("Register here"); ?></a>
        </form>
    </div>
</body>
</html>


