document.addEventListener("DOMContentLoaded", () => {
  console.log("[Chat] DOM loaded");
  const feed =
    document.getElementById("conversationFeed") ||
    document.getElementById("conversationFeedRoom");
  const composer = document.getElementById("chatComposer");
  const messageInput = document.getElementById("messageInput");
  const replyToInput = document.getElementById("replyToInput");
  const replyBanner = document.getElementById("replyBanner");
  const replyAuthor = document.getElementById("replyAuthor");
  const replyPreview = document.getElementById("replyPreview");
  const clearReplyButton = document.getElementById("clearReplyButton");
  const editBanner = document.getElementById("editBanner");
  const editPreview = document.getElementById("editPreview");
  const editMessageInput = document.getElementById("editMessageInput");
  const composerRoomInput = document.getElementById("composerRoomInput");
  const clearEditButton = document.getElementById("clearEditButton");
  const submitButton = document.getElementById("composerSubmitButton");
  const attachmentInput = document.getElementById("attachmentInput");
  const attachmentLabel = document.getElementById("attachmentLabel");
  const voiceRecordButton = document.getElementById("voiceRecordButton");
  const typingIndicator = document.getElementById("typingIndicator");
  const recordingIndicator = document.getElementById("recordingIndicator");
  const emojiButton = document.getElementById("emojiButton");
  const emojiPicker = document.getElementById("emojiPicker");
  const boldButton = document.getElementById("boldButton");
  const italicButton = document.getElementById("italicButton");
  const codeButton = document.getElementById("codeButton");
  const onlineUsersList = document.getElementById("onlineUsersList");
  const onlineUsersCount = document.getElementById("onlineUsersCount");
  const socialPostCount = document.getElementById("socialPostCount");
  const socialOnlineCount = document.getElementById("socialOnlineCount");
  const dmRequestCount = document.getElementById("dmRequestCount");
  const dmRequestsList = document.getElementById("dmRequestsList");

  // This JS file handles BOTH:
  // - post actions (feed page)
  // - chat/message actions (chat page)
  // So we must not early-return just because chat-only elements are absent.
  // feed is required for message actions/stream rendering, but the composer
  // (send/reply/edit/voice) must still work even if a particular feed id isn't present.
  if (!feed) {
    console.warn(
      "[Chat] Missing feed container element (conversationFeed). Message stream/actions will be disabled.",
    );
  }

  const roomSlug = feed.dataset.roomSlug || "";
  const layoutMode = feed.dataset.layout || "feed";
  const authUserId = Number(document.body.dataset.authUserId || 0);
  const csrfToken = document.body.dataset.csrfToken || "";
  const isAdmin = document.body.dataset.isAdmin === "1";
  let lastMessageCount = null;
  let lastMessageSignature = null;
  let lastOnlineSignature = null;
  let lastDmRequestsSignature = null;

  // Markdown formatting helpers
  const insertMarkdown = (prefix, suffix) => {
    const start = messageInput.selectionStart;
    const end = messageInput.selectionEnd;
    const text = messageInput.value;
    const selected = text.substring(start, end);
    const replacement = prefix + selected + suffix;
    messageInput.value =
      text.substring(0, start) + replacement + text.substring(end);
    messageInput.focus();
    messageInput.setSelectionRange(start + prefix.length, end + prefix.length);
    updateTextareaHeight();
  };

  const insertEmoji = (emoji) => {
    const start = messageInput.selectionStart;
    const end = messageInput.selectionEnd;
    const text = messageInput.value;
    messageInput.value = text.substring(0, start) + emoji + text.substring(end);
    messageInput.focus();
    messageInput.setSelectionRange(start + emoji.length, start + emoji.length);
    updateTextareaHeight();
    emojiPicker.style.display = "none";
  };

  if (boldButton) {
    boldButton.addEventListener("click", () => insertMarkdown("**", "**"));
  }
  if (italicButton) {
    italicButton.addEventListener("click", () => insertMarkdown("*", "*"));
  }
  if (codeButton) {
    codeButton.addEventListener("click", () => insertMarkdown("`", "`"));
  }
  if (emojiButton && emojiPicker) {
    emojiButton.addEventListener("click", (e) => {
      e.preventDefault();
      emojiPicker.style.display =
        emojiPicker.style.display === "none" ? "block" : "none";
    });
  }

  if (emojiPicker) {
    emojiPicker.addEventListener("click", (e) => {
      if (e.target.classList.contains("emoji-button")) {
        insertEmoji(e.target.dataset.emoji);
      }
    });
  }

  // Close emoji picker when clicking outside
  document.addEventListener("click", (e) => {
    if (
      emojiPicker &&
      emojiPicker.style.display === "block" &&
      !emojiPicker.closest(".emoji-picker-anchor").contains(e.target) &&
      e.target !== emojiButton
    ) {
      emojiPicker.style.display = "none";
    }
  });

  const scrollFeedToBottom = () => {
    feed.scrollTop = feed.scrollHeight;
  };

  const postRoomSelect = document.getElementById("postRoomSelect");
  postRoomSelect?.addEventListener("change", () => {
    const hidden = document.getElementById("composerRoomInput");
    if (hidden) hidden.value = postRoomSelect.value;
  });

  const postTypeSelect = document.getElementById("postTypeSelect");
  const assignmentFields = document.getElementById("assignmentFields");
  const composerExtras = document.getElementById("composerExtras");
  const composerMoreBtn = document.getElementById("composerMoreBtn");

  postTypeSelect?.addEventListener("change", () => {
    const isAssignment = postTypeSelect.value === "assignment";
    if (assignmentFields) assignmentFields.hidden = !isAssignment;
    if (isAssignment && composerExtras) {
      composerExtras.hidden = false;
      composerMoreBtn?.setAttribute("aria-expanded", "true");
    }
  });

  composerMoreBtn?.addEventListener("click", () => {
    if (!composerExtras) return;
    const willOpen = composerExtras.hidden;
    composerExtras.hidden = !willOpen;
    composerMoreBtn.setAttribute("aria-expanded", willOpen ? "true" : "false");
  });

  document
    .getElementById("scheduleToggle")
    ?.addEventListener("change", function () {
      const inp = document.getElementById("scheduledAtInput");
      if (inp) inp.disabled = !this.checked;
    });

  const updateTextareaHeight = () => {
    messageInput.style.height = "auto";
    messageInput.style.height = `${Math.min(messageInput.scrollHeight, 160)}px`;
  };

  const updateComposerMode = () => {
    const editing = Boolean(editMessageInput && editMessageInput.value);
    composer.action = editing ? "/chat/messages/edit" : "/chat/messages";
    if (submitButton) {
      submitButton.innerHTML = editing
        ? '<i class="bi bi-check2"></i><span>Save</span>'
        : '<i class="bi bi-send-fill"></i>';
    }
  };

  const setComposerRoom = (room = "") => {
    if (composerRoomInput && room) composerRoomInput.value = room;
  };

  const setReplyState = (messageId = "", author = "", body = "", room = "") => {
    if (!replyToInput || !replyBanner || !replyAuthor || !replyPreview) return;
    replyToInput.value = messageId;
    if (!messageId) {
      replyBanner.classList.remove("is-visible");
      replyAuthor.textContent = "";
      replyPreview.textContent = "";
      return;
    }
    setComposerRoom(room);
    replyAuthor.textContent = author;
    replyPreview.textContent = body;
    replyBanner.classList.add("is-visible");
  };

  const setEditState = (messageId = "", body = "", room = "") => {
    if (!editMessageInput || !editBanner || !editPreview) return;
    editMessageInput.value = messageId;

    if (!messageId) {
      editBanner.classList.remove("is-visible");
      editPreview.textContent = "";
      updateComposerMode();
      return;
    }
    setComposerRoom(room);
    editPreview.textContent = body;
    messageInput.value = body;
    editBanner.classList.add("is-visible");
    updateComposerMode();
    updateTextareaHeight();
    messageInput.focus();
  };

  const bindMessageActions = () => {
    // ===== Feed post actions (like / comments) =====
    const postLikeButtons = document.querySelectorAll(".js-post-like");
    const postCommentsButtons = document.querySelectorAll(".js-post-comments");

    const postCommentsModal = document.getElementById("postCommentsModal");

    const postCommentsClose = document.getElementById("postCommentsClose");
    const postCommentsBody = document.querySelector(".js-post-comments-body");
    const postCommentInput = document.getElementById("postCommentInput");
    const postCommentPostId = document.getElementById("postCommentPostId");
    const postCommentParentId = document.getElementById("postCommentParentId");
    const postCommentSubmit = document.getElementById("postCommentSubmit");
    const postCommentCancelReply = document.getElementById(
      "postCommentCancelReply",
    );
    const postCommentsList = document.querySelector(".js-post-comments-list");

    const openCommentsModal = () => {
      postCommentsModal?.classList.add("is-open");
      // also set display for inline style modal
      if (postCommentsModal) postCommentsModal.style.display = "flex";
      postCommentInput?.focus?.();
    };

    const closeCommentsModal = () => {
      postCommentsModal?.classList.remove("is-open");
      if (postCommentsModal) postCommentsModal.style.display = "none";
    };

    postCommentsClose?.addEventListener("click", closeCommentsModal);

    postCommentsModal?.addEventListener("click", (e) => {
      if (e.target === postCommentsModal) closeCommentsModal();
    });

    const renderCommentNodes = (nodes, level = 0) => {
      if (!Array.isArray(nodes) || nodes.length === 0) {
        return '<div style="color:var(--text-faint);">No comments yet</div>';
      }

      return nodes
        .map((node) => {
          const indent = Math.min(level, 6) * 14;
          const children =
            node.children && node.children.length
              ? renderCommentNodes(node.children, level + 1)
              : "";
          const replyTargetId = String(node.id);
          const safeName = node.name || "User";
          const safeBody = node.body || "";
          return `
            <div class="post-comment-node" style="margin-left:${indent}px; padding:10px; border:1px solid var(--border); border-radius:12px; background:var(--surface);">
              <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                <div>
                  <strong style="display:block; font-size:13px;">${escapeHtml(safeName)}</strong>
                  <div style="color:var(--text-soft); font-size:13px; line-height:1.6; margin-top:6px; white-space:pre-wrap;">${escapeHtml(safeBody)}</div>
                </div>
                <button type="button" class="button-reset js-comment-reply" style="min-height:38px;" data-parent-id="${replyTargetId}">Reply</button>
              </div>
              ${children}
            </div>
          `;
        })
        .join("");
    };

    const escapeHtml = (value) => {
      return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "<")
        .replace(/>/g, ">")
        .replace(/"/g, '"')
        .replace(/'/g, "&#039;");
    };

    postLikeButtons.forEach((btn) => {
      btn.addEventListener("click", async () => {
        const postId = Number(btn.dataset.postId || 0);
        if (!postId) return;
        try {
          const res = await fetch("/posts/like", {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
              "X-Requested-With": "XMLHttpRequest",
            },
            body: new URLSearchParams({
              _token: csrfToken,
              post_id: String(postId),
            }),
          });
          const payload = await res.json();
          if (!payload?.ok) return;
          const countEl = btn.querySelector(".js-post-like-count");
          if (countEl) countEl.textContent = String(payload.likeCount ?? 0);
        } catch (e) {
          console.error("Like failed", e);
        }
      });
    });

    // Initialize counters on page load.
    const initPostCounters = async () => {
      const postLikeButtonsLocal = document.querySelectorAll(".js-post-like");
      const postCommentsButtonsLocal =
        document.querySelectorAll(".js-post-comments");

      const tasks = [];

      postLikeButtonsLocal.forEach((btn) => {
        const postId = Number(btn.dataset.postId || 0);
        if (!postId) return;
        const countEl = btn.querySelector(".js-post-like-count");
        if (!countEl) return;

        tasks.push(
          fetch(
            `/posts/like-count?post_id=${encodeURIComponent(String(postId))}`,
            {
              headers: { "X-Requested-With": "XMLHttpRequest" },
            },
          )
            .then((r) => r.json())
            .then((payload) => {
              if (payload?.ok)
                countEl.textContent = String(payload.likeCount ?? 0);
            })
            .catch(() => {}),
        );
      });

      postCommentsButtonsLocal.forEach((btn) => {
        const postId = Number(btn.dataset.postId || 0);
        if (!postId) return;
        const countEl = btn.querySelector(".js-post-comment-count");
        if (!countEl) return;

        tasks.push(
          fetch(
            `/posts/comments-count?post_id=${encodeURIComponent(String(postId))}`,
            {
              headers: { "X-Requested-With": "XMLHttpRequest" },
            },
          )
            .then((r) => r.json())
            .then((payload) => {
              if (payload?.ok)
                countEl.textContent = String(payload.commentsCount ?? 0);
            })
            .catch(() => {}),
        );
      });

      await Promise.allSettled(tasks);
    };

    initPostCounters();

    postCommentsButtons.forEach((btn) => {
      btn.addEventListener("click", async () => {
        const postId = Number(btn.dataset.postId || 0);
        if (!postId) return;

        if (postCommentPostId) postCommentPostId.value = String(postId);
        if (postCommentParentId) postCommentParentId.value = "";

        openCommentsModal();
        if (postCommentsBody) postCommentsBody.textContent = "Loading...";
        if (postCommentsList) postCommentsList.innerHTML = "";

        try {
          const res = await fetch(
            `/posts/comments?post_id=${encodeURIComponent(String(postId))}`,
            {
              headers: { "X-Requested-With": "XMLHttpRequest" },
            },
          );
          const payload = await res.json();
          if (!payload?.ok) return;
          if (postCommentsList)
            postCommentsList.innerHTML = renderCommentNodes(
              payload.comments || [],
            );
          if (postCommentsBody) postCommentsBody.textContent = "Comments";
        } catch (e) {
          console.error("Load comments failed", e);
        }
      });
    });

    postCommentCancelReply?.addEventListener("click", () => {
      if (postCommentParentId) postCommentParentId.value = "";
    });

    postCommentsList?.addEventListener("click", (e) => {
      const replyBtn = e.target.closest(".js-comment-reply");
      if (!replyBtn) return;
      const parentId = replyBtn.dataset.parentId || "";
      if (postCommentParentId) postCommentParentId.value = String(parentId);
      postCommentInput?.focus?.();
    });

    postCommentSubmit?.addEventListener("click", async () => {
      const postId = Number(postCommentPostId?.value || 0);

      const parentIdRaw = postCommentParentId?.value || "";
      const parentId = parentIdRaw ? Number(parentIdRaw) : null;
      const body = (postCommentInput?.value || "").trim();
      if (!postId || body === "") return;

      try {
        const res = await fetch("/posts/comments", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest",
          },
          body: new URLSearchParams({
            _token: csrfToken,
            post_id: String(postId),
            parent_id: parentId !== null ? String(parentId) : "",
            body,
          }),
        });
        const payload = await res.json();
        if (!payload?.ok) return;

        if (postCommentInput) postCommentInput.value = "";
        if (postCommentParentId) postCommentParentId.value = "";
        if (postCommentsList)
          postCommentsList.innerHTML = renderCommentNodes(
            payload.comments || [],
          );

        // Update comment counter on the main feed button.
        const commentCountEls = document.querySelectorAll(
          `.js-post-comments[data-post-id="${CSS.escape(String(postId))}"] .js-post-comment-count`,
        );
        const refreshCount = async () => {
          const r = await fetch(
            `/posts/comments-count?post_id=${encodeURIComponent(String(postId))}`,
            {
              headers: { "X-Requested-With": "XMLHttpRequest" },
            },
          );
          const p = await r.json();
          const nextCount = String(p?.commentsCount ?? 0);
          commentCountEls.forEach((el) => (el.textContent = nextCount));
        };
        refreshCount().catch(() => {});
      } catch (e) {
        console.error("Create comment failed", e);
      }
    });

    // Message actions (reply/edit) are re-rendered during refreshStream().
    // Use event delegation to keep handlers working reliably.
    feed?.addEventListener("click", (e) => {
      const replyBtn = e.target.closest(".js-reply-post");
      if (replyBtn) {
        const cluster = replyBtn.closest("[data-message-id]");
        if (!cluster) return;
        setEditState();
        setReplyState(
          cluster.dataset.messageId || "",
          cluster.dataset.messageAuthor || "",
          cluster.dataset.messageBody || "",
          cluster.dataset.messageRoom || "",
        );
        return;
      }

      const editBtn = e.target.closest(".js-edit-post");
      if (editBtn) {
        const cluster = editBtn.closest("[data-message-id]");
        if (!cluster || cluster.dataset.messageCanEdit !== "1") return;
        setReplyState();
        setEditState(
          cluster.dataset.messageId || "",
          cluster.dataset.messageBody || "",
          cluster.dataset.messageRoom || "",
        );
      }
    });

    document.querySelectorAll(".js-copy-post").forEach((button) => {
      button.onclick = async () => {
        const cluster = button.closest("[data-message-id]");
        if (!cluster) return;
        const body = cluster.dataset.messageBody || "";
        try {
          await navigator.clipboard.writeText(body);
          button.classList.add("is-active");
          setTimeout(() => button.classList.remove("is-active"), 800);
        } catch (error) {
          console.error("Copy failed", error);
        }
      };
    });

    document.querySelectorAll(".js-confirm-delete").forEach((form) => {
      form.onsubmit = () =>
        window.confirm("Delete this post? This cannot be undone.");
    });
  };

  // Bind handlers for posts on feed page even when chat-only elements are missing.
  bindMessageActions();

  // Chat UI behaviors (reply/edit/stream/typing) only if chat elements exist.
  if (composer && messageInput) {
    if (layoutMode === "room") scrollFeedToBottom();
    updateTextareaHeight();
    updateComposerMode();
  }

  messageInput.addEventListener("input", updateTextareaHeight);
  messageInput.addEventListener("input", () => {
    // If the user is actively typing, poll faster for a bit so incoming messages appear quickly.
    markActivity();

    fetch("/chat/typing", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: new URLSearchParams({ _token: csrfToken, room: roomSlug }),
    }).catch(() => {});
  });

  if (attachmentInput && attachmentLabel) {
    attachmentInput.addEventListener("change", () => {
      attachmentLabel.textContent =
        attachmentInput.files && attachmentInput.files[0]
          ? attachmentInput.files[0].name
          : "";
    });
  }

  // Composer modal attachment label
  const modalAttachmentInput = document.getElementById("modalAttachmentInput");
  const modalAttachmentLabel = document.getElementById("modalAttachmentLabel");
  if (modalAttachmentInput && modalAttachmentLabel) {
    modalAttachmentInput.addEventListener("change", () => {
      modalAttachmentLabel.textContent =
        modalAttachmentInput.files && modalAttachmentInput.files[0]
          ? modalAttachmentInput.files[0].name
          : "";
    });
  }

  if (voiceRecordButton && navigator.mediaDevices && window.MediaRecorder) {
    let recorder = null;
    let chunks = [];
    let streamRef = null;

    const clearVoiceMeta = () => {
      const pathEl = document.getElementById("attachmentMetaPath");
      const typeEl = document.getElementById("attachmentMetaType");
      const nameEl = document.getElementById("attachmentMetaName");
      if (pathEl) pathEl.value = "";
      if (typeEl) typeEl.value = "";
      if (nameEl) nameEl.value = "";
      if (attachmentInput) attachmentInput.value = "";
    };

    voiceRecordButton.addEventListener("click", async () => {
      if (recorder && recorder.state === "recording") {
        recorder.stop();
        voiceRecordButton.classList.remove("is-recording");

        fetch("/chat/recording", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest",
          },
          body: new URLSearchParams({
            _token: csrfToken,
            room: roomSlug,
            state: "0",
          }),
        }).catch(() => {});

        return;
      }

      clearVoiceMeta();

      try {
        streamRef = await navigator.mediaDevices.getUserMedia({ audio: true });
        chunks = [];
        recorder = new MediaRecorder(streamRef);

        recorder.ondataavailable = (event) => chunks.push(event.data);

        recorder.onstop = async () => {
          try {
            const blob = new Blob(chunks, { type: "audio/webm" });
            const file = new File([blob], `voice-${Date.now()}.webm`, {
              type: "audio/webm",
            });

            const fd = new FormData();
            fd.append("attachment", file);
            fd.append("_token", csrfToken);
            fd.append("room", roomSlug);
            fd.append("format", "json");

            const res = await fetch("/chat/voice/upload", {
              method: "POST",
              body: fd,
              credentials: "same-origin",
              headers: { "X-Requested-With": "XMLHttpRequest" },
            });

            const payload = await res.json().catch(() => ({
              ok: false,
              message: "Unexpected upload response.",
            }));
            if (!payload?.ok || !payload?.attachment) {
              showToast(
                payload?.message || "Unable to upload voice note.",
                "error",
              );
              return;
            }

            const { path, type, name } = payload.attachment;

            const pathEl = document.getElementById("attachmentMetaPath");
            const typeEl = document.getElementById("attachmentMetaType");
            const nameEl = document.getElementById("attachmentMetaName");
            if (pathEl) pathEl.value = path || "";
            if (typeEl) typeEl.value = type || "";
            if (nameEl) nameEl.value = name || "";

            if (attachmentLabel)
              attachmentLabel.textContent = "Voice note ready";
          } finally {
            streamRef?.getTracks?.().forEach((track) => track.stop());
            streamRef = null;
          }
        };

        recorder.start();
        voiceRecordButton.classList.add("is-recording");
        markActivity();

        fetch("/chat/recording", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest",
          },
          body: new URLSearchParams({
            _token: csrfToken,
            room: roomSlug,
            state: "1",
          }),
        }).catch(() => {});
      } catch (error) {
        console.error("Voice recording failed", error);
        showToast("Voice recording failed.", "error");
      }
    });
  }

  if (clearReplyButton) {
    clearReplyButton.addEventListener("click", () => setReplyState());
  }

  if (clearEditButton) {
    clearEditButton.addEventListener("click", () => {
      setEditState();
      messageInput.value = "";
      updateTextareaHeight();
    });
  }

  const showToast = (message, type = "success") => {
    window.LetsChatRealtime?.showToast(message, type);
  };

  const resetComposerAfterSend = () => {
    messageInput.value = "";
    setReplyState();
    setEditState();
    if (attachmentInput) attachmentInput.value = "";
    if (attachmentLabel) attachmentLabel.textContent = "";
    updateTextareaHeight();
    updateComposerMode();
  };

  composer.addEventListener("submit", async (event) => {
    markActivity();
    event.preventDefault();
    if (submitButton) submitButton.disabled = true;
    try {
      markActivity();

      const payload = window.LetsChatRealtime
        ? await window.LetsChatRealtime.postFormJson(composer)
        : null;

      if (!payload?.ok) {
        showToast(payload?.message || "Unable to send message.", "error");
        return;
      }

      resetComposerAfterSend();
      await refreshStream();
      if (layoutMode === "room") scrollFeedToBottom();
    } catch (error) {
      console.error("Composer submit failed", error);
      showToast("Unable to send message.", "error");
    } finally {
      if (submitButton) submitButton.disabled = false;
    }
  });

  messageInput.addEventListener("keydown", (event) => {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      composer.requestSubmit();
    }
  });

  // Simple markdown to HTML converter
  const parseMarkdown = (text) => {
    if (!text) return "";
    // Escape HTML first
    let escaped = text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
    // Code blocks (inline)
    escaped = escaped.replace(/`([^`]+)`/g, "<code>$1</code>");
    // Bold
    escaped = escaped.replace(/\\*\\*([^*]+)\\*\\*/g, "<strong>$1</strong>");
    // Italic
    escaped = escaped.replace(/\\*([^*]+)\\*/g, "<em>$1</em>");
    return escaped;
  };

  const refreshStream = async () => {
    if (!roomSlug) return;

    const feedFilter = feed.dataset.feedFilter || "";
    const feedClass = feed.dataset.feedClass || "";
    let streamUrl = `/chat/stream?room=${encodeURIComponent(roomSlug)}`;
    if (feedFilter && feedFilter !== "all") {
      streamUrl += `&filter=${encodeURIComponent(feedFilter)}`;
    }
    if (feedClass) {
      streamUrl += `&class=${encodeURIComponent(feedClass)}`;
    }

    try {
      const response = await fetch(streamUrl, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      if (!response.ok) return;

      const payload = await response.json();
      if (!payload.ok || !Array.isArray(payload.messages)) return;

      const newMessageCount = payload.messages.length;
      if (socialPostCount)
        socialPostCount.textContent = String(newMessageCount);
      const hasNewMessage =
        lastMessageCount !== null && newMessageCount > lastMessageCount;
      lastMessageCount = newMessageCount;

      const messageSignature = getMessageSignature(payload.messages);
      const currentMessageIds = Array.from(
        feed.querySelectorAll("[data-message-id]"),
        (node) => String(node.dataset.messageId || ""),
      ).filter(Boolean);
      const payloadMessageIds = payload.messages.map((message) =>
        String(message.id || ""),
      );
      if (
        lastMessageSignature === null &&
        currentMessageIds.join("|") === payloadMessageIds.join("|")
      ) {
        lastMessageSignature = messageSignature;
      }
      if (messageSignature !== lastMessageSignature) {
        const previousScrollBottom =
          Math.abs(feed.scrollHeight - feed.scrollTop - feed.clientHeight) < 40;
        feed.innerHTML = renderMessages(
          payload.messages,
          authUserId,
          isAdmin,
          roomSlug,
          csrfToken,
          layoutMode,
        );
        bindMessageActions();
        lastMessageSignature = messageSignature;

        if (layoutMode === "room" && previousScrollBottom) scrollFeedToBottom();
      }

      if (Array.isArray(payload.rooms)) {
        payload.rooms.forEach((room) => {
          const roomLink = document.querySelector(
            `[data-room-slug="${CSS.escape(room.slug || "")}"]`,
          );
          if (!roomLink) return;
          const preview = roomLink.querySelector(".room-preview");
          const badge = roomLink.querySelector(".unread-badge");
          if (preview)
            preview.textContent =
              room.last_message_body || room.description || "No messages yet.";
          if (badge) {
            badge.textContent =
              room.unread_count > 0 ? String(room.unread_count) : "";
            badge.style.display =
              room.unread_count > 0 ? "inline-flex" : "none";
          }
          roomLink.classList.toggle(
            "has-unread",
            Number(room.unread_count || 0) > 0,
          );
        });
      }

      if (typingIndicator) {
        typingIndicator.textContent =
          Array.isArray(payload.typing) && payload.typing.length > 0
            ? `${payload.typing.join(", ")} ${payload.typing.length === 1 ? "is" : "are"} typing...`
            : "";
      }

      if (recordingIndicator) {
        recordingIndicator.textContent =
          Array.isArray(payload.recording) && payload.recording.length > 0
            ? `${payload.recording.join(", ")} ${payload.recording.length === 1 ? "is" : "are"} recording audio...`
            : "";
      }

      if (onlineUsersList && Array.isArray(payload.onlineUsers)) {
        const onlineSignature = getPayloadSignature(payload.onlineUsers);
        if (onlineSignature !== lastOnlineSignature) {
          onlineUsersList.innerHTML = renderOnlineUsers(payload.onlineUsers);
          lastOnlineSignature = onlineSignature;
        }
        if (onlineUsersCount)
          onlineUsersCount.textContent = String(payload.onlineUsers.length);
        if (socialOnlineCount)
          socialOnlineCount.textContent = String(payload.onlineUsers.length);
      }

      if (dmRequestsList && payload.dmRequests) {
        const inbox = Array.isArray(payload.dmRequests.inbox)
          ? payload.dmRequests.inbox
          : [];
        const sent = Array.isArray(payload.dmRequests.sent)
          ? payload.dmRequests.sent
          : [];
        const dmRequestsSignature = getPayloadSignature({ inbox, sent });
        if (dmRequestsSignature !== lastDmRequestsSignature) {
          dmRequestsList.innerHTML = renderDmRequests(inbox, sent, csrfToken);
          lastDmRequestsSignature = dmRequestsSignature;
        }
        if (dmRequestCount) {
          dmRequestCount.textContent =
            document.body.dataset.dmNotificationsEnabled === "1"
              ? String(inbox.length)
              : "0";
        }
      }

      if (
        hasNewMessage &&
        document.hidden &&
        Notification.permission === "granted" &&
        document.body.dataset.browserNotificationsEnabled === "1"
      ) {
        const latestMessage = payload.messages[payload.messages.length - 1];
        if (latestMessage && Number(latestMessage.user_id) !== authUserId) {
          const targetSlug =
            latestMessage.room_slug ||
            (layoutMode === "feed" ? "home" : roomSlug);
          const targetRoomName =
            latestMessage.room_name ||
            document
              .querySelector(".room-channel-title h2")
              ?.textContent?.trim() ||
            "LivingSpring";
          navigator.serviceWorker.getRegistration().then((registration) => {
            registration?.active?.postMessage({
              type: "CHAT_MESSAGE",
              roomName: targetRoomName,
              authorName: latestMessage.name || "Someone",
              messageBody:
                latestMessage.body?.substring(0, 100) || "New message",
              roomSlug: targetSlug,
              layoutMode,
            });
          });
        }
      }
    } catch (error) {
      console.error("Stream refresh failed", error);
    }
  };

  // Polling is simple but can feel laggy. Make it faster right after user activity.
  // (No websockets; still polling.)
  let recentActivityUntil = 0;
  const activityBurstMs = 10000; // poll fast for 10s after activity
  const activityPollMs = 700; // ~instant-feel without being websocket-level

  const markActivity = () => {
    recentActivityUntil = Date.now() + activityBurstMs;
  };

  const streamDelay = () => {
    const now = Date.now();
    const inBurst = now < recentActivityUntil;

    if (inBurst) return activityPollMs;

    // Default: faster when visible, slower when backgrounded.
    return document.hidden ? 30000 : 3000;
  };

  const scheduleStreamPoll = () => {
    window.setTimeout(async () => {
      // When tab is hidden, still allow burst polling if activity recently happened.
      if (!document.hidden || streamDelay() <= 8000) {
        await refreshStream();
      }
      scheduleStreamPoll();
    }, streamDelay());
  };

  window.refreshChatStream = refreshStream;
  window.renderDmRequests = renderDmRequests;

  scheduleStreamPoll();
});

function getMessageSignature(messages) {
  if (!Array.isArray(messages)) return "";
  return JSON.stringify(
    messages.map((message) => ({
      id: message.id,
      body: message.body || "",
      deleted_at: message.deleted_at || "",
      updated_at: message.updated_at || "",
      reply_count: message.reply_count || 0,
      reactions: message.reactions || {},
      is_pinned: message.is_pinned || 0,
      attachment_path: message.attachment_path || "",
      attachment_name: message.attachment_name || "",
      attachment_type: message.attachment_type || "",
    })),
  );
}

function getPayloadSignature(payload) {
  return JSON.stringify(payload || null);
}

function roleBadgeMarkup(role) {
  if (role === "admin") {
    return '<span class="avatar-role-badge avatar-role-badge--admin" title="Central Admin"><i class="bi bi-patch-check-fill"></i></span>';
  }
  if (role === "class_rep" || role === "moderator") {
    return '<span class="avatar-role-badge avatar-role-badge--mod" title="Moderator"><i class="bi bi-shield-fill"></i></span>';
  }
  return "";
}

function roleLabel(role) {
  if (role === "admin") {
    return "Central Admin";
  }
  if (role === "class_rep" || role === "moderator") {
    return "Moderator";
  }
  return "";
}

function roleMetaBadgeMarkup(role, modifier) {
  const label = roleLabel(role);
  if (!label) return "";
  const badgeClass =
    modifier === "post" ? "post-role-badge" : "message-role-badge";
  const roleClass = role === "admin" ? "admin" : "mod";
  const icon = role === "admin" ? "bi-patch-check-fill" : "bi-shield-fill";

  return `<span class="${badgeClass} ${badgeClass}--${roleClass}"><i class="bi ${icon}"></i>${escapeHtml(label)}</span>`;
}

function renderMessages(
  messages,
  authUserId,
  isAdmin,
  roomSlug,
  csrfToken,
  layoutMode = "feed",
) {
  let currentDate = "";

  if (!Array.isArray(messages) || messages.length === 0) {
    if (layoutMode === "room") {
      return `<section class="empty-feed-card empty-feed-card--room">
        <div class="empty-feed-icon"><i class="bi bi-chat-dots"></i></div>
        <h3>Start the conversation</h3>
        <p>Say hello or ask a question in this room.</p>
      </section>`;
    }
    return `<section class="empty-feed-card">
      <div class="empty-feed-icon"><i class="bi bi-megaphone"></i></div>
      <h3>No posts yet</h3>
      <p>Share the first school-wide update.</p>
    </section>`;
  }

  const parseMarkdown = (text) => {
    if (!text) return "";
    let escaped = text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
    escaped = escaped.replace(/`([^`]+)`/g, "<code>$1</code>");
    escaped = escaped.replace(/\*\*([^*]+)\*\*/g, "<strong>$1</strong>");
    escaped = escaped.replace(/\*([^*]+)\*/g, "<em>$1</em>");
    return escaped.replace(/\n/g, "<br>");
  };

  return messages
    .map((message) => {
      const timestamp = new Date(message.created_at);
      const dateLabel = timestamp.toLocaleDateString(undefined, {
        weekday: "long",
        month: "long",
        day: "numeric",
      });
      const timeLabel = timestamp.toLocaleTimeString(undefined, {
        hour: "numeric",
        minute: "2-digit",
      });

      const name = message.name || "User";
      const isDeleted = Boolean(message.deleted_at);
      const isMine = Number(message.user_id) === authUserId;
      const canDelete = !isDeleted && (isMine || isAdmin);
      const canEdit = !isDeleted && isMine;
      const isPinned = Number(message.is_pinned || 0) === 1;
      const reactions = message.reactions || {};
      const messageRoomSlug = message.room_slug || roomSlug;
      const messageRoomName = message.room_name || messageRoomSlug || "Feed";

      let dateDivider = "";
      if (dateLabel !== currentDate) {
        currentDate = dateLabel;
        dateDivider = `<div class="message-date">${escapeHtml(dateLabel)}</div>`;
      }

      if (layoutMode === "room") {
        return (
          dateDivider +
          renderRoomMessage(
            message,
            authUserId,
            isAdmin,
            roomSlug,
            csrfToken,
            parseMarkdown,
          )
        );
      }

      const avatarUrl =
        message.avatar_path ||
        `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random`;
      const roleBadge = roleBadgeMarkup(
        message.user_role || message.role || "",
      );
      const roleMetaBadge = roleMetaBadgeMarkup(
        message.user_role || message.role || "",
        "post",
      );

      const interactionsMarkup = `
      <div class="post-interactions">
        <form method="POST" action="/chat/messages/react" class="interaction-form">
          <input type="hidden" name="_token" value="${escapeAttribute(csrfToken)}">
          <input type="hidden" name="message_id" value="${escapeAttribute(String(message.id))}">
          <input type="hidden" name="room" value="${escapeAttribute(messageRoomSlug)}">
          <input type="hidden" name="reaction" value="like">
          <button class="post-action-btn ${reactions.like ? "is-active" : ""}" type="submit">
            <i class="bi ${reactions.like ? "bi-heart-fill" : "bi-heart"}"></i>
            <span>${Number(reactions.like || 0)}</span>
          </button>
        </form>
        <button class="post-action-btn js-reply-post" title="Comment">
          <i class="bi bi-chat-dots"></i>
          <span>${message.reply_count || 0}</span>
        </button>
        <button class="post-action-btn js-copy-post" title="Share Content">
          <i class="bi bi-share"></i>
        </button>
        ${canEdit ? `<button class="post-action-btn js-edit-post"><i class="bi bi-pencil"></i></button>` : ""}
        ${
          canDelete
            ? `<form method="POST" action="/chat/messages/delete" class="js-confirm-delete d-inline">
            <input type="hidden" name="_token" value="${escapeAttribute(csrfToken)}"><input type="hidden" name="room" value="${escapeAttribute(messageRoomSlug)}"><input type="hidden" name="message_id" value="${escapeAttribute(String(message.id))}">
            <button class="post-action-btn text-danger" type="submit"><i class="bi bi-trash"></i></button>
          </form>`
            : ""
        }
      </div>`;

      const contextMarkup = message.reply_body
        ? `<div class="post-context">Replying to <strong>${escapeHtml(message.reply_name)}</strong></div>`
        : "";

      const attachmentMarkup = renderAttachment(message);

      return `${dateDivider}
      <article class="post-card${isMine ? " is-mine" : ""}" data-message-id="${message.id}" data-message-author="${escapeAttribute(name)}" data-message-body="${escapeAttribute(message.body || "")}" data-message-room="${escapeAttribute(messageRoomSlug)}" data-message-can-edit="${canEdit ? "1" : "0"}">
        <header class="post-header">
          <div class="avatar-with-role-badge">
            <img src="${avatarUrl}" class="post-avatar">
            ${roleBadge}
          </div>
          <div class="post-meta">
            <span class="post-author">${escapeHtml(name)}</span>
            ${roleMetaBadge}
            <span class="post-room-tag">#${escapeHtml(messageRoomName)}</span>
            <span class="post-time">${escapeHtml(timeLabel)}</span>
          </div>
          ${isPinned ? '<i class="bi bi-pin-angle-fill ms-auto text-success"></i>' : ""}
        </header>
        
        <section class="post-body">
          ${contextMarkup}
          <div class="post-text">${parseMarkdown(isDeleted ? "[This post has been deleted]" : message.body || "")}</div>
          ${!isDeleted ? attachmentMarkup : ""}
        </section>

        <footer class="post-footer">
          ${interactionsMarkup}
        </footer>
      </article>`;
    })
    .join("");
}

