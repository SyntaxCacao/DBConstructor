// scroll to selected tab in nav
document.querySelectorAll(".tabnav-tabs").forEach(tabnav => {
  const tab = tabnav.querySelector(".tabnav-tab.selected");

  if (tab !== null && tabnav.offsetWidth < tab.offsetLeft - tabnav.offsetLeft) {
    tabnav.scroll(tab.offsetLeft - 96, 0);
  }
});

document.addEventListener("click", event => {
  // show clicked tab body
  if (event.target.matches(".tabnav-tab") && (event.target.attributes["href"] === null || event.target.attributes["href"].value === "#")) {
    if (! event.target.classList.contains("selected")) {
      const prev = event.target.parentNode.querySelector(".selected");
      prev.classList.remove("selected");

      if ("tabBody" in prev.dataset) {
        document.querySelector(prev.dataset.tabBody).style["display"] = "none";
      }

      event.target.classList.add("selected");

      if ("tabBody" in event.target.dataset) {
        document.querySelector(event.target.dataset.tabBody).style["display"] = "initial";
      }
    }
  }

  // markdown preview
  if (event.target.matches(".js-markdown-tab")) {
    if (document.querySelector(event.target.dataset.markdownSource).value === "") {
      document.querySelector(event.target.dataset.tabBody).innerHTML = "<em>Keine Eingabe vorhanden</em>";
      return;
    }

    document.querySelector(event.target.dataset.tabBody).innerHTML = "<em>Laden...</em>";

    fetch(document.body.dataset.baseurl + "/xhr/markdown/", {
      body: "src=" + encodeURIComponent(document.querySelector(event.target.dataset.markdownSource).value),
      headers: new Headers({
        "Content-Type": "application/x-www-form-urlencoded"
      }),
      method: "POST",
      redirect: "manual"
    }).then(response => {
      if (response.ok) {
        response.text().then(text => {
          document.querySelector(event.target.dataset.tabBody).innerHTML = text;
        });
      } else {
        document.querySelector(event.target.dataset.tabBody).innerHTML = "<em>Fehler bei der Verarbeitung</em>";
      }
    });
  }
});
