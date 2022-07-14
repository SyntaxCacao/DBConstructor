document.addEventListener("click", function(event) {
  if (event.target.matches(".form-list-create")) {
    event.preventDefault();

    const counter = event.target.parentNode.querySelector(".form-list-counter");
    const count = parseInt(counter.value);

    let rows = event.target.parentNode.querySelector(".form-list-rows");
    rows.appendChild(rows.children[0].cloneNode(true));
    rows = event.target.parentNode.querySelector(".form-list-rows");

    rows.children[rows.children.length-1].querySelectorAll("input").forEach(function (element) {
      element.name = "field-" + rows.dataset.listName + "-" + (count+1) + "-" + element.dataset.columnName;
      element.value = null;
    });

    counter.value = count+1;
  } else if (event.target.matches(".form-list-delete")) {
    event.preventDefault();

    let rows = event.target.parentNode.parentNode;

    if (rows.children.length === 1) {
      // if only one row exists, empty it instead of removing it
      rows.children[0].querySelectorAll("input").forEach(function (element) {
        element.value = null;
      });
    } else {
      rows.removeChild(event.target.parentNode);
    }
  }
});
