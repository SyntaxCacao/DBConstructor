function updateDependingField(element) {
  let groupElement = element;

  while (! groupElement.classList.contains("form-group-depend")) {
    groupElement = groupElement.parentElement;
  }

  if (document.querySelector('[name="field-' + element.dataset.dependsOn + '"]').value === element.dataset.dependsOnValue) {
    groupElement.classList.add("form-group-depend-show");
  } else if (groupElement.classList.contains("form-group-depend-show")) {
    groupElement.classList.remove("form-group-depend-show");
  }
}

document.querySelectorAll("[data-depends-on]").forEach(function(element) {
  updateDependingField(element);
  document.querySelector('[name="field-' + element.dataset.dependsOn + '"]').addEventListener("change", function() {
    document.querySelectorAll("[data-depends-on]").forEach(function(elem) {
      updateDependingField(elem);
    });
  });
});
