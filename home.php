<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/home.css">
    <link rel="stylesheet" href="styles/navbar.css">
    <title>Homepage - ACLC Blogs</title>
</head>
<body class="home-body">
    <?php include 'navbar.php'; ?>
    <div class="home-container">
        <div class="home-filters" style="margin-bottom: 20px;">
            <form method="get" style="display: flex; gap: 10px; align-items: center;">
            <select name="filter" id="filter-select">
                <option value="latest" <?php if(isset($_GET['filter']) && $_GET['filter'] == 'latest') echo 'selected'; ?>>Latest</option>
                <option value="trending" <?php if(isset($_GET['filter']) && $_GET['filter'] == 'trending') echo 'selected'; ?>>Trending</option>
                <option value="date" <?php if(isset($_GET['filter']) && $_GET['filter'] == 'date') echo 'selected'; ?>>Filter by Month</option>
            </select>
            <input type="month" name="date" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>" <?php if(!isset($_GET['filter']) || $_GET['filter'] != 'date') echo 'style="display:none;"'; ?> id="date-input">
            <a href="home.php" class="reset-btn" style="text-decoration:none;">
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
        // Example articles array
        $articles = [
            [
                'title' => 'Welcome to ACLC Blogs!',
                'author' => 'Admin',
                'date' => '2024-06-01',
                'content' => 'This is the first post on ACLC Blogs. Stay tuned for more updates and articles!',
                'image' => 'styles/images/article-sample.png',
                'views' => 10 // For trending
            ],
            [
                'title' => 'Getting Started with Blogging',
                'author' => 'Jane Doe',
                'date' => '2024-06-02',
                'content' => 'Learn how to start your own blog and share your thoughts with the world.',
                'image' => 'styles/images/article-sample.png',
                'views' => 50 // For trending
            ]
        ];

        $filter = isset($_GET['filter']) ? $_GET['filter'] : '';
        $hasFilter = isset($_GET['filter']) && ($_GET['filter'] === 'trending' || $_GET['filter'] === 'date');
        if ($filter === 'trending') {
            usort($articles, function($a, $b) {
                return $b['views'] - $a['views'];
            });
        } elseif ($filter === 'date' && !empty($_GET['date'])) {
            $month = $_GET['date']; // format: YYYY-MM
            $articles = array_filter($articles, function($article) use ($month) {
                return strpos($article['date'], $month) === 0;
            });
            usort($articles, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
        } elseif ($filter === 'latest') {
            usort($articles, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
        }

        if ($hasFilter || ($filter === 'latest' && isset($_GET['filter']))) {
            // Only show filtered posts
            echo '<div class="filtered-articles">';
            if (empty($articles)) {
                echo '<div>No posts found for the selected filter.</div>';
            } else {
                foreach ($articles as $article) {
                    ?>
                    <div class="home-article">
                        <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="Article Main Image" class="home-article-image">
                        <div>
                            <div class="article-title"><?php echo htmlspecialchars($article['title']); ?></div>
                            <div class="article-meta">By <?php echo htmlspecialchars($article['author']); ?> | <?php echo htmlspecialchars($article['date']); ?></div>
                            <div class="article-content"><?php echo htmlspecialchars($article['content']); ?></div>
                        </div>
                    </div>
                    <?php
                }
            }
            echo '</div>';
        } else {
            // No filter: show 3 sections
            ?>
            <div class="home-sections-container">
                <div class="home-section" id="recent-posts">
                <h2>Just In</h2>
                <div class="home-article">
                    <img src="styles/images/article-sample.png" alt="Recent Post" class="home-article-image">
                    <div>
                        <div class="article-title">[Recent Post Title]</div>
                        <div class="article-meta">By [Author] | [Date]</div>
                        <div class="article-content">[Short summary of a recent post goes here...]</div>
                    </div>
                </div>
                <div class="home-article">
                    <img src="styles/images/article-sample.png" alt="Recent Post" class="home-article-image">
                    <div>
                        <div class="article-title">[Another Recent Post]</div>
                        <div class="article-meta">By [Author] | [Date]</div>
                        <div class="article-content">[Another recent post summary goes here...]</div>
                    </div>
                </div>
            </div>
            <div class="home-section" id="latest-post">
                <h2>Latest Posts</h2>
                <?php
                usort($articles, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
                $latest = $articles[0];
                ?>
                <div class="home-article">
                    <img src="<?php echo htmlspecialchars($latest['image']); ?>" alt="Latest Post" class="home-article-image">
                    <div>
                        <div class="article-title"><?php echo htmlspecialchars($latest['title']); ?></div>
                        <div class="article-meta">By <?php echo htmlspecialchars($latest['author']); ?> | <?php echo htmlspecialchars($latest['date']); ?></div>
                        <div class="article-content"><?php echo htmlspecialchars($latest['content']); ?></div>
                    </div>
                </div>
            </div>
            <div class="home-section" id="trending-posts">
                <h2>Trending</h2>
                <?php
                usort($articles, function($a, $b) {
                    return $b['views'] - $a['views'];
                });
                $trending = array_slice($articles, 0, 2);
                foreach ($trending as $trend) {
                    ?>
                    <div class="home-article">
                        <img src="<?php echo htmlspecialchars($trend['image']); ?>" alt="Trending Post" class="home-article-image">
                        <div>
                            <div class="article-title"><?php echo htmlspecialchars($trend['title']); ?></div>
                            <div class="article-meta">By <?php echo htmlspecialchars($trend['author']); ?> | <?php echo htmlspecialchars($trend['date']); ?></div>
                            <div class="article-content"><?php echo htmlspecialchars($trend['content']); ?></div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            </div>
            <?php
        }
        ?>
</body>
</html>

</html>
