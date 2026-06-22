<?php
$layoutMode = $layoutMode ?? 'feed';
$isHomeFeed = ($layoutMode === 'feed');
$isNoticeBoardReadOnly = ($activeRoom['slug'] ?? '') === 'notice-board' && !has_admin_privileges();
$feedComposeRooms = $feedComposeRooms ?? feed_compose_rooms($rooms ?? [], has_admin_privileges());
$selectedComposeSlug = (string) ($feedComposeRooms[0]['slug'] ?? '');
if ($isHomeFeed && !empty($_GET['post_room'])) {
    $selectedComposeSlug = (string) $_GET['post_room'];
}
$composeRoom = $isHomeFeed ? $selectedComposeSlug : (string)($activeRoom['slug'] ?? '');
$activeRoomName = (string)($activeRoom['name'] ?? 'Room');
$isMuted = !empty($user['is_muted']);
?>
<?php if ($isNoticeBoardReadOnly): ?>
    <div class="composer composer-readonly">
        <i class="bi bi-megaphone"></i>
        <span>Notice Board is read-only for students.</span>
    </div>
<?php elseif ($isMuted): ?>
    <div class="composer composer-readonly">
        <i class="bi bi-volume-mute"></i>
        <span>You are muted until <?= htmlspecialchars(date('M j, g:i A', strtotime((string) $user['muted_until'])), ENT_QUOTES, 'UTF-8'); ?>.</span>
    </div>
