document.addEventListener("change", event => {
  const closest = event.target.closest(".js-validate-within");

  if (closest !== null && "rulesElement" in closest.dataset && "columnId" in closest.dataset) {
    let value = event.target.value;

    if ("name" in event.target.attributes && event.target.attributes.name.value.endsWith("[]")) {
      // https://stackoverflow.com/a/31544256/5489107
      value = JSON.stringify(Array.from(event.target.selectedOptions).map(option => option.value));
    }

    if ("type" in closest.dataset && closest.dataset.type === "date") {
      // normalize date values

      // 20221128 => 2022-11-28
      let match = value.match(/^(\d{4})(\d{1,2})(\d{1,2})$/);

      if (match !== null) {
        value = match[1] + "-" + match[2] + "-" + match[3];
        event.target.value = value;
      }

      // 28.11.2022 => 2022-11-28
      match = value.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/);

      if (match !== null) {
        value = match[3] + "-" + match[2] + "-" + match[1];
        event.target.value = value;
      }

      // 2022-11-1 => 2022-11-01
      if (value.length !== 10) {
        match = value.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);

        if (match !== null) {
          let month = match[2];
          let day = match[3];

          if (month.length === 1) {
            month = "0" + month;
          }

          if (day.length === 1) {
            day = "0" + day;
          }

          value = match[1] + "-" + month + "-" + day;
          event.target.value = value;
        }
      }
    }

    fetch(document.body.dataset.baseurl + "/xhr/validation/", {
      body: "id=" + encodeURIComponent(closest.dataset.columnId) + "&value=" + encodeURIComponent(value),
      headers: new Headers({
        "Content-Type": "application/x-www-form-urlencoded"
      }),
      method: "POST",
      redirect: "manual"
    }).then(response => {
      if (response.ok) {
        response.text().then(text => {
          const rulesElement = document.querySelector(closest.dataset.rulesElement);
          rulesElement.innerHTML = text;

          if (rulesElement.querySelector(".js-result").dataset.result === "0" && event.target.value !== "") {
            // last check is to make sure that class won't be set if there is no value;
            // with no value the text decoration would only be visible once user starts inserting;
            // inserting something means there probably is now a valid value
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
