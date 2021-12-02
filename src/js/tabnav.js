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

      if (prev.attributes["data-tab-body"] !== null) {
        document.querySelector(prev.attributes["data-tab-body"].value).style["display"] = "none";
      }

      event.target.classList.add("selected");

      if (event.target.attributes["data-tab-body"] !== null) {
        document.querySelector(event.target.attributes["data-tab-body"].value).style["display"] = "initial";
      }
    }
  }

  // markdown preview
  if (event.target.matches(".js-markdown-tab")) {
    if (document.querySelector(event.target.attributes["data-markdown-source"].value).value === "") {
      document.querySelector(event.target.attributes["data-tab-body"].value).innerHTML = "<em>Keine Eingabe vorhanden</em>";
      return;
    }

    document.querySelector(event.target.attributes["data-tab-body"].value).innerHTML = "<em>Laden...</em>";

    fetch(document.body.attributes["data-baseurl"].value + "/api/markdown/", {
      body: "src=" + encodeURIComponent(document.querySelector(event.target.attributes["data-markdown-source"].value).value),
      headers: new Headers({
        "Content-Type": "application/x-www-form-urlencoded"
      }),
      method: "POST",
      redirect: "manual"
    }).then(response => {
      if (response.ok) {
        response.text().then(text => {
          document.querySelector(event.target.attributes["data-tab-body"].value).innerHTML = text;
        });
      } else {
        document.querySelector(event.target.attributes["data-tab-body"].value).innerHTML = "<em>Fehler bei der Verarbeitung</em>";
      }
    });
  }
});
