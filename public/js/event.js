(function(){
  const root = document.querySelector('.event-single');
  if (!root) return;
  const eventId = parseInt(root.getAttribute('data-event-id'), 10);
  const currentUser = root.getAttribute('data-current-user') || 'You';
  const isEditor = (root.getAttribute('data-is-editor') === '1');

  const shareBtn = document.getElementById('share-btn');
  const sharePopover = document.getElementById('share-popover');
  const shareInput = document.getElementById('share-input');
  const shareSuggestions = document.getElementById('share-suggestions');
  const shareMessageInput = document.getElementById('share-message-input');

  const deputyBtn = document.getElementById('add-deputy-btn');
  const deputyPopover = document.getElementById('deputy-popover');
  const deputyInput = document.getElementById('deputy-input');
  const deputySuggestions = document.getElementById('deputy-suggestions');

  const sharedList = document.getElementById('shared-list');

  const addEventImageBtn = document.getElementById('add-event-image-btn');
  const eventImageInput = document.getElementById('event-image-input');

  const addPhotoBtn = document.getElementById('add-photo-btn');
  const photoInput = document.getElementById('photo-input');
  const photoGrid = document.getElementById('photo-grid');

  const eventCommentsList = document.getElementById('event-comments-list');
  const postEventCommentBtn = document.getElementById('post-event-comment');
  const eventCommentName = document.getElementById('event-comment-name');
  const eventCommentText = document.getElementById('event-comment-text');

  function initials(name){
    const parts = (name||'').trim().split(/\s+/);
    return parts.slice(0,2).map(p=>p[0]?.toUpperCase()||'').join('') || '?';
  }

  function renderSuggestion(container, person, onPick){
    const item = document.createElement('div');
    item.className = 'suggestion-item';
    const avatar = document.createElement('div');
    avatar.className = 'suggestion-avatar';
    avatar.textContent = initials(person.display_name || person.handle || '');
    const label = document.createElement('div');
    label.textContent = `${person.display_name} ${person.handle ? '(' + person.handle + ')' : ''}`;
    item.appendChild(avatar);
    item.appendChild(label);
    item.addEventListener('click', ()=> onPick(person.display_name));
    container.appendChild(item);
  }

  function togglePopover(popover, show){
    if (!popover) return;
    popover.classList[show ? 'add' : 'remove']('visible');
  }

  // Share popover
  if (shareBtn && sharePopover){
    shareBtn.addEventListener('click', ()=>{
      togglePopover(sharePopover, !sharePopover.classList.contains('visible'));
      if (sharePopover.classList.contains('visible')) {
        shareInput?.focus();
      }
    });
    sharePopover.querySelector('.popover-close')?.addEventListener('click', ()=> togglePopover(sharePopover, false));
  }

  // Deputy popover
  if (deputyBtn && deputyPopover){
    deputyBtn.addEventListener('click', ()=>{
      togglePopover(deputyPopover, !deputyPopover.classList.contains('visible'));
      if (deputyPopover.classList.contains('visible')) {
        deputyInput?.focus();
      }
    });
    deputyPopover.querySelector('.popover-close')?.addEventListener('click', ()=> togglePopover(deputyPopover, false));
  }

  async function fetchSuggestions(q){
    const url = new URL('event_api.php', window.location.origin);
    url.searchParams.set('action', 'search_people');
    url.searchParams.set('q', q || '');
    url.searchParams.set('event_id', String(eventId));
    const resp = await fetch(url.toString());
    const data = await resp.json();
    return data.people || [];
  }

  async function addShare(person){
    if (!person) return;
    const message = (shareMessageInput?.value || '').trim();
    await fetch('event_api.php?action=share', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ event_id: eventId, person, message })
    });
    shareInput.value = '';
    if (shareMessageInput) shareMessageInput.value = '';
    await loadShares();
  }

  async function addDeputy(person){
    if (!person) return;
    await fetch('event_api.php?action=add_deputy', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ event_id: eventId, person })
    });
    deputyInput.value = '';
  }

  function bindLiveEntry(input, container, onPick){
    if (!input) return;
    input.addEventListener('input', async ()=>{
      const q = input.value.trim();
      const people = await fetchSuggestions(q);
      container.innerHTML = '';
      people.forEach(p => renderSuggestion(container, p, onPick));
    });

    input.addEventListener('keydown', async (e)=>{
      if (e.key === 'Enter'){
        e.preventDefault();
        const value = input.value.trim();
        if (value) {
          await onPick(value);
        }
      }
    });
  }

  bindLiveEntry(shareInput, shareSuggestions, addShare);
  bindLiveEntry(deputyInput, deputySuggestions, addDeputy);

  async function loadShares(){
    if (!sharedList) return;
    const url = new URL('event_api.php', window.location.origin);
    url.searchParams.set('action', 'list_shares');
    url.searchParams.set('event_id', String(eventId));
    const resp = await fetch(url.toString());
    const data = await resp.json();
    const shares = data.shares || [];

    sharedList.innerHTML = '';
    if (shares.length === 0){
      sharedList.classList.add('empty-state');
      sharedList.textContent = 'No shares yet.';
      return;
    }
    sharedList.classList.remove('empty-state');

    shares.forEach(s => {
      const row = document.createElement('div');
      row.className = 'shared-item';
      const left = document.createElement('div');
      left.className = 'shared-left';
      const avatar = document.createElement('div');
      avatar.className = 'shared-avatar';
      avatar.textContent = initials(s.display_name);
      const label = document.createElement('div');
      label.className = 'shared-name';
      label.innerHTML = `${s.display_name}${s.handle ? ' <span class="shared-handle">(@'+s.handle+')</span>' : ''}` + (s.message ? `<div class="subtle">Message: ${s.message}</div>` : '');
      left.appendChild(avatar);
      left.appendChild(label);

      const right = document.createElement('div');
      const btn = document.createElement('button');
      btn.className = 'button-small';
      btn.textContent = 'Remove invitation';
      btn.addEventListener('click', async ()=>{
        await fetch('event_api.php?action=unshare', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ event_id: eventId, person: s.person }) });
        await loadShares();
      });
      right.appendChild(btn);

      row.appendChild(left); row.appendChild(right);
      sharedList.appendChild(row);
    });
  }

  loadShares();

  if (addEventImageBtn && eventImageInput){
    addEventImageBtn.addEventListener('click', ()=> eventImageInput.click());
    eventImageInput.addEventListener('change', async ()=>{
      if (!eventImageInput.files || !eventImageInput.files[0]) return;
      const fd = new FormData();
      fd.append('event_id', String(eventId));
      fd.append('image', eventImageInput.files[0]);
      const resp = await fetch('event_api.php?action=upload_event_image', { method:'POST', body: fd });
      const data = await resp.json();
      if (data.success){
        window.location.reload();
      }
    });
  }

  if (addPhotoBtn && photoInput){
    addPhotoBtn.addEventListener('click', ()=> photoInput.click());
    photoInput.addEventListener('change', async ()=>{
      if (!photoInput.files || !photoInput.files[0]) return;
      const fd = new FormData();
      fd.append('event_id', String(eventId));
      fd.append('uploaded_by', currentUser || 'You');
      fd.append('photo', photoInput.files[0]);
      const resp = await fetch('event_api.php?action=upload_photo', { method:'POST', body: fd });
      const data = await resp.json();
      if (data.success){
        const p = data.photo;
        const el = document.createElement('div');
        el.className = 'photo-card';
        el.setAttribute('data-photo-id', String(p.id));
        el.innerHTML = `
          <img src="uploads/${p.filename}" alt="${p.original_name}">
          <div class="photo-event">Event: ${root.querySelector('h2').textContent}</div>
          <div class="photo-card-actions">
            <button class="button-small add-photo-comment" data-photo-id="${p.id}">Add Comment</button>
          </div>
        `;
        photoGrid?.prepend(el);
      }
      photoInput.value = '';
    });
  }

  function delegate(el, selector, eventName, handler){
    el.addEventListener(eventName, (e)=>{
      const target = e.target;
      if (!(target instanceof Element)) return;
      const match = target.closest(selector);
      if (match && el.contains(match)) handler(e, match);
    });
  }

  delegate(document, '.add-photo-comment', 'click', async (e, btn)=>{
    const photoId = parseInt(btn.getAttribute('data-photo-id'), 10);
    const name = prompt('Your name:') || '';
    const comment = prompt('Comment:') || '';
    if (!name || !comment) return;
    await fetch('event_api.php?action=add_photo_comment', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ photo_id: photoId, name, comment }) });
    // Append comment UI
    const card = btn.closest('.photo-card');
    if (!card) return;
    let comments = card.querySelector('.comments');
    if (!comments){
      comments = document.createElement('div');
      comments.className = 'comments';
      comments.innerHTML = '<strong>Comments:</strong>';
      card.insertBefore(comments, btn.parentElement);
    }
    const div = document.createElement('div');
    div.className = 'comment';
    div.innerHTML = `<span class=\"comment-author\">${name}:</span> <span class=\"comment-text\"> ${comment}</span>` + (isEditor ? ` <button class=\"button-small delete-photo-comment\">Delete</button>` : '');
    comments.appendChild(div);
  });

  if (isEditor){
    delegate(document, '.delete-photo', 'click', async (e, btn)=>{
      const photoId = parseInt(btn.getAttribute('data-photo-id'), 10);
      if (!confirm('Delete this photo?')) return;
      await fetch('event_api.php?action=delete_photo', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ photo_id: photoId }) });
      const card = btn.closest('.photo-card');
      if (card) card.remove();
    });

    delegate(document, '.delete-photo-comment', 'click', async (e, btn)=>{
      const commentEl = btn.closest('.comment');
      const commentId = parseInt(btn.getAttribute('data-comment-id') || commentEl?.getAttribute('data-comment-id') || '0', 10);
      if (!commentId) return;
      if (!confirm('Delete this comment?')) return;
      await fetch('event_api.php?action=delete_photo_comment', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ comment_id: commentId }) });
      if (commentEl) commentEl.remove();
    });

    const removeEventImageBtn = document.getElementById('remove-event-image-btn');
    if (removeEventImageBtn){
      removeEventImageBtn.addEventListener('click', async ()=>{
        if (!confirm('Remove event image?')) return;
        await fetch('event_api.php?action=delete_event_image', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ event_id: eventId }) });
        window.location.reload();
      });
    }
  }

  async function loadEventComments(){
    if (!eventCommentsList) return;
    const url = new URL('event_api.php', window.location.origin);
    url.searchParams.set('action', 'list_event_comments');
    url.searchParams.set('event_id', String(eventId));
    const resp = await fetch(url.toString());
    const data = await resp.json();
    const comments = data.comments || [];
    eventCommentsList.innerHTML = '';
    if (comments.length === 0){
      eventCommentsList.classList.add('empty-state');
      eventCommentsList.textContent = 'No comments yet.';
      return;
    }
    eventCommentsList.classList.remove('empty-state');
    comments.forEach(c => {
      const div = document.createElement('div');
      div.className = 'comment';
      div.setAttribute('data-comment-id', String(c.id));
      div.innerHTML = `<span class=\"comment-author\">${c.name}:</span> <span class=\"comment-text\"> ${c.comment}</span>` + (isEditor ? ` <button class=\"button-small delete-event-comment\">Delete</button>` : '');
      eventCommentsList.appendChild(div);
    });
  }

  if (isEditor){
    delegate(document, '.delete-event-comment', 'click', async (e, btn)=>{
      const row = btn.closest('.comment');
      const id = parseInt(row?.getAttribute('data-comment-id') || '0', 10);
      if (!id) return;
      if (!confirm('Delete this comment?')) return;
      await fetch('event_api.php?action=delete_event_comment', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ comment_id: id }) });
      row?.remove();
    });
  }

  loadEventComments();

  if (postEventCommentBtn){
    postEventCommentBtn.addEventListener('click', async ()=>{
      const name = (eventCommentName?.value || '').trim();
      const text = (eventCommentText?.value || '').trim();
      if (!name || !text) return;
      await fetch('event_api.php?action=add_event_comment', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ event_id: eventId, name, comment: text }) });
      eventCommentText.value = '';
      await loadEventComments();
    });
  }
})();
