(function(){
  const state = {
    today: new Date(),
    month: null,
    year: null,
    events: [],
    filtered: [],
    filters: [],
    viewerRole: 'Public',
    currentUser: 'You',
    canceledInstances: {}, // {eventId: Set('YYYY-MM-DD')}
  };

  function byId(id){ return document.getElementById(id); }
  function qs(sel, root=document){ return root.querySelector(sel); }
  function qsa(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

  function pad(n){ return String(n).padStart(2,'0'); }
  function fmtDate(d){ return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`; }
  function parseISO(s){ const [y,m,dd]=s.split('-').map(x=>parseInt(x,10)); return new Date(y,(m-1),dd); }

  function canSeeEvent(ev){
    const role = state.viewerRole;
    if (role === 'Admin') return true;
    const isCreator = (ev.owner||'') === state.currentUser || (ev.owner||'You') === 'You' && role==='Creator';
    const isDeputy = (ev.deputies||[]).includes(state.currentUser) || role === 'Deputy';
    const isShared = (ev.sharedWith||[]).includes(state.currentUser) || role === 'Shared';
    const vis = (ev.visibility||'Public');
    if (vis === 'Public') return true;
    if (vis === 'Private') return isCreator || isDeputy;
    if (vis === 'Invite Only') return isCreator || isDeputy || isShared;
    if (vis === 'Event Specific') return isCreator || isDeputy || isShared; // treat same as invite-only for mockup
    return true;
  }

  function canSeeMediaVisibility(vis, ev){
    const role = state.viewerRole;
    if (role === 'Admin') return true;
    const isCreator = (ev.owner||'') === state.currentUser || role==='Creator';
    const isDeputy = (ev.deputies||[]).includes(state.currentUser) || role==='Deputy';
    const isShared = (ev.sharedWith||[]).includes(state.currentUser) || role==='Shared';
    if ((vis||'Public') === 'Public') return true;
    if (vis === 'Private') return isCreator || isDeputy;
    if (vis === 'Invite Only') return isCreator || isDeputy || isShared;
    if (vis === 'Event Specific') return isCreator || isDeputy || isShared;
    return false;
  }

  function applyFilters(){
    const base = state.events.filter(canSeeEvent);
    if (state.filters.length === 0){ state.filtered = base; return; }
    state.filtered = base.filter(ev => state.filters.every(f =>
      ev.title.toLowerCase().includes(f) || (ev.description||'').toLowerCase().includes(f) || (ev.tags||[]).join(' ').toLowerCase().includes(f)
    ));
  }

  function setViewerRole(role){ state.viewerRole = role; applyFilters(); renderEventList(); renderMiniCal(); }

  function initData(){
    const bootstrap = window.__EVENTS__ || [];
    state.events = bootstrap.map((e, idx)=>({ id: e.id || ('evt_'+(idx+1)), ...e }));
    state.month = (new Date()).getMonth()+1;
    state.year = (new Date()).getFullYear();
    // hydrate canceled map
    state.canceledInstances = {};
    applyFilters();
  }

  function clampClass(){
    const def = parseInt(localStorage.getItem('eventListLines')||'2',10);
    return 'clamp-'+(isNaN(def)?2:def);
  }

  function renderEventList(){
    const wrap = byId('event-list');
    wrap.innerHTML = '';
    const clamp = clampClass();
    state.filtered.forEach(ev => {
      const row = document.createElement('div');
      row.className = 'event-row';
      row.setAttribute('data-event-id', ev.id);
      row.innerHTML = `
        <div class="event-main">
          <div class="event-name">${escapeHtml(ev.title)}</div>
          <div class="event-desc ${clamp}">${escapeHtml((ev.description||'').trim())}</div>
        </div>
        <div class="event-thin">
          <button class="button-thin copy-btn" title="Copy into Add/Edit">Copy to Add/Edit</button>
          <div class="cal-blurb">${escapeHtml(shortCalBlurb(ev))}</div>
          <button class="collapse" title="Collapse/Expand">▤</button>
        </div>
      `;
      wrap.appendChild(row);
    });
  }

  function shortCalBlurb(ev){
    const t = ev.startTime ? ` @ ${ev.startTime}` : '';
    if (ev.recurrence && ev.recurrence.freq){
      return `${ev.recurrence.freq}${ev.recurrence.interval && ev.recurrence.interval>1 ? '×'+ev.recurrence.interval: ''}${t}`;
    }
    return `${ev.date}${t}`;
  }

  function buildMonthMatrix(year, month){
    const first = new Date(year, month-1, 1);
    const daysInMonth = new Date(year, month, 0).getDate();
    const startIdx = first.getDay(); // Sun=0
    const total = 35; // 7x5 requested
    const cells = [];
    for (let i=0;i<total;i++){
      const dayNum = i - startIdx + 1;
      const inMonth = dayNum>=1 && dayNum<=daysInMonth;
      const date = inMonth ? new Date(year, month-1, dayNum) : null;
      cells.push({ inMonth, day: dayNum, date });
    }
    return cells;
  }

  function expandOccurrences(ev, year, month){
    // returns array of yyyy-mm-dd
    if (!ev.recurrence || !ev.recurrence.freq){ return [ev.date]; }
    const results = [];
    const start = parseISO(ev.date);
    const endCap = ev.recurrence.until ? parseISO(ev.recurrence.until) : null;
    const cutoff = endCap || new Date(year, month, 0);
    const firstOfMonth = new Date(year, month-1, 1);
    const lastOfMonth = new Date(year, month, 0);

    const freq = ev.recurrence.freq; // Weekly, Monthly, Yearly, Every other month
    const interval = ev.recurrence.interval || (freq==='Every other month' ? 2 : 1);

    function pushIfInRange(d){
      const iso = fmtDate(d);
      const canceledSet = state.canceledInstances[ev.id];
      if (canceledSet && canceledSet.has(iso)) return;
      if (ev.cancelAfter && iso > ev.cancelAfter) return;
      if (iso >= fmtDate(firstOfMonth) && iso <= fmtDate(lastOfMonth)) results.push(iso);
    }

    if (freq === 'Weekly'){
      // find first occurrence in month on specified weekday
      const dow = ev.recurrence.byDay != null ? ev.recurrence.byDay : start.getDay();
      const d = new Date(firstOfMonth);
      while (d.getDay() !== dow) d.setDate(d.getDate()+1);
      while (d <= lastOfMonth){ pushIfInRange(new Date(d)); d.setDate(d.getDate()+7*interval); }
    } else if (freq === 'Monthly' || freq === 'Every other month'){
      // nth weekday of each month or same day-of-month
      const nth = ev.recurrence.nth || null; // 1..4 or -1 for last
      let m = new Date(firstOfMonth);
      while (m <= lastOfMonth){
        let cand;
        if (nth != null && ev.recurrence.byDay != null){
          cand = nthWeekdayOfMonth(year, m.getMonth()+1, ev.recurrence.byDay, nth);
        } else {
          // same day of month as start
          const dom = start.getDate();
          cand = new Date(year, m.getMonth(), dom);
        }
        pushIfInRange(cand);
        m.setMonth(m.getMonth()+interval);
      }
    } else if (freq === 'Yearly'){
      const target = new Date(year, start.getMonth(), start.getDate());
      pushIfInRange(target);
    } else {
      // None
      pushIfInRange(start);
    }
    return results;
  }

  function nthWeekdayOfMonth(year, month, weekday, n){
    // weekday: 0-6 Sun-Sat, n: 1..4 or -1 for last
    if (n === -1){
      const last = new Date(year, month, 0);
      const d = new Date(last);
      while (d.getDay() !== weekday) d.setDate(d.getDate()-1);
      return d;
    }
    const first = new Date(year, month-1, 1);
    const d = new Date(first);
    while (d.getDay() !== weekday) d.setDate(d.getDate()+1);
    d.setDate(d.getDate() + (n-1)*7);
    return d;
  }

  function renderMiniCal(){
    const host = byId('mini-cal');
    const cells = buildMonthMatrix(state.year, state.month);
    const occMap = new Map(); // yyyy-mm-dd => [events]
    state.filtered.forEach(ev => {
      expandOccurrences(ev, state.year, state.month).forEach(iso => {
        if (!occMap.has(iso)) occMap.set(iso, []);
        occMap.get(iso).push(ev);
      });
    });

    const weekHead = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    host.innerHTML = `
      <div class="cal-head">
        <button class="nav-btn" id="cal-prev">◀</button>
        <div class="cal-title">${new Date(state.year, state.month-1, 1).toLocaleString(undefined, {month:'long', year:'numeric'})}</div>
        <button class="nav-btn" id="cal-next">▶</button>
      </div>
      <div class="grid">
        ${weekHead.map(d=>`<div class="dow">${d}</div>`).join('')}
        ${cells.map(c=>{
          if (!c.inMonth) return `<div class="cell dim"></div>`;
          const iso = fmtDate(c.date);
          const events = occMap.get(iso)||[];
          const has = events.length>0;
          const pop = has ? `<div class=\"minipop\">${events.slice(0,3).map(ev=>`<div class=\"mini-item\" data-iso=\"${iso}\" data-eid=\"${ev.id}\">${escapeHtml(ev.title)}</div>`).join('')}${events.length>3?`<div class=\"help-hint\">+${events.length-3} more</div>`:''}</div>` : '';
          return `<div class=\"cell ${has?'has':''}\"><span>${c.day}</span>${has?'<span class=dot></span>':''}${pop}</div>`;
        }).join('')}
      </div>
    `;
    byId('cal-prev').onclick = ()=>{ const d = new Date(state.year, state.month-2, 1); state.year=d.getFullYear(); state.month=d.getMonth()+1; renderMiniCal(); };
    byId('cal-next').onclick = ()=>{ const d = new Date(state.year, state.month, 1); state.year=d.getFullYear(); state.month=d.getMonth()+1; renderMiniCal(); };

    host.addEventListener('click', (e)=>{
      const item = e.target.closest('.mini-item');
      if (!item) return;
      const id = item.getAttribute('data-eid');
      const ev = state.events.find(x=>String(x.id)===String(id));
      if (ev){ prefillForm(ev); }
    });
  }

  function prefillForm(ev){
    const form = byId('addedit-form');
    if (!form) return;
    form.reset();
    form.title.value = ev.title || '';
    form.description.value = ev.description || '';
    form.date.value = ev.date || '';
    form.start_time.value = ev.startTime || '';
    form.end_time.value = ev.endTime || '';
    form.owner.value = ev.owner || state.currentUser;
    form.deputies.value = (ev.deputies||[]).join(', ');
    form.tags.value = (ev.tags||[]).join(', ');
    form.visibility.value = ev.visibility || 'Public';
    // recurrence
    form.freq.value = (ev.recurrence && ev.recurrence.freq) || 'None';
    form.interval.value = (ev.recurrence && ev.recurrence.interval) || '';
    form.byday.value = (ev.recurrence && (ev.recurrence.byDay ?? ''));
    form.nth.value = (ev.recurrence && (ev.recurrence.nth ?? ''));
    form.until.value = (ev.recurrence && (ev.recurrence.until || ''));

    // photos/comments rendering
    renderPhotos(ev);
    renderComments(ev);

    byId('addedit').scrollIntoView({behavior:'smooth', block:'start'});
    // update top image preview
    const img = byId('top-image');
    if (img){ img.src = ev.image || 'https://picsum.photos/seed/mock/600/400'; }
    const cap = byId('top-image-cap');
    if (cap){ cap.textContent = ev.title || 'Selected event'; }
  }

  function renderPhotos(ev){
    const host = byId('photo-grid-mini');
    host.innerHTML = '';
    (ev.photos||[]).forEach((p, idx)=>{
      if (!canSeeMediaVisibility(p.visibility||'Public', ev)) return;
      const card = document.createElement('div');
      card.className = 'photo-card-mini';
      card.innerHTML = `
        <img src="${escapeHtml(p.url)}" alt="photo">
        <div class="meta">
          <select data-idx="${idx}" class="photo-vis">
            ${['Public','Private','Invite Only','Event Specific'].map(opt=>`<option value="${opt}" ${p.visibility===opt?'selected':''}>${opt}</option>`).join('')}
          </select>
          <button class="button-thin button-muted tag-photo" data-idx="${idx}">Tag</button>
        </div>
        <div class="chips">${(p.tags||[]).map(t=>`<span class=chip>#${escapeHtml(t)}</span>`).join('')}</div>
      `;
      host.appendChild(card);
    });
    host.addEventListener('change', (e)=>{
      const sel = e.target.closest('.photo-vis'); if (!sel) return;
      const evId = (byId('addedit-form').title.value || '').trim();
      // For mockup, update current selected event object (find by title)
      const cur = findEventByForm(); if (!cur) return;
      const idx = parseInt(sel.getAttribute('data-idx'), 10); if (isNaN(idx)) return;
      cur.photos[idx].visibility = sel.value;
    });
    host.addEventListener('click', (e)=>{
      const btn = e.target.closest('.tag-photo'); if (!btn) return;
      const cur = findEventByForm(); if (!cur) return;
      const idx = parseInt(btn.getAttribute('data-idx'),10);
      const tag = prompt('Enter tag (without #):');
      if (tag){ cur.photos[idx].tags = cur.photos[idx].tags || []; cur.photos[idx].tags.push(tag.trim()); renderPhotos(cur); }
    });
  }

  function renderComments(ev){
    const list = byId('comment-list');
    list.innerHTML = '';
    (ev.comments||[]).forEach(c => {
      if (!canSeeMediaVisibility(c.visibility||'Public', ev)) return;
      const li = document.createElement('div');
      li.className = 'comment';
      li.textContent = `${c.author||'Anon'}: ${c.text}` + (c.visibility && c.visibility!=='Public' ? ` [${c.visibility}]` : '');
      list.appendChild(li);
    });
  }

  function findEventByForm(){
    const title = (byId('addedit-form').title.value||'').trim();
    return state.events.find(e => (e.title||'') === title) || null;
  }

  function wireForm(){
    const form = byId('addedit-form');
    form.addEventListener('submit', (e)=>{ e.preventDefault(); alert('Mock submit: values will not persist.'); });
    byId('add-comment-btn').addEventListener('click', ()=>{
      const cur = findEventByForm(); if (!cur) { alert('Select or prefill an event first.'); return; }
      const text = (byId('comment-text').value||'').trim(); if (!text) return;
      const vis = byId('comment-vis').value;
      cur.comments = cur.comments || [];
      cur.comments.push({ author: state.currentUser, text, visibility: vis });
      byId('comment-text').value = '';
      renderComments(cur);
    });
  }

  function attachListHandlers(){
    byId('event-list').addEventListener('click', (e)=>{
      const copy = e.target.closest('.copy-btn');
      if (copy){ const row = e.target.closest('.event-row'); const id = row.getAttribute('data-event-id'); const ev = state.events.find(ev=>String(ev.id)===String(id)); if (ev) prefillForm(ev); }
      const collapse = e.target.closest('.collapse');
      if (collapse){ const row = e.target.closest('.event-row'); row.classList.toggle('compact'); }
    });
  }

  function wireFilters(){
    const q = byId('search-input');
    byId('btn-filter').addEventListener('click', ()=>{ state.filters = []; const t = (q.value||'').trim().toLowerCase(); if (t) state.filters.push(t); applyFilters(); renderEventList(); renderMiniCal(); });
    byId('btn-add-filter').addEventListener('click', ()=>{ const t=(q.value||'').trim().toLowerCase(); if (t) state.filters.push(t); applyFilters(); renderEventList(); renderMiniCal(); });
  }

  function wireViewerRole(){
    const sel = byId('viewer-role');
    sel.addEventListener('change', ()=> setViewerRole(sel.value));
  }

  function contextMenuInit(){
    const menu = byId('ctx-menu');
    document.addEventListener('contextmenu', (e)=>{
      const targetRow = e.target.closest('.event-row');
      const selection = String(window.getSelection?.().toString()||'').trim();
      e.preventDefault();
      renderCtxMenu(menu, { row: targetRow, selection }, e.clientX, e.clientY);
    });
    document.addEventListener('click', ()=> menu.classList.remove('visible'));
  }

  function renderCtxMenu(menu, ctx, x, y){
    const items = [];
    if (ctx.row){
      const id = ctx.row.getAttribute('data-event-id');
      const ev = state.events.find(e=>String(e.id)===String(id));
      items.push({label:'Copy to Add/Edit', action:()=> prefillForm(ev)});
      items.push({label:'Mark Public', action:()=>{ ev.visibility='Public'; applyFilters(); renderEventList(); renderMiniCal(); }});
      items.push({label:'Mark Invite Only', action:()=>{ ev.visibility='Invite Only'; applyFilters(); renderEventList(); renderMiniCal(); }});
      items.push({label:'Mark Private', action:()=>{ ev.visibility='Private'; applyFilters(); renderEventList(); renderMiniCal(); }});
      if (ev && ev.recurrence && ev.recurrence.freq){
        items.push({label:'Cancel future occurrences', action:()=>{ const d = prompt('Enter cutoff date YYYY-MM-DD from which to cancel (inclusive):', fmtDate(new Date())); if (d){ ev.cancelAfter = d; renderMiniCal(); }});
      }
      items.push({label:'Set list lines: 1', action:()=> setListLines(1)});
      items.push({label:'Set list lines: 2', action:()=> setListLines(2)});
      items.push({label:'Set list lines: 3', action:()=> setListLines(3)});
      items.push({label:'Set list lines: 5', action:()=> setListLines(5)});
    }
    if ((ctx.selection||'').length>0){
      items.push({label:`Search “${ctx.selection.slice(0,40)}”`, action:()=> openSearch(ctx.selection)});
      if (looksLikeAddress(ctx.selection)) items.push({label:'Open in Maps', action:()=> openMaps(ctx.selection)});
    }
    items.push({label:'Help: search docs (?)', action:()=> openHelp((ctx.selection||'').trim())});

    menu.innerHTML = items.map(it=>`<div class="item">${escapeHtml(it.label)}</div>`).join('');
    qsa('.item', menu).forEach((el,i)=> el.onclick = ()=>{ menu.classList.remove('visible'); items[i].action(); });
    menu.style.left = x+ 'px';
    menu.style.top = y+ 'px';
    menu.classList.add('visible');
  }

  function setListLines(n){ localStorage.setItem('eventListLines', String(n)); renderEventList(); }

  function looksLikeAddress(s){
    const suffixes = /(st|street|rd|road|ave|avenue|blvd|boulevard|ln|lane|dr|drive|hwy|highway|way|ct|court)\b/i;
    return /\d+/.test(s) && suffixes.test(s);
  }
  function openSearch(q){ window.open('https://duckduckgo.com/?q=' + encodeURIComponent(q),'_blank'); }
  function openMaps(q){ window.open('https://www.google.com/maps/search/' + encodeURIComponent(q), '_blank'); }
  function openHelp(q){ window.open('help.html?q=' + encodeURIComponent(q||''), '_blank'); }

  function keyboardShortcuts(){
    document.addEventListener('keydown', (e)=>{
      if (e.altKey && e.key === '/'){ e.preventDefault(); const sel = String(window.getSelection?.().toString()||'').trim(); if (!sel) return; if (looksLikeAddress(sel)) openMaps(sel); else openSearch(sel); }
      if (e.altKey && (e.key === '?' || (e.shiftKey && e.key === '/'))){ e.preventDefault(); const sel = String(window.getSelection?.toString()||''); openHelp(sel); }
    });
  }

  function searchControlsInit(){ wireFilters(); }

  function rightClickCancelOnMiniCal(){
    // Allow cancel this occurrence by ctrl/right-clicking a mini item
    byId('mini-cal').addEventListener('mousedown', (e)=>{
      if (e.button !== 2 && !(e.ctrlKey && e.button===0)) return;
      const mini = e.target.closest('.mini-item'); if (!mini) return;
      e.preventDefault();
      const iso = mini.getAttribute('data-iso'); const id = mini.getAttribute('data-eid');
      state.canceledInstances[id] = state.canceledInstances[id] || new Set();
      state.canceledInstances[id].add(iso);
      renderMiniCal();
    });
  }

  function copyButtonsInit(){ attachListHandlers(); }

  function build(){
    initData();
    renderEventList();
    renderMiniCal();
    wireForm();
    searchControlsInit();
    keyboardShortcuts();
    contextMenuInit();
    wireViewerRole();
    rightClickCancelOnMiniCal();
    copyButtonsInit();
  }

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c])); }

  // bootstrap
  document.addEventListener('DOMContentLoaded', build);
})();
