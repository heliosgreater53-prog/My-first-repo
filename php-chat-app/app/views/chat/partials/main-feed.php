<?php
$postCount = is_array($feedItems ?? null) ? count($feedItems) : 0;
$onlineCount = count(is_array($onlineUsers ?? null) ? $onlineUsers : []);
$feedClassFilter = $feedClassFilter ?? '';
$feedViewFilter = $feedViewFilter ?? 'all';
$notificationCount = (int) ($notificationCount ?? 0);
$todayLabel = '';

$feedItems = array_merge($messages ?? [], $posts ?? []);
usort($feedItems, fn($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));
$feedItems = array_slice($feedItems, 0, 80);
?>


<link rel="stylesheet" href="<?= htmlspecialchars(asset('css/post-attachments.css'), ENT_QUOTES, 'UTF-8'); ?>">

<link rel="stylesheet" href="<?= htmlspecialchars(asset('css/styles.css'), ENT_QUOTES, 'UTF-8'); ?>">

<div class="feed-toolbar">
    <nav class="feed-filter-pills" aria-label="Feed filters">
        <a class="feed-pill<?= $feedViewFilter === 'all' && $feedClassFilter === '' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>">All posts</a>
        <a class="feed-pill<?= $feedViewFilter === 'announcements' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/feed?filter=announcements'), ENT_QUOTES, 'UTF-8'); ?>">Announcements</a>
        <a class="feed-pill<?= $feedViewFilter === 'assignments' ? ' is-active' : ''; ?>" href="<?= htmlspecialchars(url('/feed?filter=assignments'), ENT_QUOTES, 'UTF-8'); ?>">Assignments</a>
    </nav>
    <div class="feed-toolbar-end">
        <form class="feed-filter-group" method="GET" action="<?= htmlspecialchars(url('/feed'), ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($feedViewFilter !== 'all'): ?>
                <input type="hidden" name="filter" value="<?= htmlspecialchars($feedViewFilter, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <select id="feed-class" name="class" class="compose-select" aria-label="Filter by class" onchange="this.form.submit()">
                <option value="">All classes</option>
                <?php foreach ($classOptions ?? [] as $option): ?>
                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= $feedClassFilter === $option ? 'selected' : ''; ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="feed-quick-links">
            <a class="feed-quick-link" href="<?= htmlspecialchars(url('/communities'), ENT_QUOTES, 'UTF-8'); ?>" title="Communities"><i class="bi bi-grid"></i></a>
            <a class="feed-quick-link" href="<?= htmlspecialchars(url('/search'), ENT_QUOTES, 'UTF-8'); ?>" title="Search"><i class="bi bi-search"></i></a>
            <a class="feed-quick-link" href="<?= htmlspecialchars(url('/users'), ENT_QUOTES, 'UTF-8'); ?>" title="People"><i class="bi bi-people"></i></a>
        </div>
    </div>
</div>

<button type="button" class="create-post-trigger" id="createPostTrigger" aria-label="Create post">
    <span class="create-post-placeholder">Share a school update...</span>
</button>

<!-- Create Post Modal -->
<div class="modal-overlay" id="createPostModal" role="dialog" aria-modal="true" aria-labelledby="createPostTitle" data-modal="create-post">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="createPostTitle">Create Post</h3>
            <button type="button" class="modal-close" id="createPostClose" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <?php require view_path('chat/partials/composer-modal'); ?>
        </div>
    </div>
</div>

<?php require view_path('chat/partials/post-comments-modal'); ?>

<div class="conversation-feed conversation-feed--posts" id="conversationFeed" data-room-slug="home" data-layout="feed" data-feed-filter="<?= htmlspecialchars($feedViewFilter, ENT_QUOTES, 'UTF-8'); ?>" data-feed-class="<?= htmlspecialchars($feedClassFilter, ENT_QUOTES, 'UTF-8'); ?>">


    <?php if ($feedItems === []): ?>
        <section class="empty-feed-card">
            <div class="empty-feed-icon"><i class="bi bi-megaphone"></i></div>
            <h3>No posts yet</h3>
            <p>Share a quick school update by clicking the box above.</p>
        </section>
    <?php endif; ?>

    <?php foreach ($feedItems as $item): ?>
        <?php
        $isPost = !isset($item['room_slug']);
        if ($isPost) {
            $post = $item;
            $viewer = $user;
            require view_path('chat/partials/post-feed');
        } else {
            $message = $item;
            $viewer = $user;
            require view_path('chat/partials/message-feed');
        }
        ?>
    <?php endforeach; ?>

</div>

<script>

document.addEventListener('DOMContentLoaded', function() {
    const createPostTrigger = document.getElementById('createPostTrigger');
    const createPostModal = document.getElementById('createPostModal');
    const createPostClose = document.getElementById('createPostClose');
    const modalCancelBtn = document.getElementById('modalCancelBtn');
    
    const openModal = () => createPostModal?.classList.add('is-open');
    const closeModal = () => createPostModal?.classList.remove('is-open');
    
    createPostTrigger?.addEventListener('click', openModal);
    createPostClose?.addEventListener('click', closeModal);
    modalCancelBtn?.addEventListener('click', closeModal);
    
    createPostModal?.addEventListener('click', (e) => {
        if (e.target === createPostModal) closeModal();
    });
});

</script>

