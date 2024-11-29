<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];

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
        echo "Video deleted successfully.";
    } else {
        echo "Video not found.";
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
    <title>Remove Video</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Remove Video</h1>
        <div class="video-list">
            <?php foreach ($videos as $video): ?>
                <div class="video-item">
                    <h2><?php echo htmlspecialchars($video['title']); ?></h2>
                    <form action="remover.php" method="post">
                        <input type="hidden" name="id" value="<?php echo $video['id']; ?>">
                        <button type="submit">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>