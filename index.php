<?php
include 'db.php';

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
    <title>Video List</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Uploaded Videos</h1>
        <input type="text" id="search" placeholder="Search...">
        <div class="video-list">
            <?php foreach ($videos as $video): ?>
                <div class="video-item">
                    <h2><?php echo htmlspecialchars($video['title']); ?></h2>
                    <p><?php echo htmlspecialchars($video['description']); ?></p>
                    <a href="watch.php?id=<?php echo $video['id']; ?>">Watch</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
        const search = document.getElementById('search');
        search.addEventListener('input', function() {
            const filter = search.value.toLowerCase();
            const videoItems = document.querySelectorAll('.video-item');
            videoItems.forEach(item => {
                const title = item.querySelector('h2').textContent.toLowerCase();
                item.style.display = title.includes(filter) ? '' : 'none';
            });
        });
    </script>
</body>
</html>