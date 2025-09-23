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

    public function getApprovedArticlesByCategory($userId, $categoryId = null, $sort_by = 'latest', $order = 'desc') {
        $column = match($sort_by) {
            'likes' => 'likes',
            'comments' => 'comment_count',
            default => 'a.created_at'
        };

        $sql = "
            SELECT 
                a.id, a.user_id, a.title, a.created_at,
                u.name,
                (SELECT COUNT(*) FROM article_likes WHERE article_id = a.id) AS likes,
                COUNT(c.id) AS comment_count,
                EXISTS (
                    SELECT 1 FROM article_likes 
                    WHERE article_id = a.id AND user_id = :user_id
                ) AS liked_by_user
            FROM articles a
            JOIN user u ON a.user_id = u.usn
            LEFT JOIN article_comments c ON a.id = c.article_id
            WHERE a.approved = 1
        ";

        $params = ['user_id' => $userId];

        if ($categoryId) {
            $sql .= " AND a.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }

        $sql .= " GROUP BY a.id, u.name
            ORDER BY $column " . strtoupper($order);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getArticleBlocks($articleId) {
        $stmt = $this->pdo->prepare("SELECT * FROM article_blocks WHERE article_id = ? ORDER BY sort_order");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDraftBlocks($draftId) {
        $stmt = $this->pdo->prepare("SELECT * FROM article_draft_blocks WHERE draft_id = ? ORDER BY sort_order");
        $stmt->execute([$draftId]);
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

    public function processDraftContent($blocks) {
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
            
            $tables = ['article_likes', 'article_comments', 'article_blocks', 'article_tags'];
            foreach ($tables as $table) {
                $stmt = $this->pdo->prepare("DELETE FROM $table WHERE article_id = ?");
                $stmt->execute([$articleId]);
            }
            
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

    public function getDraft($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.*, u.name as editor_name, a.title as original_title,
                       a.user_id as original_author_id, au.name as original_author_name
                FROM article_drafts d
                JOIN user u ON d.user_id = u.usn
                JOIN articles a ON d.original_article_id = a.id
                JOIN user au ON a.user_id = au.usn
                WHERE d.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting draft: " . $e->getMessage());
            return false;
        }
    }

    private function updateArticleTags($articleId, $tagsInput) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM article_tags WHERE article_id = ?");
            $stmt->execute([$articleId]);
            
            if (!empty($tagsInput)) {
                $tags_array = array_map('trim', explode(',', $tagsInput));
                $tags_array = array_filter($tags_array);
                
                foreach ($tags_array as $tag_name) {
                    $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
                    $stmt->execute([$tag_name]);
                    $tag = $stmt->fetch();
                    
                    if (!$tag) {
                        $stmt = $this->pdo->prepare("INSERT INTO tags (name) VALUES (?)");
                        $stmt->execute([$tag_name]);
                        $tag_id = $this->pdo->lastInsertId();
                    } else {
                        $tag_id = $tag['id'];
                    }
                    
                    $stmt = $this->pdo->prepare("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)");
                    $stmt->execute([$articleId, $tag_id]);
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Error updating tags: " . $e->getMessage());
            return false;
        }
    }

    public function createEditDraft($articleId, $userId, $title, $blocks, $categoryId = null, $tagsInput = '') {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO article_drafts (original_article_id, user_id, title, category_id, tags, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$articleId, $userId, $title, $categoryId, $tagsInput]);
            $draftId = $this->pdo->lastInsertId();
            
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
            
            $draft = $draftData[0];
            
            $stmt = $this->pdo->prepare("
                UPDATE articles 
                SET title = ?, 
                    modified_at = NOW(),
                    last_editor_id = ?,
                    category_id = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $draft['title'],
                $draft['user_id'],
                $draft['category_id'],
                $draft['original_article_id']
            ]);
            
            if (!empty($draft['tags'])) {
                $this->updateArticleTags($draft['original_article_id'], $draft['tags']);
            }
            
            // Replace article blocks
            $stmt = $this->pdo->prepare("DELETE FROM article_blocks WHERE article_id = ?");
            $stmt->execute([$draft['original_article_id']]);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO article_blocks (article_id, block_type, content, sort_order)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($draftData as $block) {
                if ($block['block_type'] !== null) {
                    $stmt->execute([
                        $draft['original_article_id'],
                        $block['block_type'],
                        $block['content'],
                        $block['sort_order']
                    ]);
                }
            }
            
            // Clean up draft
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
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("DELETE FROM article_draft_blocks WHERE draft_id = ?");
            $stmt->execute([$draftId]);
            
            $stmt = $this->pdo->prepare("DELETE FROM article_drafts WHERE id = ?");
            $result = $stmt->execute([$draftId]);
            
            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error deleting draft: " . $e->getMessage());
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

    public function updateArticle($articleId, $title, $blocks, $editorId, $categoryId = null, $tagsInput = '') {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("
                UPDATE articles 
                SET title = ?, 
                    last_editor_id = ?,
                    modified_at = NOW(),
                    category_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $editorId, $categoryId, $articleId]);
            
            $this->updateArticleTags($articleId, $tagsInput);
            
            $stmt = $this->pdo->prepare("DELETE FROM article_blocks WHERE article_id = ?");
            $stmt->execute([$articleId]);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO article_blocks (article_id, block_type, content, sort_order) 
                VALUES (?, ?, ?, ?)
            ");
            foreach ($blocks as $index => $block) {
                $stmt->execute([$articleId, $block['type'], $block['content'], $index]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error updating article: " . $e->getMessage());
            return false;
        }
    }
}
