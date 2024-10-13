<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: AdminLogin.html");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lgutestdb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$feedback_id = $_GET['feedback_id'];

// Prepared statement to prevent SQL injection
$sql = "SELECT * FROM Feedback WHERE feedbackid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $feedback_id);
$stmt->execute();
$result = $stmt->get_result();
$feedback = $result->fetch_assoc();

if (!$feedback) {
    echo "Feedback not found!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback</title>
    <link rel="stylesheet" href="../modal.css"> <!-- Separate CSS for Modal -->
</head>
<body>

<div class="view-feedback-container">
    <div class="view-feedback-header">
        <h2>Feedback Details</h2>
        
        <!-- Table for Feedback Information -->
        <table>
            <tr>
                <th>Field</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Feedback ID:</td>
                <td><?php echo htmlspecialchars($feedback['feedbackid']); ?></td>
            </tr>
            <tr>
                <td>Email:</td>
                <td><?php echo htmlspecialchars($feedback['email']); ?></td>
            </tr>
            <tr>
                <td>Topic:</td>
                <td><?php echo htmlspecialchars($feedback['topic']); ?></td>
            </tr>
            <tr>
                <td>Description:</td>
                <td><?php echo htmlspecialchars($feedback['description']); ?></td>
            </tr>
            <tr>
                <td>Submitted Date:</td>
            <td><?php 
                $submittedDate = new DateTime($feedback['submitted_date']);
                echo $submittedDate->format('F/j/Y'); 
            ?></td>
            </tr>
             <tr>
            <td>Time:</td>
            <td><?php 
                echo $submittedDate->format('g:i a');
            ?></td>
        </tr>
        </table>

        <!-- Display Images if available -->
        <div class="images-container">
            <h3>Attached Images</h3>
            <div class="image-gallery">
                <?php if (!empty($feedback['images']) && $feedback['images'] !== 'NULL'): ?>
                    <?php $images = explode(',', $feedback['images']); ?>
                    <?php foreach ($images as $image): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($image); ?>" class="feedback-image" style="width:100px; margin-right: 5px; cursor: pointer;" alt="Attached Image" onclick="openModal(this.src)">
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No image attached.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="backtoreview">
    <a href="../reviewsubmissions.php">Back to Review Submissions</a>
</div>

<!-- Modal for Zoomed Image -->
<div id="myModal" class="modal" style="display:none;">
    <span class="close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage" alt="Zoomed Image">
</div>

<script>
    function openModal(src) {
        document.getElementById('modalImage').src = src;
        document.getElementById('myModal').style.display = "flex";
    }

    function closeModal() {
        document.getElementById('myModal').style.display = "none";
    }

    // Close the modal when clicking outside of the image
    window.onclick = function(event) {
        if (event.target == document.getElementById('myModal')) {
            closeModal();
        }
    }
</script>

</body>
</html>

<?php
$conn->close();
?>
