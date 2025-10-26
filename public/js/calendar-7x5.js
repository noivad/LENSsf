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
    
    initializeStickyPopovers();
    initializeMiniMaps();
    initializeCalendarSearch();
    initializeEmptyDayClicks();
});

function initializeStickyPopovers() {
    const grid = document.querySelector('.calendar-grid');
    if (!grid) return;
    
    function closestDay(el) {
        return el.closest('.calendar-day');
    }
    
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('event-flag')) {
            const day = closestDay(e.target);
            if (day) day.classList.toggle('sticky');
        } else if (!e.target.closest('.event-popover')) {
            document.querySelectorAll('.calendar-day.sticky').forEach(d => d.classList.remove('sticky'));
        }
    });
}

function initializeMiniMaps() {
    const mapCache = new Map();
    
    async function geocode(q) {
        const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q);
        try {
            const r = await fetch(url);
            if (!r.ok) return null;
            const j = await r.json();
            if (j && j[0]) {
                return { lat: parseFloat(j[0].lat), lng: parseFloat(j[0].lon) };
            }
        } catch (_) {
            return null;
        }
        return null;
    }
    
    document.addEventListener('mouseenter', async (e) => {
        const loc = e.target.closest('.event-location');
        if (!loc) return;
        const item = loc.closest('.event-item');
        const mapEl = item && item.querySelector('.mini-map');
        if (!mapEl) return;
        mapEl.style.display = 'block';
        const q = loc.getAttribute('data-location');
        if (!q) return;
        const cacheKey = q;
        if (!mapCache.has(cacheKey)) {
            const pos = await geocode(q);
            mapCache.set(cacheKey, pos);
        }
        const pos = mapCache.get(cacheKey) || { lat: 37.773972, lng: -122.431297 };
        if (typeof L !== 'undefined') {
            if (!mapEl._map) {
                const m = L.map(mapEl).setView([pos.lat, pos.lng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(m);
                L.marker([pos.lat, pos.lng]).addTo(m);
                mapEl._map = m;
            } else {
                mapEl._map.setView([pos.lat, pos.lng], 14);
            }
        }
    }, true);
}

function initializeCalendarSearch() {
    const input = document.getElementById('calendar-search');
    if (!input) return;
    
    function applySearch() {
        const q = (input.value || '').trim();
        const isTag = q.startsWith('#');
        const tag = isTag ? q.slice(1).toLowerCase() : '';
        
        document.querySelectorAll('.calendar-day').forEach((day) => {
            const items = day.querySelectorAll('.event-item');
            let matchDay = false;
            items.forEach((it) => {
                const title = (it.getAttribute('data-title') || '').toLowerCase();
                const venue = (it.getAttribute('data-venue') || '').toLowerCase();
                const desc = (it.getAttribute('data-description') || '').toLowerCase();
                const tags = (it.getAttribute('data-tags') || '').toLowerCase();
                let match = false;
                if (isTag) {
                    match = tags.split(',').includes(tag);
                } else if (q) {
                    match = title.includes(q.toLowerCase()) || venue.includes(q.toLowerCase()) || desc.includes(q.toLowerCase());
                } else {
                    match = true;
                }
                if (match) matchDay = true;
                it.style.display = match ? '' : 'none';
            });
            if (q) {
                day.classList.toggle('dim', !matchDay);
            } else {
                day.classList.remove('dim');
            }
        });
    }
    
    input.addEventListener('input', (e) => {
        applySearch();
    });
    
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            applySearch();
        }
    });
}

function initializeEmptyDayClicks() {
    const calendarGrid = document.querySelector('.calendar-grid');
    if (!calendarGrid) return;
    
    const urlParams = new URLSearchParams(window.location.search);
    const currentMonth = parseInt(urlParams.get('month') || new Date().getMonth() + 1);
    const currentYear = parseInt(urlParams.get('year') || new Date().getFullYear());
    
    calendarGrid.addEventListener('click', function(e) {
        const calendarDay = e.target.closest('.calendar-day');
        
        if (!calendarDay) return;
        
        const hasEvents = calendarDay.classList.contains('has-event');
        
        if (hasEvents) return;
        
        const dayNumberEl = calendarDay.querySelector('.day-number');
        if (!dayNumberEl) return;
        
        const dayNumber = parseInt(dayNumberEl.textContent);
        if (!dayNumber || isNaN(dayNumber)) return;
        
        const formattedMonth = String(currentMonth).padStart(2, '0');
        const formattedDay = String(dayNumber).padStart(2, '0');
        const dateString = `${currentYear}-${formattedMonth}-${formattedDay}`;
        
        window.location.href = `add-event.php?date=${dateString}`;
    });
}
