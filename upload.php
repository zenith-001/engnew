<?php
include 'db.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('log_errors', 'On');
ini_set('error_log', __DIR__ . '/error.log');

$ftp_server = "ftp.kushmaartproject.com.np"; // Replace with your FTP server
$ftp_username = "zenith@kushmaartproject.com.np"; // Replace with your FTP username
$ftp_password = "8038@Zenith"; // Replace with your FTP password
$ftp_directory = "uploads/"; // Replace with the path on the FTP server where you want to store the videos

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get chunk information
    $chunkIndex = isset($_POST['chunkIndex']) ? (int)$_POST['chunkIndex'] : 0;
    $totalChunks = isset($_POST['totalChunks']) ? (int)$_POST['totalChunks'] : 0;
    $fileName = isset($_POST['fileName']) ? $_POST['fileName'] : 'video.mp4'; // Get original filename

    // Define the target file path
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
            // Combine chunks in order
            $combinedFile = $localTargetFile;
            for ($i = 0; $i <= $totalChunks; $i++) {
                $tempFile = __DIR__ . '/uploads/' . $fileName . '.' . $i . '.tmp';
                if (file_exists($tempFile)) {
                    $content = file_get_contents($tempFile);
                    file_put_contents($combinedFile, $content, FILE_APPEND);
                    unlink($tempFile);
                }
            }

            // Move the combined file to the target directory
            rename($combinedFile, $localTargetFile);

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
                        echo "There was an error saving video details to the database.";
                    }
                } else {
                    echo "Failed to upload the file to FTP server.";
                }
            } else {
                echo "FTP login failed.";
            }
            ftp_close($ftp_conn);
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
        /* Basic styles for the progress bars */
        #progressContainer {
            width: 100%;
            margin-top: 20px;
        }
        .progressBar {
            width: 100%;
            background: #f3f3f3;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .progress {
            height: 30px;
            width: 0;
            background: #4caf50;
            text-align: center;
            line-height: 30px; /* Center text vertically */
            color: white;
        }
    </style>
    <script>
        function uploadFileInChunks(file) {
            const chunkSize = 1024 * 1024 * 100; // 100MB
            const totalChunks = Math.ceil(file.size / chunkSize);
            let currentChunk = 0;

            // Show progress container
            const progressContainer = document.getElementById('progressContainer');
            const currentProgressBar = document.getElementById('currentProgressBar');
            const totalProgressBar = document.getElementById('totalProgressBar');
            progressContainer.style.display = 'block';

            function uploadChunk() {
                const start = currentChunk * chunkSize;
                const end = Math.min(start + chunkSize, file.size);
                const chunk = file.slice(start, end);
                
                const formData = new FormData();
                formData.append('fileChunk', chunk);
                formData.append('chunkIndex', currentChunk);
                formData.append('totalChunks', totalChunks);
                formData.append('title', document.getElementById('titleInput').value);
                formData.append('description', document.getElementById('descriptionInput').value);
                formData.append('fileName', file.name); // Preserve original filename

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'upload.php', true);

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        currentChunk++;
                        const currentProgressPercentage = Math.round((currentChunk / totalChunks) * 100);
                        currentProgressBar.style.width = currentProgressPercentage + '%';
                        currentProgressBar.textContent = currentProgressPercentage + '% uploaded';
                        document.getElementById('chunkStatus').textContent = `Uploaded ${currentChunk} of ${totalChunks} chunks`;

                        const totalProgressPercentage = Math.round((currentChunk / totalChunks) * 100);
                        totalProgressBar.style.width = totalProgressPercentage + '%';
                        totalProgressBar.textContent = totalProgressPercentage + '% total uploaded';

                        if (currentChunk < totalChunks) {
                            uploadChunk();
                        } else {
                            alert('All chunks uploaded successfully!');
                        }
                    } else {
                        alert('Error uploading chunk: ' + xhr.responseText);
                    }
                };

                xhr.send(formData);
            }

            uploadChunk();
        }

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                uploadFileInChunks(file);
            }
        }
    </script>
</head>
<body>
    <h1>Upload Video</h1>
    <input type="text" id="titleInput" placeholder="Enter Title" required />
    <input type="text" id="descriptionInput" placeholder="Enter Description" required />
    <input type="file" id="fileInput" accept="video/*" onchange="handleFileSelect(event)">
    <button onclick="uploadFileInChunks(document.getElementById('fileInput').files[0])">Upload</button> <!-- Submit button added -->

    <div id="progressContainer" style="display:none;">
        <div class="progressBar">
            <div id="currentProgressBar" class="progress">0% uploaded</div>
        </div>
        <div class="progressBar">
            <div id="totalProgressBar" class="progress">0% total uploaded</div>
        </div>
        <div id="chunkStatus">Uploaded 0 of 0 chunks</div>
    </div>
</body>
</html>
