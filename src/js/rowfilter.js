document.addEventListener("click", event => {
  const button = event.target.closest(".page-table-view-controls-expand");
  if (button !== null) {
    if (button.classList.contains("button-selected")) {
      button.classList.remove("button-selected");
      button.parentNode.parentNode.querySelectorAll(".page-table-view-controls-row-expandable").forEach(row => {
        row.classList.remove("expanded");
      });
    } else {
      button.classList.add("button-selected");
      button.parentNode.parentNode.querySelectorAll(".page-table-view-controls-row-expandable").forEach(row => {
        row.classList.add("expanded");
      });
    }
  }
});

/*
document.addEventListener("submit", event => {
  if (event.target.classList.contains("page-table-view-controls")) {
    event.target.querySelectorAll("input, select").forEach(element => {
      element.disabled = element.value === "";
    });
  }
});*/
