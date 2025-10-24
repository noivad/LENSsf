document.addEventListener('DOMContentLoaded', function() {
    initializeEventMap();
});

async function geocode(address) {
    const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(address);
    try {
        const response = await fetch(url);
        if (!response.ok) return null;
        const data = await response.json();
        if (data && data[0]) {
            return {
                lat: parseFloat(data[0].lat),
                lng: parseFloat(data[0].lon)
            };
        }
    } catch (error) {
        console.error('Geocoding error:', error);
        return null;
    }
    return null;
}

async function initializeEventMap() {
    const mapElement = document.getElementById('event-map');
    if (!mapElement || typeof L === 'undefined') {
        return;
    }

    const locationData = window.__EVENT_LOCATION__ || {};
    if (!locationData.address && !locationData.name) {
        return;
    }

    const fullAddress = [
        locationData.name,
        locationData.address,
        locationData.city,
        locationData.state
    ].filter(Boolean).join(', ');

    const coords = await geocode(fullAddress);
    const position = coords || { lat: 37.773972, lng: -122.431297 };

    const map = L.map(mapElement).setView([position.lat, position.lng], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    L.marker([position.lat, position.lng])
        .addTo(map)
        .bindPopup(locationData.name || 'Event Location')
        .openPopup();
}
