document.addEventListener("click", event => {
  const closest = event.target.closest("a");

  if (closest === null) {
    return;
  }

  // .js-confirm
  if (closest.classList.contains("js-confirm") && ! closest.classList.contains("button-disabled") && ! closest.disabled && "confirmMessage" in closest.dataset) {
    if (! confirm(closest.dataset.confirmMessage)) {
      event.preventDefault();
    }
  }

  // href="#"
  if ("href" in closest.attributes && closest.attributes["href"].value === "#") {
    event.preventDefault();
  }
});
