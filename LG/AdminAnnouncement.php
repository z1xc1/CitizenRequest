<?php
session_set_cookie_params(0); 
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: AdminLogin.html");
    exit();
}

// Initialize error and success messages
$errorMessage = "";
$successMessage = "";

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lgutestdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve admin data from the database
$currentUsername = $_SESSION['username'];
$stmt = $conn->prepare("SELECT username, firstname, lastname, barangay FROM admincredentials WHERE username = ?");
$stmt->bind_param("s", $currentUsername);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $username = htmlspecialchars($row['username']);
    $firstname = htmlspecialchars($row['firstname']);
    $lastname = htmlspecialchars($row['lastname']);
    $barangay = htmlspecialchars($row['barangay']);
} else {
    echo "Error: User not found.";
    exit();
}

// Handle the announcement submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit'])) {
        // Update announcement logic
        $announcementID = $_POST['announcementID'];
        $topic = $_POST['topic'];
        $description = $_POST['description'];
        $image = null;

        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = basename($_FILES['image']['name']);
            $targetDir = "uploads/";
            $targetFile = $targetDir . $image;

            // Move uploaded file to the target directory
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $errorMessage = "Error uploading image.";
            }
        }

        // Update the announcement in the database
        if (empty($errorMessage)) {
            $stmt = $conn->prepare("UPDATE announcements SET topic = ?, description = ?, images = ? WHERE announcementID = ?");
            $stmt->bind_param("sssi", $topic, $description, $image, $announcementID);

            if ($stmt->execute()) {
                $successMessage = "Announcement updated successfully.";
            } else {
                $errorMessage = "Error updating announcement.";
            }

            $stmt->close();
        }
    } else {
        // Insert announcement logic (for new announcements)
        $topic = $_POST['topic'];
        $description = $_POST['description'];
        $image = null;

        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = basename($_FILES['image']['name']);
            $targetDir = "uploads/";
            $targetFile = $targetDir . $image;

            // Move uploaded file to the target directory
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $errorMessage = "Error uploading image.";
            }
        }

        // Insert announcement into the database
        if (empty($errorMessage)) {
            $stmt = $conn->prepare("INSERT INTO announcements (username, topic, description, images, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $username, $topic, $description, $image);

            if ($stmt->execute()) {
                $successMessage = "Announcement posted successfully.";
            } else {
                $errorMessage = "Error posting announcement.";
            }

            $stmt->close();
        }
    }
}

// Fetch announcements including ID
$announcements = [];
$stmt = $conn->prepare("SELECT announcementID, username, topic, description, images, created_at FROM announcements");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link rel="stylesheet" href="style.css">
    <title>Admin Announcements</title>
</head>
<body>

