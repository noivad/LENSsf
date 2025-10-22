<?php
/** @var array $events */
/** @var array $venues */

$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$currentUserId = $_SESSION['user_id'] ?? 1;
$tab = $_GET['tab'] ?? 'shared_with_me';
?>
<section>
    <h2>Shared</h2>

    <div class="card">
        <div class="actions" style="margin-bottom: 1rem;">
            <a href="?page=shared&tab=shared_with_me" class="<?= $tab === 'shared_with_me' ? 'active' : '' ?>">Shared with me</a>
            <a href="?page=shared&tab=my_shares" class="<?= $tab === 'my_shares' ? 'active' : '' ?>">My shares</a>
        </div>

        <?php if ($tab === 'shared_with_me'): ?>
            <h3>Events Shared With Me</h3>
            <div id="shared-events-list" class="item-list">
                <p class="loading">Loading shared events...</p>
            </div>

            <h3 style="margin-top: 2rem;">Venues Shared With Me</h3>
            <div id="shared-venues-list" class="item-list">
                <p class="loading">Loading shared venues...</p>
            </div>

        <?php elseif ($tab === 'my_shares'): ?>
            <h3>Events I've Shared</h3>
            <div id="my-event-shares-list" class="item-list">
                <p class="loading">Loading your event shares...</p>
            </div>

            <h3 style="margin-top: 2rem;">Venues I've Shared</h3>
            <div id="my-venue-shares-list" class="item-list">
                <p class="loading">Loading your venue shares...</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function() {
    const currentUserId = <?= json_encode($currentUserId) ?>;
    const tab = <?= json_encode($tab) ?>;

    if (tab === 'shared_with_me') {
        loadSharedWithMe();
    } else if (tab === 'my_shares') {
        loadMyShares();
    }

    async function loadSharedWithMe() {
        try {
            const eventsResponse = await fetch('api/sharing.php?action=get_events_shared_with_me');
            const eventsData = await eventsResponse.json();
            
            const eventsList = document.getElementById('shared-events-list');
            if (eventsData.success && eventsData.events && eventsData.events.length > 0) {
                eventsList.innerHTML = eventsData.events.map(item => `
                    <div class="shared-item">
                        <h4><a href="?page=event&id=${item.id}">${escapeHtml(item.title || 'Event #' + item.id)}</a></h4>
                        <p class="subtle">Shared by: ${escapeHtml(item.shared_by_name || 'Unknown')} on ${formatDate(item.created_at)}</p>
                        ${item.message ? `<p><em>"${escapeHtml(item.message)}"</em></p>` : ''}
                    </div>
                `).join('');
            } else {
                eventsList.innerHTML = '<p>No events have been shared with you yet.</p>';
            }

            const venuesResponse = await fetch('api/sharing.php?action=get_venues_shared_with_me');
            const venuesData = await venuesResponse.json();
            
            const venuesList = document.getElementById('shared-venues-list');
            if (venuesData.success && venuesData.venues && venuesData.venues.length > 0) {
                venuesList.innerHTML = venuesData.venues.map(item => `
                    <div class="shared-item">
                        <h4><a href="?page=venue&id=${item.id}">${escapeHtml(item.name || 'Venue #' + item.id)}</a></h4>
                        <p class="subtle">Shared by: ${escapeHtml(item.shared_by_name || 'Unknown')} on ${formatDate(item.created_at)}</p>
                        ${item.message ? `<p><em>"${escapeHtml(item.message)}"</em></p>` : ''}
                    </div>
                `).join('');
            } else {
                venuesList.innerHTML = '<p>No venues have been shared with you yet.</p>';
            }
        } catch (error) {
            console.error('Error loading shared items:', error);
            document.getElementById('shared-events-list').innerHTML = '<p class="error">Failed to load shared events.</p>';
            document.getElementById('shared-venues-list').innerHTML = '<p class="error">Failed to load shared venues.</p>';
        }
    }

    async function loadMyShares() {
        try {
            const eventsResponse = await fetch('api/sharing.php?action=get_my_event_shares');
            const eventsData = await eventsResponse.json();
            
            const eventsList = document.getElementById('my-event-shares-list');
            if (eventsData.success && eventsData.shares && eventsData.shares.length > 0) {
                eventsList.innerHTML = eventsData.shares.map(item => {
                    const shares = item.shares || [];
                    return `
                        <div class="shared-item">
                            <h4><a href="?page=event&id=${item.event_id}">${escapeHtml(item.event_title || 'Event #' + item.event_id)}</a></h4>
                            <p class="subtle">Shared with ${shares.length} user(s):</p>
                            <div class="share-recipients">
                                ${shares.map(share => `
                                    <div class="share-recipient">
                                        <span>${escapeHtml(share.shared_with_name || 'User #' + share.shared_with)}</span>
                                        <button class="button-small" onclick="revokeShare('event', ${item.event_id}, ${share.shared_with})">Revoke</button>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                eventsList.innerHTML = '<p>You haven\'t shared any events yet.</p>';
            }

            const venuesResponse = await fetch('api/sharing.php?action=get_my_venue_shares');
            const venuesData = await venuesResponse.json();
            
            const venuesList = document.getElementById('my-venue-shares-list');
            if (venuesData.success && venuesData.shares && venuesData.shares.length > 0) {
                venuesList.innerHTML = venuesData.shares.map(item => {
                    const shares = item.shares || [];
                    return `
                        <div class="shared-item">
                            <h4><a href="?page=venue&id=${item.venue_id}">${escapeHtml(item.venue_name || 'Venue #' + item.venue_id)}</a></h4>
                            <p class="subtle">Shared with ${shares.length} user(s):</p>
                            <div class="share-recipients">
                                ${shares.map(share => `
                                    <div class="share-recipient">
                                        <span>${escapeHtml(share.shared_with_name || 'User #' + share.shared_with)}</span>
                                        <button class="button-small" onclick="revokeShare('venue', ${item.venue_id}, ${share.shared_with})">Revoke</button>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                venuesList.innerHTML = '<p>You haven\'t shared any venues yet.</p>';
            }
        } catch (error) {
            console.error('Error loading my shares:', error);
            document.getElementById('my-event-shares-list').innerHTML = '<p class="error">Failed to load your event shares.</p>';
            document.getElementById('my-venue-shares-list').innerHTML = '<p class="error">Failed to load your venue shares.</p>';
        }
    }

    window.revokeShare = async function(type, itemId, sharedWithId) {
        if (!confirm('Are you sure you want to revoke this share?')) {
            return;
        }

        try {
            const endpoint = type === 'event' ? 'revoke_event_share' : 'revoke_venue_share';
            const payload = type === 'event' 
                ? { event_id: itemId, shared_with: sharedWithId }
                : { venue_id: itemId, shared_with: sharedWithId };

            const response = await fetch(`api/sharing.php?action=${endpoint}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            if (data.success) {
                alert('Share revoked successfully!');
                loadMyShares();
            } else {
                alert('Failed to revoke share: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error revoking share:', error);
            alert('Failed to revoke share.');
        }
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateStr) {
        if (!dateStr) return 'Unknown date';
        try {
            return new Date(dateStr).toLocaleDateString();
        } catch (e) {
            return dateStr;
        }
    }
})();
</script>

<style>
.shared-item {
    padding: 1rem;
    border: 1px solid var(--border-color, #ccc);
    border-radius: 8px;
    margin-bottom: 1rem;
    background: var(--bg-secondary, #f9f9f9);
}

.share-recipients {
    margin-top: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.share-recipient {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: var(--bg-tertiary, #fff);
    border-radius: 6px;
}

.loading {
    color: var(--text-secondary, #666);
    font-style: italic;
}

.error {
    color: #d9534f;
}
</style>
