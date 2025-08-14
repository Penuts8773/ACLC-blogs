
<?php
// Contains admin panel PHP functions
function displayArticle($article, $isApproved, $articleController) {
	$blocks = $articleController->getArticleBlocks($article['id']);
	$content = $articleController->processArticleContent($blocks);
	?>
	<div class="article" data-article-id="<?= $article['id'] ?>" style="background-image: url('<?= htmlspecialchars($content['thumbnail']) ?>');">
		<div class="article-content">
			<h2><?= htmlspecialchars($article['title']) ?></h2>
			<small>
				By <?= htmlspecialchars($article['name']) ?> | <?= $article['created_at'] ?> |
				👍 <?= $article['likes'] ?> | 💬 <?= $article['comment_count'] ?>
			</small>
			<p class="preview"><?= htmlspecialchars($content['preview']) ?></p>
            
			<div class="article-actions">
				<?php if ($_SESSION['user']['privilege'] == 1): ?>
					<!-- Admin-only actions -->
					<form method="POST" onsubmit="return confirmAction(this, '<?= $isApproved ? 'unapprove' : 'approve' ?>')">
						<input type="hidden" name="article_id" value="<?= $article['id'] ?>">
						<input type="hidden" name="action" value="<?= $isApproved ? 'unapprove' : 'approve' ?>">
						<button type="submit" class="action-btn <?= $isApproved ? 'unapprove-btn' : 'approve-btn' ?>">
							<?= $isApproved ? 'Unapprove' : 'Approve' ?>
						</button>
					</form>
				<?php endif; ?>
                
				<!-- View button with confirmation -->
				<button onclick="confirmView(<?= $article['id'] ?>)" class="action-btn view-btn">View</button>
                
				<?php if ($_SESSION['user']['privilege'] == 1 || $_SESSION['user']['usn'] == $article['user_id']): ?>
					<!-- Edit button with confirmation -->
					<button onclick="confirmEdit(<?= $article['id'] ?>)" class="action-btn edit-btn">Edit</button>
                    
					<!-- Delete button -->
					<form method="POST" onsubmit="return confirmAction(this, 'delete')">
						<input type="hidden" name="article_id" value="<?= $article['id'] ?>">
						<input type="hidden" name="action" value="delete">
						<button type="submit" class="action-btn delete-btn">Delete</button>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}
?>
