<?php require_once __DIR__ . '/../api/gatekeeper.php';
?>
<link rel="stylesheet" href="../assets/css/home.css">
<link rel="stylesheet" href="../assets/css/navbar.css">
<div class="home-body">
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
                <a href="/pages/home.php" class="reset-btn" style="text-decoration:none;">
                    <button type="button">Reset</button>
                </a>
                <button type="submit">Apply</button>
            </form>
        </div>

        <?php
        require_once __DIR__ . '/../api/conn.php';
        $articles = [];
        $sql = "SELECT a.id, a.title, a.created_at, u.name as author FROM articles a JOIN user u ON a.user_id = u.usn ORDER BY a.created_at DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $blockSql = "SELECT block_type, content FROM article_blocks WHERE article_id = ? ORDER BY sort_order ASC";
                $blockStmt = $conn->prepare($blockSql);
                $blockStmt->bind_param('i', $row['id']);
                $blockStmt->execute();
                $blockResult = $blockStmt->get_result();
                $summary = '';
                $image = '/assets/images/article-sample.png';
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
                    'views' => 0 // Placeholder
                ];
            }
        }

        $filter = $_GET['filter'] ?? '';
        $hasFilter = isset($_GET['filter']) && ($_GET['filter'] === 'trending' || $_GET['filter'] === 'date');

        if ($filter === 'trending') {
            usort($articles, fn($a, $b) => $b['views'] - $a['views']);
        } elseif ($filter === 'date' && !empty($_GET['date'])) {
            $month = $_GET['date'];
            $articles = array_filter($articles, fn($article) => strpos($article['date'], $month) === 0);
            usort($articles, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        } elseif ($filter === 'latest') {
            usort($articles, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        }

        if ($hasFilter || ($filter === 'latest' && isset($_GET['filter']))) {
            echo '<div class="filtered-articles">';
            if (empty($articles)) {
                echo '<div>No posts found for the selected filter.</div>';
            } else {
                foreach ($articles as $article) {
                    ?>
                    <a href="#" onclick="loadArticle(<?php echo $article['id']; ?>); return false;" style="text-decoration:none;color:inherit;">
                        <div class="home-article">
                            <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="Article Main Image" class="home-article-image">
                            <div>
                                <div class="article-title"><?php echo htmlspecialchars($article['title']); ?></div>
                                <div class="article-meta">By <?php echo htmlspecialchars($article['author']); ?> | <?php echo htmlspecialchars($article['date']); ?></div>
                                <div class="article-content"><?php echo htmlspecialchars($article['content']); ?></div>
                            </div>
                        </div>
                    </a>
                    <?php
                }
            }
            echo '</div>';
        } else {
            ?>
            <div class="home-sections-container">
                <div class="home-section" id="recent-posts">
                    <h2>Just In</h2>
                    <?php
                    $recentArticles = array_slice($articles, 1, 2); // Skip the latest, show next 2
                    if (empty($recentArticles)) {
                        echo '<p style="color: #888;">No recent posts available.</p>';
                    } else {
                        foreach ($recentArticles as $recent) {
                            ?>
                            <a href="#" onclick="loadArticle(<?php echo $recent['id']; ?>); return false;" style="text-decoration:none;color:inherit;">
                                <div class="home-article">
                                    <img src="<?php echo htmlspecialchars($recent['image']); ?>" alt="Recent Post" class="home-article-image">
                                    <div>
                                        <div class="article-title"><?php echo htmlspecialchars($recent['title']); ?></div>
                                        <div class="article-meta">By <?php echo htmlspecialchars($recent['author']); ?> | <?php echo htmlspecialchars($recent['date']); ?></div>
                                        <div class="article-content"><?php echo htmlspecialchars($recent['content']); ?></div>
                                    </div>
                                </div>
                            </a>
                            <?php
                        }
                    }
                    ?>
                </div>


                <?php
                usort($articles, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
                if (!empty($articles)) {
                    $latest = $articles[0];
                    ?>
                    <div class="home-section" id="latest-post">
                        <h2>Latest Posts</h2>
                        <div class="latest-article-highlight">
                            <img src="<?php echo htmlspecialchars($latest['image']); ?>" alt="Latest Post" class="latest-img-highlight">
                            <div style="flex: 1;">
                                <div class="article-title" style="font-size: 2rem; font-weight: bold; color: #2d3a4a; margin-bottom: 10px;"><?php echo htmlspecialchars($latest['title']); ?></div>
                                <div class="article-meta" style="color: #888; font-size: 1rem; margin-bottom: 16px;">By <?php echo htmlspecialchars($latest['author']); ?> | <?php echo htmlspecialchars($latest['date']); ?></div>
                                <div class="article-content" style="font-size: 1.15rem; color: #444; line-height: 1.6; margin-bottom: 12px;">
                                    <?php echo htmlspecialchars($latest['content']); ?>
                                </div>
                                <a href="#" onclick="loadPage('article', '&id=<?php echo $latest['id']; ?>'); return false;">View Article</a>
                            </div>
                        </div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="home-section" id="latest-post">
                        <h2>Latest Posts</h2>
                        <p style="color: #888;">No latest post available.</p>
                    </div>
                    <?php
                }
                ?>

                <div class="home-section" id="trending-posts">
                    <h2>Trending</h2>
                    <?php
                    usort($articles, fn($a, $b) => $b['views'] - $a['views']);
                    $trending = array_slice($articles, 0, 2);
                    if (empty($trending)) {
                        echo '<p style="color: #888;">No trending posts available.</p>';
                    } else {
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
                    }
                    ?>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</div>
