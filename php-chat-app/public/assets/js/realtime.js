(() => {
  const csrfToken = document.body.dataset.csrfToken || "";

  const formPath = (form) => {
    try {
      return new URL(form.action, window.location.href).pathname;
    } catch {
      return "";
    }
  };

  const pathEndsWith = (path, suffix) =>
    path === suffix || path.endsWith(suffix);

  const showToast = (message, type = "success") => {
    if (!message) return;
    let stack = document.querySelector(".toast-stack");
    if (!stack) {
      stack = document.createElement("div");
      stack.className = "toast-stack";
      document.body.appendChild(stack);
    }
    const toast = document.createElement("div");
    toast.className = `toast toast-${type === "error" ? "error" : "success"}`;
    toast.textContent = message;
    stack.appendChild(toast);
    
    requestAnimationFrame(() => toast.classList.add("show"));
    
    window.setTimeout(() => {
      toast.classList.remove("show");
      toast.addEventListener("transitionend", () => toast.remove(), { once: true });
    }, 4200);
  };

  const postFormJson = async (form) => {
    const body = new FormData(form);
    body.append("format", "json");
    const response = await fetch(form.action, {
      method: "POST",
      body,
      credentials: "same-origin",
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    let payload = null;
    try {
      payload = await response.json();
    } catch {
      payload = { ok: false, message: "Unexpected server response." };
    }

    if (!payload || typeof payload !== "object") {
      return { ok: false, message: "Unexpected server response." };
    }

    return payload;
  };

  const updateDirectoryPendingRequests = (inbox) => {
    const list = document.querySelector(".directory-request-list");
    const section = list?.closest(".directory-aside-section");
    if (!list || !section) return;

    const requests = Array.isArray(inbox) ? inbox : [];
    if (requests.length === 0) {
      section.innerHTML = `<div class="empty-section-notice">
        <i class="bi bi-inbox"></i>
        <p>No pending requests</p>
        <span class="muted">You'll see incoming DM requests here.</span>
      </div>`;
      return;
    }

    const respondAction =
      list.querySelector('form[action*="dm-request/respond"]')?.action ||
      document.querySelector('form[action*="dm-request/respond"]')?.action ||
      "";

    list.innerHTML = requests
      .map((dmReq) => {
        const requesterName = dmReq.requester_name || "User";
        const requesterAvatar = dmReq.requester_avatar || "";
        const requestId = dmReq.id || "";
        const initials = requesterName
          .split(/\s+/)
          .slice(0, 2)
          .map((part) => part.charAt(0).toUpperCase())
          .join("");
        const avatarMarkup = requesterAvatar
          ? `<img src="${escapeAttr(requesterAvatar)}" alt="">`
          : `<div class="avatar-fallback">${escapeHtml(initials || "U")}</div>`;

        return `<div class="directory-request-item">
          <div class="directory-request-avatar">${avatarMarkup}</div>
          <div class="directory-request-info">
            <strong>${escapeHtml(requesterName)}</strong>
            <p class="muted">${escapeHtml(dmReq.requester_email || "")}</p>
          </div>
          <div class="directory-request-actions">
            <form method="POST" action="${escapeAttr(respondAction)}" class="inline-form">
              <input type="hidden" name="_token" value="${escapeAttr(csrfToken)}">
              <input type="hidden" name="request_id" value="${escapeAttr(String(requestId))}">
              <input type="hidden" name="decision" value="accepted">
              <button class="directory-btn-accept" type="submit" title="Accept"><i class="bi bi-check-lg"></i></button>
            </form>
            <form method="POST" action="${escapeAttr(respondAction)}" class="inline-form">
              <input type="hidden" name="_token" value="${escapeAttr(csrfToken)}">
              <input type="hidden" name="request_id" value="${escapeAttr(String(requestId))}">
              <input type="hidden" name="decision" value="declined">
              <button class="directory-btn-decline" type="submit" title="Decline"><i class="bi bi-x-lg"></i></button>
            </form>
          </div>
        </div>`;
      })
      .join("");
  };

  const applyDmRequestsPayload = (dmRequests) => {
    if (!dmRequests) return;
    const inbox = dmRequests.inbox || [];
    const sent = dmRequests.sent || [];
    const countEl = document.getElementById("dmRequestCount");
    if (countEl) {
      const enabled =
        document.body.dataset.dmNotificationsEnabled === "1";
      countEl.textContent = enabled ? String(inbox.length) : "0";
    }
    const listEl = document.getElementById("dmRequestsList");
    if (listEl && typeof window.renderDmRequests === "function") {
      listEl.innerHTML = window.renderDmRequests(inbox, sent, csrfToken);
    }
    updateDirectoryPendingRequests(inbox);
  };

  const markDirectoryRequestSent = (form) => {
    if (!form.classList.contains("directory-chat-form")) return;
    form.outerHTML = '<span class="dm-state-badge">Request sent</span>';
  };

  const handleDmRequest = async (form) => {
    const button = form.querySelector('[type="submit"]');
    if (button) button.disabled = true;
    try {
      const payload = await postFormJson(form);
      if (!payload.ok) {
        showToast(payload.message || "Unable to send DM request.", "error");
        return;
      }
      showToast(payload.message || "DM request sent.", "success");
      applyDmRequestsPayload(payload.dmRequests);
      markDirectoryRequestSent(form);
      const onlineForm = form.closest(".online-user-form");
      if (onlineForm) {
        const peerName =
          button?.getAttribute("aria-label")?.replace(/^Message\s+/i, "") ||
          "User";
        onlineForm.outerHTML = `<p class="dm-empty">Request sent to ${escapeHtml(peerName)}.</p>`;
      }
    } finally {
      if (button) button.disabled = false;
    }
  };

  const handleDmRespond = async (form) => {
    const button = form.querySelector('[type="submit"]');
    if (button) button.disabled = true;
    try {
      const payload = await postFormJson(form);
      if (!payload.ok) {
        showToast(payload.message || "Unable to update DM request.", "error");
        return;
      }
      showToast(payload.message || "DM request updated.", "success");
      applyDmRequestsPayload(payload.dmRequests);
      if (payload.decision === "accepted" && payload.room_slug) {
        const target = payload.redirect_url || `/chat?room=${encodeURIComponent(payload.room_slug)}`;
        window.setTimeout(() => {
          window.location.assign(target);
        }, 350);
        return;
      }
      if (typeof window.refreshChatStream === "function") {
        window.refreshChatStream();
      }
    } finally {
      if (button) button.disabled = false;
    }
  };

  document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    const path = formPath(form);
    if (pathEndsWith(path, "/users/dm-request/respond")) {
      event.preventDefault();
      handleDmRespond(form);
      return;
    }
    if (
      pathEndsWith(path, "/users/dm-request") &&
      !pathEndsWith(path, "/users/dm-request/respond")
    ) {
      event.preventDefault();
      handleDmRequest(form);
    }
  });

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function escapeAttr(value) {
    return escapeHtml(value);
  }

  window.LetsChatRealtime = {
    showToast,
    postFormJson,
    applyDmRequestsPayload,
  };
})();
