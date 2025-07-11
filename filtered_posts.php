<?php
if (!isset($articles)) return;

$filter = $_GET['filter'] ?? '';
if ($filter) {
    if ($filter === 'trending') {
        usort($articles, fn($a, $b) => $b['views'] - $a['views']);
    } elseif ($filter === 'date' && !empty($_GET['date'])) {
        $month = $_GET['date']; // format: YYYY-MM
        $articles = array_filter($articles, fn($article) => strpos($article['date'], $month) === 0);
        usort($articles, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
    } elseif ($filter === 'latest') {
        usort($articles, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
    }

    echo '<div class="filtered-articles slide-up">';
    echo '<h2>Filtered Results</h2>';
    if (empty($articles)) {
        echo '<div>No posts found for the selected filter.</div>';
    } else {
        foreach ($articles as $article) {
            ?>
            <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="article-link">
                <div class="home-article">
                    <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="Article Main Image" class="home-article-image">
                    <div>
                        <div class="article-title"><?php echo htmlspecialchars($article['title']); ?></div>
                        <div class="article-content"><?php echo htmlspecialchars($article['content']); ?></div>
                        <div class="article-meta">By <?php echo htmlspecialchars($article['author']); ?> | <?php echo htmlspecialchars($article['date']); ?></div>    
                    </div>
                </div>
            </a>
            <?php
        }
    }
    echo '</div>';
}
?>
