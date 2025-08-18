
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.like-btn').forEach(btn => {
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