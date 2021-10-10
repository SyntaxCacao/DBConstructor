document.querySelectorAll(".js-confirm").forEach(function (element) {
  element.addEventListener("click", function (event) {
    if (! event.target.classList.contains("button-disabled") && ! event.target.disabled && "data-confirm-message" in event.target.attributes) {
      if (! confirm(event.target.attributes["data-confirm-message"].value)) {
        event.preventDefault();
      }
    }
  });
});

document.addEventListener("click", event => {
  const closest = event.target.closest("a");

  if (closest !== null && "href" in closest.attributes && closest.attributes["href"].value === "#") {
    event.preventDefault();
  }
});
