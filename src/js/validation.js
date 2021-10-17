document.addEventListener("change", event => {
  const closest = event.target.closest(".js-validate-within");

  if (closest !== null && "data-rules-element" in closest.attributes && "data-column-id" in closest.attributes) {
    let value = event.target.value;

    fetch(new Request(document.body.attributes["data-baseurl"].value + "/validation?id=" + encodeURIComponent(closest.attributes["data-column-id"].value) + "&value=" + encodeURIComponent(value)), {redirect: "manual"})
      .then(response => {
        if (response.ok) {
          response.text().then(text => {
            const rulesElement = document.querySelector(closest.attributes["data-rules-element"].value);
            rulesElement.innerHTML = text;

            if (rulesElement.querySelector(".js-result").attributes["data-result"].value === "0") {
              closest.classList.add("page-table-insert-invalid");
            } else {
              closest.classList.remove("page-table-insert-invalid");
            }
          });
        } else {
          console.error("Validation failed");
        }
      });
  }

  const closestRelational = event.target.closest(".js-validate-relational");

  if (closestRelational !== null && "data-rules-element" in closestRelational.attributes && "data-nullable" in closestRelational.attributes) {
    const value = event.target.value;
    const rulesElements = document.querySelector(closestRelational.attributes["data-rules-element"].value).querySelectorAll(".validation-step");

    closestRelational.classList.remove("page-table-insert-invalid");

    let existsIndex = 0;
    let validIndex = 1;

    if (closestRelational.attributes["data-nullable"].value === "false") {
      if (value === "") {
        setFailed(rulesElements.item(0));
        closestRelational.classList.add("page-table-insert-invalid");
      } else {
        setSuccess(rulesElements.item(0));
      }

      existsIndex = 1;
      validIndex = 2;
    }

    if (value === "") {
      setSkipped(rulesElements.item(existsIndex));
      setSkipped(rulesElements.item(validIndex));
    } else {
      setSuccess(rulesElements.item(existsIndex));

      closestRelational.querySelectorAll("option").forEach(option => {
        if (option.attributes["value"].value === value) {
          if (option.attributes["data-valid"].value === "true") {
            setSuccess(rulesElements.item(validIndex));
          } else {
            setFailed(rulesElements.item(validIndex));
            closestRelational.classList.add("page-table-insert-invalid");
          }
        }
      });
    }
  }
});

function setFailed(element) {
  element = element.querySelector(".bi");
  element.classList.remove("bi-check-lg");
  element.classList.remove("bi-dash-lg");
  element.classList.add("bi-x-lg");
}

function setSkipped(element) {
  element = element.querySelector(".bi");
  element.classList.remove("bi-check-lg");
  element.classList.remove("bi-x-lg");
  element.classList.add("bi-dash-lg");
}

function setSuccess(element) {
  element = element.querySelector(".bi");
  element.classList.remove("bi-dash-lg");
  element.classList.remove("bi-x-lg");
  element.classList.add("bi-check-lg");
}
