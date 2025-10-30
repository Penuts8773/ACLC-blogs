function confirmView(articleId) {
	showConfirmModal('View this article?', () => {
		window.location.href = `article.php?id=${articleId}`;
	});
}

function confirmEdit(articleId) {
	showConfirmModal('Edit this article?', () => {
		window.location.href = `editArticle.php?id=${articleId}`;
	});
}

function showConfirmModal(message, callback) {
	const modal = document.getElementById('confirmModal');
	const messageEl = document.getElementById('modalMessage');
	const confirmBtn = document.getElementById('confirmBtn');
    
	messageEl.textContent = message;
	modal.classList.add('show');
	document.querySelector('.modal').classList.add('active');
    
	confirmBtn.onclick = () => {
		closeModal();
		callback();
	};
}

function closeModal() {
	const modal = document.getElementById('confirmModal');
	document.querySelector('.modal').classList.remove('active');
	setTimeout(() => {
		modal.classList.remove('show');
	}, 300);
}

function searchUsers() {
	const input = document.getElementById('userSearch');
	const filter = input.value.toLowerCase();
	const userList = document.getElementById('userList');
	const forms = userList.getElementsByTagName('form');

	if (filter.length > 0) {
		for (let form of forms) {
			const wrapper = form.closest('.user-wrapper') || form;
			const usn = form.querySelector('span').textContent || '';
			if (usn.toLowerCase().includes(filter)) {
				wrapper.style.display = "";
			} else {
				wrapper.style.display = "none";
			}
		}
	} else {
		let idx = 0;
		for (let form of forms) {
			const wrapper = form.closest('.user-wrapper') || form;
			wrapper.style.display = idx < 3 ? "" : "none";
			idx++;
		}
	}
}

function searchArticles(input) {
	const section = input.dataset.section;
	const filter = input.value.toLowerCase();
	const container = document.getElementById(section + '-articles');
	const articles = container.getElementsByClassName('article-wrapper');
	let visibleCount = 0;
	let totalMatches = 0;

	// Count total matches first
	for (let wrapper of articles) {
		const article = wrapper.querySelector('.article');
		const title = article.querySelector('h2').textContent;
		const author = article.querySelector('small').textContent;
        
		if (title.toLowerCase().includes(filter) || 
			author.toLowerCase().includes(filter)) {
			totalMatches++;
		}
	}

	// Now handle visibility
	for (let wrapper of articles) {
		const article = wrapper.querySelector('.article');
		const title = article.querySelector('h2').textContent;
		const author = article.querySelector('small').textContent;
        
		if (filter.length > 0) {
			// When searching, show/hide based on match
			if (title.toLowerCase().includes(filter) || 
				author.toLowerCase().includes(filter)) {
				wrapper.style.display = "";
				visibleCount++;
			} else {
				wrapper.style.display = "none";
			}
		} else {
			// When not searching, respect the initial limit
			const limit = section === 'pending' ? 4 : 3;
			wrapper.style.display = visibleCount < limit ? "" : "none";
			visibleCount++;
		}
	}

	// Update buttons visibility
	const showMoreBtn = container.parentElement.querySelector('.show-more');
	const showLessBtn = container.parentElement.querySelector('.show-less');
    
	if (showMoreBtn && showLessBtn) {
		if (filter.length > 0) {
			// Hide both buttons during search
			showMoreBtn.style.display = "none";
			showLessBtn.style.display = "none";
		} else {
			// Show appropriate buttons when not searching
			const limit = section === 'pending' ? 4 : 3;
			showMoreBtn.style.display = visibleCount < totalMatches ? "" : "none";
			showLessBtn.style.display = visibleCount > limit ? "" : "none";
		}
	}
}