function renderRoomMessage(
  message,
  authUserId,
  isAdmin,
  roomSlug,
  csrfToken,
  parseMarkdown,
) {
  const name = message.name || "User";
  const isDeleted = Boolean(message.deleted_at);
  const isMine = Number(message.user_id) === authUserId;
  const canDelete = !isDeleted && (isMine || isAdmin);
  const canEdit = !isDeleted && isMine;
  const timeLabel = new Date(message.created_at).toLocaleTimeString(undefined, {
    hour: "numeric",
    minute: "2-digit",
  });
  const roleBadge = roleBadgeMarkup(message.user_role || message.role || "");
  const roleMetaBadge = roleMetaBadgeMarkup(
    message.user_role || message.role || "",
    "message",
  );
  const initials =
    name
      .split(/\s+/)
      .slice(0, 2)
      .map((w) => w.charAt(0).toUpperCase())
      .join("") || "U";
  const avatar = message.avatar_path
    ? `<img class="cluster-avatar" src="${escapeAttribute(message.avatar_path)}" alt="">`
    : `<div class="cluster-avatar cluster-avatar-fallback">${escapeHtml(initials)}</div>`;
  const replySnippet = message.reply_body
    ? `<div class="reply-snippet"><strong>${escapeHtml(message.reply_name || "Reply")}</strong><span>${escapeHtml(message.reply_body)}</span></div>`
    : "";

  return `<div class="message-cluster${isMine ? " mine" : ""}" data-message-id="${message.id}" data-message-author="${escapeAttribute(name)}" data-message-body="${escapeAttribute(message.body || "")}" data-message-room="${escapeAttribute(roomSlug)}" data-message-can-edit="${canEdit ? "1" : "0"}">
    ${isMine ? "" : `<div class="avatar-with-role-badge">${avatar}${roleBadge}</div>`}
    <div class="message-bundle">
      ${isMine ? "" : `<div class="message-meta"><strong>${escapeHtml(name)}</strong>${roleMetaBadge}<span>${escapeHtml(timeLabel)}</span></div>`}
      ${replySnippet}
      <div class="message-bubble ${isMine ? "sent" : "received"}">${parseMarkdown(isDeleted ? "[deleted]" : message.body || "")}${!isDeleted ? renderAttachment(message) : ""}</div>
      <div class="message-actions">
        <button class="message-action-button js-reply-post" type="button" title="Reply"><i class="bi bi-arrow-return-left"></i></button>
        ${canEdit ? `<button class="message-action-button js-edit-post" type="button"><i class="bi bi-pencil"></i></button>` : ""}
        ${canDelete ? `<form method="POST" action="/chat/messages/delete" class="js-confirm-delete d-inline"><input type="hidden" name="_token" value="${escapeAttribute(csrfToken)}"><input type="hidden" name="room" value="${escapeAttribute(roomSlug)}"><input type="hidden" name="message_id" value="${message.id}"><button class="message-action-button text-danger" type="submit"><i class="bi bi-trash"></i></button></form>` : ""}
      </div>
    </div>
  </div>`;
}

