<?php
class ArticleController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getUnapprovedArticles() {
        $sql = "SELECT 
            a.*, u.name,
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) AS likes,
            COUNT(c.id) AS comment_count
        FROM articles a
        JOIN user u ON a.user_id = u.usn
        LEFT JOIN article_comments c ON a.id = c.article_id
        WHERE a.approved = 0
        GROUP BY a.id, u.name
        ORDER BY a.created_at DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getApprovedArticles() {
        $sql = "SELECT 
            a.*, u.name,
            (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) AS likes,
            COUNT(c.id) AS comment_count
        FROM articles a
        JOIN user u ON a.user_id = u.usn
        LEFT JOIN article_comments c ON a.id = c.article_id
        WHERE a.approved = 1
        GROUP BY a.id, u.name
        ORDER BY a.created_at DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getArticleBlocks($articleId) {
        $stmt = $this->pdo->prepare("SELECT * FROM article_blocks WHERE article_id = ? ORDER BY sort_order");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function processArticleContent($blocks) {
        $thumbnail = '';
        $preview = '';
        foreach ($blocks as $block) {
            if ($block['block_type'] === 'image' && empty($thumbnail)) {
                $thumbnail = $block['content'];
            }
            if ($block['block_type'] === 'text' && empty($preview)) {
                $text = strip_tags($block['content']);
                if (strlen($text) > 150) {
                    $preview = substr($text, 0, 150);
                    $lastSpace = strrpos($preview, ' ');
                    $preview = substr($text, 0, $lastSpace) . '...';
                } else {
                    $preview = $text;
                }
            }
        }
        return ['thumbnail' => $thumbnail, 'preview' => $preview];
    }

    public function approveArticle($articleId, $approve = true) {
        try {
            // Simplified query without approval_date for now
            $stmt = $this->pdo->prepare("
                UPDATE articles 
                SET approved = :approved,
                    modified_at = NOW()
                WHERE id = :id
            ");
            
            $result = $stmt->execute([
                ':approved' => $approve ? 1 : 0,
                ':id' => $articleId
            ]);
            
            if ($result && $approve) {
                // Update approval date in a separate query
                $stmt = $this->pdo->prepare("
                    UPDATE articles 
                    SET approval_date = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([':id' => $articleId]);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error " . ($approve ? "approving" : "unapproving") . " article: " . $e->getMessage());
            return false;
        }
    }

    public function deleteArticle($articleId) {
        try {
            $this->pdo->beginTransaction();
            
            // Delete related records first
            $tables = ['article_likes', 'article_comments', 'article_blocks'];
            foreach ($tables as $table) {
                $stmt = $this->pdo->prepare("DELETE FROM $table WHERE article_id = ?");
                $stmt->execute([$articleId]);
            }
            
            // Delete the article
            $stmt = $this->pdo->prepare("DELETE FROM articles WHERE id = ?");
            $result = $stmt->execute([$articleId]);
            
            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error deleting article: " . $e->getMessage());
            return false;
        }
    }

    public function getArticle($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT a.*, u.name as author_name, 
                       e.name as last_editor_name
                FROM articles a 
                JOIN user u ON a.user_id = u.usn
                LEFT JOIN user e ON a.last_editor_id = e.usn
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting article: " . $e->getMessage());
            return false;
        }
    }

    public function createEditDraft($articleId, $userId, $title, $blocks) {
        try {
            $this->pdo->beginTransaction();
            
            // Create draft entry
            $stmt = $this->pdo->prepare("
                INSERT INTO article_drafts (original_article_id, user_id, title, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$articleId, $userId, $title]);
            $draftId = $this->pdo->lastInsertId();
            
            // Add draft blocks
            $stmt = $this->pdo->prepare("
                INSERT INTO article_draft_blocks (draft_id, block_type, content, sort_order)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($blocks as $index => $block) {
                $stmt->execute([$draftId, $block['type'], $block['content'], $index]);
            }
            
            $this->pdo->commit();
            return $draftId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error creating draft: " . $e->getMessage());
            return false;
        }
    }

    public function approveDraft($draftId) {
        try {
            error_log("Starting draft approval for ID: " . $draftId);
            $this->pdo->beginTransaction();
            
            // Get draft data with blocks
            $stmt = $this->pdo->prepare("
                SELECT d.*, db.block_type, db.content, db.sort_order 
                FROM article_drafts d
                LEFT JOIN article_draft_blocks db ON d.id = db.draft_id
                WHERE d.id = ?
                ORDER BY db.sort_order
            ");
            $stmt->execute([$draftId]);
            $draftData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($draftData)) {
                error_log("Draft not found with ID: " . $draftId);
                throw new Exception("Draft not found");
            }
            
            $draft = $draftData[0]; // First row contains draft info
            error_log("Processing draft: " . json_encode($draft));
            
            // Update original article
            $stmt = $this->pdo->prepare("
                UPDATE articles 
                SET title = ?, 
                    modified_at = NOW(),
                    last_editor_id = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $draft['title'],
                $draft['user_id'],
                $draft['original_article_id']
            ]);
            
            // Delete existing blocks
            $stmt = $this->pdo->prepare("DELETE FROM article_blocks WHERE article_id = ?");
            $stmt->execute([$draft['original_article_id']]);
            
            // Insert new blocks from draft data
            $stmt = $this->pdo->prepare("
                INSERT INTO article_blocks (article_id, block_type, content, sort_order)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($draftData as $block) {
                if ($block['block_type'] !== null) { // Skip the first row if it doesn't have block data
                    $stmt->execute([
                        $draft['original_article_id'],
                        $block['block_type'],
                        $block['content'],
                        $block['sort_order']
                    ]);
                }
            }
            
            // Clean up: Delete draft and its blocks
            $stmt = $this->pdo->prepare("DELETE FROM article_draft_blocks WHERE draft_id = ?");
            $stmt->execute([$draftId]);
            
            $stmt = $this->pdo->prepare("DELETE FROM article_drafts WHERE id = ?");
            $stmt->execute([$draftId]);
            
            $this->pdo->commit();
            error_log("Draft approval completed successfully");
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error approving draft: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
    }

    public function deleteDraft($draftId) {
        try {
            error_log("Starting draft deletion for ID: " . $draftId);
            
            // Also delete draft blocks
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("DELETE FROM article_draft_blocks WHERE draft_id = ?");
            $stmt->execute([$draftId]);
            
            $stmt = $this->pdo->prepare("DELETE FROM article_drafts WHERE id = ?");
            $result = $stmt->execute([$draftId]);
            
            $this->pdo->commit();
            error_log("Draft deletion completed successfully");
            return $result;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error deleting draft: " . $e->getMessage());
            return false;
        }
    }

    public function updateArticle($articleId, $title, $blocks, $editorId) {
        try {
            $this->pdo->beginTransaction();
            
            // Update article title and editor info
            $stmt = $this->pdo->prepare("
                UPDATE articles 
                SET title = ?, 
                    last_editor_id = ?,
                    modified_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $editorId, $articleId]);
            
            // Delete existing blocks
            $stmt = $this->pdo->prepare("
                DELETE FROM article_blocks 
                WHERE article_id = ?
            ");
            $stmt->execute([$articleId]);
            
            // Insert new blocks
            $stmt = $this->pdo->prepare("
                INSERT INTO article_blocks (article_id, block_type, content, sort_order) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($blocks as $index => $block) {
                $stmt->execute([
                    $articleId,
                    $block['type'],
                    $block['content'],
                    $index
                ]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error updating article: " . $e->getMessage());
            return false;
        }
    }

    public function getPendingDrafts() {
        try {
            $sql = "SELECT 
                d.*, a.title as original_title, u.name as editor_name,
                a.user_id as original_author_id
                FROM article_drafts d
                JOIN articles a ON d.original_article_id = a.id
                JOIN user u ON d.user_id = u.usn
                ORDER BY d.created_at DESC";
                
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting pending drafts: " . $e->getMessage());
            return [];
        }
    }

    public function hasPendingDraft($articleId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM article_drafts 
                WHERE original_article_id = ? AND user_id = ?
            ");
            $stmt->execute([$articleId, $userId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking pending draft: " . $e->getMessage());
            return false;
        }
    }
}