<?php
require_once("includes/config.php");
session_start();

$errors = array();
$_SESSION['user_id'] = 0;
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = "en";
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['lang'])) {
        $firstname = $_POST['fname'];
        $surname = $_POST['lname'];
        $email = $_POST['email'];
        $password = $_POST['password1'];
        $passwordConfirm = $_POST['password2'];
    
        // Hashing algorithm
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
        // Password length validation
        if (strlen($password) < 8) {
            array_push($errors, "Password must be at least 8 characters long.");  // NeedsTranslation
        }
    
        // Password confirmation check
        if ($password !== $passwordConfirm) {
            array_push($errors, "Passwords do not match.");  // NeedsTranslation
        }
    
        $emailCheck = $mysqli->prepare("SELECT * FROM users WHERE email = ?");
        $emailCheck->bind_param('s', $email);
        $emailCheck->execute();
        $emailResult = $emailCheck->get_result();
        if (mysqli_num_rows($emailResult) > 0) {
            array_push($errors, "Email is already in use");  // NeedsTranslation
        }
    
        if (count($errors) == 0) {
            $sql = "INSERT INTO users (fname, lname, email, password, jobRole) VALUES (?, ?, ?, ?, 'None')";
            $stmt = $mysqli->prepare($sql);
            
            if (!$stmt) {
                die("SQL statement preparation failed: " . $mysqli->error);
            }
    
            $stmt->bind_param("ssss", $firstname, $surname, $email, $passwordHash);
    
            if ($stmt->execute()) {
                $_SESSION['user_id'] = mysqli_insert_id($mysqli);
                header("Location: index.php");
                exit();
            } else {
                die("Error executing statement: " . $stmt->error);
            }
        }
    }
}
?>
<?php include("includes/lang-config.php");?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate("Signup"); ?> | MIROS</title>
    <link rel="stylesheet" href="css/mobile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/desktop.css" media="only screen and (min-width : 790px)"/>
</head>
<body>
    <?php include_once("includes/simplified-header.php") ?>
    <h1 class="segment-header"><?php echo translate("Signup"); ?></h1>
    <div class="segment-container signup-container">
        <form class="signup-form" method="post">
            
            <div class="signup-div">
                <div class="signup-seperate" id= "left">
                    <h3><?php echo translate("First name"); ?></h3>
                    <input type="text" name="fname" required>
                    <h3><?php echo translate("Surname"); ?></h3>
                    <input type="text" name="lname" required>
                    <h3><?php echo translate("Email"); ?></h3>
                    <input type="text" name="email" required>
                </div>
                <div class="signup-seperate" id="right">
                    <h3><?php echo translate("Password"); ?></h3>
                    <input type="password" name="password1" required>
                    <h3><?php echo translate("Confirm Password"); ?></h3>
                    <input type="password" name="password2" required>
                    <button type="submit" id="signup-button"><?php echo translate("Signup"); ?></button>
                </div>
            </div>
            <?php
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    echo "<div class='error-message'>$error</div>";
                }
            }
            ?>
            <p class="account-link"><?php echo translate("Have an account?"); ?></p>
            <a href="login.php" class="login-change"><?php echo translate("Login"); ?></a>
        </form>
    </div>
</body>
</html>

