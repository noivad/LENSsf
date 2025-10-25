(function(){
  const params = new URLSearchParams(location.search);
  const q = params.get('q') || '';
  const input = document.getElementById('q');
  const results = document.getElementById('results');
  input.value = q;
  function search(text){
    const t = (text||'').toLowerCase();
    const data = [
      {t:'recurring events', d:'Set Frequency, Interval, By Day, and Nth for monthly patterns.'},
      {t:'privacy', d:'Visibility options: Public, Invite Only, Private, Event Specific.'},
      {t:'context menu', d:'Right-click entries to see actions like copy, cancel occurrences, or set list lines.'},
      {t:'keyboard', d:'Option-/ searches the highlighted text (addresses open in Maps). Option-? searches help.'}
    ];
    results.innerHTML = '';
    data.filter(x => x.t.includes(t) || x.d.toLowerCase().includes(t)).forEach(x => {
      const div = document.createElement('div');
      div.className = 'result';
      div.innerHTML = `<strong>${x.t}</strong><div>${x.d}</div>`;
      results.appendChild(div);
    });
    if (!results.children.length){
      const div = document.createElement('div');
      div.className = 'result muted';
      div.textContent = 'No help results.';
      results.appendChild(div);
    }
  }
  input.addEventListener('input', ()=> search(input.value));
  search(q);
})();
