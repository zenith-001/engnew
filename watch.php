<?php
include 'db.php';

$id = $_GET['id'];
$query = "SELECT * FROM videos WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$id]);
$video = $stmt->fetch();

if (!$video) {
    echo "Video not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($video['title']); ?></h1>
        <p><?php echo htmlspecialchars($video['description']); ?></p>
        <video width="600" controls>
            <source src="<?php echo htmlspecialchars($video['file_path']); ?>" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
</body>
</html>