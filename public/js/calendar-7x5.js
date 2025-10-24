function toggleTheme() {
    const body = document.body;
    const currentTheme = body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    body.setAttribute('data-theme', newTheme);
    
    const themeIcon = document.getElementById('theme-icon');
    themeIcon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
    
    localStorage.setItem('lens-theme', newTheme);
}

function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('active');
}

document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const avatar = document.querySelector('.user-avatar');
    
    if (dropdown && avatar && !dropdown.contains(event.target) && !avatar.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

function previousMonth() {
    alert('Loading previous month...\nIn production, this would navigate to the previous month.');
}

function nextMonth() {
    alert('Loading next month...\nIn production, this would navigate to the next month.');
}

function shareEvent(eventName) {
    window.location.href = 'shared.html?event=' + encodeURIComponent(eventName);
}

function editEvent(eventName) {
    window.location.href = 'events-list-add-info.php?edit=' + encodeURIComponent(eventName);
}

let eventToDelete = '';

function confirmDelete(eventName) {
    eventToDelete = eventName;
    document.getElementById('eventToDelete').textContent = eventName;
    document.getElementById('deleteModal').classList.add('active');
}

function closeModal() {
    document.getElementById('deleteModal').classList.remove('active');
    eventToDelete = '';
}

function deleteEvent() {
    if (eventToDelete) {
        alert(`Event "${eventToDelete}" has been deleted.\nIn production, this would send a delete request to the server.`);
        closeModal();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('lens-theme');
    if (savedTheme) {
        document.body.setAttribute('data-theme', savedTheme);
        document.getElementById('theme-icon').textContent = savedTheme === 'dark' ? '☀️' : '🌙';
    }
    
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal();
            }
        });
    }
});
