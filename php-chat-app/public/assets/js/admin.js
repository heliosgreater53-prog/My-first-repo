(() => {
  const shell = document.querySelector(".admin-shell");
  if (!shell) {
    return;
  }

  const menuBtn = document.getElementById("adminMenuBtn");
  const backdrop = document.getElementById("adminSidebarBackdrop");

  const closeNav = () => document.body.classList.remove("admin-nav-open");
  const openNav = () => document.body.classList.add("admin-nav-open");
  const toggleNav = () =>
    document.body.classList.toggle("admin-nav-open");

  menuBtn?.addEventListener("click", toggleNav);
  backdrop?.addEventListener("click", closeNav);

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeNav();
    }
  });

  shell.querySelectorAll(".admin-sidebar-link").forEach((link) => {
    link.addEventListener("click", () => {
      if (window.matchMedia("(max-width: 760px)").matches) {
        closeNav();
      }
    });
  });
})();
