<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/managers/EventManager.php';

if (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Local Event Network Service');
}

$siteName = SITE_NAME;
$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$uploadDir = defined('UPLOAD_DIR') ? rtrim((string) UPLOAD_DIR, '/') : __DIR__ . '/uploads';

$pdo = Database::connect();
$eventManager = new EventManager($pdo, $uploadDir);

$allEvents = $eventManager->all();

$allTags = [];
foreach ($allEvents as $event) {
    if (!empty($event['tags'])) {
        foreach ($event['tags'] as $tag) {
            $tag = strtolower(trim($tag));
            if ($tag !== '') {
                if (!isset($allTags[$tag])) {
                    $allTags[$tag] = 0;
                }
                $allTags[$tag]++;
            }
        }
    }
}

ksort($allTags);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tags - <?= e($siteName) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .tags-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .tag-card {
            padding: 1rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .tag-card:hover {
            background: var(--hover-bg);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .tag-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .tag-count {
            color: var(--text-subtle);
            font-size: 0.9rem;
        }
        .search-box {
            margin-bottom: 1.5rem;
        }
        .search-box input {
            width: 100%;
            max-width: 600px;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .selected-tags {
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .selected-tag-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .selected-tag-badge button {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0;
            line-height: 1;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php"><?= e($siteName) ?></a></h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="event-list.php">Events</a>
                <a href="calendar-7x5.php">Calendar</a>
                <a href="venue-info.php">Venues</a>
                <a href="tags.php" class="active">Tags</a>
                <a href="account.php">Account</a>
                <a href="add-event.php">Add Event</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="card">
            <h2>Event Tags</h2>
            
            <div class="info-box">
                <strong>Search tags:</strong> Type #tag1, #tag2 (comma-separated) to search for specific tags. 
                Click any tag below to filter events by that tag.
            </div>

            <div class="search-box">
                <input 
                    type="text" 
                    id="tag-search" 
                    placeholder="Search tags with #tag syntax (e.g., #music, #art)" 
                    aria-label="Search tags"
                >
            </div>

            <div id="selected-tags-container" class="selected-tags" style="display: none;">
                <strong>Selected tags:</strong>
                <div id="selected-tags"></div>
                <button class="button-small" id="clear-filters">Clear All Filters</button>
            </div>

            <?php if (empty($allTags)): ?>
                <p class="subtle">No tags found. Tags will appear here once events are created with tags.</p>
            <?php else: ?>
                <div class="tags-grid" id="tags-grid">
                    <?php foreach ($allTags as $tag => $count): ?>
                        <div class="tag-card" data-tag="<?= e($tag) ?>" onclick="selectTag('<?= e($tag) ?>')">
                            <div class="tag-name">#<?= e($tag) ?></div>
                            <div class="tag-count"><?= e((string) $count) ?> event<?= $count !== 1 ? 's' : '' ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= e($siteName) ?></p>
        </div>
    </footer>

    <script>
        const allTagsData = <?= json_encode($allTags, JSON_UNESCAPED_UNICODE) ?>;
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

        document.getElementById('clear-filters').addEventListener('click', () => {
            selectedTags.clear();
            updateSelectedTags();
            window.location.href = 'event-list.php';
        });

        const searchInput = document.getElementById('tag-search');
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
    </script>
</body>
</html>
