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
            const commentsDiv = document.getElementById('comments-container');
            const noComments = commentsDiv.querySelector('.no-comments');
            
            if (noComments) {
                noComments.remove();
            }
            
            const newComment = document.createElement('div');
            newComment.className = 'comment';
            newComment.innerHTML = `
                <div class="comment-user">
                    <img src="assets/images/user-icon.png" alt="User Icon" class="user-icon">
                    <strong>${escapeHtml(result.name)}</strong>
                </div>
                <p class="comment-content">${escapeHtml(result.content).replace(/\n/g, '<br>')}</p>
                <div class="comment-meta">
                    <small>Just now</small>
                </div>
            `;
            
            // Insert at the beginning of comments container
            commentsDiv.insertBefore(newComment, commentsDiv.firstChild);
            
            // Clear form
            e.target.reset();
            
            // Update comment count
            const countHeading = document.querySelector('#comments h3');
            if (countHeading) {
                const currentCount = parseInt(countHeading.textContent.match(/\d+/)?.[0] || 0);
                countHeading.textContent = `Comments (${currentCount + 1})`;
            }
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
            mainCommentTextarea.form.requestSubmit();
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
    if (actions) actions.style.display = 'none';
    form.style.display = 'block';
    
    const textarea = form.querySelector('textarea');
    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
}

// Cancel edit: show content & actions, hide form
function cancelEdit(id) {
    const commentDiv = document.getElementById(`comment-${id}`);
    if (!commentDiv) return;
    const content = commentDiv.querySelector('.comment-content');
    const form = commentDiv.querySelector('.edit-form');
    const actions = commentDiv.querySelector('.comment-actions');

    if (form) form.style.display = 'none';
    if (content) content.style.display = '';
    if (actions) actions.style.display = '';
}

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
                const commentsContainer = document.getElementById('comments-container');
                const remainingComments = commentsContainer.querySelectorAll('.comment');
                
                if (remainingComments.length === 0) {
                    commentsContainer.innerHTML = '<p class="no-comments">No comments yet. Be the first to comment!</p>';
                }
                
                // Update comment count
                const countHeading = document.querySelector('#comments h3');
                if (countHeading) {
                    const currentCount = parseInt(countHeading.textContent.match(/\d+/)?.[0] || 0);
                    countHeading.textContent = `Comments (${Math.max(0, currentCount - 1)})`;
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

// Toggle "show all comments"
async function loadAllComments() {
    const btn = document.getElementById('show-all-comments-btn');
    const container = document.getElementById('comments-container');

    // Get article ID
    let articleId =
        document.querySelector('input[name="article_id"]')?.value ||
        btn?.dataset?.articleId ||
        container?.dataset?.articleId ||
        (new URLSearchParams(window.location.search).get('id'));

    if (typeof articleId === 'string') {
        articleId = articleId.trim();
    }
    
    if (!btn || !container || !articleId) {
        console.error('Missing button/container/article id', { btn, container, articleId });
        return;
    }

    // If already expanded, collapse back to original preview
    if (btn.dataset.expanded === '1') {
        if (btn._originalHTML) {
            container.innerHTML = btn._originalHTML;
            attachEditFormListeners();
        }
        
        btn.dataset.expanded = '0';
        delete btn._originalHTML;
        btn.textContent = 'Show all comments';
        return;
    }

    // Save original HTML before loading all comments
    btn._originalHTML = container.innerHTML;
    btn.disabled = true;
    btn.textContent = 'Loading…';

    try {
        const url = `../backend/comment.php?article_id=${encodeURIComponent(articleId)}`;
        const res = await fetch(url, { credentials: 'same-origin' });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            const txt = await res.text();
            console.error('Unexpected non-JSON response:', txt);
            throw new Error('Unexpected server response');
        }

        const json = await res.json();
        if (!json.success) {
            throw new Error(json.error || 'Failed to load comments');
        }

        // Build HTML for all comments
        let html = '';
        if (json.comments && json.comments.length > 0) {
            json.comments.forEach(c => {
                html += createCommentHtml(c);
            });
        } else {
            html = '<p class="no-comments">No comments yet. Be the first to comment!</p>';
        }

        container.innerHTML = html;
        attachEditFormListeners();

        btn.dataset.expanded = '1';
        btn.disabled = false;
        btn.textContent = 'Hide comments';
        
    } catch (err) {
        console.error('loadAllComments error', err);
        alert('Failed to load comments: ' + err.message);
        
        // Restore original HTML on error
        if (btn._originalHTML) {
            container.innerHTML = btn._originalHTML;
            attachEditFormListeners();
            delete btn._originalHTML;
        }
        
        btn.disabled = false;
        btn.textContent = 'Show all comments';
    }
}

// Expose globally for inline onclick
window.loadAllComments = loadAllComments;
window.editComment = editComment;
window.cancelEdit = cancelEdit;
window.deleteComment = deleteComment;

// Ensure button listener is attached
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('show-all-comments-btn');
    if (btn) {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            await loadAllComments();
        });
    }
    
    // Attach listeners to initial comments
    attachEditFormListeners();
});

