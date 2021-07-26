function updateDependingField(element) {
  let groupElement = element;

  while (! groupElement.classList.contains("form-group-depend")) {
    groupElement = groupElement.parentElement;
  }

  if (document.querySelector('[name="field-' + element.attributes["data-depends-on"].value + '"]').value == element.attributes["data-depends-on-value"].value) {
    groupElement.classList.add("form-group-depend-show");
  } else if (groupElement.classList.contains("form-group-depend-show")) {
    groupElement.classList.remove("form-group-depend-show");
  }
}

document.querySelectorAll("[data-depends-on]").forEach(function(element) {
  updateDependingField(element);
  document.querySelector('[name="field-' + element.attributes["data-depends-on"].value + '"]').addEventListener("change", function(event) {
    document.querySelectorAll("[data-depends-on]").forEach(function(elem) {
      updateDependingField(elem);
    });
  });
});
