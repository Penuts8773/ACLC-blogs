document.querySelector('select[name="filter"]').addEventListener('change', function() {
    var dateInput = document.getElementById('date-input');
    if(this.value === 'date') {
        dateInput.style.display = '';
    } else {
        dateInput.style.display = 'none';
    }
});
function loadArticle(articleId) {
    fetch(`pages/article.php?id=${articleId}`)
        .then(res => res.text())
        .then(html => {
            document.getElementById('app').innerHTML = html;
            history.pushState({}, '', `?page=article&id=${articleId}`);
            
            // Load article JS if needed
            const script = document.createElement('script');
            script.src = 'assets/js/article.js';
            document.body.appendChild(script);
        });
}

// Filter form handling
document.getElementById('filter-select').addEventListener('change', function() {
    if (this.value === 'date') {
        document.getElementById('date-input').style.display = 'block';
    } else {
        document.getElementById('date-input').style.display = 'none';
    }
});

// Form submission handling
document.querySelector('.home-filters form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const params = new URLSearchParams(formData).toString();
    loadPage('home', params);
});