function renderAttachment(message) {
  if (!message.attachment_path) return "";
  const path = escapeAttribute(message.attachment_path);
  const name = escapeHtml(message.attachment_name || "Download file");
  if (message.attachment_type === "image") {
    return `<div class="message-attachment"><a href="${path}" target="_blank" rel="noopener"><img src="${path}" alt="Attachment"></a></div>`;
  }
  if (message.attachment_type === "audio") {
    return `<div class="message-attachment"><audio controls src="${path}"></audio></div>`;
  }
  return `<div class="message-attachment"><a class="attachment-link" href="${path}" target="_blank" rel="noopener"><i class="bi bi-paperclip"></i> ${name}</a></div>`;
}

function renderOnlineUsers(users) {
  if (!Array.isArray(users) || users.length === 0) {
    return '<p class="no-users-text">No one is online in this room yet.</p>';
  }

  return users
    .map((user) => {
      const name = user.name || "Unknown";
      const initials =
        name
          .split(/\s+/)
          .slice(0, 2)
          .map((word) => word.charAt(0).toUpperCase())
          .join("") || "U";
      const avatar = user.avatar_path
        ? `<img class="online-user-avatar" src="${escapeAttribute(user.avatar_path)}" alt="${escapeAttribute(name)}">`
        : `<div class="online-user-avatar-fallback">${escapeHtml(initials)}</div>`;

      return `<div class="online-user-item">
      ${avatar}
      <div class="online-user-info">
        <strong>${escapeHtml(name)}</strong>
        <span>${escapeHtml(user.class_name || "")}</span>
      </div>
      <span class="presence-dot online"></span>
    </div>`;
    })
    .join("");
}

