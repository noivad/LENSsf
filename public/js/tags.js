const allTagsData = window.__ALL_TAGS__ || {};
let selectedTags = new Set();

function selectTag(tag) {
    if (selectedTags.has(tag)) {
        selectedTags.delete(tag);
    } else {
        selectedTags.add(tag);
    }
    updateSelectedTags();
    filterEvents();
}

function updateSelectedTags() {
    const container = document.getElementById('selected-tags-container');
    const selectedTagsDiv = document.getElementById('selected-tags');
    
    if (selectedTags.size === 0) {
        container.style.display = 'none';
    } else {
        container.style.display = 'flex';
        selectedTagsDiv.innerHTML = Array.from(selectedTags).map(tag => `
            <div class="selected-tag-badge">
                #${escapeHtml(tag)}
                <button onclick="selectTag('${escapeHtml(tag)}')" aria-label="Remove tag">&times;</button>
            </div>
        `).join('');
    }
}

function filterEvents() {
    if (selectedTags.size > 0) {
        window.location.href = 'event-list.php?tags=' + encodeURIComponent(Array.from(selectedTags).join(','));
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    const clearButton = document.getElementById('clear-filters');
    if (clearButton) {
        clearButton.addEventListener('click', () => {
            selectedTags.clear();
            updateSelectedTags();
            window.location.href = 'event-list.php';
        });
    }

    const searchInput = document.getElementById('tag-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const value = e.target.value.toLowerCase();
            const tags = value.split(',').map(t => t.trim().replace(/^#/, '')).filter(t => t);
            
            const tagCards = document.querySelectorAll('.tag-card');
            tagCards.forEach(card => {
                const tagName = card.dataset.tag;
                if (value === '' || tags.some(t => tagName.includes(t))) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const value = e.target.value.toLowerCase();
                const tags = value.split(',').map(t => t.trim().replace(/^#/, '')).filter(t => t);
                if (tags.length > 0) {
                    tags.forEach(tag => {
                        if (allTagsData[tag]) {
                            selectedTags.add(tag);
                        }
                    });
                    updateSelectedTags();
                    filterEvents();
                }
            }
        });
    }
});
