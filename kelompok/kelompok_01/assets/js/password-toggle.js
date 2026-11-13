document.querySelectorAll("[data-password-toggle]").forEach(function (toggle) {
    const input = document.getElementById(toggle.getAttribute("aria-controls"));

    if (!input) {
        return;
    }

    toggle.addEventListener("click", function () {
        const shouldShow = input.type === "password";
        input.type = shouldShow ? "text" : "password";
        toggle.classList.toggle("is-visible", shouldShow);
        toggle.setAttribute("aria-label", shouldShow ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
        toggle.setAttribute("aria-pressed", String(shouldShow));
    });
});
