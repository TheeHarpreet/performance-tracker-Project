<?php
require_once("includes/config.php");
require_once("includes/redirect-login.php");
ob_clean();

$query = $mysqli->query("SELECT * FROM users WHERE userID = $userID");
$user = $query->fetch_object();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['new-submission'])) {
        $_SESSION['newSubmission'] = $_POST['new-submission'];
        header("Location: new-submission.php");
    } else {
        $_SESSION['viewSubmission'] = $_POST['submission-id'];
        header("Location: view-submission.php");
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | MIROS</title>
    <link rel="stylesheet" href="css/mobile.css" />
    <link rel="stylesheet" href="css/desktop.css" media="only screen and (min-width : 790px)"/>
    <script src="js/index.js"></script>
    <script src="js/performance-bars.js" defer></script>
</head>
<body>
    <?php include_once("includes/header.php") ?>
        <div class="index-container">
            <?php
                if ($user->jobRole == "None") {
                    echo "<p class='invalid-role'>Your account doesn't have a role assigned. Please speak to an admin to assign you one.</p>";
                    echo "</div>";
                    include_once("includes/footer.php");
                    exit();
                } else if ($user->jobRole == "Admin") {
                    header("Location: admin-index.php");
                } else if ($user->jobRole == "Manager") {
                    header("Location: manager-index.php");
                }
            ?>
            <?php
                if ($user->jobRole == "Supervisor") {
                    echo "<h1>Select a researcher to view their work</h1>";
                    echo "<div class='supervisor-user-selection'>";
                    echo "<p><a href='index.php'>View your own work</a></p>";
                    $results = $mysqli->query("SELECT * FROM users, researcherssupervisor WHERE supervisorID = $userID and researcherID = userID");
                    echo "<div class='researchers-names'>";
                    while ($researcher = $results->fetch_object()) {
                        echo "<p><a href='index.php?user_override=$researcher->userID'>$researcher->fname $researcher->lname</a></p>";
                    }
                    echo "</div>";
                    echo "</div>";
                }
                if (isset($_GET['user_override'])) {
                    $userID = $_GET['user_override'];
                }
            ?>
            <div class="performance">
                <h1>Performance Overview</h1>
                <div class="performance-overview">
                    <div class="performance-section">
                        <?php
                        $sectionTitles = array ("Personal Particulars", "Professional Achievements", "Research And Development", "Professional Consultations", "Research Outcomes", "Professional Recognition", "Service To Community");
                        $sectionTypes = array ("A", "B", "C", "D", "E", "F", "G");
                        $author = $userID;
                        $pointsTotal = 0;
                        for ($loop = 0; $loop < 7; $loop++) {
                            $section = $sectionTypes[$loop];
                            include("includes/calculate-score.php");
                            $pointsTotal += $points;
                            if ($points == 0){
                                echo "<p>$sectionTitles[$loop]: Not enough data to calculate scores</p>";
                            }
                            else {
                                $percent = (($points-$minPoints)*100)/($maxPoints-$minPoints);
                                echo "
                                <p>$sectionTitles[$loop]:</p>
                                <div class='percent-bar'>
                                <p class='point-boundary'>$minPoints</p>
                                <div class='progress-bar-container'>
                                    <div id='myBar' class='progress-bar' style='width: $percent%;'>"; if ($percent >= 10) { echo "<p style='padding: 4px 7px 0px 0px; margin: 0px; border: 0px; text-align: right;'>$points</p>"; } echo"</div>";
                                    if ($percent < 10) { echo "<p style='padding: 4px 0px 0px 3px; margin: 0px; border: 0px; text-align: right;'>$points</p>"; }
                                echo "</div>
                                <p class='point-boundary'>$maxPoints</p>
                                </div>
                                ";
                            }
                        }
                        ?>
                    </div>
                    <div class="performance-section">
                    <p><?php echo "$pointsTotal"; ?> / 55</p> <!-- 42 is the minimum if you have something in all categories -->
                        <svg width="250" height="250" viewBox="0 0 250 250">
                        <circle class="bg" cx="125" cy="125" r="115" fill="none" stroke="#ddd" stroke-width="20"></circle>
                        <circle class="fg"cx="125" cy="125" r="115" fill="none" stroke="#f8b822" stroke-width="20" stroke-dasharray="362.25 362.25"></circle>
                        </svg>
                        <p>The circle does nothing yet</p>
                    </div>
                </div>
            </div>
            <div class="tasks">
                <?php
                    $i = 0;

                    echo "<h1>Submissions</h1>";

                    while ($i < 7) {
                        echo "<div class='section-container'>";
                        echo "<div class='section-name-bar'>";
                        echo "<h2 class='section-header'>$sectionTitles[$i]</h2>";
                        echo "<button onclick='hideSection($i)' id='toggle-button$i'>Hide</button>";
                        echo "</div>";
                        
                        echo "<div id='section-hide$i'>";
                        $type = $sectionTypes[$i];
                        $query = $mysqli->query("SELECT * FROM submission WHERE author = $userID AND type = '$type'");
                        while ($obj = $query->fetch_object()) {
                            $isAuthor = true;
                            include("includes/submission-preview-fill.php");
                        }
                        $query = $mysqli->query("SELECT * FROM submission, submissioncoauthor WHERE submissioncoauthor.userID = $userID AND submissioncoauthor.submissionID = submission.submissionID AND submission.type = '$type'");
                        while ($obj = $query->fetch_object()) {
                            $isAuthor = false;
                            include("includes/submission-preview-fill.php");
                        }
                        echo "<div>";
                        echo "<form method='post'>";
                        echo "<button class='new-submission' name='new-submission' value='$sectionTypes[$i]'>+ Add New Submission</button>";
                        echo "</form>";
                        echo "</div>";
                        echo "</div>";
                        echo "</div>";
                        $i++;
                    }
                ?>
            </div>
        </div>
    <?php include_once("includes/footer.php") ?>
</body>
</html>