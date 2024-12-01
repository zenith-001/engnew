<?php
include 'db.php'; // Include your database connection file

// Set error reporting
error_reporting(E_ALL);
ini_set('log_errors', 'On');
ini_set('error_log', __DIR__ . '/error.log');

// FTP configuration
$ftp_server = "ftp.kushmaartproject.com.np"; // Replace with your FTP server
$ftp_username = "zenith@kushmaartproject.com.np"; // Replace with your FTP username
$ftp_password = "8038@Zenith"; // Replace with your FTP password
$ftp_directory = "uploads/"; // Path on the FTP server

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get chunk information
    $chunkIndex = isset($_POST['chunkIndex']) ? (int)$_POST['chunkIndex'] : 0;
    $totalChunks = isset($_POST['totalChunks']) ? (int)$_POST['totalChunks'] : 0;
    $fileName = isset($_POST['fileName']) ? $_POST['fileName'] : 'video.mp4'; // Get original filename

    // Define target file paths
    $localTargetFile = __DIR__ . '/uploads/' . basename($fileName);
    $tempTargetFile = $localTargetFile . '.tmp';

    // Open the file in append mode
    $out = fopen($tempTargetFile, 'ab');
    if (!$out) {
        error_log("Failed to open target file for writing.");
        echo "Failed to open target file for writing.";
        exit;
    }

    // Open the uploaded chunk
    $in = fopen($_FILES['fileChunk']['tmp_name'], 'rb');
    if ($in) {
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff); // Write the chunk to the target file
        }
        fclose($in);
        fclose($out);

        // Check if all chunks have been uploaded
        if ($chunkIndex + 1 == $totalChunks) {
            // Rename the temporary file to the final filename
            rename($tempTargetFile, $localTargetFile);

            // Check if the local file exists before attempting to upload
            if (file_exists($localTargetFile)) {
                // All chunks uploaded, upload to FTP
                $ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
                $login = ftp_login($ftp_conn, $ftp_username, $ftp_password);

                if ($login) {
                    // Upload the file to the FTP server
                    if (ftp_put($ftp_conn, $ftp_directory . basename($fileName), $localTargetFile, FTP_BINARY)) {
                        // Insert into the database
                        try {
                            $query = "INSERT INTO videos (title, description, file_path) VALUES (?, ?, ?)";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute([$_POST['title'], $_POST['description'], $ftp_directory . basename($fileName)]);
                            echo "The file has been uploaded and combined successfully.";
                        } catch (PDOException $e) {
                            error_log("Database insert failed: " . $e->getMessage());
                            echo "There was an error saving video details to the database: " . $e->getMessage();
                        }
                    } else {
                        echo "Failed to upload the file to FTP server.";
                    }
                } else {
                    echo "FTP login failed.";
                }
                ftp_close($ftp_conn);
            } else {
                echo "Local file does not exist: ". $localTargetFile;
            }
        } else {
            echo "Chunk $chunkIndex uploaded successfully.";
        }
    } else {
        error_log("Failed to open the chunk file for reading.");
        echo "Failed to upload the chunk.";
    }
    exit; // Exit to prevent the HTML from rendering below
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video</title>
    <link rel="stylesheet" href="style.css"> <!-- Ensure this path is correct -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        input[type="text"], input[type="file"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="submit"] {
            background: #5cb85c;
            color: #fff;
            border: none;
            padding: 10px;
            cursor: pointer;
            border-radius: 5px;
            width: 100%;
        }
        input[type="submit"]:hover {
            background: #4cae4c;
        }
    </style>
</head>
<body>

<h1>Upload Video</h1>
<form action="upload.php" method="post" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Video Title" required>
    <input type="text" name="description" placeholder="Video Description" required>
    <input type="file" name="fileChunk" required>
    <input type="hidden" name="chunkIndex" value="0">
    <input type="hidden" name="totalChunks" value="1">
    <input type="hidden" name="fileName" value="video.mp4">
    <input type="submit" value="Upload">
</form>

</body>
</html>