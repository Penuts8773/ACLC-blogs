// Comment form submission
        document.getElementById('comment-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('../backend/comment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Add new comment to the DOM
                    const commentsDiv = document.getElementById('comments');
                    const noComments = commentsDiv.querySelector('.no-comments');
                    
                    if (noComments) {
                        noComments.remove();
                    }
                    
                    const newComment = document.createElement('div');
                    newComment.className = 'comment';
                    newComment.innerHTML = `
                        <strong>${result.name}</strong>
                        <small>Just now</small>
                        <p class="comment-content">${result.content.replace(/\n/g, '<br>')}</p>
                    `;
                    
                    // Insert after the "Comments" heading
                    const heading = commentsDiv.querySelector('h3');
                    heading.insertAdjacentElement('afterend', newComment);
                    
                    // Clear form
                    e.target.reset();
                } else {
                    alert('Error posting comment: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error posting comment. Please try again.');
            }
        });

        const mainCommentTextarea = document.querySelector('#comment-form textarea');
        if (mainCommentTextarea) {
            mainCommentTextarea.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    mainCommentTextarea.form.requestSubmit(); // triggers your submit listener above
                }   
            });
        }

        // Comment editing functions
        function editComment(id) {
            const commentDiv = document.getElementById(`comment-${id}`);
            const content = commentDiv.querySelector('.comment-content');
            const form = commentDiv.querySelector('.edit-form');
            const actions = commentDiv.querySelector('.comment-actions');
            
            content.style.display = 'none';
            actions.style.display = 'none';
            form.style.display = 'block';
            
            const textarea = form.querySelector('textarea');
            textarea.focus();
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        }

        function cancelEdit(id) {
            const commentDiv = document.getElementById(`comment-${id}`);
            const content = commentDiv.querySelector('.comment-content');
            const form = commentDiv.querySelector('.edit-form');
            const actions = commentDiv.querySelector('.comment-actions');
            
            content.style.display = 'block';
            actions.style.display = 'block';
            form.style.display = 'none';
        }

        // Handle edit form submissions
        document.querySelectorAll('.edit-form').forEach(form => {
            const textarea = form.querySelector('textarea');

            // 🔑 Enter = submit, Shift+Enter = newline
            textarea.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    form.requestSubmit(); // will trigger the submit handler below
                }
            });
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const commentDiv = this.closest('.comment');
                const id = commentDiv.id.split('-')[1];
                const content = this.querySelector('textarea').value;
                
                showConfirmModal('Save changes to this comment?', async () => {
                    try {
                        const response = await fetch('../backend/comment.php', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                comment_id: parseInt(id),
                                content: content
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            const contentElement = commentDiv.querySelector('.comment-content');
                            contentElement.innerHTML = content.replace(/\n/g, '<br>');
                            cancelEdit(id);
                            
                            // Add edit indicator if not present
                            if (!commentDiv.querySelector('.edit-indicator')) {
                                const timeElement = commentDiv.querySelector('small');
                                timeElement.innerHTML += ' <span class="edit-indicator">(edited)</span>';
                            }
                        } else {
                            alert('Error updating comment: ' + result.error);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error updating comment. Please try again.');
                    }
                });
            });

        });

        // Delete comment function
        async function deleteComment(id) {
            showConfirmModal('Are you sure you want to delete this comment?', async () => {
                try {
                    const response = await fetch('../backend/comment.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ comment_id: parseInt(id) })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        document.getElementById(`comment-${id}`).remove();
                        
                        // Show "no comments" message if all comments are deleted
                        const commentsDiv = document.getElementById('comments');
                        const remainingComments = commentsDiv.querySelectorAll('.comment');
                        
                        if (remainingComments.length === 0) {
                            const heading = commentsDiv.querySelector('h3');
                            heading.insertAdjacentHTML('afterend', '<p class="no-comments">No comments yet. Be the first to comment!</p>');
                        }
                    } else {
                        alert('Error deleting comment: ' + result.error);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error deleting comment. Please try again.');
                }
            });
        }

        // Load all comments function
        async function loadAllComments() {
            const urlParams = new URLSearchParams(window.location.search);
            const articleId = urlParams.get('id');
            
            try {
                const response = await fetch(`../backend/comment.php?article_id=${articleId}&all=true`);
                const result = await response.json();
                
                if (result.success) {
                    const container = document.getElementById('comments-container');
                    const button = document.getElementById('show-all-comments-btn');
                    
                    // Replace container content with all comments
                    container.innerHTML = '';
                    result.comments.forEach(comment => {
                        const commentHtml = createCommentHtml(comment);
                        container.insertAdjacentHTML('beforeend', commentHtml);
                    });
                    
                    // Remove the button
                    if (button) {
                        button.remove();
                    }
                    
                    // Re-attach event listeners to edit forms
                    attachEditFormListeners();
                } else {
                    alert('Error loading comments: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error loading comments. Please try again.');
            }
        }

        // Helper function to create comment HTML
        function createCommentHtml(comment) {
            const isOwner = comment.is_owner || false;
            const isAdminOrMod = comment.can_restrict || false;
            
            let html = `
                <div class='comment' id='comment-${comment.id}'>
                    <div class="comment-user">
                        <img src="assets/images/user-icon.png" alt="User Icon" class="user-icon">
                        <strong>${escapeHtml(comment.name)}</strong>
                    </div>
                    <p class='comment-content'>${escapeHtml(comment.content).replace(/\n/g, '<br>')}</p>
                    <div class="comment-meta">
                        <small>
                            ${escapeHtml(comment.created_at)}
                            ${comment.modified_at ? '<span class="edit-indicator">(edited)</span>' : ''}
                        </small>
            `;
            
            if (isOwner) {
                html += `
                        <div class='comment-actions'>
                            <a class='comment-edit' onclick='editComment(${comment.id})'>Edit</a>
                            <a class='comment-edit' onclick='deleteComment(${comment.id})'>Delete</a>
                        </div>
                        <form class='edit-form' style='display:none;'>
                            <textarea required>${escapeHtml(comment.content)}</textarea>
                            <div class="form-buttons">
                                <button type='submit' class="save-btn action-btn">Save</button>
                                <button type='button' class="cancel-btn action-btn" onclick='cancelEdit(${comment.id})'>Cancel</button>
                            </div>
                        </form>
                `;
            }
            
            if (isAdminOrMod && ![1, 2, 5].includes(comment.user_privilege)) {
                html += `
                        <form method="post" action="restrictUser.php" style="display:inline;">
                            <input type="hidden" name="user_id" value="${escapeHtml(comment.user_id)}">
                            <button type="submit" class="restrict-btn" onclick="return confirm('Restrict this user from commenting?');">
                                Restrict User
                            </button>
                        </form>
                `;
            }
            
            html += `
                    </div>
                </div>
            `;
            
            return html;
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Re-attach event listeners to edit forms
        function attachEditFormListeners() {
            document.querySelectorAll('.edit-form').forEach(form => {
                const textarea = form.querySelector('textarea');

                textarea.addEventListener('keydown', e => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        form.requestSubmit();
                    }
                });
                
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const commentDiv = this.closest('.comment');
                    const id = commentDiv.id.split('-')[1];
                    const content = this.querySelector('textarea').value;
                    
                    showConfirmModal('Save changes to this comment?', async () => {
                        try {
                            const response = await fetch('../backend/comment.php', {
                                method: 'PUT',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    comment_id: parseInt(id),
                                    content: content
                                })
                            });
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                const contentElement = commentDiv.querySelector('.comment-content');
                                contentElement.innerHTML = content.replace(/\n/g, '<br>');
                                cancelEdit(id);
                                
                                if (!commentDiv.querySelector('.edit-indicator')) {
                                    const timeElement = commentDiv.querySelector('small');
                                    timeElement.innerHTML += ' <span class="edit-indicator">(edited)</span>';
                                }
                            } else {
                                alert('Error updating comment: ' + result.error);
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Error updating comment. Please try again.');
                        }
                    });
                });
            });
        }