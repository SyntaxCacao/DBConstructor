document.querySelectorAll(".js-confirm").forEach(function (element) {
  element.addEventListener("click", function (event) {
    if (! event.target.classList.contains("button-disabled") && ! event.target.disabled && "data-confirm-message" in event.target.attributes) {
      if (! confirm(event.target.attributes["data-confirm-message"].value)) {
        event.preventDefault();
      }
    }
  });
});

document.querySelectorAll("a").forEach(function (element) {
  element.addEventListener("click", function (event) {
    if ("href" in event.target.attributes && event.target.attributes["href"].value === "#") {
      event.preventDefault();
    }
  });
});
