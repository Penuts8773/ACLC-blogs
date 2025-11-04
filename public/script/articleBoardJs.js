
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.ab-like-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');

            fetch('../backend/like.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'article_id=' + encodeURIComponent(id),
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(data => {
                if (data.likes !== undefined) {
                    document.getElementById('likes-' + id).textContent = data.likes;

                    const btn = document.querySelector(`.like-btn[data-id="${id}"]`);
                    if (data.liked) {
                        btn.classList.add('liked');
                        btn.textContent = '👍 Liked';
                    } else {
                        btn.classList.remove('liked');
                        btn.textContent = '👍';
                    }
                }
            })

            .catch(error => console.error('Error:', error));
        });
    });
});

function searchBoardArticles() {
    const input = document.getElementById('articleSearch');
    const filter = input.value.toLowerCase();
    const articles = document.querySelectorAll('.ab-article');
    
    articles.forEach(article => {
        const title = article.querySelector('h2').textContent;
        const author = article.querySelector('small').textContent;
        
        if (title.toLowerCase().includes(filter) || 
            author.toLowerCase().includes(filter)) {
            article.style.display = "";
        } else {
            article.style.display = "none";
        }
    });
}