<?php else: ?>
    <form class="composer<?= $isHomeFeed ? ' composer--feed' : ' composer--room'; ?>" id="chatComposer" method="POST" action="<?= htmlspecialchars(url($editMessage ? '/chat/messages/edit' : '/chat/messages'), ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
        <?= csrf_input(); ?>
        <?php if (!$isHomeFeed): ?>
            <input type="hidden" name="room" id="composerRoomInput" value="<?= htmlspecialchars($composeRoom, ENT_QUOTES, 'UTF-8'); ?>">
        <?php else: ?>
            <input type="hidden" id="composerRoomInput" value="<?= htmlspecialchars($composeRoom, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="return_to" value="feed">
        <?php endif; ?>
        <input type="hidden" name="reply_to_id" id="replyToInput" value="<?= htmlspecialchars((string)($replyMessage['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="message_id" id="editMessageInput" value="<?= htmlspecialchars((string)($editMessage['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

<?php if ($isHomeFeed && $feedComposeRooms !== []): ?>
            <div class="composer-bar composer-bar-feed">
                <div class="composer-bar-main">
                    <select name="room" id="postRoomSelect" class="compose-select" style="flex:1;">
                        <?php foreach ($feedComposeRooms as $roomOption): ?>
                            <?php $slug = (string) ($roomOption['slug'] ?? ''); ?>
                            <option value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" <?= $slug === $composeRoom ? 'selected' : ''; ?>><?= htmlspecialchars((string) ($roomOption['name'] ?? $slug), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="composer-more-btn" id="composerMoreBtn" aria-expanded="false" aria-controls="composerExtras">
                        <i class="bi bi-sliders"></i>
                    </button>
                </div>
                <div class="composer-extras" id="composerExtras" hidden>
                    <div class="composer-extras-panel" id="assignmentFields" hidden>
                        <select name="post_type" id="postTypeSelect" class="compose-select">
                            <option value="message">Update</option>
                            <option value="assignment">Assignment</option>
                            <?php if (has_admin_privileges()): ?>
                                <option value="announcement">Announcement</option>
                            <?php endif; ?>
                        </select>
                        <input type="text" name="assignment_title" class="compose-input" placeholder="Assignment title">
                        <input type="datetime-local" name="due_at" class="compose-input" aria-label="Due date">
                    </div>
                    <?php if (has_admin_privileges()): ?>
                        <label class="composer-check">
                            <input type="checkbox" id="scheduleToggle">
                            <span>Schedule for later</span>
                        </label>
                        <input type="datetime-local" name="scheduled_at" id="scheduledAtInput" class="compose-input" disabled aria-label="Schedule time">
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="composer-hint">No rooms available to post in yet.</p>
            <?php endif; ?>

        <!-- IMPORTANT: prevent chat composer from accidentally being routed to feed post creation -->
        <input type="hidden" name="_chat_mode" value="1">

        <div class="reply-banner<?= $replyMessage ? ' is-visible' : ''; ?>" id="replyBanner">

            <div>
                <strong id="replyAuthor"><?= htmlspecialchars((string)($replyMessage['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                <span id="replyPreview"><?= htmlspecialchars((string)($replyMessage['body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <button class="message-action-button" id="clearReplyButton" type="button" title="Clear reply"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="reply-banner<?= $editMessage ? ' is-visible' : ''; ?>" id="editBanner">
            <div>
                <strong>Editing message</strong>
                <span id="editPreview"><?= htmlspecialchars((string)($editMessage['body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <button class="message-action-button" id="clearEditButton" type="button" title="Cancel edit"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="composer-row">
            <div class="input-wrap input-wrap-textarea">
                <textarea name="message" id="messageInput" rows="1" placeholder="<?= $isHomeFeed ? 'Share a school update…' : 'Message #' . htmlspecialchars($activeRoomName, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string)($editMessage['body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <?php if ($layoutMode === 'feed'): ?>
                <div class="composer-tools composer-tools-feed">
                    <button class="composer-tool" type="button" id="boldButton" title="Bold"><i class="bi bi-type-bold"></i></button>
                    <button class="composer-tool" type="button" id="italicButton" title="Italic"><i class="bi bi-type-italic"></i></button>
                    <button class="composer-tool" type="button" id="emojiButton" title="Emoji"><i class="bi bi-emoji-smile"></i></button>
                </div>
            <?php endif; ?>
            <label class="composer-tool" title="Attach file">
                <i class="bi bi-paperclip"></i>
                <input type="file" name="attachment" id="attachmentInput" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.mp3,.wav,.ogg,.webm">
            </label>
            <?php if ($layoutMode === 'room'): ?>
                <button class="composer-tool" type="button" id="voiceRecordButton" title="Voice note"><i class="bi bi-mic"></i></button>
            <?php endif; ?>

            <!-- Used by the voice upload flow to attach audio reliably -->
            <input type="hidden" id="attachmentMetaPath" name="attachment_meta_path" value="">
            <input type="hidden" id="attachmentMetaType" name="attachment_meta_type" value="">
            <input type="hidden" id="attachmentMetaName" name="attachment_meta_name" value="">

            <button type="submit" id="composerSubmitButton" class="composer-send" title="Send"><i class="bi bi-send-fill"></i><span class="composer-send-label"><?= $editMessage ? 'Save' : 'Post'; ?></span></button>
        </div>
        <div class="composer-meta">
            <span id="attachmentLabel"></span>
            <span id="typingIndicator" aria-live="polite"></span>
            <span id="recordingIndicator" class="composer-recording-indicator" aria-live="polite"></span>
        </div>
    </form>
    <?php if ($layoutMode === 'feed'): ?>
        <div class="emoji-picker-anchor">
            <div class="emoji-picker" id="emojiPicker" style="display:none;" role="listbox" aria-label="Emoji">
                <div class="emoji-grid">
                    <?php foreach (['😀', '😂', '❤️', '👍', '🔥', '✨', '🎉', '🙏', '💯', '👀', '😮', '🤔'] as $emoji): ?>
                        <button type="button" class="emoji-button" data-emoji="<?= htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8'); ?>"><?= $emoji; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const postTypeSelect = document.getElementById('postTypeSelect');
    const assignmentFields = document.getElementById('assignmentFields');
    
    if (postTypeSelect && assignmentFields) {
        postTypeSelect.addEventListener('change', function() {
            assignmentFields.hidden = this.value !== 'assignment';
        });
    }
});
</script>