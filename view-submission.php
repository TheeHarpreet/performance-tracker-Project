<?php
require_once("includes/config.php");
require_once("includes/redirect-login.php");
ob_clean();

$submissionID = $_SESSION['viewSubmission'];

$userID = $_SESSION['user_id'];
$userQuery = $mysqli->query("SELECT * FROM users WHERE userID = $userID");
$user = $userQuery->fetch_object();

// Get submission, coauthors, and the author.
$submissionQuery = $mysqli->query("SELECT * FROM submission WHERE submissionID = $submissionID");
$submission = $submissionQuery->fetch_object();
$coauthorsQuery = $mysqli->query("SELECT * FROM submissioncoauthor WHERE submissionID = $submissionID");
$authorQuery = $mysqli->query("SELECT * FROM users WHERE userID = $submission->author");
$author = $authorQuery->fetch_object();
$rejectedQuery = $mysqli->query("SELECT * FROM submissionreturn WHERE submissionID = $submissionID ORDER BY returnDate DESC");

if (isset($_REQUEST['resubmit'])) {
    $_SESSION['resubmit'] = $submissionID;
    $_SESSION['newSubmission'] = $submission->section;
    header("Location: new-submission.php");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['lang'])) {
    if (isset($_POST['approve'])) {
        $approveQuery = $mysqli->prepare("UPDATE submission SET submitted = 1 WHERE submissionID = ?");
        $approveQuery->bind_param("s", $submissionID);
        $approveQuery->execute();
    } else if (isset($_POST['return'])) {
        $returnQuery = $mysqli->prepare("INSERT INTO submissionreturn (submissionID, returner, comments) VALUES (?, ?, ?)");
        $returnQuery->bind_param("sss", $submissionID, $userID, $_POST['return-comments']);
        $returnQuery->execute();

        $updateStatusQuery = $mysqli->query("UPDATE submission SET submitted = 0 AND approved = 0");
    } else if (isset($_POST['manager-approve'])) {
        $assignedPoints = 1;
        if ($submission->sectionID != 1 && $submission->sectionID != 4) {
            $assignedPoints = $_POST['type-select'];
        }
        $assignQuery = $mysqli->prepare("UPDATE submission SET approved = ? WHERE submissionID = ?");
        $assignQuery->bind_param("ss", $assignedPoints, $submissionID);
        $assignQuery->execute();

        $minRange = 0;
        $maxRange = 0;
        $allUsersQuery = $mysqli->query("SELECT * FROM users WHERE jobRole = 'Researcher' OR jobRole = 'Supervisor'");

        while ($user = $allUsersQuery->fetch_object()) {
            // Gets the points where author
            $pointsQuery = $mysqli->query("SELECT SUM(`approved`) AS amount FROM `submission` WHERE `author` = $user->userID AND sectionID = $submission->sectionID");
            
            // Gets the points where coauthor, and the amount
            $coauthorPointsQuery = $mysqli->query("SELECT SUM(`approved`) AS amount, COUNT('approved') AS count FROM submission, submissioncoauthor WHERE submission.submissionID = submissioncoauthor.submissionID AND submissioncoauthor.coauthor = $user->userID AND sectionID = $submission->sectionID AND submission.approved > 0");
            $coauthorPoints = $coauthorPointsQuery->fetch_object();
            
            // Coauthors get 1 less point than coauthor. So calculation is points where author + points where coauthor - amount of coauthor submissions
            $currentAmount = $pointsQuery->fetch_object()->amount + $coauthorPoints->amount - $coauthorPoints->count;

            if ($maxRange < $currentAmount) {
                $maxRange = $currentAmount;
            }
            if (($minRange == 0 && $currentAmount > 0) || ($minRange > $currentAmount && $currentAmount != 0)) {
                $minRange = $currentAmount;
            }
        }
        $mysqli->query("UPDATE sections SET minRange = $minRange, maxRange = $maxRange WHERE sectionID = $submission->sectionID");
    }
    header("Location: view-submission.php");
}

