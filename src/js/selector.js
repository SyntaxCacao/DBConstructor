function loadTable(modal, searchColumn, searchValue, page) {
  const modalContent = modal.querySelector(".modal-content");
  modalContent.innerHTML = '<div class="blankslate"><h3 class="blankslate-text">Laden...</h3></div>';
  let body = "projectId=" + modal.dataset.projectId + "&page=" + page;

  if (searchColumn !== null) {
    body += "&searchColumn=" + encodeURIComponent(searchColumn);
  }

  if (searchValue !== null) {
    body += "&searchValue=" + encodeURIComponent(searchValue);
  }

  fetch(document.body.dataset.baseurl + "/xhr/selector/" + modal.dataset.tableId, {
    body: body,
    headers: new Headers({
      "Content-Type": "application/x-www-form-urlencoded"
    }),
    method: "POST",
    redirect: "manual"
  }).then(response => {
    if (response.ok) {
      response.text().then(text => {
        modalContent.innerHTML = text;
        //modalContent.querySelector(".page-table-selector-modal-form-column").focus();

        const pageInput = modalContent.querySelector(".page-table-selector-modal-form-page");
        const page = parseInt(pageInput.value);

        if (page === 1) {
          modal.querySelector(".page-table-selector-modal-prev").classList.add("button-disabled");
        } else {
          modal.querySelector(".page-table-selector-modal-prev").classList.remove("button-disabled");
        }

        if (page >= parseInt(pageInput.dataset.pageCount)) {
          modal.querySelector(".page-table-selector-modal-next").classList.add("button-disabled");
        } else {
          modal.querySelector(".page-table-selector-modal-next").classList.remove("button-disabled");
        }
      });
    } else {
      modalContent.innerHTML = "<p>Error while loading table</p>";
      console.error("Loading selector table failed");
    }
  });
}

document.addEventListener("openModal", event => {
  if (event.detail.element.classList.contains("page-table-selector-modal")) {
    if (event.detail.element.querySelector(".modal-content").childNodes.length === 0) {
      loadTable(event.detail.element, null, null, 1);
    }/* else {
      event.detail.element.querySelector(".page-table-selector-modal-form-column").focus();
    }*/
  }
});

/*
document.addEventListener("closeModal", event => {
  if (event.detail.element.classList.contains("page-table-selector-modal")) {
    document.querySelector("input[name=field-relational-" + event.detail.element.attributes["data-column-id"].value + "]").parentNode.querySelector(".page-table-selector-button").focus();
  }
});
*/

// //

document.addEventListener("click", event => {
  // refresh button
  let button = event.target.closest(".page-table-selector-modal-button");

  if (button !== null) {
    if (button.classList.contains("button-disabled")) {
      return;
    }

    button.classList.add("button-disabled");
    const modal = event.target.closest(".page-table-selector-modal");
    loadTable(modal, modal.querySelector(".page-table-selector-modal-form-column").value, modal.querySelector(".page-table-selector-modal-form-value").value, modal.querySelector(".page-table-selector-modal-form-page").value);
    return;
  }

  // select button
  button = event.target.closest(".js-table-selector");

  if (button !== null) {
    const modal = event.target.closest(".page-table-selector-modal");
    const input = document.querySelector("input[name=field-relational-" + modal.dataset.columnId + "]")

    if (input.value === button.dataset.rowId) {
      return;
    }

    if (button.dataset.rowId === "") {
      input.value = "";
      input.dataset.valueExists = "0";
      input.parentNode.querySelector(".page-table-selector-value").innerHTML = 'Keine Auswahl';
      input.parentNode.querySelector(".page-table-selector-indicator").classList.add("hide");
      updateRules(input);
      return;
    }

    input.dataset.valueExists = "1";
    input.dataset.valueValid = button.dataset.valid;
    input.dataset.valueDeleted = button.dataset.deleted;
    input.value = button.dataset.rowId;
    input.parentNode.querySelector(".page-table-selector-value").innerHTML = '<a class="main-link" href="' + document.querySelector("body").dataset.baseurl + '/projects/' + modal.dataset.projectId + '/tables/' + modal.dataset.tableId + '/view/' + button.dataset.rowId + '/" target="_blank">#' + button.dataset.rowId + '</a>';

    let icon;

    if (button.dataset.deleted === "1") {
      icon = "trash";
    } else if (button.dataset.valid === "1") {
      icon = "check-lg";
    } else {
      icon = "x-lg";
    }

    input.parentNode.querySelector(".page-table-selector-indicator").classList.remove("hide");
    input.parentNode.querySelector(".page-table-selector-indicator .bi").className = "bi bi-" + icon;

    updateRules(input);
    return;
  }

  // pagination
  button = event.target.closest(".page-table-selector-modal-prev");

  if (button !== null && ! button.classList.contains("button-disabled")) {
    const input = event.target.closest(".modal").querySelector(".page-table-selector-modal-form-page");
    input.value = parseInt(input.value) - 1;
    input.parentElement.querySelector(".page-table-selector-modal-button").click();
    return;
  }

  button = event.target.closest(".page-table-selector-modal-next");

  if (button !== null && ! button.classList.contains("button-disabled")) {
    const input = event.target.closest(".modal").querySelector(".page-table-selector-modal-form-page");
    input.value = parseInt(input.value) + 1;
    input.parentElement.querySelector(".page-table-selector-modal-button").click();
  }
});

function updateRules(input) {
  const rules = document.querySelector(input.parentElement.dataset.rulesElement).querySelectorAll(".validation-step");

  let existsIndex = 0;
  let validIndex = 1;

  if (input.dataset.nullable === "0") {
    if (input.value === "") {
      setFailed(rules.item(0));
    } else {
      setSuccess(rules.item(0));
    }

    existsIndex += 1;
    validIndex += 1;
  }

  if (input.value === "") {
    setSkipped(rules.item(existsIndex));
    setSkipped(rules.item(validIndex));
  } else {
    setSuccess(rules.item(existsIndex));

    if (input.dataset.valueValid === "1" && input.dataset.valueDeleted === "0") {
      setSuccess(rules.item(validIndex));
    } else {
      setFailed(rules.item(validIndex));
    }
  }
}