function renderDmRequests(inbox, sent, csrfToken) {
  if (typeof csrfToken !== "string" || csrfToken === "") {
    csrfToken = document.body.dataset.csrfToken || "";
  }
  const incoming = inbox
    .map(
      (request) => `<article class="dm-request-item">
    <div>
      <strong>${escapeHtml(request.requester_name || "User")}</strong>
      <span>${escapeHtml(request.requester_class || "")} wants to DM you</span>
    </div>
    <div class="dm-request-actions">
      <form method="POST" action="/users/dm-request/respond">
        <input type="hidden" name="_token" value="${escapeAttribute(csrfToken)}">
        <input type="hidden" name="request_id" value="${escapeAttribute(String(request.id || ""))}">
        <input type="hidden" name="decision" value="accepted">
        <button type="submit">Accept</button>
      </form>
      <form method="POST" action="/users/dm-request/respond">
        <input type="hidden" name="_token" value="${escapeAttribute(csrfToken)}">
        <input type="hidden" name="request_id" value="${escapeAttribute(String(request.id || ""))}">
        <input type="hidden" name="decision" value="declined">
        <button class="decline" type="submit">Decline</button>
      </form>
    </div>
  </article>`,
    )
    .join("");

  const outgoing = sent
    .map(
      (request) => `<article class="dm-request-item is-sent">
    <div>
      <strong>${escapeHtml(request.recipient_name || "User")}</strong>
      <span>Request sent / waiting</span>
    </div>
  </article>`,
    )
    .join("");

  return incoming || outgoing
    ? incoming + outgoing
    : '<p class="dm-empty">No pending DM requests.</p>';
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function escapeAttribute(value) {
  return escapeHtml(value);
}
