document.querySelectorAll(".dropdown").forEach(element => {
  element.addEventListener("toggle", () => {
    if (element.open) {
      document.body.classList.add("js-dropdown-open");
    } else {
      document.body.classList.remove("js-dropdown-open");
    }
  });
});

document.addEventListener("click", event => {
  if (document.body.classList.contains("js-dropdown-open") && (event.target.closest(".dropdown-menu") === null || event.target.closest(".js-dropdown-close") !== null) && event.target.closest(".dropdown[open] summary") === null) {
    document.querySelectorAll(".dropdown[open]").forEach(element => {
      element.removeAttribute("open");
    });
  }
});
