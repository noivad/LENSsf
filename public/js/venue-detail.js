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

// Show edit button if user is owner/creator (mock implementation)
const currentUser = 'You';
const venueOwner = 'You';
if (currentUser === venueOwner) {
    document.getElementById('edit-venue-btn').style.display = 'inline-block';
}

// Cache for unsaved changes
let cachedChanges = {
    name: venueData.name,
    address: venueData.address,
    description: venueData.description,
    tags: venueData.tags.join(', ')
};

document.getElementById('edit-venue-btn')?.addEventListener('click', function() {
    document.getElementById('venue-display-mode').style.display = 'none';
    document.getElementById('venue-edit-mode').style.display = 'block';
    this.style.display = 'none';
    document.getElementById('save-venue-btn').style.display = 'inline-block';
    document.getElementById('cancel-edit-btn').style.display = 'inline-block';
});

document.getElementById('cancel-edit-btn')?.addEventListener('click', function() {
    document.getElementById('edit-venue-name').value = cachedChanges.name;
    document.getElementById('edit-venue-address').value = cachedChanges.address;
    document.getElementById('edit-venue-description').value = cachedChanges.description;
    document.getElementById('edit-venue-tags').value = cachedChanges.tags;
    
    document.getElementById('venue-edit-mode').style.display = 'none';
    document.getElementById('venue-display-mode').style.display = 'block';
    this.style.display = 'none';
    document.getElementById('save-venue-btn').style.display = 'none';
    document.getElementById('edit-venue-btn').style.display = 'inline-block';
});

document.getElementById('save-venue-btn')?.addEventListener('click', function() {
    cachedChanges.name = document.getElementById('edit-venue-name').value;
    cachedChanges.address = document.getElementById('edit-venue-address').value;
    cachedChanges.description = document.getElementById('edit-venue-description').value;
    cachedChanges.tags = document.getElementById('edit-venue-tags').value;
    
    alert('Changes saved! (In production, this would save to the database)');
    
    document.getElementById('venue-edit-mode').style.display = 'none';
    document.getElementById('venue-display-mode').style.display = 'block';
    this.style.display = 'none';
    document.getElementById('cancel-edit-btn').style.display = 'none';
    document.getElementById('edit-venue-btn').style.display = 'inline-block';
});

// Popover mini-map on address hover
let popoverMap = null;
let popoverMapElement = null;

const addressHover = document.getElementById('venue-address-hover');
if (addressHover) {
    addressHover.addEventListener('mouseenter', function() {
        if (!popoverMapElement) {
            popoverMapElement = document.createElement('div');
            popoverMapElement.className = 'venue-address-popover-map';
            popoverMapElement.style.position = 'absolute';
            popoverMapElement.style.width = '300px';
            popoverMapElement.style.height = '200px';
            popoverMapElement.style.zIndex = '1000';
            popoverMapElement.style.border = '2px solid var(--border-color)';
            popoverMapElement.style.borderRadius = '8px';
            popoverMapElement.style.boxShadow = 'var(--shadow-strong)';
            popoverMapElement.style.background = 'var(--bg-secondary)';
            
            const rect = addressHover.getBoundingClientRect();
            popoverMapElement.style.left = rect.left + 'px';
            popoverMapElement.style.top = (rect.bottom + 10) + 'px';
            
            document.body.appendChild(popoverMapElement);
            
            setTimeout(() => {
                popoverMap = L.map(popoverMapElement).setView([venueData.lat, venueData.lng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OSM'
                }).addTo(popoverMap);
                L.marker([venueData.lat, venueData.lng]).addTo(popoverMap);
            }, 100);
        }
    });
    
    addressHover.addEventListener('mouseleave', function(e) {
        setTimeout(() => {
            if (popoverMapElement && !popoverMapElement.matches(':hover')) {
                if (popoverMap) {
                    popoverMap.remove();
                    popoverMap = null;
                }
                if (popoverMapElement) {
                    popoverMapElement.remove();
                    popoverMapElement = null;
                }
            }
        }, 300);
    });
    
    document.addEventListener('mouseover', function(e) {
        if (popoverMapElement && !popoverMapElement.contains(e.target) && e.target !== addressHover && !addressHover.contains(e.target)) {
            if (popoverMap) {
                popoverMap.remove();
                popoverMap = null;
            }
            if (popoverMapElement) {
                popoverMapElement.remove();
                popoverMapElement = null;
            }
        }
    });
}
