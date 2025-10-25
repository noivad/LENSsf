document.getElementById('year').textContent = new Date().getFullYear();

window.__VENUES__ = [
  { id: 1, name: 'Blue Note Club', address: '123 Music Ave', city: 'San Francisco', state: 'CA', zip_code: '94105', description: 'Legendary jazz club with nightly performances.', owner: 'Alex Johnson', open_times: 'Mon-Sun 5pm-1am', tags: ['jazz','live','late-night'] },
  { id: 2, name: 'Modern Art Gallery', address: '55 Canvas Rd', city: 'San Francisco', state: 'CA', zip_code: '94107', description: 'Contemporary art exhibits and community workshops.', owner: 'Pat Lee', open_times: 'Tue-Sun 10am-6pm', tags: ['art','gallery','family'] }
];
window.__CURRENT_USER__ = 'Alex Johnson';
