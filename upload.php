<?php
include 'db.php';

$ftp_server = "localhost"; // Replace with your FTP server
$ftp_username = "zenith1"; // Replace with your FTP username
$ftp_password = "8038@Zenith"; // Replace with your FTP password
$ftp_directory = "uploads/"; // Replace with the path on the FTP server where you want to store the videos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $file = $_FILES['video'];

    // Establishing FTP connection
    $conn_id = ftp_connect($ftp_server);
    $login_result = ftp_login($conn_id, $ftp_username, $ftp_password);

    if (!$conn_id || !$login_result) {
        die("FTP connection has failed!");
    }

    // Uploading the file
    $target_file = $ftp_directory . basename($file["name"]);
    if (ftp_put($conn_id, $target_file, $file["tmp_name"], FTP_BINARY)) {
        // File uploaded successfully, now insert into the database
        $query = "INSERT INTO videos (title, description, file_path) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$title, $description, $target_file]);
        echo "The file has been uploaded successfully via FTP.";
    } else {
        echo "There was an error uploading your file via FTP.";
    }

    // Closing the FTP connection
    ftp_close($conn_id);
    exit; // Exit to prevent the HTML from rendering below
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOMcG6q1dJb6vR1d1K6F1d2yZcK2e5l5D8l5F5" crossorigin="anonymous">
    <script>
        function uploadFile(event) {
            event.preventDefault(); // Prevent the default form submission

            const formData = new FormData(document.getElementById('uploadForm'));
            const xhr = new XMLHttpRequest();

            // Disable the upload button
            const uploadButton = document.getElementById('uploadButton');
            uploadButton.disabled = true;

            xhr.open('POST', 'upload.php', true);

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    document.getElementById('progressBar').style.width = percentComplete + '%';
                    document.getElementById('progressText').innerText = Math.round(percentComplete) + '% uploaded';
                }
            };

            xhr.onload = function() {
                if (xhr.status === 200) {
                    document.getElementById('progressText').innerText = 'Upload complete!';
                } else {
                    document.getElementById('progressText').innerText = 'Upload failed. Please try again.';
                }
                uploadButton.disabled = false; // Re-enable the upload button
            };

            xhr.send(formData);
        }
    </script>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-upload"></i> Upload Your Video</h1>
        <form id="uploadForm" onsubmit="uploadFile(event)">
            <input type="text" name="title" placeholder="Video Title" required>
            <textarea name="description" placeholder="Video Description" required></textarea>
            <input type="file" name="video" accept="video/*" required>
            <button id="uploadButton" type="submit"><i class="fas fa-cloud-upload-alt"></i> Upload</button>
        </form>
        <div id="progressContainer">
            <div id="progressBar"></div>
        </div>
        <div id="progressText"></div>
        <div class="video-list">
            <div class="video-item">
                <h2><i class="fas fa-video"></i> Sample Video 1</h2>
                <p>Uploaded on: November 26, 2024</p>
                <a href="#"><i class="fas fa-eye"></i> View</a>
            </div>
            <div class="video-item">
                <h2><i class="fas fa-video"></i> Sample Video 2</h2>
                <p>Uploaded on: November 26, 2024</p>
                <a href="#"><i class="fas fa-eye"></i> View</a>
            </div>
        </div>
    </div>
</body>
</html>