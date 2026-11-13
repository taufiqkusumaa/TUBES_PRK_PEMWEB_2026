export function bindControls() {
  // MENU NAVIGASI PUBLIK - ORANG 4
  const mobileButton = document.getElementById("mobile-menu-button");
  const mobileMenu = document.getElementById("mobile-menu");
  if (mobileButton && mobileMenu)
    mobileButton.addEventListener("click", () =>
      mobileMenu.classList.toggle("hidden"),
    );

  const adminButton = document.getElementById("admin-menu-button");
  const sidebar = document.getElementById("admin-sidebar");
  const backdrop = document.getElementById("admin-backdrop");
  const toggleAdmin = () => {
    if (!sidebar || !backdrop) return;
    sidebar.classList.toggle("-translate-x-full");
    backdrop.classList.toggle("hidden");
  };
  if (adminButton) adminButton.addEventListener("click", toggleAdmin);
  if (backdrop) backdrop.addEventListener("click", toggleAdmin);

  // KONFIRMASI FORM (BERSAMA) - INFRASTRUKTUR
  document.querySelectorAll("[data-confirm]").forEach((form) => {
    form.addEventListener("submit", (event) => {
      const message =
        form.getAttribute("data-confirm") || "Yakin ingin melanjutkan?";
      if (!window.confirm(message)) event.preventDefault();
    });
  });

  const imageInput = document.querySelector("[data-image-input]");
  const imagePreview = document.querySelector("[data-image-preview]");
  if (imageInput && imagePreview) {
    let previewObjectUrl = null;
    imageInput.addEventListener("change", () => {
      const file = imageInput.files && imageInput.files[0];
      if (!file) return;
      if (!file.type.startsWith("image/")) return;
      if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
      previewObjectUrl = URL.createObjectURL(file);
      imagePreview.src = previewObjectUrl;
      imagePreview.classList.remove("hidden");
    });
  }

  const togglePassword = document.getElementById("toggle-password");
  togglePassword?.addEventListener("click", () => {
    const password = document.getElementById("password");
    password.type = password.type === "password" ? "text" : "password";
  });

  for (const image of document.querySelectorAll(".faculty-news-grid img")) {
    image.addEventListener(
      "error",
      () => {
        image.src = new URL(
          "../assets/news-placeholder.svg",
          import.meta.url,
        ).href;
      },
      { once: true },
    );
  }
}
