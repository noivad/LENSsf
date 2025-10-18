(function(){
  const overlay = document.getElementById('overlay-mask');
  const popover = document.getElementById('venue-popover');
  const popoverTitle = document.getElementById('venue-popover-title');
  const closeBtn = popover.querySelector('.popover-close');
  const createBtn = document.getElementById('create-venue-btn');

  const form = document.getElementById('venue-form');
  const infoPane = document.getElementById('venue-info');
  const infoContent = document.getElementById('venue-info-content');
  const tagInput = document.getElementById('tag_input');
  const tagList = document.getElementById('tag_list');
  const tagsCsv = document.getElementById('tags_csv');

  const infoTagInput = document.getElementById('info_tag_input');
  const infoTagList = document.getElementById('info_tag_list');
  const publicRadio = document.getElementById('public_tag_radio');

  const addressEl = document.getElementById('venue_address');
  const cityEl = document.getElementById('venue_city');
  const stateEl = document.getElementById('venue_state');
  const zipEl = document.getElementById('venue_zip');

  const mapEl = document.getElementById('mini_map');
  let map, mapMarker, mapDebounce;

  const currentUser = window.__CURRENT_USER__ || 'Demo User';
  const venues = Array.isArray(window.__VENUES__) ? window.__VENUES__ : [];

  function showPopover(){
    overlay.classList.add('visible');
    popover.classList.add('visible');
    popover.setAttribute('aria-hidden', 'false');
  }
  function hidePopover(){
    overlay.classList.remove('visible');
    popover.classList.remove('visible');
    popover.setAttribute('aria-hidden', 'true');
  }

  function resetForm(){
    form.reset();
    newVenueTags = [];
    renderCreateTags();
  }

  function initMap(){
    if (map) return;
    map = L.map(mapEl).setView([37.773972, -122.431297], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);
  }

  function setMapTo(lat, lng){
    initMap();
    if (!mapMarker){
      mapMarker = L.marker([lat, lng]).addTo(map);
    } else {
      mapMarker.setLatLng([lat, lng]);
    }
    map.setView([lat, lng], 14);
  }

  async function geocodeAddress(q){
    const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=1`;
    try {
      const res = await fetch(url, { headers: { 'Accept-Language': 'en' }});
      if (!res.ok) return null;
      const data = await res.json();
      if (Array.isArray(data) && data[0]){
        return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
      }
    } catch (_) {}
    return null;
  }

  function fullAddress(){
    const parts = [addressEl.value, cityEl.value, stateEl.value, zipEl.value].map((s)=> (s||'').trim()).filter(Boolean);
    return parts.join(', ');
  }

  function updateMapFromAddress(){
    clearTimeout(mapDebounce);
    mapDebounce = setTimeout(async () => {
      const q = fullAddress();
      if (!q) return;
      const pos = await geocodeAddress(q);
      if (pos){ setMapTo(pos.lat, pos.lng); }
    }, 400);
  }

  // Create flow
  let newVenueTags = [];
  function renderCreateTags(){
    tagList.innerHTML = '';
    tagsCsv.value = newVenueTags.join(',');
    newVenueTags.forEach((t) => {
      const span = document.createElement('span');
      span.className = 'badge';
      span.textContent = `#${t}`;
      tagList.appendChild(span);
    });
  }
  function addCreateTag(tag){
    const t = (tag||'').toLowerCase().trim();
    if (!t) return;
    if (!newVenueTags.includes(t)){
      newVenueTags.push(t);
      renderCreateTags();
    }
  }
  function handleTagInputKey(e){
    if (e.key === 'Enter' || e.key === ','){
      e.preventDefault();
      const raw = tagInput.value;
      tagInput.value = '';
      raw.split(',').forEach(addCreateTag);
    }
  }

  // Info flow
  function privateTagsStorageKey(){
    const safeUser = currentUser.replace(/[^a-z0-9]+/gi, '_').toLowerCase();
    return 'venue_private_tags_' + safeUser;
  }
  function loadPrivateTagsMap(){
    try{
      const raw = localStorage.getItem(privateTagsStorageKey());
      const obj = raw ? JSON.parse(raw) : {};
      return (obj && typeof obj === 'object') ? obj : {};
    }catch(_){return {}};
  }
  function savePrivateTagsMap(map){
    try{ localStorage.setItem(privateTagsStorageKey(), JSON.stringify(map)); }catch(_){ }
  }
  function getPrivateTags(venueId){
    const m = loadPrivateTagsMap();
    return Array.isArray(m[venueId]) ? m[venueId] : [];
  }
  function addPrivateTag(venueId, tag){
    const m = loadPrivateTagsMap();
    const list = Array.isArray(m[venueId]) ? m[venueId] : [];
    const t = (tag||'').toLowerCase().trim();
    if (!t) return;
    if (!list.includes(t)){
      list.push(t);
      m[venueId] = list;
      savePrivateTagsMap(m);
    }
  }

  function renderInfoTags(venue){
    infoTagList.innerHTML = '';
    const all = [];
    (venue.tags||[]).forEach(t => all.push({t:(t||'').toLowerCase(), kind:'public'}));
    getPrivateTags(String(venue.id)).forEach(t => all.push({t, kind:'private'}));
    all.forEach(({t,kind}) => {
      const span = document.createElement('span');
      span.className = 'badge';
      span.textContent = `#${t}` + (kind==='private' ? ' (you)' : '');
      infoTagList.appendChild(span);
    });
  }

  function setInfoMode(venue){
    form.style.display = 'none';
    infoPane.style.display = 'block';
    popoverTitle.textContent = venue.name;
    const owner = String(venue.owner || '');
    const isOwner = owner.toLowerCase() === String(currentUser).toLowerCase();
    publicRadio.disabled = !isOwner;

    const addrParts = [venue.address, venue.city, venue.state, venue.zip_code].filter(Boolean).join(', ');
    const hours = venue.open_times ? `<div class=\"line\"><strong>Hours:</strong> ${escapeHtml(String(venue.open_times))}</div>` : '';
    const desc = venue.description ? `<div class=\"line\">${escapeHtml(String(venue.description))}</div>` : '';

    infoContent.innerHTML = `
      <div class=\"event-single-sub\">
        <div class=\"line\"><strong>Address:</strong> ${escapeHtml(addrParts)}</div>
        ${hours}
        <div class=\"line\"><strong>Owner:</strong> ${escapeHtml(owner)}</div>
      </div>
      ${desc}
    `;

    // Set map to venue address if possible
    if (addrParts){
      geocodeAddress(addrParts).then((pos) => { if (pos) setMapTo(pos.lat, pos.lng); });
    }
    renderInfoTags(venue);
  }

  function setCreateMode(){
    infoPane.style.display = 'none';
    form.style.display = 'block';
    popoverTitle.textContent = 'Create Venue';
    resetForm();
    // Set map to default city
    initMap();
    map.setView([37.773972, -122.431297], 12);
  }

  function escapeHtml(str){
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // Wire up
  createBtn?.addEventListener('click', () => {
    setCreateMode();
    showPopover();
  });

  closeBtn?.addEventListener('click', hidePopover);
  overlay?.addEventListener('click', hidePopover);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hidePopover(); });

  // Tag input for create
  tagInput?.addEventListener('keydown', handleTagInputKey);

  // Address → map
  [addressEl, cityEl, stateEl, zipEl].forEach((el) => {
    el?.addEventListener('input', updateMapFromAddress);
    el?.addEventListener('change', updateMapFromAddress);
  });

  // Venue list open buttons
  document.querySelectorAll('.open-venue').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      const item = e.target.closest('.venue-item');
      if (!item) return;
      const id = item.getAttribute('data-venue-id');
      const venue = venues.find(v => String(v.id) === String(id));
      if (!venue) return;
      setInfoMode(venue);
      showPopover();
    });
  });

  // Info add tag
  infoTagInput?.addEventListener('keydown', async (e) => {
    if (e.key !== 'Enter' && e.key !== ',') return;
    e.preventDefault();
    const raw = infoTagInput.value;
    infoTagInput.value = '';
    const tags = raw.split(',').map(s => s.trim()).filter(Boolean);
    const currentVenueName = popoverTitle.textContent || '';
    const v = venues.find(x => String(x.name) === currentVenueName);
    if (!v) return;
    for (const t of tags){
      const tag = (t||'').toLowerCase();
      const visibility = (document.querySelector('input[name="tag_visibility"]:checked')?.value) || 'private';
      if (visibility === 'private'){
        addPrivateTag(String(v.id), tag);
      } else {
        try{
          const res = await fetch('venue-info.php?action=add_public_tag', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ venue_id: v.id, tag })
          });
          if (res.ok){
            const json = await res.json();
            if (json && json.success && json.venue){
              // update our local copy
              const idx = venues.findIndex(x => String(x.id) === String(v.id));
              if (idx >= 0) venues[idx] = json.venue;
            }
          }
        }catch(_){/* noop */}
      }
    }
    // re-render
    const venue = venues.find(x => String(x.name) === currentVenueName) || v;
    renderInfoTags(venue);
  });

  // Initialize base map once
  initMap();
})();
