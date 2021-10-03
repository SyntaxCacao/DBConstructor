document.querySelectorAll(".tabnav-tabs").forEach(function (tabnav) {
  const tab = tabnav.querySelector(".tabnav-tab.selected");

  if (tab !== null && tabnav.offsetWidth < tab.offsetLeft-tabnav.offsetLeft) {
    tabnav.scroll(tab.offsetLeft-96, 0);
  }
});
