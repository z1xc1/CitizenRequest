<?php
// Start the session
session_start();

// Create a connection to the database
$conn = new mysqli("localhost", "root", "", "lgutestdb"); // Change to your database credentials

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the announcement ID from the POST request
    $announcementID = $_POST['announcementID'];

    // Prepare the delete statement
    $deleteQuery = "DELETE FROM announcements WHERE announcementID=?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $announcementID); // Bind the ID parameter
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Redirect back to the announcements page with a success message
        header("Location: adminannouncement.php?success=Announcement deleted successfully.");
    } else {
        // Redirect back to the announcements page with an error message
        header("Location: adminannouncement.php?error=Announcement not found.");
    }

    // Close the statement
    $stmt->close();
} else {
    // Respond with a 405 Method Not Allowed
    http_response_code(405);
}

// Close the database connection
$conn->close();
?>
