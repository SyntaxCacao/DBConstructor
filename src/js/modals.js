function closeModal(element) {
  document.querySelector("body").classList.remove("modal-open-within");
  element.classList.remove("modal-open");
  //event.target.closest(".modal").classList.remove("modal-open");
}

document.addEventListener("click", event => {
  if (document.querySelector("body").classList.contains("modal-open-within")) {
    // close modal
    if (event.target.closest(".js-close-modal") !== null || (event.target.closest(".modal") !== null && event.target.closest(".modal-dialog") === null)) {
      // case 1: click on .js-close-modal element
      // case 2: click outside .modal-dialog
      // (case 3: esc key => onkeyup listener below)
      closeModal(event.target.closest(".modal"));
      //document.querySelector("body").classList.remove("modal-open-within");
      //event.target.closest(".modal").classList.remove("modal-open");
    }
  } else {
    // open modal
    if (event.target.closest(".js-open-modal") !== null) {
      document.querySelector("body").classList.add("modal-open-within");
      document.getElementById(event.target.closest(".js-open-modal").attributes["data-modal"].value).classList.add("modal-open");
    }
  }
});

// https://stackoverflow.com/questions/3369593/how-to-detect-escape-key-press-with-pure-js-or-jquery
document.addEventListener("keyup", event => {
  if (event.key === "Escape" && document.querySelector("body").classList.contains("modal-open-within")) {
    closeModal(document.querySelector(".modal.modal-open"));
  }
});
