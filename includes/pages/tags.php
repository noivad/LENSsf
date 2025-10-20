<?php
?>
<section class="card">
    <h2>Tags Explorer</h2>
    <div class="form-row" style="margin-bottom:0.5rem">
        <input type="text" id="tag-search" placeholder="Search tags..." />
    </div>
    <div class="grid" style="grid-template-columns: 1fr 2fr; gap: 1rem; align-items: start;">
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
</section>
<script>
(function(){
  const base = new URL('../api/tags.php', window.location.origin);
  const popularEl = document.getElementById('popular-tags');
  const searchInput = document.getElementById('tag-search');
  const selectedTagEl = document.getElementById('selected-tag');
  const eventsEl = document.getElementById('tag-events');
  const venuesEl = document.getElementById('tag-venues');

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
        li.innerHTML = `<a href="?page=event&id=${it.id}">${it.title || it.name || 'Event #' + it.id}</a> ${date ? '('+date+')' : ''}`;
      } else if (type === 'venue'){
        li.innerHTML = `<a href="?page=venue&id=${it.id}">${it.name}</a>`;
      } else {
        li.textContent = it.name || String(it.id);
      }
      el.appendChild(li);
    });
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

  async function onSearch(){
    const q = (searchInput.value || '').trim();
    if (q === '') { loadPopular(); return; }
    const data = await api('search_tags', { query: q, limit: 20 });
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

  searchInput.addEventListener('input', ()=>{ clearTimeout(searchInput._t); searchInput._t = setTimeout(onSearch, 250); });
  loadPopular();
})();
</script>
