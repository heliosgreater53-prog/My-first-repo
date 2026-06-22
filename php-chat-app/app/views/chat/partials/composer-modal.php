<?php
$feedComposeRooms = $feedComposeRooms ?? feed_compose_rooms($rooms ?? [], has_admin_privileges());
?>

<form id="createPostForm" method="POST" action="<?= htmlspecialchars(url('/posts/create'), ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
    <?= csrf_input(); ?>
    
    <div class="composer-modal-fields">
        <div class="composer-field">
            <label for="modalPostType" class="composer-label">Type:</label>
            <select name="post_type" id="modalPostType" class="compose-select">
                <option value="message">Update</option>
                <option value="assignment">Assignment</option>
                <?php if (has_admin_privileges()): ?>
                    <option value="announcement">Announcement</option>
                <?php endif; ?>
            </select>
        </div>
        
        <div class="composer-field" id="modalAssignmentFields" hidden>
            <input type="text" name="assignment_title" class="compose-input" placeholder="Assignment title">
            <input type="datetime-local" name="due_at" class="compose-input" aria-label="Due date">
        </div>
        
        <?php if (has_admin_privileges()): ?>
        <div class="composer-field composer-field-check">
            <label class="composer-check">
                <input type="checkbox" id="modalScheduleToggle">
                <span>Schedule for later</span>
            </label>
            <input type="datetime-local" name="scheduled_at" id="modalScheduledAtInput" class="compose-input" disabled aria-label="Schedule time">
        </div>
        <?php endif; ?>
        
        <div class="composer-field">
            <textarea name="message" id="modalMessageInput" rows="4" placeholder="Share a school update..." required></textarea>
        </div>

        <div class="composer-field composer-field-attachment">
            <label class="composer-attachment-label" for="modalAttachmentInput">
                <i class="bi bi-paperclip"></i>
                Attach a file / image / video
            </label>
            <input
                type="file"
                name="attachment"
                id="modalAttachmentInput"
                accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.mp3,.wav,.ogg,.webm"
            >
            <div class="composer-attachment-hint" id="modalAttachmentLabel"></div>
        </div>
    </div>

    <div class="modal-footer" style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border);">
        <button type="button" class="modal-close-btn" id="modalCancelBtn">Cancel</button>
        <button type="submit" class="composer-send">Publish</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const postTypeSelect = document.getElementById('modalPostType');
    const assignmentFields = document.getElementById('modalAssignmentFields');
    const scheduleToggle = document.getElementById('modalScheduleToggle');
    const scheduledAtInput = document.getElementById('modalScheduledAtInput');
    
    postTypeSelect?.addEventListener('change', function() {
        assignmentFields.hidden = this.value !== 'assignment';
    });
    
    scheduleToggle?.addEventListener('change', function() {
        scheduledAtInput.disabled = !this.checked;
    });
});
</script>