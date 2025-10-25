<?php
?>
<section class="card">
    <h2>Tag Search & Discovery</h2>
    <p class="subtle">Search for events and venues by tags. Use #tag format and separate multiple tags with commas.</p>
    
    <div class="form-row" style="margin-bottom:1.5rem">
        <label for="tag-search-input">Search Tags</label>
        <input type="text" id="tag-search-input" placeholder="e.g., #music, #outdoor, #family" style="width: 100%; padding: 0.75rem; font-size: 1rem;" />
        <small class="subtle">Separate multiple tags with commas. Example: #music, #outdoor, #family</small>
    </div>
    
    <div class="actions" style="margin-bottom: 1.5rem;">
        <button id="search-tags-btn" class="button" type="button">Search Tags</button>
        <button id="clear-search-btn" class="button" type="button">Clear</button>
    </div>

    <div id="search-results" style="display: none;">
        <h3>Search Results</h3>
        <div id="searched-tags-display" style="margin-bottom: 1rem;"></div>
        
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="card-subsection">
                <h4>Events</h4>
                <ul id="tag-search-events" class="item-list"></ul>
            </div>
            <div class="card-subsection">
                <h4>Venues</h4>
                <ul id="tag-search-venues" class="item-list"></ul>
            </div>
        </div>
    </div>

    <div id="popular-section">
        <div class="grid" style="grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">
            <div>
                <h3>Popular Tags</h3>
                <ul id="popular-tags" class="item-list"></ul>
            </div>
            <div>
                <div class="card-subsection">
                    <h3>Tag Details</h3>
                    <div id="selected-tag" class="subtle">Select a tag to see related events and venues.</div>
                </div>
                <div class="card-subsection">
                    <h4>Events</h4>
                    <ul id="tag-events" class="item-list"></ul>
                </div>
                <div class="card-subsection">
                    <h4>Venues</h4>
                    <ul id="tag-venues" class="item-list"></ul>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
(function(){
  const base = new URL('../api/tags.php', window.location.origin);
  const popularEl = document.getElementById('popular-tags');
  const searchInput = document.getElementById('tag-search-input');
  const searchBtn = document.getElementById('search-tags-btn');
  const clearBtn = document.getElementById('clear-search-btn');
  const selectedTagEl = document.getElementById('selected-tag');
  const eventsEl = document.getElementById('tag-events');
  const venuesEl = document.getElementById('tag-venues');
  const searchResults = document.getElementById('search-results');
  const popularSection = document.getElementById('popular-section');
  const searchedTagsDisplay = document.getElementById('searched-tags-display');
  const tagSearchEvents = document.getElementById('tag-search-events');
  const tagSearchVenues = document.getElementById('tag-search-venues');

  async function api(action, params={}){
    const url = new URL(base);
    url.searchParams.set('action', action);
    Object.entries(params).forEach(([k,v]) => url.searchParams.set(k, String(v)));
    const resp = await fetch(url.toString());
    return await resp.json();
  }

  function renderList(el, items, type){
    el.innerHTML = '';
    if (!items || items.length === 0){ el.innerHTML = '<li class="subtle">No results.</li>'; return; }
    items.forEach((it)=>{
      const li = document.createElement('li');
      if (type === 'event'){
        const date = it.start_datetime || it.event_date || '';
        li.innerHTML = `<a href="?page=event&id=${it.id}">${escapeHtml(it.title || it.name || 'Event #' + it.id)}</a> ${date ? '('+date+')' : ''}`;
      } else if (type === 'venue'){
        li.innerHTML = `<a href="?page=venue&id=${it.id}">${escapeHtml(it.name)}</a>`;
      } else {
        li.textContent = it.name || String(it.id);
      }
      el.appendChild(li);
    });
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  async function selectTag(tag){
    selectedTagEl.textContent = `#${tag.name}`;
    const [ev, vn] = await Promise.all([
      api('get_events_by_tag', { tag_id: tag.id }),
      api('get_venues_by_tag', { tag_id: tag.id })
    ]);
    renderList(eventsEl, (ev && ev.events) || [], 'event');
    renderList(venuesEl, (vn && vn.venues) || [], 'venue');
  }

  async function loadPopular(){
    const data = await api('get_popular_tags', { limit: 20 });
    const tags = (data && data.tags) || [];
    popularEl.innerHTML = '';
    tags.forEach(t => {
      const li = document.createElement('li');
      const btn = document.createElement('button');
      btn.className = 'button-small';
      btn.textContent = '#' + t.name;
      btn.addEventListener('click', ()=> selectTag(t));
      li.appendChild(btn);
      popularEl.appendChild(li);
    });
  }

  async function searchByTags() {
    const input = (searchInput.value || '').trim();
    if (input === '') {
      alert('Please enter at least one tag to search.');
      return;
    }

    const tagStrings = input.split(',').map(t => t.trim().replace(/^#/, '')).filter(t => t !== '');
    if (tagStrings.length === 0) {
      alert('Please enter valid tags.');
      return;
    }

    popularSection.style.display = 'none';
    searchResults.style.display = 'block';

    searchedTagsDisplay.innerHTML = tagStrings.map(t => `<span class="badge">#${escapeHtml(t)}</span>`).join(' ');

    const allEvents = new Map();
    const allVenues = new Map();

    for (const tagName of tagStrings) {
      const searchData = await api('search_tags', { query: tagName, limit: 1 });
      const tags = (searchData && searchData.tags) || [];
      
      if (tags.length > 0) {
        const tag = tags[0];
        const [ev, vn] = await Promise.all([
          api('get_events_by_tag', { tag_id: tag.id }),
          api('get_venues_by_tag', { tag_id: tag.id })
        ]);

        if (ev && ev.events) {
          ev.events.forEach(event => allEvents.set(event.id, event));
        }
        if (vn && vn.venues) {
          vn.venues.forEach(venue => allVenues.set(venue.id, venue));
        }
      }
    }

    renderList(tagSearchEvents, Array.from(allEvents.values()), 'event');
    renderList(tagSearchVenues, Array.from(allVenues.values()), 'venue');
  }

  function clearSearch() {
    searchInput.value = '';
    searchResults.style.display = 'none';
    popularSection.style.display = 'block';
    selectedTagEl.textContent = 'Select a tag to see related events and venues.';
    eventsEl.innerHTML = '';
    venuesEl.innerHTML = '';
  }

  searchBtn.addEventListener('click', searchByTags);
  clearBtn.addEventListener('click', clearSearch);
  
  searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      searchByTags();
    }
  });

  loadPopular();
})();
</script>
