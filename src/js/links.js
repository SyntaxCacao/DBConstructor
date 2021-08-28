document.querySelectorAll("a").forEach(function(element) {
  element.addEventListener("click", function(event) {
    if (event.target.attributes["href"].value == "#") {
      event.preventDefault();
    }
  });
});
