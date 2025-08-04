function updateDependingField(element) {
  let groupElement = element;

  while (! groupElement.classList.contains("form-group-depend")) {
    groupElement = groupElement.parentElement;
  }

  const dependency = document.querySelector('[name="field-' + element.dataset.dependsOn + '"]');
  let show;

  if (dependency.matches("input[type=checkbox]")) {
    // checkbox
    if (element.dataset.dependsOnValue === "checked") {
      show = dependency.checked;
    } else {
      show = ! dependency.checked;
    }
  } else {
    // other element
    show = document.querySelector('[name="field-' + element.dataset.dependsOn + '"]').value === element.dataset.dependsOnValue;
  }

  if (dependency.matches("[data-depends-on]") && ! dependency.parentElement.classList.contains("form-group-depend-show")) {
    show = false;
  }

  if (show) {
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

// // //

let form = document.querySelector("#form-export");

if (form !== null) {
  form.onsubmit = (event) => {
    event.target.classList.add("submitted");
  };
}
