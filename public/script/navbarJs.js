function confirmLogout() {
    showConfirmModal('Are you sure you want to logout?', () => {
        window.location.href = 'logout.php';
    });
}
const burgerBtn = document.getElementById('burger-btn');
const dropdownMenu = document.getElementById('dropdown-menu');

burgerBtn.addEventListener('click', () => {
  dropdownMenu.classList.toggle('show');
});

// Close dropdown if clicking outside of it
window.addEventListener('click', (event) => {
  if (!burgerBtn.contains(event.target) && !dropdownMenu.contains(event.target)) {
    dropdownMenu.classList.remove('show');
  }
});

function confirmNavigation(url) {
    showConfirmModal('Do you want to create a new article?', () => {
        window.location.href = url;
    });
}