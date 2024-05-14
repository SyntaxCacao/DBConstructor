// https://stackoverflow.com/a/30810322
// Works only on localhost or with HTTPS!
document.querySelectorAll(".js-clipboard-write").forEach(element => {
  element.addEventListener("pointerdown", event => {
    event.preventDefault();

    if (! navigator.clipboard) {
      console.error("Could not write to clipboard (navigator.clipboard does not exist)");
      return;
    }

    navigator.clipboard.writeText(event.currentTarget.dataset.clipboard).then(() => {
    }, error => {
      console.error("Could not write to clipboard", error);
    });
  });
});
