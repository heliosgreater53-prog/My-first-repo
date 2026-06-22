<?php
// Mobile bottom navigation / quick actions
// This partial is required by app-shell-close.php.
// The actual list items are handled by existing markup/classes elsewhere.
?>
<nav class="mobile-dock" aria-label="Mobile navigation">
    <a class="mobile-dock-item" href="<?= htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Explore">
        <i class="bi bi-compass"></i>
        <span>Explore</span>
    </a>
    <a class="mobile-dock-item" href="<?= htmlspecialchars(url('/users'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Users">
        <i class="bi bi-people"></i>
        <span>Users</span>
    </a>

    <a class="mobile-dock-item" href="<?= htmlspecialchars(url('/profile'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Profile">
        <i class="bi bi-person"></i>
        <span>Profile</span>
    </a>
    <a class="mobile-dock-item" href="<?= htmlspecialchars(url('/search'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Search">
        <i class="bi bi-search"></i>
        <span>Search</span>
    </a>
    <a class="mobile-dock-item" href="<?= htmlspecialchars(url('/logout'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Logout">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </a>
</nav>