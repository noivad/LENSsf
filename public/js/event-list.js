function removeTagFilter(tag) {
    const currentTags = window.__CURRENT_FILTER_TAGS__ || [];
    const newTags = currentTags.filter(t => t !== tag);
    if (newTags.length > 0) {
        window.location.href = 'event-list.php?tags=' + encodeURIComponent(newTags.join(','));
    } else {
        window.location.href = 'event-list.php';
    }
}
