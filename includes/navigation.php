<?php
if (!function_exists('renderNavigation')) {
    function renderNavigation(string $activePage = '', string $pageTitle = 'LENSsf'): void {
        ?>
        <div class="app-container">
            <nav class="sidebar-nav">
                <div class="nav-logo">LENS</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="calendar-7x5.php" class="nav-link<?= $activePage === 'calendar-7x5' ? ' active' : '' ?>">
                            <span class="nav-icon">🏠</span>
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="event-list.php" class="nav-link<?= $activePage === 'events' ? ' active' : '' ?>">
                            <span class="nav-icon">📋</span>
                            <span>Events</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="venues-list.php" class="nav-link<?= $activePage === 'venues' ? ' active' : '' ?>">
                            <span class="nav-icon">📍</span>
                            <span>Venues</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="tags.php" class="nav-link<?= $activePage === 'tags' ? ' active' : '' ?>">
                            <span class="nav-icon">🏷️</span>
                            <span>Tags</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="add-event.php" class="nav-link<?= $activePage === 'add-event' ? ' active' : '' ?>">
                            <span class="nav-icon">➕</span>
                            <span>Add Event</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <header class="top-header">
                <h1 class="header-title"><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></h1>
                <div class="user-controls">
                    <div class="searchbar">
                        <input id="calendar-search" type="text" placeholder="Search events/venues/descriptions or #tag" />
                    </div>
                    <button class="theme-toggle" onclick="toggleTheme()">
                        <span id="theme-icon">☀️</span> Toggle Theme
                    </button>
                    <div class="user-profile">
                        <img src="https://i.pravatar.cc/150?img=33" alt="User Avatar" class="user-avatar" onclick="toggleUserDropdown()">
                        <div class="user-dropdown" id="userDropdown">
                            <a href="account-unified.php?tab=info" class="dropdown-item">
                                ⚙️ Account & Contact Info
                            </a>
                            <a href="account-unified.php?tab=notifications" class="dropdown-item">
                                🔔 Notifications
                            </a>
                            <a href="account-unified.php?tab=past-events" class="dropdown-item">
                                📜 My Past Events
                            </a>
                            <div class="dropdown-item" onclick="alert('Logging out...')">
                                🚪 Logout
                            </div>
                        </div>
                    </div>
                </div>
            </header>
        <?php
    }

    function renderFooter(): void {
        ?>
            <footer class="footer">
                <p>© <?= date('Y') ?> LENS - Local Event Network Service | Built with ❤️ for the community</p>
            </footer>
        </div>

        <script src="js/calendar-7x5.js"></script>
        <?php
    }
}
