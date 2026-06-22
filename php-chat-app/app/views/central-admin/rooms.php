<?php
$activeAdminPage = 'rooms';
require view_path('partials.header');
$rooms = $rooms ?? [];
require view_path('partials.central-admin-layout-open');
?>
            <div class="admin-page-head">
                <div>
                    <span class="admin-kicker">Rooms</span>
                    <h1>Manage rooms</h1>
                    <p>Edit room details, set passwords, or delete rooms.</p>
                </div>
                <button class="auth-button button-reset admin-primary-action" onclick="openCreateRoomModal()"><i class="bi bi-plus-lg"></i> Create room</button>
            </div>

            <section class="admin-panel">
                <div class="admin-list admin-room-list">
                    <?php foreach ($rooms as $room): ?>
                        <?php
                        $roomId = (int) $room['id'];
                        $hasPassword = !empty($room['password_hash']);
                        $scopeLabels = ['public' => 'Public', 'class' => 'Class', 'direct' => 'Direct'];
                        $scopeLabel = $scopeLabels[$room['scope'] ?? 'public'] ?? 'Public';
                        $roomPayload = json_encode([
                            'id' => $roomId,
                            'name' => (string) ($room['name'] ?? ''),
                            'description' => (string) ($room['description'] ?? ''),
                            'scope' => (string) ($room['scope'] ?? 'public'),
                            'class_name' => (string) ($room['class_name'] ?? ''),
                            'accent_color' => (string) ($room['accent_color'] ?? '#14b8a6'),
                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG);
                        $deleteMessage = json_encode('Delete room "' . (string) ($room['name'] ?? 'Room') . '" and all its messages? This cannot be undone.', JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG);
                        ?>
                        <article class="admin-card admin-room-card">
                            <div class="admin-card-head">
                                <div>
                                    <div class="admin-user-title">
                                        <strong><?= htmlspecialchars((string) ($room['name'] ?? 'Room'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span class="room-scope-badge <?= htmlspecialchars((string) ($room['scope'] ?? 'public'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <?php if ($hasPassword): ?>
                                            <span class="room-password-badge" title="Password protected"><i class="bi bi-key"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <p>Slug: <?= htmlspecialchars((string) ($room['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p>Created: <?= htmlspecialchars(date('M j, Y', strtotime((string) ($room['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (!empty($room['last_message_at']) || !empty($room['created_at'])): ?> · Last message: <?= htmlspecialchars(date('M j, g:i A', strtotime((string) ($room['last_message_at'] ?? $room['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                                    </p>
                                </div>
                                <div class="admin-account-state">
                                    <span class="admin-state-pill"><?= (int) ($room['message_count'] ?? 0) ?> messages</span>
                                </div>
                            </div>

                            <div class="admin-room-actions">
                                <button class="admin-inline-button" onclick='openEditRoomModal(<?= $roomPayload ?>)'>
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="admin-inline-button danger" onclick='showConfirm(<?= $deleteMessage ?>, function(){ deleteRoom(<?= $roomId ?>); })'>
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($rooms === []): ?>
                        <p class="admin-empty">No rooms exist yet.</p>
                    <?php endif; ?>
                </div>
            </section>
<?php require view_path('partials.admin-layout-close'); ?>

<!-- Create Room Modal Template (hidden) -->
<div class="modal-overlay" id="createRoomModalTemplate" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Create a room</h3>
            <button class="modal-close" type="button">&times;</button>
        </div>
        <div class="modal-body">
            <form id="createRoomForm" method="POST" action="<?= htmlspecialchars(url('/central-admin/rooms/create'), ENT_QUOTES, 'UTF-8'); ?>">
                <?= csrf_input(); ?>
                <div class="field">
                    <label>Room name</label>
                    <input name="name" type="text" placeholder="e.g., Homework Help" required>
                </div>
                <div class="field">
                    <label>Description</label>
                    <input name="description" type="text" placeholder="What is this room for?">
                </div>
                <div class="field">
                    <label>Scope</label>
                    <select name="scope" class="field-select">
                        <option value="public">Public (whole app)</option>
                        <option value="class">Class only</option>
                    </select>
                </div>
                <div class="field">
                    <label>Class (optional)</label>
                    <select name="class_name" class="field-select">
                        <option value="">Public (all classes)</option>
                        <?php foreach (['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'] as $option): ?>
                            <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="field">
                     <label>Accent color</label>
                     <input name="accent_color" type="color" value="#14b8a6">
                 </div>
                <div class="field">
                    <label>Password (optional)</label>
                    <input name="password" type="password" placeholder="Leave blank for open access">
                    <small>Set a password to restrict who can join this room.</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="button-reset auth-button" type="button">Cancel</button>
            <button class="button-reset auth-button" type="button">Create room</button>
        </div>
    </div>
</div>

<!-- Edit Room Modal Template (hidden) -->
<div class="modal-overlay" id="editRoomModalTemplate" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Edit room</h3>
            <button class="modal-close" type="button">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editRoomForm" method="POST" action="<?= htmlspecialchars(url('/central-admin/rooms/update'), ENT_QUOTES, 'UTF-8'); ?>">
                <?= csrf_input(); ?>
                <input type="hidden" name="room_id" id="editRoomId">
                <div class="field">
                    <label>Room name</label>
                    <input name="name" type="text" id="editRoomName" required>
                </div>
                <div class="field">
                    <label>Description</label>
                    <input name="description" type="text" id="editRoomDescription">
                </div>
                 <div class="field">
                     <label>Accent color</label>
                     <input name="accent_color" type="color" id="editRoomAccent" value="#14b8a6">
                 </div>
                <div class="field">
                    <label>Password (leave blank to keep/remove)</label>
                    <input name="password" type="text" id="editRoomPassword" placeholder="New password or empty">
                    <small>Set a password to restrict room access. Clear to remove restriction.</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="button-reset auth-button" type="button">Cancel</button>
            <button class="button-reset auth-button" type="button">Save changes</button>
        </div>
    </div>
</div>

<script>
function openCreateRoomModal() {
    const template = document.getElementById('createRoomModalTemplate');
    const clone = template.cloneNode(true);
    clone.id = '';
    clone.style.display = 'block';
    document.body.appendChild(clone);

    const cancelBtn = clone.querySelector('.modal-footer .button-reset');
    const createBtn = clone.querySelector('.modal-footer .button-reset:last-child');
    const form = clone.querySelector('#createRoomForm');

    cancelBtn.onclick = function() { document.body.removeChild(clone); };
    createBtn.onclick = function() { if (form) { form.submit(); } };
}

function openEditRoomModal(room) {
    const template = document.getElementById('editRoomModalTemplate');
    const clone = template.cloneNode(true);
    clone.id = '';
    clone.style.display = 'block';
    document.body.appendChild(clone);

    clone.querySelector('#editRoomId').value = room.id || '';
    clone.querySelector('#editRoomName').value = room.name || '';
    clone.querySelector('#editRoomDescription').value = room.description || '';
    clone.querySelector('#editRoomAccent').value = room.accent_color || '#14b8a6';
    clone.querySelector('#editRoomPassword').value = '';

    const cancelBtn = clone.querySelector('.modal-footer .button-reset');
    const saveBtn = clone.querySelector('.modal-footer .button-reset:last-child');
    const form = clone.querySelector('#editRoomForm');

    cancelBtn.onclick = function() { document.body.removeChild(clone); };
    saveBtn.onclick = function() { if (form) { form.submit(); } };
}

function deleteRoom(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= htmlspecialchars(url('/central-admin/rooms/delete'), ENT_QUOTES, 'UTF-8'); ?>';
    form.style.display = 'none';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '<?= csrf_token(); ?>';
    form.appendChild(csrf);
    const roomId = document.createElement('input');
    roomId.type = 'hidden';
    roomId.name = 'room_id';
    roomId.value = id;
    form.appendChild(roomId);
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php require view_path('partials.footer'); ?>
