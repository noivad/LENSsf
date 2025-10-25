let currentTab = 'shared_with_me';

function showTab(tabName) {
    currentTab = tabName;
    const sharedTab = document.getElementById('shared-with-me-tab');
    const mySharesTab = document.getElementById('my-shares-tab');
    
    if (tabName === 'shared_with_me') {
        sharedTab.style.display = 'block';
        mySharesTab.style.display = 'none';
        loadSharedWithMe();
    } else {
        sharedTab.style.display = 'none';
        mySharesTab.style.display = 'block';
        loadMyShares();
    }
}

async function loadSharedWithMe() {
    const eventsList = document.getElementById('shared-events-list');
    const venuesList = document.getElementById('shared-venues-list');
    
    eventsList.innerHTML = `
        <div class="shared-item">
            <h4><a href="index.php?page=event&id=1">Sample Event</a></h4>
            <p class="subtle">Shared by: John Doe on 2025-10-15</p>
            <p><em>"Check out this cool event!"</em></p>
        </div>
    `;
    
    venuesList.innerHTML = `
        <div class="shared-item">
            <h4><a href="index.php?page=venue&id=1">Sample Venue</a></h4>
            <p class="subtle">Shared by: Jane Smith on 2025-10-18</p>
        </div>
    `;
}

async function loadMyShares() {
    const eventsList = document.getElementById('my-event-shares-list');
    const venuesList = document.getElementById('my-venue-shares-list');
    
    eventsList.innerHTML = `
        <div class="shared-item">
            <h4><a href="index.php?page=event&id=2">My Shared Event</a></h4>
            <p class="subtle">Shared with 2 user(s):</p>
            <div class="share-recipients">
                <div class="share-recipient">
                    <span>Alice Johnson</span>
                    <button class="button-small" onclick="alert('Revoke share')">Revoke</button>
                </div>
                <div class="share-recipient">
                    <span>Bob Williams</span>
                    <button class="button-small" onclick="alert('Revoke share')">Revoke</button>
                </div>
            </div>
        </div>
    `;
    
    venuesList.innerHTML = `<p>You haven't shared any venues yet.</p>`;
}

loadSharedWithMe();
