// https://developer.mozilla.org/en-US/docs/Web/API/File_API/Using_files_from_web_applications
let uploadCounter = 0;

function upload(upload, files) {
  const list = upload.querySelector(".upload-list");

  for (let i = 0, length = files.length; i < length; i++) {
    uploadCounter++;
    list.innerHTML += '<div class="box-row box-row-flex" id="js-upload-' + uploadCounter + '">' +
      '<div class="box-row-flex-conserve upload-list-icon"><span class="bi bi-file-earmark-text"></span></div>' +
      '<div class="box-row-flex-extend upload-list-text"><p class="upload-list-name">' + files[i].name + '</p></div>' +
      '<div class="box-row-flex-conserve box-row-margin-left upload-list-progress"></div>' +
      '</div>';

    // TODO: Check max file size on client side
    const request = new XMLHttpRequest();
    // TODO: Modification of XMLHttpRequest model is not ideal
    request._dbc_upload = uploadCounter;
    request.upload._dbc_upload = uploadCounter;
    request.open("POST", document.body.dataset.baseurl + "/xhr/upload/" + upload.dataset.uploadPath + "/");

    request.upload.addEventListener("progress", event => {
      document.querySelector("#js-upload-" + event.target._dbc_upload + " .upload-list-progress").innerHTML = Math.round((event.loaded * 100) / event.total) + "%";
    });

    request.addEventListener("readystatechange", event => {
      if (event.target.readyState !== 4) return;
      const element = document.getElementById("js-upload-" + event.target._dbc_upload);

      if (event.target.status === 200) {
        element.querySelector(".upload-list-progress").innerHTML = '<span class="bi bi-check-lg"></span>';
      } else {
        element.querySelector(".upload-list-progress").innerHTML = '<span class="bi bi-x-lg"></span>';

        try {
          const response = JSON.parse(event.target.responseText);
          element.querySelector(".upload-list-text").innerHTML += '<p class="upload-list-error">' + response["message"] + '</p>';
        } catch (error) {
          element.querySelector(".upload-list-text").innerHTML += '<p class="upload-list-error">Das Hochladen der Datei ist fehlgeschlagen.</p>';
        }
      }
    });

    const form = new FormData();
    form.append("file", files[i]);

    request.send(form);
  }
}

document.querySelectorAll(".upload-input").forEach(element => {
  element.addEventListener("change", () => {
    upload(element.closest(".upload"), element.files);
  }, false);
});

document.querySelectorAll(".upload-zone").forEach(element => {
  element.addEventListener("dragenter", event => {
    event.stopPropagation();
    event.preventDefault();
  }, false);

  element.addEventListener("dragover", event => {
    event.stopPropagation();
    event.preventDefault();
  }, false);

  element.addEventListener("drop", event => {
    event.stopPropagation();
    event.preventDefault();

    if (event.dataTransfer !== null && event.dataTransfer.files !== null) {
      upload(element.closest(".upload"), event.dataTransfer.files);
    }
  }, false);
});
