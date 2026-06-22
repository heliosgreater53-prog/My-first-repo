<?php
// Global modal for feed post likes/comments.
?>
<div class="modal-overlay" id="postCommentsModal" role="dialog" aria-modal="true" aria-labelledby="postCommentsTitle" data-modal="post-comments" style="display:none;">
  <div class="modal-box" style="width:min(96%,720px);">
    <div class="modal-header">
      <h3 id="postCommentsTitle">Comments</h3>
      <button type="button" class="modal-close" id="postCommentsClose" aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
      <div class="post-comments-preview" style="margin-bottom:16px;">
        <div class="post-comments-preview-text js-post-comments-body" style="color:var(--text-soft);line-height:1.6;"></div>
      </div>

      <div class="post-comments-compose" style="display:grid;gap:10px;margin-bottom:14px;">
        <textarea id="postCommentInput" class="modal-input" name="body" placeholder="Write a comment..."></textarea>
        <input type="hidden" id="postCommentPostId" value="">
        <input type="hidden" id="postCommentParentId" value="">
        <div class="post-comments-actions" style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" class="button-reset" id="postCommentCancelReply" style="min-height:44px;">Cancel reply</button>
          <button type="button" class="auth-button" id="postCommentSubmit" style="min-height:44px;">Post comment</button>
        </div>
      </div>

      <div class="post-comments-list js-post-comments-list" style="display:grid;gap:12px;"></div>
    </div>
  </div>
</div>