?>
<?php include("includes/lang-config.php");?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate("View Submission"); ?></title>
    <link rel="stylesheet" href="css/mobile.css" />
    <link rel="stylesheet" href="css/desktop.css" media="only screen and (min-width : 790px)"/>
</head>
<body>
    <?php include_once("includes/header.php") ?>
            <?php
                // status code
                if ($submission->approved > 0) {
                    $status = translate("Approved");
                } else if ($submission->submitted == 1 && $submission->approved == 0) {
                    $status = translate("Needing Manager approval");
                } else if ($submission->submitted == 0 && mysqli_num_rows($rejectedQuery) > 0) {
                    $status = translate("Rejected");
                    $recent = $mysqli->query("SELECT * FROM submission, submissionreturn WHERE submission.submissionID = submissionreturn.submissionID AND submissionreturn.returnDate > submission.dateSubmitted AND submission.submissionID = $submissionID");
                    if (mysqli_num_rows($recent) == 0) {
                        $status = translate("Needing Supervisor approval");
                    }
                } else {
                    $status = translate("Needing Supervisor approval");
                }
                // datetime code
                $timeToTranslate = $submission->dateSubmitted;
                include("includes/format-date.php");
                echo "
                <h1 class='segment-header'>$submission->title</h1>
                <div class='segment-container'>
                <div class='view-submission-container'>
                <h2>". translate("By") . " $author->fname $author->lname (". translate($author->jobRole) .")</h2>
                <p><span style='font-weight: bold'>". translate("Date Submitted") . ": </span> $dateTimeOutput </p>
                <h2 class='submission-description'>$submission->comments</h2>
                <div class='submission-status'><h2>". translate("Status") . ": $status</h2></div>
                ";
                if (mysqli_num_rows($coauthorsQuery)) {
                    echo "
                    <div class='coauthors'>
                    <h1>". translate("Coauthors") . "</h1>
                    ";
                    while ($obj = $coauthorsQuery->fetch_object()) {
                        $coauthorQuery = $mysqli->query("SELECT * FROM users where userID = $obj->coauthor");
                        $coauthor = $coauthorQuery->fetch_object();
                        echo "$coauthor->fname $coauthor->lname";
                    }
                    echo "</div>";
                }
                echo "<div class='files'>";
                $files = $mysqli->query("SELECT * FROM file, submissionfile WHERE file.fileID = submissionfile.fileID AND submissionfile.submissionID = $submissionID");
                while ($file = $files->fetch_object()) {
                    echo "
                    <p>$file->name</p>
                    <a class='download-submission' href='submissionfiles/" . htmlspecialchars($file->address) . "' download='" . basename($file->name) . "'>" . translate("Download File") . "</a>
                    ";
                }
                echo "</div></div></div>";
                if ($user->jobRole == "Supervisor") {
                    $supervisorQuery = $mysqli->query("SELECT * FROM researcherssupervisor WHERE researcherID = $author->userID AND supervisorID = $user->userID");
                    if (mysqli_num_rows($supervisorQuery) > 0 ) {
                        if ($status == translate("Needing Supervisor approval")) { // Checks for $status instead of ($submission->submitted = 0) as the latter would immediately allow the supervisor to resubmit after rejected.
                            echo "
                            <h1 class='segment-header'>". translate("Review Work") . "</h1><div class='segment-container'>
                            <form method='post'>
                            <button name='approve'>". translate("Approve") . "</button>
                            </form>
                            <form method='post'>
                                <div class='decline-div'>
                                    <input type='text' placeholder='". translate("Comments (For declines only)") . "' name='return-comments' required>
                                    <button name='return'>". translate("Return") . "</button>
                                </div>
                            </form>
                            </div>
                            ";
                        }
                    } else {
                        echo "<h2>". translate("You can only view details of this task") . "</h2>";
                    }
                } else if ($user->jobRole == "Researcher") {
                    $coauthorQuery = $mysqli->query("SELECT * FROM submissioncoauthor WHERE coauthor = $userID AND submissionID = $submissionID");
                    if ($submission->author != $userID && mysqli_num_rows($coauthorQuery) == 0) {
                        if ($status == translate("Approved") && $submission->section == 3 || $submission->section == 4 || $submission->section == 5) {
                            echo "<h2>". translate("You can only view the details of this task") . "</h2>";
                        } else {
                            header("Location: index.php");
                        }
                    } else if ($status == translate("Rejected")) {
                        echo "
                        <form method='request'>
                        <button name='resubmit'>". translate("Resubmit") . "</button>
                        </form>
                        ";
                    }
                } else if ($user->jobRole == "Manager") {
                    if ($status == translate("Needing Manager approval")) {
                        // Section A, D - A has no coauthor. Approve or deny, 1 point.
                        // Section B, E - B has no coauthor. Internal - 1 point. National - 2 points. International - 3 points.
                        // Section C - Internal - 1 point. Operation - 2 points. External - 3 points.
                        // Section F - Supervision - 2 points. Local - 1 point. National - 2 points. International - 3 points.
                        // Section G - Institute - 1 point. District - 2 points. State - 2 points. National - 3 points. International 4 points.
                        echo "
                        <h1 class='segment-header'>" . translate("Review Work") . "</h1>
                        <div class='segment-container'>
                        <form method='post'>
                        <div>
                        ";
                        if ($submission->sectionID != 1 && $submission->sectionID != 4) {
                            echo "<select name='type-select' id='type-select'>";
                            if ($submission->sectionID == 2 || $submission->sectionID == 5) {
                                echo "
                                <option value='1'>" . translate("Internal") . "</option>  
                                <option value='2'>" . translate("National") . "</option>
                                <option value='3'>" . translate("International") . "</option>
                                ";
                            } else if ($submission->sectionID == 3) {
                                echo "
                                <option value='1'>" . translate("Internal Project") . "</option>
                                <option value='3'>" . translate("External Project") . "</option>
                                <option value='2'>" . translate("Operations") . "</option>
                                ";
                            } else if ($submission->sectionID == 6) {
                                echo "
                                <option value='2'>" . translate("Project Supervision") . "</option>
                                <option value='1'>" . translate("Local") . "</option>
                                <option value='2'>" . translate("National") . "</option>
                                <option value='3'>" . translate("International") . "</option>
                                ";
                            } else if ($submission->sectionID == 7) {
                                echo "
                                <option value='1'>" . translate("Institute") . "</option>
                                <option value='2'>" . translate("District") . "</option>
                                <option value='2'>" . translate("State") . "</option>
                                <option value='3'>" . translate("National") . "</option>
                                <option value='4'>" . translate("International") . "</option>
                                ";
                            }
                            echo "</select>";
                        }
                        echo "
                        <button name='manager-approve'>". translate("Approve") . "</button>
                        </div>
                        </form>
                        <form method='post'>
                            <div class='decline-div'>
                                <input type='text' placeholder='". translate("Comments (For declines only)") . "' name='return-comments' required>
                                <button name='return'>". translate("Return") . "</button>
                            </div>
                        </form>
                        </div>
                        ";
                    }
                }
                if (mysqli_num_rows($rejectedQuery) > 0) {
                    echo "
                    <h1 class='segment-header'>Rejection History:</h1>
                    <div class='segment-container'>
                    ";
                    while ($rejection = $rejectedQuery->fetch_object()) {
                        $returnerQuery = $mysqli->query("SELECT * FROM users WHERE userID = $rejection->returner");
                        $returner = $returnerQuery->fetch_object();
                        $timeToTranslate = $rejection->returnDate;
                        include("includes/format-date.php");
                        echo "
                            <div class='return-div'>
                                <p>" . translate("Returned by") . " $returner->fname $returner->lname</p>
                                <p>" . translate("Date returned") . ": $dateTimeOutput</p>
                                <p>" . translate("Reason") . ": $rejection->comments</p>
                            </div>
                        ";
                    }
                    echo "</div>";
            
                }
            ?>
        </div>
</body>
</html>


