const venueData = window.__VENUE_DATA__;

const map = L.map('venueMap').setView([venueData.lat, venueData.lng], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);
L.marker([venueData.lat, venueData.lng]).addTo(map)
    .bindPopup('<strong>' + venueData.name + '</strong><br>' + venueData.address)
    .openPopup();

function toggleTheme() {
    const body = document.body;
    const currentTheme = body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    body.setAttribute('data-theme', newTheme);
    document.getElementById('theme-icon').textContent = newTheme === 'dark' ? '☀️' : '🌙';
}

function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('active');
}

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('userDropdown');
    const avatar = document.querySelector('.user-avatar');
    if (!dropdown.contains(e.target) && e.target !== avatar) {
        dropdown.classList.remove('active');
    }
});