<div class="container">
    <!-- Side bar -->
    <aside id="sidebar">
        <div class="toggle">
            <div class="logo">
                <img src="images/crfms.png" alt="Logo">
            </div>
            <div class="close" id="toggle-btn">
                <span class="material-icons-sharp">menu_open</span>
            </div>
        </div>

        <div class="sidebar">
            <a href="AdminDashboard.php">
                <span class="material-symbols-outlined">dashboard</span>
                <h3>Dashboard</h3>
            </a>
            <a href="admin.php">
                <span class="material-symbols-outlined">shield_person</span>
                <h3>Admin</h3>
            </a>
            <a href="AdminAnnouncement.php" class="active">
                <span class="material-symbols-outlined">add_box</span>
                <h3>Announcements</h3>
            </a>
            <a href="Reviewsubmissions.php">
                <span class="material-symbols-outlined">rate_review</span>
                <h3>Review Request & Feedback</h3>
            </a>
        </div>
    </aside>
    <!-- Sidebar end -->

    <!-- Main content per page -->
    <div class="main--content">
        <h2>Create an Announcement</h2>

        <!-- Form for announcement creation -->
        <form id="announcementForm" method="POST" enctype="multipart/form-data">
            <label for="topic">Topic:</label><br>
                <input type="text" id="topic" name="topic" required><br><br>

            <label for="description">Description:</label><br>
                <textarea id="description" name="description" rows="5" cols="40" required></textarea><br><br>

                <label for="image">Upload Image:</label><br>
                <input type="file" id="image" name="image" accept="image/*"><br><br>

            <!-- Image preview section -->
            <img id="image-preview" src="" alt="Image Preview" style="max-width: 300px; display:none;"><br><br>

            <div class="postannouncementbutton">
                <button type="submit">Post Announcement</button>
            </div>
        </form>

        <!-- Success message -->
        <p id="successMessage" style="color:green; display:<?php echo !empty($successMessage) ? 'block' : 'none'; ?>;"><?php echo $successMessage; ?></p>

        <!-- Error message -->
        <p id="errorMessage" style="color:red; display:<?php echo !empty($errorMessage) ? 'block' : 'none'; ?>;"><?php echo $errorMessage; ?></p>

        <!-- Button to view all announcements -->
        <div class="manageAnnouncements"> 
            <h2>Manage Announcements</h2>
        </div>
        <button class="viewAnnouncementsBtn" id="viewAnnouncementsBtn">View Announcements</button>

        <!-- Announcement table -->
        <div id="announcementsTable" style="display:none;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Topic</th>
                        <th>Description</th>
                        <th>Image</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $announcement): ?>
                        <tr id="announcement-<?php echo htmlspecialchars($announcement['announcementID']); ?>">
                            <td><?php echo htmlspecialchars($announcement['announcementID']); ?></td>
                            <td><?php echo htmlspecialchars($announcement['username']); ?></td>
                            <td><?php echo htmlspecialchars($announcement['topic']); ?></td>
                            <td><?php echo htmlspecialchars($announcement['description']); ?></td>
                            <td>
                                <?php if (!empty($announcement['images'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($announcement['images']); ?>" alt="Announcement Image" style="max-width: 100px;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($announcement['created_at']); ?></td>
                            <td>
                                <button class="editannouncementbtn" data-id="<?php echo htmlspecialchars($announcement['announcementID']); ?>" data-topic="<?php echo htmlspecialchars($announcement['topic']); ?>" data-description="<?php echo htmlspecialchars($announcement['description']); ?>" data-image="<?php echo htmlspecialchars($announcement['images']); ?>">Edit</button>
                                <form method="POST" action="delete_announcement.php" style="display:inline;">
                                    <input type="hidden" name="announcementID" value="<?php echo htmlspecialchars($announcement['announcementID']); ?>">
                                    <button type="submit" onclick="return confirm('Are you sure you want to delete this announcement?');" class="deleteannouncementbtn" >Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Edit Announcement Form -->
        <div id="editAnnouncementForm" style="display:none;">
            <h2>Edit Announcement</h2>
            <form id="editForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="announcementID" name="announcementID">
                <label for="editTopic">Topic:</label><br>
                <input type="text" id="editTopic" name="topic" required><br><br>

                <label for="editDescription">Description:</label><br>
                <textarea id="editDescription" name="description" rows="5" cols="40" required></textarea><br><br>

                <label for="editImage">Upload Image:</label><br>
                <input type="file" id="editImage" name="image" accept="image/*"><br><br>

                <img id="editImagePreview" src="" alt="Image Preview" style="max-width: 300px; display:none;"><br><br>

                <div class="postannouncementbutton">
                    <button type="submit" name="edit">Update Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Show/Hide Announcements table
document.getElementById('viewAnnouncementsBtn').addEventListener('click', function() {
    const table = document.getElementById('announcementsTable');
    table.style.display = table.style.display === 'none' ? 'block' : 'none';
});

// Handle Edit button click
const editButtons = document.querySelectorAll('.editannouncementbtn');
editButtons.forEach(button => {
    button.addEventListener('click', function() {
        const announcementID = this.getAttribute('data-id');
        const topic = this.getAttribute('data-topic');
        const description = this.getAttribute('data-description');
        const image = this.getAttribute('data-image');

        // Populate the edit form with existing data
        document.getElementById('announcementID').value = announcementID;
        document.getElementById('editTopic').value = topic;
        document.getElementById('editDescription').value = description;
        
        const editImagePreview = document.getElementById('editImagePreview');
        if (image) {
            editImagePreview.src = "uploads/" + image;
            editImagePreview.style.display = 'block';
        } else {
            editImagePreview.src = "";
            editImagePreview.style.display = 'none';
        }

        // Show the edit form and hide the main announcement form
        document.getElementById('editAnnouncementForm').style.display = 'block';
        document.getElementById('announcementForm').style.display = 'none';
    });
});

// Image preview for the edit form
document.getElementById('editImage').addEventListener('change', function(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('editImagePreview');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        preview.src = "";
        preview.style.display = 'none';
    }
});

  // Image preview when a file is selected
    document.getElementById('image').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const imagePreview = document.getElementById('image-preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.src = '';
            imagePreview.style.display = 'none';
        }
    });
</script>
<script src="script.js"></script>
</body>
</html>
