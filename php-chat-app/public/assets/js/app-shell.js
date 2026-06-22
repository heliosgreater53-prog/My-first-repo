(() => {
  const isCentralAdmin = Boolean(document.querySelector(".admin-app"));
  if (isCentralAdmin) {
    return;
  }

  const shell = document.getElementById("chatShell");
  if (!shell) {
    return;
  }

  const panel = document.getElementById("chatInfoPanel");
  const mqTablet = window.matchMedia("(max-width: 1100px)");
  const mqMobile = window.matchMedia("(max-width: 760px)");
  const toggle = document.getElementById("panelDrawerToggle");
  const panelBackdrop = document.getElementById("panelDrawerBackdrop");
  const mobilePanelBtn = document.getElementById("mobilePanelBtn");

  const setPanelOpen = (open) => {
    if (!panel) {
      return;
    }
    const isDesktop = !mqTablet.matches;

    // Persist state so a refresh doesn't re-open.
    try {
      localStorage.setItem(
        "letschat_panel_open",
        open ? "1" : "0",
      );
    } catch (e) {
      // ignore
    }

    document.body.classList.toggle("inspector-collapsed", isDesktop && !open);
    document.body.classList.toggle("panel-drawer-open", !isDesktop && open);
    document.body.classList.toggle("inspector-open", !isDesktop && open);
    if (toggle) {
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
    }
    if (mobilePanelBtn) {
      mobilePanelBtn.setAttribute("aria-expanded", open ? "true" : "false");
    }
    if (panelBackdrop) {
      panelBackdrop.setAttribute(
        "aria-hidden",
        !isDesktop && open ? "false" : "true",
      );
    }
    panel.setAttribute("aria-hidden", open ? "false" : "true");
    if (open) {
      closeRoomsSheet();
    }
  };

  const closePanel = () => setPanelOpen(false);

  const openPanel = () => {
    if (!panel) {
      return;
    }
    const isOpen = mqTablet.matches
      ? document.body.classList.contains("panel-drawer-open")
      : !document.body.classList.contains("inspector-collapsed");
    setPanelOpen(!isOpen);
  };

  toggle?.addEventListener("click", () => {
    if (mqMobile.matches) {
      return;
    }
    const isOpen = mqTablet.matches
      ? document.body.classList.contains("panel-drawer-open")
      : !document.body.classList.contains("inspector-collapsed");
    setPanelOpen(!isOpen);
  });

  mobilePanelBtn?.addEventListener("click", openPanel);
  panelBackdrop?.addEventListener("click", closePanel);

  const roomsBtn = document.getElementById("mobileRoomsBtn");
  const roomsSheet = document.getElementById("mobileRoomsSheet");
  const roomsBackdrop = document.getElementById("mobileRoomsBackdrop");
  const roomsClose = document.getElementById("mobileRoomsClose");

  const setRoomsOpen = (open) => {
    if (!roomsSheet) {
      return;
    }
    document.body.classList.toggle("mobile-rooms-open", open);
    roomsSheet.setAttribute("aria-hidden", open ? "false" : "true");
    if (roomsBtn) {
      roomsBtn.setAttribute("aria-expanded", open ? "true" : "false");
    }
    if (roomsBackdrop) {
      roomsBackdrop.setAttribute("aria-hidden", open ? "false" : "true");
    }
    if (open) {
      closePanel();
    }
  };

  const closeRoomsSheet = () => setRoomsOpen(false);

  roomsBtn?.addEventListener("click", () => {
    setRoomsOpen(!document.body.classList.contains("mobile-rooms-open"));
  });
  roomsBackdrop?.addEventListener("click", closeRoomsSheet);
  roomsClose?.addEventListener("click", closeRoomsSheet);

  // Context (rooms sidebar) toggle for topbar button and backdrop
  const contextToggleBtn = document.getElementById("contextToggleBtn");
  const contextBackdrop = document.getElementById("contextBackdrop");

  const setContextOpen = (open) => {
    document.body.classList.toggle("context-open", open);
    if (contextToggleBtn) {
      contextToggleBtn.setAttribute("aria-expanded", open ? "true" : "false");
    }
    if (contextBackdrop) {
      contextBackdrop.setAttribute("aria-hidden", open ? "false" : "true");
    }
    if (open) {
      // close other panels to avoid overlap
      closePanel();
      closeRoomsSheet();
    }
  };

  const closeContext = () => setContextOpen(false);

  contextToggleBtn?.addEventListener("click", () => {
    setContextOpen(!document.body.classList.contains("context-open"));
  });
  contextBackdrop?.addEventListener("click", closeContext);

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closePanel();
      closeRoomsSheet();
      closeContext();
    }
  });

  mqTablet.addEventListener("change", () => {
    if (!mqTablet.matches) {
      document.body.classList.remove("panel-drawer-open", "inspector-open");
      if (panelBackdrop) {
        panelBackdrop.setAttribute("aria-hidden", "true");
      }
      if (mobilePanelBtn) {
        mobilePanelBtn.setAttribute(
          "aria-expanded",
          document.body.classList.contains("inspector-collapsed")
            ? "false"
            : "true",
        );
      }
      closeContext();
    }
  });

  if (panel) {
    const tabButtons = panel.querySelectorAll(".info-tab-button");
    const tabPanels = panel.querySelectorAll("[data-info-panel]");

    const setInfoTab = (tab) => {
      tabButtons.forEach((button) => {
        button.classList.toggle("is-active", button.dataset.infoTab === tab);
      });
      tabPanels.forEach((tabPanel) => {
        tabPanel.classList.toggle(
          "is-active",
          tabPanel.dataset.infoPanel === tab,
        );
      });
    };

    tabButtons.forEach((button) => {
      button.addEventListener("click", () => {
        setInfoTab(button.dataset.infoTab || "people");
      });
    });

    const tabsRoot = panel.querySelector(".info-tabs");
    const defaultTab = tabsRoot?.dataset.defaultTab || "people";
    setInfoTab(defaultTab);

    if (window.location.hash === "#dmRequestsList") {
      setInfoTab("requests");
      openPanel();
    }
  }

  document.body.classList.add("has-mobile-nav");
})();