function searchDrafts(input) {
	const filter = input.value.toLowerCase();
	const container = document.getElementById('drafts-articles');
	const drafts = container.getElementsByClassName('article-wrapper');
	let visibleCount = 0;
	let totalMatches = 0;

	// Count total matches first
	for (let wrapper of drafts) {
		const article = wrapper.querySelector('.article');
		const title = article.querySelector('h2').textContent;
		const author = article.querySelector('small').textContent;
        
		if (title.toLowerCase().includes(filter) || 
			author.toLowerCase().includes(filter)) {
			totalMatches++;
		}
	}

	// Now handle visibility
	for (let wrapper of drafts) {
		const article = wrapper.querySelector('.article');
		const title = article.querySelector('h2').textContent;
		const author = article.querySelector('small').textContent;
        
		if (filter.length > 0) {
			// When searching, show/hide based on match
			if (title.toLowerCase().includes(filter) || 
				author.toLowerCase().includes(filter)) {
				wrapper.style.display = "";
				visibleCount++;
			} else {
				wrapper.style.display = "none";
			}
		} else {
			// When not searching, respect the initial limit
			wrapper.style.display = visibleCount < 3 ? "" : "none";
			visibleCount++;
		}
	}

	// Update buttons visibility
	const showMoreBtn = container.parentElement.querySelector('.show-more');
	const showLessBtn = container.parentElement.querySelector('.show-less');
    
	if (showMoreBtn && showLessBtn) {
		if (filter.length > 0) {
			// Hide both buttons during search
			showMoreBtn.style.display = "none";
			showLessBtn.style.display = "none";
		} else {
			// Show appropriate buttons when not searching
			showMoreBtn.style.display = visibleCount < totalMatches ? "" : "none";
			showLessBtn.style.display = visibleCount > 3 ? "" : "none";
		}
	}
}

// Show more/less functionality
document.addEventListener('DOMContentLoaded', function() {
	document.querySelectorAll('.show-more').forEach(button => {
		button.addEventListener('click', function() {
			const section = this.dataset.section;
			const container = document.getElementById(section + '-articles');
			const hidden = container.querySelectorAll('.article-wrapper[style="display: none;"]');
            
			hidden.forEach(article => {
				article.style.display = "";
			});

			// Hide show more button and show the show less button
			this.style.display = "none";
			const showLessBtn = container.parentElement.querySelector('.show-less');
			if (showLessBtn) {
				showLessBtn.style.display = "";
			}
		});
	});

	document.querySelectorAll('.show-less').forEach(button => {
		button.addEventListener('click', function() {
			const section = this.dataset.section;
			const container = document.getElementById(section + '-articles');
			const articles = container.getElementsByClassName('article-wrapper');
            
			// Show only initial articles based on section
			const limit = section === 'pending' ? 4 : 3;
			Array.from(articles).forEach((article, index) => {
				article.style.display = index < limit ? "" : "none";
			});

			// Show show more button and hide show less button
			this.style.display = "none";
			const showMoreBtn = container.parentElement.querySelector('.show-more');
			if (showMoreBtn) {
				showMoreBtn.style.display = "";
			}
		});
	});

	// Update form submissions to use the new modal
	document.querySelectorAll('.user-form').forEach(form => {
		form.onsubmit = (e) => {
			e.preventDefault();
			showConfirmModal('Are you sure you want to change this user\'s privilege level?', () => {
				form.submit();
			});
			return false;
		};
	});
});

// Replace the confirmAction function
function confirmAction(form, action) {
	event.preventDefault();
    
	let message = '';
	switch(action) {
		case 'approve':
			message = 'Do you want to approve this article?';
			break;
		case 'unapprove':
			message = 'Do you want to unapprove this article?';
			break;
		case 'delete':
			message = 'Do you want to delete this article? This cannot be undone.';
			break;
		case 'approve_draft':
			message = 'Do you want to approve this edit request?';
			break;
		case 'reject_draft':
			message = 'Do you want to reject this edit request?';
			break;
		default:
			message = 'Do you want to proceed with this action?';
	}
    
	showConfirmModal(message, () => {
		form.submit();
	});
    
	return false;
}

// Update logout button
function logout() {
	showConfirmModal('Are you sure you want to logout?', () => {
		fetch('logout.php', {
			method: 'POST',
			credentials: 'same-origin'
		}).then(response => {
			if (response.redirected) {
				window.location.href = response.url;
			} else {
				window.location.href = 'login.php';
			}
		});
	});
}