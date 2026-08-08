/* Common JS for PMB Siti Maryam */

// Toggle Dropdown Menu di Sidebar
function toggleDropdown(element) {
    var parent = element.parentElement;
    parent.classList.toggle('active');
}

// Toggle Mobile Sidebar
function toggleSidebar() {
    var sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

// Modal Konfirmasi Logout
function openLogoutModal() {
    var modal = document.getElementById('logoutModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeLogoutModal() {
    var modal = document.getElementById('logoutModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside modal box
window.addEventListener('click', function(e) {
    var logoutModal = document.getElementById('logoutModal');
    if (logoutModal && e.target === logoutModal) {
        closeLogoutModal();
    }
});
