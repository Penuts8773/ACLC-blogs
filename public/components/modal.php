<div id="confirmModal" class="modal-overlay">
    <div class="modal">
        <p id="modalMessage"></p>
        <div class="modal-buttons">
            <button id="confirmBtn" class="confirm-btn">Yes</button>
            <button type="button" onclick="closeModal()" class="cancel-btn">No</button>
        </div>
    </div>
</div>

<script>
function showConfirmModal(message, callback) {
    const modal = document.getElementById('confirmModal');
    const messageEl = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('confirmBtn');
    
    if (!modal || !messageEl || !confirmBtn) {
        console.error('Modal elements not found');
        return;
    }
    
    messageEl.textContent = message;
    modal.classList.add('show');
    modal.querySelector('.modal').classList.add('active');
    
    // Remove any existing click handlers
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    // Add new click handler
    newConfirmBtn.onclick = () => {
        closeModal();
        callback();
    };
}

function closeModal() {
    const modal = document.getElementById('confirmModal');
    if (!modal) return;
    
    const modalContent = modal.querySelector('.modal');
    if (modalContent) {
        modalContent.classList.remove('active');
    }
    
    setTimeout(() => {
        modal.classList.remove('show');
    }, 300);
}

// Close modal when clicking outside
document.getElementById('confirmModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('confirmModal')) {
        closeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('confirmModal').classList.contains('show')) {
        closeModal();
    }
});
</script>