document.addEventListener("change", event => {
  const closest = event.target.closest(".js-validate-within");

  if (closest !== null && "data-rules-element" in closest.attributes && "data-column-id" in closest.attributes) {
    let value = event.target.value;

    fetch(document.body.attributes["data-baseurl"].value + "/api/validation/", {
      body: "id=" + encodeURIComponent(closest.attributes["data-column-id"].value) + "&value=" + encodeURIComponent(value),
      headers: new Headers({
        "Content-Type": "application/x-www-form-urlencoded"
      }),
      method: "POST",
      redirect: "manual"
    }).then(response => {
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
});

function setFailed(element) {
  element.querySelector(".bi").className = "bi bi-x-lg";
}

function setSkipped(element) {
  element.querySelector(".bi").className = "bi bi-dash-lg";
}

function setSuccess(element) {
  element.querySelector(".bi").className = "bi bi-check-lg";
}
