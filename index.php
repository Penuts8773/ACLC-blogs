<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Simple PHP SPA</title>
  <link rel="stylesheet" href="/assets/css/navbar.css">
</head>
<body>
  <?php if (isset($_SESSION['user'])): ?>
  <nav class="navbar">
    <div class="nav-logo">
        <img src="assets/images/aclc-logo.png" alt="ACLC Logo" id="aclc-logo">
        <strong>Blogs</strong>
    </div>
    <div>
        <a href="#" onclick="loadPage('home'); return false;">Home</a>
        <a href="#" onclick="loadPage('postBoard'); return false;">Post Board</a>
        <a href="api/logout.php" style="color:red; margin-left:20px;">Logout</a>
    </div>
  </nav>
  <?php endif; ?>

  <div id="app">Loading...</div>

  <script>
    function loadPage(page, query = '') {
      fetch(`pages/${page}.php${query ? '?' + query.slice(1) : ''}`)
        .then(res => res.text())
        .then(html => {
          document.getElementById('app').innerHTML = html;
          history.pushState({}, '', `?page=${page}${query}`);

          // Remove old scripts to prevent duplicates
          const oldScript = document.querySelector(`script[data-page]`);
          if (oldScript) oldScript.remove();

          // Load page-specific JS
          if (!html.includes('name="login-form"')) { // or some unique identifier
            const script = document.createElement('script');
            script.src = `assets/js/${page}.js`;
            script.dataset.page = page;
            script.onload = () => console.log(`${page}.js loaded`);
            document.body.appendChild(script);
          }
        });
    }


    window.addEventListener('popstate', () => {
      const params = new URLSearchParams(location.search);
      const page = params.get('page') || 'login';
      const query = location.search.replace(`?page=${page}`, '');
      loadPage(page, query);
    });


    // Load initial page
    const page = new URLSearchParams(location.search).get('page') || 'login';
    loadPage(page);
  </script>
  
</body>
</html>
