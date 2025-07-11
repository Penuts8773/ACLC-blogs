<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}
require_once __DIR__ . '/backend/blog.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    deleteArticleController($id);
    header('Location: articleList.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News - ACLC Blogs</title>
    <link rel="stylesheet" href="styles/articleList.css">
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/animations.css">
    <link rel="icon" type="image/png" href="styles/images/aclc-emblem.png">
</head>
<body class="news-body">
    <?php include 'navbar.php'; ?>
    <div>
        <a href="postBoard.php">Create Post</a>
    </div>
    <div class="news-article-body">
        <?php include 'filtered_posts.php'; ?>
        <div class="home-filters" style="margin-bottom: 20px;">
            <form method="get" style="display: flex; gap: 10px; align-items: center;">
            <select name="filter" id="filter-select">
                <option value="latest" <?php if(isset($_GET['filter']) && $_GET['filter'] == 'latest') echo 'selected'; ?>>Latest</option>
                <option value="date" <?php if(isset($_GET['filter']) && $_GET['filter'] == 'date') echo 'selected'; ?>>Filter by Month</option>
            </select>
            <input type="month" name="date" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>" <?php if(!isset($_GET['filter']) || $_GET['filter'] != 'date') echo 'style="display:none;"'; ?> id="date-input">
            <a href="articleList.php" class="reset-btn" style="text-decoration:none;">
                <button type="button">Reset</button>
            </a>
            <button type="submit">Apply</button>
            </form>
        </div>
        <script>
            // Show/hide date input based on filter selection
            document.querySelector('select[name="filter"]').addEventListener('change', function() {
                var dateInput = document.getElementById('date-input');
                if(this.value === 'date') {
                    dateInput.style.display = '';
                } else {
                    dateInput.style.display = 'none';
                }
            });
        </script>
        <?php
            require_once __DIR__ . '/backend/conn.php';

            $filter = $_GET['filter'] ?? 'latest';
            $dateFilter = $_GET['date'] ?? '';
            $articles = [];

            $sql = "SELECT a.id, a.title, a.created_at, u.name as author 
                    FROM articles a 
                    JOIN user u ON a.user_id = u.usn";


            $params = [];
            $types = '';
            $whereClauses = [];

            // Handle filter by month
            if ($filter === 'date' && !empty($dateFilter)) {
                $month = date('m', strtotime($dateFilter));
                $year = date('Y', strtotime($dateFilter));
                $whereClauses[] = "MONTH(a.created_at) = ? AND YEAR(a.created_at) = ?";
                $types .= 'ii';
                $params[] = $month;
                $params[] = $year;
            }

            // Add WHERE clause if needed
            if (!empty($whereClauses)) {
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }

            // Handle sorting
            switch ($filter) {
                case 'latest':
                case 'date': // date already filtered above
                    $sql .= " ORDER BY a.created_at DESC";
                    break;
                case 'trending':
                    $sql .= " ORDER BY a.views DESC";
                    break;
                default:
                    $sql .= " ORDER BY a.created_at DESC";
            }

            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                // Fetch first block for summary and image
                $blockSql = "SELECT block_type, content FROM article_blocks WHERE article_id = ? ORDER BY sort_order ASC";
                $blockStmt = $conn->prepare($blockSql);
                $blockStmt->bind_param('i', $row['id']);
                $blockStmt->execute();
                $blockResult = $blockStmt->get_result();
                $summary = '';
                $image = 'styles/images/article-sample.png';

                while ($block = $blockResult->fetch_assoc()) {
                    if ($block['block_type'] === 'text' && $summary === '') {
                        $summary = mb_substr(strip_tags($block['content']), 0, 120) . '...';
                    }
                    if ($block['block_type'] === 'image' && $image === 'styles/images/article-sample.png' && !empty($block['content'])) {
                        $image = 'uploads/' . $block['content'];
                    }
                }
                $blockStmt->close();

                $articles[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'author' => $row['author'],
                    'date' => date('Y-m-d', strtotime($row['created_at'])),
                    'content' => $summary,
                    'image' => $image,
                ];
            }
            $stmt->close();
?>

            <?php
                foreach ($articles as $article) {
                    ?>
                    <a href="article.php?id=<?php echo urlencode($article['id']); ?>" style="text-decoration:none;color:inherit;">
                        <div class="news-article">
                            <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="Article Image" class="news-article-image">
                            <div>
                                <div class="news-article-title"><?php echo htmlspecialchars($article['title']); ?></div>
                                <div class="news-article-content"><?php echo htmlspecialchars($article['content']); ?></div>
                                <div class="news-article-meta">By <?php echo htmlspecialchars($article['author']); ?> | <?php echo htmlspecialchars($article['date']); ?></div>
                            </div>
                        </div>
                    </a>
                    <?php
                }
            ?>

                echo '<li><a href="article.php?id=' . $row['id'] . '">' . htmlspecialchars($row['title']) . '</a> - ' . htmlspecialchars($row['created_at']);
                echo '<form method="POST" style="display:inline;"><input type="hidden" name="delete_id" value="' . $row['id'] . '"><button type="submit" class="delete-button">Delete</button></form></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No articles found.</p>';
        }
        ?>
    </div>
    
</body>
</html>