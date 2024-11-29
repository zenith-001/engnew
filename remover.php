<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_id'])) {
        // Handle deletion
        $id = $_POST['delete_id'];

        // Fetch the file path before deletion
        $query = "SELECT file_path FROM videos WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
        $video = $stmt->fetch();

        if ($video) {
            // Delete the file from the server
            unlink($video['file_path']);

            // Delete the record from the database
            $query = "DELETE FROM videos WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id]);
            echo "<div class='alert success'>Video deleted successfully.</div>";
        } else {
            echo "<div class='alert error'>Video not found.</div>";
        }
    } elseif (isset($_POST['edit_id'])) {
        // Handle editing
        $id = $_POST['edit_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];

        // Update the video record in the database
        $query = "UPDATE videos SET title = ?, description = ? WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$title, $description, $id]);
        echo "<div class='alert success'>Video updated successfully.</div>";
    }
}

$query = "SELECT * FROM videos";
$stmt = $pdo->prepare($query);
$stmt->execute();
$videos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Videos</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .video-list {
            margin-top: 20px;
        }

        .video-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            transition: background 0.3s;
        }

        .video-item:hover {
            background: #f1f1f1;
        }

        .video-item h2 {
            margin: 0 0 10px;
            color: #007bff;
        }

        .video-item p {
            margin: 0 0 10px;
            color: #555;
        }

        form {
            display: flex;
            flex-direction: column;
            margin-top: 10px;
        }

        input[type="text"], textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 10px;
            resize: vertical;
        }

        button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Videos</h1>
        <div class="video-list">
            <?php foreach ($videos as $video): ?>
                <div class="video-item">
                    <h2><?php echo htmlspecialchars($video['title']); ?></h2>
                    <p><?php echo htmlspecialchars($video['description']); ?></p>
                    
                    <!-- Edit Form -->
                    <form action="remover.php" method="post">
                        <input type="hidden" name="edit_id" value="<?php echo $video['id']; ?>">
                        <input type="text" name="title" value="<?php echo htmlspecialchars($video['title']); ?>" required>
                        <textarea name="description" required><?php echo htmlspecialchars($video['description']); ?></textarea>
                        <button type="submit">Update</button>
                    </form>

                    <!-- Delete Form -->
                    <form action="remover.php" method="post">
                        <input type="hidden" name="delete_id" value="<?php echo $video['id']; ?>">
                        <button type="submit">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>