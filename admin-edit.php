<?php
require_once("includes/config.php");
require_once("includes/redirect-login.php");
ob_clean();

$userID = $_GET['userID'];
$query = $mysqli->query("SELECT * FROM `users` WHERE userID = $userID");
$user = $query->fetch_object();

// reset password query
if (isset($_GET['reset']) && !isset($_POST['lang'])) {
    $newPassword = "katalaluan123";
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("UPDATE users SET `password` = ? WHERE userID = ?");
    $stmt->bind_param('ss', $passwordHash, $_GET['userID'] );
    $stmt->execute();
}

// update query
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['lang'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    if ($user->jobRole != "Admin") { // admins account type is locked, will only update if not an admin.
        $jobRole = $_POST['jobRole'];
        $stmt = $mysqli->prepare("UPDATE users SET fname = ?, lname = ?, email = ?, jobRole = ? WHERE userID = ?");
        $stmt->bind_param("sssss", $fname, $lname, $email, $jobRole, $userID);
    } else {
        $stmt = $mysqli->prepare("UPDATE users SET fname = ?, lname = ?, email = ? WHERE userID = ?");
        $stmt->bind_param("ssss", $fname, $lname, $email, $userID);
    }
    $stmt->execute();

    $mysqli->query("DELETE FROM researcherssupervisor WHERE researcherID = $userID");

    if (isset($_POST['supervisors'])) {
        $i = 1;
        $queryText = "INSERT INTO researcherssupervisor VALUES ";
        foreach ($_POST['supervisors'] as $supervisor) {
            $queryText .= "($userID, $supervisor)";
            $queryText .= ($i == count($_POST['supervisors']) ? ";" : ",");
            $i++;
        }
        $supervisorInsert = $mysqli->query($queryText);
    }

    header("Location: admin-index.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User Details | MIROS</title>  <!-- // NeedsTranslation -->
    <link rel="stylesheet" href="css/mobile.css" />
    <link rel="stylesheet" href="css/desktop.css" media="only screen and (min-width : 790px)"/>
</head>
<body>
    <?php include_once("includes/simplified-header.php") ?>
        <div class="login-container">
            <form method="post">

                <label><?php echo translate("FirstName"); ?>:</label>
                <input type="text" name="fname" value="<?php echo $user->fname; ?>" required>
                
                <label><?php echo translate("LastName"); ?>:</label>
                <input type="text" name="lname" value="<?php echo $user->lname; ?>" required>
                
                <label><?php echo translate("Email"); ?>:</label>
                <input type="email" name="email" value="<?php echo $user->email; ?>" required>
                
                <?php if ($user->jobRole != "Admin") { // admins account type is locked, will only update if not an admin.
                echo "<label>" . translate("Job Role") . ":</label>
                <select id='select' name='jobRole'>
                    <option value='None'" . ($user->jobRole == 'None' ? ' selected' : '') . ">" . translate("None") . "</option>
                    <option value='Researcher'" . ($user->jobRole == 'Researcher' ? ' selected' : '') . ">" . translate("Researcher") . "</option>
                    <option value='Supervisor'" . ($user->jobRole == 'Supervisor' ? ' selected' : '') . ">" . translate("Supervisor") . "</option>
                    <option value='Manager'" . ($user->jobRole == 'Manager' ? ' selected' : '') . ">" . translate("Manager") . "</option>
                </select>";
                } ?>
                <button type="submit" class="submit-button"><?php echo translate("Update"); ?></button>
                <p><a href="admin-edit.php?userID=<?php echo $userID; ?>&reset=1" class="reset-link"><?php echo translate("Reset Password"); ?></a></p>
                <p><?php echo translate("Passwords are reset to \"katalaluan123\""); ?></p>
                <?php 
                if ($user->jobRole == "Researcher") {
                    echo "
                    <table>
                    <tr class='assign-supervisor-table'>
                        <th>First name</th>
                        <th>Last name</th>
                        <th>Email</th>
                        <th>Supervisor</th>
                    </tr>
                    ";
                    $possibleSupervisorsQuery = $mysqli->query("SELECT * FROM users WHERE userID != $user->userID AND jobRole = 'Supervisor'");
                    while ($supervisor = $possibleSupervisorsQuery->fetch_object()) {
                        echo "
                        <tr>
                        <td>$supervisor->fname</td>
                        <td>$supervisor->lname</td>
                        <td>$supervisor->email</td>
                        <td><input type='checkbox' name='supervisors[]' value='" . $supervisor->userID . "' ";
                        $selectedCheck = $mysqli->query("SELECT * FROM researcherssupervisor WHERE researcherID = $userID AND supervisorID = $supervisor->userID");
                        if (mysqli_num_rows($selectedCheck) > 0) {
                            echo "checked";
                        }
                        echo "></td>
                        </tr>
                        </table>
                        ";
                    }
                }
                ?>
            </form>
        </div>
    <?php include_once("includes/footer.php") ?>
</body>
</html>

<?php include("includes/lang-config.php");
function translate($key) {
    $translations = array(
        /*
        "en" => array(
            "FirstName" => "FirstName",
            "LastName" => "LastName",
            "Email" => "Email",
            "Job Role" => "Job Role",
            "Update" => "Update",
            "Reset Password" => "Reset Password",
            "Passwords are reset to \"Password123\"" => "Passwords are reset to \"Password123\"",
            "None" => "None",
            "Researcher" => "Researcher",
            "Supervisor" => "Supervisor",
            "Manager" => "Manager",

        ),
        */
        "bm" => array(
            "FirstName" => "Nama Pertama",
            "LastName" => "Nama Akhir",
            "Email" => "Emel",
            "Job Role" => "Peranan Pekerjaan",
            "Update" => "Kemaskini",
            "Reset Password" => "Tetapkan Semula Kata Laluan",
            "Passwords are reset to \"Password123\"" => "Kata Laluan telah ditetapkan semula kepada \"Password123\"",
            "None" => "Tiada",
            "Researcher" => "Penyelidik",
            "Supervisor" => "Penyelia",
            "Manager" => "Pengurus",
        )
    );

    $language = $_SESSION['language'];
    return isset($translations[$language][$key]) ? $translations[$language][$key] : $key;
} ?>