// Helper function to create comment HTML
function createCommentHtml(comment) {
    const isOwner = comment.is_owner || false;
    const isAdminOrMod = comment.can_restrict || false;
    const isHidden = comment.hidden || false;
    const canSeeHiddenContent = isOwner || isAdminOrMod;
    const commentUserIsAdminOrMod = [1, 3].includes(comment.user_privilege || 0);
    
    let html = `
        <div class='comment ${isHidden ? 'hidden-comment' : ''}' id='comment-${comment.id}'>
            <div class="comment-user">
                <div class="user-icon-container">
                    <img src="assets/images/user-icon.png" alt="User Icon" class="user-icon">`;
    
    if (commentUserIsAdminOrMod) {
        html += `<img src="assets/images/verified.gif" alt="Admin/Mod Badge" class="badge-overlay">`;
    }
    
    html += `
                </div>
                <strong>${escapeHtml(comment.name)}</strong>
            </div>`;

    if (isHidden && !canSeeHiddenContent) {
        html += `<p class='comment-content hidden-content'>[This comment has been hidden by a moderator]</p>`;
    } else {
        html += `<p class='comment-content'>${escapeHtml(comment.content).replace(/\n/g, '<br>')}</p>`;
    }

    // Edit form (for owners)
    if (isOwner) {
        html += `
            <form class='edit-form' style='display:none;' data-comment-id='${comment.id}'>
                <textarea name="edit_comment" class="edit-textarea" rows="4" required>${escapeHtml(comment.content)}</textarea>
                <div class="form-buttons">
                    <button type='submit' class="save-btn action-btn">Save</button>
                    <button type='button' class="cancel-btn action-btn" onclick='cancelEdit(${comment.id})'>Cancel</button>
                </div>
            </form>
        `;
    }

    // Comment meta and actions
    html += `
            <div class="comment-meta">
                <small>
                    ${escapeHtml(comment.created_at)}
                    ${comment.modified_at ? '<span class="edit-indicator">(edited)</span>' : ''}
                    ${isHidden ? '<span class="hidden-indicator">(hidden)</span>' : ''}
                </small>
    `;

    if (isOwner) {
        html += `
                <div class='comment-actions'>
                    <a class='comment-edit' onclick='editComment(${comment.id})'>Edit</a>
                    <a class='comment-edit' onclick='deleteComment(${comment.id})'>Delete</a>
                </div>
        `;
    }

    if (isAdminOrMod && ![1, 2].includes(comment.user_privilege || 0)) {
        html += `
                <div class="admin-actions">
                    <form method="post" action="restrictUser.php" style="display:inline;">
                        <input type="hidden" name="user_id" value="${escapeHtml(comment.user_id)}">
                        <button type="submit" class="restrict-btn" onclick="return confirm('Restrict this user from commenting?');">Restrict User</button>
                    </form>
                    <button class="hide-btn" onclick="toggleCommentVisibility(${comment.id}, ${isHidden ? 'false' : 'true'})">
                        ${isHidden ? 'Unhide' : 'Hide'} Comment
                    </button>
                </div>
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
    if (text === null || text === undefined) return '';
    text = String(text);
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
        if (!textarea) return;

        // Normalize textarea
        if (!textarea.classList.contains('edit-textarea')) {
            textarea.classList.add('edit-textarea');
        }
        if (!textarea.getAttribute('rows')) {
            textarea.setAttribute('rows', '4');
        }

        // Cancel button
        const cancelBtn = form.querySelector('.cancel-btn');
        if (cancelBtn && !cancelBtn._listenerAttached) {
            cancelBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = form.getAttribute('data-comment-id');
                if (id) cancelEdit(id);
            });
            cancelBtn._listenerAttached = true;
        }

        // Submit handler
        if (!form._submitAttached) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const cid = form.getAttribute('data-comment-id');
                const newContent = textarea.value.trim();
                if (!cid || newContent.length === 0) return;

                try {
                    const res = await fetch('../backend/comment.php', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ comment_id: parseInt(cid), content: newContent })
                    });
                    const json = await res.json();
                    if (json.success) {
                        const commentDiv = document.getElementById(`comment-${cid}`);
                        if (commentDiv) {
                            const contentP = commentDiv.querySelector('.comment-content');
                            contentP.innerHTML = escapeHtml(newContent).replace(/\n/g, '<br>');
                            cancelEdit(cid);
                            
                            // Add edit indicator if not present
                            const metaSmall = commentDiv.querySelector('.comment-meta small');
                            if (metaSmall && !metaSmall.querySelector('.edit-indicator')) {
                                const editIndicator = document.createElement('span');
                                editIndicator.className = 'edit-indicator';
                                editIndicator.textContent = '(edited)';
                                metaSmall.appendChild(document.createTextNode(' '));
                                metaSmall.appendChild(editIndicator);
                            }
                        }
                    } else {
                        alert(json.error || 'Failed to update comment');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Failed to update comment');
                }
            });
            form._submitAttached = true;
        }

        // Enter to submit, Shift+Enter for newline
        if (!textarea._keydownAttached) {
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    form.requestSubmit();
                }
            });
            textarea._keydownAttached = true;
        }
    });
}

// Toggle comment visibility (hide/unhide)
async function toggleCommentVisibility(commentId, hide) {
    try {
        const hideBool = (hide === true || hide === 'true' || hide === 1 || hide === '1');
        const response = await fetch('../backend/comment.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comment_id: commentId, hidden: hideBool ? 1 : 0 })
        });
        const result = await response.json();
        if (result.success) {
            // Reload page to show updated state
            window.location.reload();
        } else {
            alert('Error: ' + (result.error || 'Failed to update comment'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error updating comment visibility');
    }
}

window.toggleCommentVisibility = toggleCommentVisibility;