function toggleTheme() {
    const body = document.body;
    const currentTheme = body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    body.setAttribute('data-theme', newTheme);

    const themeIcon = document.getElementById('theme-icon');
    if (themeIcon) {
        themeIcon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
    }

    localStorage.setItem('lens-theme', newTheme);
}

function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown?.classList.toggle('active');
}

function shareEvent(eventName) {
    alert(`Sharing event: "${eventName}"\nIn production, this would open a share dialog with options to share via email, social media, or copy a link.`);
}

function editEvent(eventName) {
    alert(`Editing event: "${eventName}"\nIn production, this would open an event editor form.`);
}

let eventToDelete = '';

function confirmDelete(eventName) {
    eventToDelete = eventName;
    const label = document.getElementById('eventToDelete');
    if (label) {
        label.textContent = eventName;
    }
    document.getElementById('deleteModal')?.classList.add('active');
}

function closeModal() {
    document.getElementById('deleteModal')?.classList.remove('active');
    eventToDelete = '';
}

function deleteEvent() {
    if (eventToDelete) {
        alert(`Event "${eventToDelete}" has been deleted.\nIn production, this would send a delete request to the server.`);
        closeModal();
    }
}

document.addEventListener('click', (event) => {
    const dropdown = document.getElementById('userDropdown');
    const avatar = document.querySelector('.user-avatar');
    if (dropdown && avatar && !dropdown.contains(event.target) && !avatar.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    applySavedTheme();
    setupModalDismiss();
    initializeCalendarExperience();
});

function applySavedTheme() {
    const savedTheme = localStorage.getItem('lens-theme');
    if (savedTheme) {
        document.body.setAttribute('data-theme', savedTheme);
        const themeIcon = document.getElementById('theme-icon');
        if (themeIcon) {
            themeIcon.textContent = savedTheme === 'dark' ? '☀️' : '🌙';
        }
    }
}

function setupModalDismiss() {
    const deleteModal = document.getElementById('deleteModal');
    if (!deleteModal) return;
    deleteModal.addEventListener('click', (event) => {
        if (event.target === deleteModal) {
            closeModal();
        }
    });
}

function initializeCalendarExperience() {
    const calendarEvents = Array.isArray(window.__CAL_EVENTS__) ? window.__CAL_EVENTS__ : [];
    const eventState = new Map();
    calendarEvents.forEach((event) => {
        if (!event || !event.id) return;
        if (!eventState.has(event.id)) {
            eventState.set(event.id, { ...event, rsvp: 'none' });
        }
    });

    bindDayScroll(eventState);
    bindEventItemRouting();
    bindFlagInteractions(eventState);
    bindAddButtons(eventState);
    bindRsvpControls(eventState);
    bindSearchFilter();
    bindMapPreview();
}

function bindDayScroll(eventState) {
    const dayNodes = document.querySelectorAll('.calendar-day[data-date]');
    dayNodes.forEach((day) => {
        day.addEventListener('click', (evt) => {
            if (evt.target.closest('.event-popover') || evt.target.closest('.event-action-btn')) {
                return;
            }
            const date = day.dataset.date;
            const totalEvents = parseInt(day.dataset.eventCount || '0', 10);
            if (!date || totalEvents <= 0) {
                return;
            }
            scrollToEventDate(date);
        });
    });
}

function bindEventItemRouting() {
    document.querySelectorAll('.event-item').forEach((item) => {
        const eventUrl = item.dataset.eventUrl;
        if (!eventUrl) {
            return;
        }
        item.addEventListener('click', (evt) => {
            if (evt.target.closest('a') || evt.target.closest('button')) {
                return;
            }
            window.location.href = eventUrl;
        });
    });
}

function bindFlagInteractions(eventState) {
    document.addEventListener('click', (event) => {
        const flag = event.target.closest('.event-flag');
        if (flag) {
            const day = flag.closest('.calendar-day');
            if (day) {
                day.classList.toggle('sticky');
            }
            const eventId = flag.dataset.eventId;
            if (eventId) {
                const eventData = eventState.get(eventId);
                if (eventData) {
                    scrollToEventDate(eventData.date || '');
                }
            }
            return;
        }

        if (!event.target.closest('.event-popover')) {
            document.querySelectorAll('.calendar-day.sticky').forEach((d) => d.classList.remove('sticky'));
        }
    });
}

function bindAddButtons(eventState) {
    document.querySelectorAll('.event-add-trigger').forEach((button) => {
        button.addEventListener('click', () => {
            const eventId = button.dataset.eventId;
            if (!eventId) return;
            updateRsvp(eventId, 'yes', eventState, { source: 'add-button' });
        });
    });
}

function bindRsvpControls(eventState) {
    document.querySelectorAll('.event-rsvp').forEach((container) => {
        container.querySelectorAll('.rsvp-choice').forEach((button) => {
            button.addEventListener('click', () => {
                const eventId = container.dataset.eventId;
                const choice = button.dataset.rsvp;
                if (!eventId || !choice) {
                    return;
                }
                updateRsvp(eventId, choice, eventState, { source: 'rsvp' });
            });
        });
    });
}

function bindSearchFilter() {
    const searchInput = document.getElementById('calendar-search');
    if (!searchInput) return;
    const handler = () => applyCalendarSearch(searchInput.value || '');
    searchInput.addEventListener('input', handler);
    searchInput.addEventListener('keydown', (evt) => {
        if (evt.key === 'Enter') {
            evt.preventDefault();
            handler();
        }
    });
}

function bindMapPreview() {
    const mapCache = new Map();

    async function geocode(query) {
        const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`;
        try {
            const response = await fetch(url, { headers: { 'Accept-Language': 'en' } });
            if (!response.ok) return null;
            const data = await response.json();
            if (Array.isArray(data) && data[0]) {
                return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
            }
        } catch (_) {
            // ignore network issues in mock environment
        }
        return null;
    }

    document.addEventListener('mouseenter', async (event) => {
        const locationLink = event.target.closest('.event-location a');
        if (!locationLink) return;
        const eventItem = locationLink.closest('.event-item');
        if (!eventItem) return;
        const miniMap = eventItem.querySelector('.mini-map');
        if (!miniMap) return;

        miniMap.style.display = 'block';
        const query = locationLink.getAttribute('data-location') || locationLink.textContent || '';
        if (!query) return;

        if (!mapCache.has(query)) {
            mapCache.set(query, await geocode(query));
        }
        const coords = mapCache.get(query) || { lat: 37.773972, lng: -122.431297 };
        if (!miniMap._map) {
            const map = L.map(miniMap).setView([coords.lat, coords.lng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            L.marker([coords.lat, coords.lng]).addTo(map);
            miniMap._map = map;
        } else {
            miniMap._map.setView([coords.lat, coords.lng], 14);
        }
    }, true);
}

function applyCalendarSearch(query) {
    const trimmed = query.trim().toLowerCase();
    const isTagQuery = trimmed.startsWith('#');
    const tagValue = isTagQuery ? trimmed.slice(1) : '';

    document.querySelectorAll('.calendar-day[data-date]').forEach((day) => {
        const items = day.querySelectorAll('.event-item');
        let matchesDay = false;

        items.forEach((item) => {
            const tags = (item.dataset.tags || '').toLowerCase().split(',');
            const description = (item.dataset.description || '').toLowerCase();
            const title = (item.querySelector('.event-title')?.textContent || '').toLowerCase();

            let matches = false;
            if (!trimmed) {
                matches = true;
            } else if (isTagQuery) {
                matches = tags.includes(tagValue);
            } else {
                matches = title.includes(trimmed) || description.includes(trimmed) || tags.some((t) => t.includes(trimmed));
            }

            item.style.display = matches ? '' : 'none';
            if (matches) {
                matchesDay = true;
            }
        });

        day.classList.toggle('dim', Boolean(trimmed) && !matchesDay);
    });
}

function updateRsvp(eventId, response, eventState, options = {}) {
    const eventData = eventState.get(eventId);
    if (!eventData) {
        return;
    }

    eventData.rsvp = response;
    setActiveRsvpButtons(eventId, response);
    decorateEventCard(eventId, response);
    applyFlagState(eventId, response);
    updateDayAttendance(eventId, response);

    const title = eventData.title || 'this event';
    if (response === 'yes') {
        pushNotification('accept', `Added <strong>${title}</strong> to your calendar. ${eventData.creator || 'The organizer'} has been notified.`);
        scrollToEventDate(eventData.date || '');
    } else if (response === 'interested') {
        pushNotification('interested', `Marked <strong>${title}</strong> as "Interested". ${eventData.creator || 'The planner'} will see your interest.`);
        scrollToEventDate(eventData.date || '');
    } else if (response === 'no') {
        pushNotification('info', `You set RSVP to "No" for <strong>${title}</strong>.`);
    }

    if (options.source === 'add-button') {
        const addButtons = document.querySelectorAll(`.event-add-trigger[data-event-id="${escapeSelector(eventId)}"]`);
        addButtons.forEach((btn) => {
            btn.textContent = 'Added to your calendar';
            btn.disabled = true;
            btn.classList.add('is-added');
        });
    }
}

function setActiveRsvpButtons(eventId, response) {
    const container = document.querySelector(`.event-rsvp[data-event-id="${escapeSelector(eventId)}"]`);
    if (!container) return;
    container.querySelectorAll('.rsvp-choice').forEach((button) => {
        button.classList.toggle('is-active', button.dataset.rsvp === response);
    });
}

function decorateEventCard(eventId, response) {
    const card = document.getElementById(`event-card-${eventId}`);
    if (!card) return;
    card.classList.remove('rsvp-yes', 'rsvp-interested');
    if (response === 'yes') {
        card.classList.add('rsvp-yes');
    } else if (response === 'interested') {
        card.classList.add('rsvp-interested');
    }
}

function applyFlagState(eventId, response) {
    document.querySelectorAll(`.event-flag[data-event-id="${escapeSelector(eventId)}"]`).forEach((flag) => {
        flag.classList.remove('rsvp-yes', 'rsvp-interested');
        if (response === 'yes') {
            flag.classList.add('rsvp-yes');
        } else if (response === 'interested') {
            flag.classList.add('rsvp-interested');
        }
    });
}

function updateDayAttendance(eventId, response) {
    const flag = document.querySelector(`.event-flag[data-event-id="${escapeSelector(eventId)}"]`);
    if (!flag) return;
    const day = flag.closest('.calendar-day');
    if (!day) return;

    if (response === 'yes' || response === 'interested') {
        day.classList.add('on-my-calendar');
    } else {
        const hasInterested = Array.from(day.querySelectorAll('.event-flag')).some((node) => node.classList.contains('rsvp-yes') || node.classList.contains('rsvp-interested'));
        if (!hasInterested) {
            day.classList.remove('on-my-calendar');
        }
    }
}

function scrollToEventDate(date) {
    if (!date) return;
    const group = document.querySelector(`.event-day-group[data-event-date="${escapeSelector(date)}"]`);
    if (!group) return;
    group.scrollIntoView({ behavior: 'smooth', block: 'start' });
    highlightEventCards(date);
}

function highlightEventCards(date) {
    const cards = document.querySelectorAll(`.event-card[data-event-date="${escapeSelector(date)}"]`);
    if (!cards.length) return;
    cards.forEach((card) => {
        card.classList.add('is-highlighted');
        setTimeout(() => card.classList.remove('is-highlighted'), 3500);
    });
}

function pushNotification(type, message) {
    const container = document.getElementById('calendar-notifications');
    if (!container) return;

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    const time = document.createElement('span');
    time.className = 'notification__time';
    time.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const body = document.createElement('span');
    body.innerHTML = message;

    notification.append(body, time);
    container.prepend(notification);

    while (container.children.length > 5) {
        container.lastElementChild?.remove();
    }

    setTimeout(() => notification.remove(), 10000);
}

function escapeSelector(value) {
    if (window.CSS?.escape) {
        return CSS.escape(value);
    }
    return value.replace(/([.*+?^${}()|[\]\\])/g, '\\$1');
}
