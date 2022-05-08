const $albumChooserBtn = $("#album-chooser-btn");
const $uploadPhotosBtn = $("#upload-photos-btn");
const $photosUploadInput = $("#photos-upload-input");
const $uploadPhotosModal = $("#upload-photos-modal");
const $confirmPhotosUploadBtn = $("#confirm-photos-upload-btn");
const $photosUploadProgressList = $("#photos-upload-progress-list");
const $albumNameInput = $("#album-name-input");
const $automaticAlbumCreationNote = $("#automatic-album-creation-note");
const $fileUploadInput = $("#file-upload-input");
const $fileUploadPopup = $("#file-upload-popup");
const $fileUploadPopupFileName = $fileUploadPopup.find("label");
const $cancelFileUploadBtn = $("#cancel-file-upload-btn");
const $fileUploadCriticalError = $("#file-upload-critical-error");
const $fileUploadProgressBar = $("#file-upload-progress-bar");
const $videoUploadInput = $("#video-upload-input");
const $videoUploadModal = $("#video-upload-modal");
/** @type {JQuery<HTMLVideoElement>} */ const $uploadVideoPreview = $("#upload-video-preview");
const $videoUploadNameInput = $("#video-upload-name-input");
const $videoUploadDescriptionInput = $("#video-upload-description-input");
const $videoUploadThumbnailPreview = $("#video-upload-thumbnail-preview");
const $videoUploadThumbnailInput = $("#video-upload-thumbnail-input");
const $videoUploadProgressOverlay = $("#video-upload-progress-overlay");
const $videoUploadProgressBar = $("#video-upload-progress-bar");

setActiveNavTab("/media");

let currentTab = localStorage.getItem("lastMediaTab") ?? "video";
/** @type {?string} */
let currentChosenAlbum = null;

function onTabChange(tabId) {
    $(`#${currentTab}-tab`).css("display", "none");
    $(".press-enter-to-search-message").attr("data-animationenabled", "0");
    $(`#${tabId}-tab`).css("display", "block");
    localStorage.setItem("lastMediaTab", tabId);

    currentTab = tabId;
}

setTabCallback(onTabChange);
onTabChange(currentTab);
activateTab(currentTab);

function showAllAlbums() {
    $(".media-photo-item").css({ display: "" });
}

/**
 * @param albumName {string}
 */
function showAlbum(albumName) {
    $(".media-photo-item").css("display", "none");
    $(`.media-photo-item[data-album="${albumName}"]`).css({ display: "" } );
}

$("#album-chooser-menu > .dropdown-item").on("click", function() {
    const self = $(this);
    /** @type {string} */
    const special = self.data("special");
    /** @type {string} */
    const albumName = self.data("albumref");
    let albumChooserButtonText = albumName;

    if (special) {
        currentChosenAlbum = null;
        albumChooserButtonText = self.text();
        localStorage.removeItem("lastSelectedAlbumName");

        switch (special) {
            case "all":
                showAllAlbums();
                break;
        }
    } else {
        showAlbum(albumName);
        currentChosenAlbum = albumName;
        localStorage.setItem("lastSelectedAlbumName", albumName);
    }
    
    $albumChooserBtn.text(albumChooserButtonText);
    $uploadPhotosBtn.text(albumName ? `+ wrzuć zdjęcie do albumu ${albumName}` : "+ wrzuć zdjęcie");
});

const lastSelectedAlbumName = localStorage.getItem("lastSelectedAlbumName");
if (lastSelectedAlbumName) {
    $(`#album-chooser-menu > .dropdown-item[data-albumref="${lastSelectedAlbumName}"]`).trigger("click");
}

/**
 * @param fileName {string}
 * @param fileIndex {number}
 * @returns {HTMLLIElement}
 */
function generateFileProgressListItem(fileName, fileIndex) {
    const listItem = document.createElement("li");
    listItem.setAttribute("data-state", "idle");
    listItem.setAttribute("data-fileindex", fileIndex.toString());

    const paragraph = document.createElement("p");
    paragraph.innerText = fileName;

    listItem.appendChild(paragraph);

    return listItem;

}

$photosUploadInput.on("change", () => {
    $photosUploadProgressList.html("");

    /** @type {File[]} */
    const chosenFiles = $photosUploadInput.prop("files");

    for (const [ fileIndex, file ] of Object.entries(chosenFiles)) {
        // noinspection JSCheckFunctionSignatures
        const listItem = generateFileProgressListItem(file.name, fileIndex);
        $photosUploadProgressList.append(listItem);
    }

    if (currentChosenAlbum) {
        $albumNameInput.val(currentChosenAlbum);
        $albumNameInput.prop("disabled", true);
        $automaticAlbumCreationNote.css("display", "none");
    } else {
        $albumNameInput.val("");
        $albumNameInput.prop("disabled", false);
        $automaticAlbumCreationNote.css("display", "block");
    }

    $uploadPhotosModal.modal("show");
});

$uploadPhotosBtn.on("click", () => {
    $photosUploadInput.trigger("click");
});

$confirmPhotosUploadBtn.on("click", async () => {
    const albumName = $albumNameInput.val().trim();

    if (albumName.length === 0) {
        Toast.show("podaj nazwę albumu", "alert", 2);
        return;
    }

    $confirmPhotosUploadBtn.prop("disabled", true);
    $confirmPhotosUploadBtn.text("wrzucanie...");
    $("#photos-upload-progress-list > li").attr("data-state", "waiting");

    /** @type {File[]} */
    const filesToSend = $photosUploadInput.prop("files");
    let error = false;

    for (const [ fileIndex, file ] of Object.entries(filesToSend)) {
        /** @param errorMessage {string} */
        const reportError = errorMessage => {
            $(`#photos-upload-progress-list > li[data-fileindex="${fileIndex}"]`)
                .attr("data-state", "error")
                .attr("data-error", errorMessage);
        };

        const markDone = () => {
            $(`#photos-upload-progress-list > li[data-fileindex="${fileIndex}"]`)
                .attr("data-state", "done");
        }

        const formData = new FormData();
        formData.append("album", albumName);
        formData.append("photo", file);

        try {
            const response = await fetch('/media/zdjecie', {
                method: "POST",
                body: formData
            });

            if (response.ok) {
                markDone();
            } else {
                error = true;

                /** @type {{error: "cannot write to disk"|"too large"}} */
                const data = await response.json();

                switch (data.error) {
                    case "cannot write to disk":
                        reportError("błąd zapisu");
                        break;
                    case "too large":
                        reportError("za duży");
                        break;
                    default:
                        // noinspection ExceptionCaughtLocallyJS
                        throw new Error();
                }
            }
        } catch (e) {
            reportError("nieznany błąd");
        }
    }

    $confirmPhotosUploadBtn.prop("disabled", false);
    $confirmPhotosUploadBtn.text("wrzuć");
    if (!error) {
        $uploadPhotosModal.modal("hide");
        location.reload();
    }
});

$(".delete-photo-btn").on("click", function() {
    Toast.show("usuwanie zdjęcia...", "bin");

    /** @type {HTMLDivElement} */
    const photoElement = this.parentElement.parentElement.parentElement;
    const photoId = photoElement.getAttribute("data-photoid");

    fetch(`/media/zdjecie?id=${photoId}`, { method: "DELETE" })
        .then(response => {
            if (!response.ok) throw new Error();

            photoElement.remove();
            Toast.show("usunięto zdjęcie", "bin", 1);
        })
        .catch(() => {
            Toast.show("nie udało się usunąć zdjęcia", "alert", 2);
        });
});

/**
 * @param file {File}
 * @param chunkSize {number} chunk size in bytes
 * @returns {Blob[]} array of blobs representing the file in chunks
 */
function sliceFileIntoChunks(file, chunkSize) {
    const chunksCount = Math.ceil(file.size / chunkSize);
    const result = [];

    for (let chunk = 0; chunk < chunksCount; chunk++) {
        let offset = chunk * chunkSize;
        result.push(file.slice(offset, offset + chunkSize));
    }

    return result;
}

function hideFileUploadPopup() {
    $fileUploadPopup.attr("data-show", "0");
    $fileUploadProgressBar.css("width", "0");
}

let uploadStopRequested = false;

/**
 * @param file {File}
 * @param onProgress {(percentage: number) => void}
 * @param endpoints {{start: string, uploadPart: string, cancel: string}}
 * @param startData {Object<string, string|Blob>}
 * @returns {Promise<"completed"|"cancelled"|"cancelled with error"|"failed to start"|"error">}
 */
async function startUploadInParts(file, onProgress, endpoints, startData) {
    const tokenRequestFormData = new FormData();

    for (const [ name, value ] of Object.entries(startData)) {
        tokenRequestFormData.append(name, value);
    }

    let tokenRequestResponse;

    try {
        tokenRequestResponse = await fetch(endpoints.start, {
            method: "POST",
            body: tokenRequestFormData
        });

        if (!tokenRequestResponse.ok) { // noinspection ExceptionCaughtLocallyJS
            throw new Error();
        }
    } catch (e) {
        return "failed to start";
    }

    const chunks = sliceFileIntoChunks(file, 1024 * 1024);
    const lastChunkIndex = chunks.length - 1;
    let sentBytes = 0;

    for (let chunkIndex = 0; chunkIndex <= lastChunkIndex; chunkIndex++) {
        const formData = new FormData();

        if (uploadStopRequested) {
            try {
                await fetch(endpoints.cancel, { method: "POST" });
            } catch (e) {
                return "cancelled with error";
            }

            return "cancelled";
        }

        const chunk = chunks[chunkIndex];
        formData.append("part", chunk);

        if (chunkIndex === lastChunkIndex) {
            formData.append("final", "1");
        }

        try {
            const partUploadResponse = await fetch(endpoints.uploadPart, {
                method: "POST",
                body: formData
            });

            if (!partUploadResponse.ok) { // noinspection ExceptionCaughtLocallyJS
                throw new Error();
            }

            sentBytes += chunk.size;
            const percentageSent = Math.ceil(sentBytes / file.size * 100);
            onProgress(percentageSent);
        } catch (e) {
            return "error";
        }
    }

    return "completed";
}

function stopUploadInParts() {
    uploadStopRequested = true;
}

$fileUploadInput.on("change", async () => {
    uploadStopRequested = false;
    /** @type {File} */
    const file = $fileUploadInput.prop("files")[0];

    $fileUploadPopupFileName.text(file.name);
    $fileUploadPopup.attr("data-show", "1");

    const status = await startUploadInParts(
        file,
        percentage => {
            $fileUploadProgressBar
                .css("width", `${percentage}%`)
                .text(`${percentage}%`);
        },
        {
            start: "/media/plik/wrzuc/start",
            uploadPart: "/media/plik/wrzuc",
            cancel: "/media/plik/wrzuc/anuluj"
        },
        {
            "filename": file.name
        }
    );

    switch (status) {
        case "completed":
            location.reload();
            break;
        case "error":
            hideFileUploadPopup();
            Toast.show("transfer nieudany", "alert", 2);
            break;
        case "cancelled":
            hideFileUploadPopup();
            Toast.show("anulowano transfer", "info", 2);
            break;
        case "failed to start":
        case "cancelled with error":
            $fileUploadCriticalError.modal("show");
            break;
    }
});

$cancelFileUploadBtn.on("click", () => {
    stopUploadInParts();
    Toast.show("zatrzymywanie transferu...", "info");
});

/**
 * @param blob {Blob}
 * @param filename {string}
 */
function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);

    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    a.click();
}

$(".file-actions > button").on("click", function() {
    const fileId = this.parentElement.getAttribute("data-fileid");
    /** @type {"delete"|"download"|"share"} */
    const action = this.getAttribute("data-action");

    switch (action) {
        case "delete":
            fetch(`/media/plik?id=${fileId}`, { method: "DELETE" })
                .then(response => {
                    if (!response.ok) throw new Error();

                    Toast.show("usunięto plik", "bin", 1);
                    this.parentElement.parentElement.parentElement.remove();
                })
                .catch(() => {
                    Toast.show("nie udało się usunąć pliku", "alert", 2);
                });
            break;
        case "download":
            Toast.show("rozpoczynanie pobierania...", "download");

            fetch(`/media_sources/${fileId}`)
                .then(response => response.blob())
                .then(blob => {
                    downloadBlob(blob, this.getAttribute("data-filename"));
                    Toast.show("pobieranie rozpoczęte", "download", 1);
                });
            break;
        case "share":
            Toast.show("generowanie linku...", "link");

            fetch(`/media/plik/udostepnij?id=${fileId}`)
                .then(response => {
                    if (!response.ok) throw new Error();

                    return response.text();
                })
                .then(token => {
                    const url = `${location.origin}/media/plik/udostepnione?token=${token}`;

                    return navigator.clipboard.writeText(url);
                })
                .then(() => {
                    Toast.show("skopiowano link do schowka", "link", 2);
                })
                .catch(() => {
                    Toast.show("nie udało się udostępnić", "alert", 2);
                });
            break;
    }
});

/** @type {?Blob} */
let currentVideoUploadThumbnail = null;

/**
 * @param videoElement {HTMLVideoElement}
 * @returns {Promise<Blob>}
 */
function generateThumbnail(videoElement) {
    return new Promise(resolve => {
        const canvas = document.createElement("canvas");
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;

        const context = canvas.getContext("2d");
        context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(resolve, "image/jpeg", 0.5);
    });
}

/**
 * @param thumbnailBlob {?Blob}
 */
function updateThumbnail(thumbnailBlob) {
    currentVideoUploadThumbnail = thumbnailBlob;

    $videoUploadThumbnailPreview.attr(
        "src",
        thumbnailBlob instanceof Blob ? URL.createObjectURL(thumbnailBlob) : null
    );
}

async function setCurrentVideoPreviewFrameAsThumbnail() {
    const thumbnail = await generateThumbnail($uploadVideoPreview.get(0))

    $videoUploadThumbnailInput.parent().get(0).reset();
    updateThumbnail(thumbnail);
}

$videoUploadInput.on("change", async () => {
    /** @type {File} */
    const videoFile = $videoUploadInput.prop("files")[0];
    const previewElement = $uploadVideoPreview.get(0);

    updateThumbnail(null);
    $uploadVideoPreview.attr("src", URL.createObjectURL(videoFile));
    $videoUploadNameInput.val(videoFile.name);
    BigChooser.setActiveOption("video-visibility", "public");

    previewElement.muted = true;
    await previewElement.play();

    Toast.show("przygotowywanie miniaturki...", "thumbnail");

    setTimeout(() => {
        setCurrentVideoPreviewFrameAsThumbnail();

        previewElement.muted = false;
        previewElement.currentTime = 0;
        previewElement.pause();

        $videoUploadModal.modal("show");
        Toast.dismiss();
    }, 1000);
});

$videoUploadModal.find(".btn-close").on("click", () => {
    $uploadVideoPreview.get(0).pause();
});

$videoUploadThumbnailInput.on("change", () => {
    /** @type {File} */
    const thumbnailFile = $videoUploadThumbnailInput.prop("files")[0];

    updateThumbnail(thumbnailFile);
});

async function startVideoUpload() {
    $uploadVideoPreview.get(0).pause();
    $videoUploadProgressOverlay.removeClass("d-none").addClass("d-flex");

    const name = $videoUploadNameInput.val().trim();

    if (name.length === 0) {
        Toast.show("nazwa filmu nie może być pusta", "alert", 2);
    }

    const description = $videoUploadDescriptionInput.val().trim();
    const visibility = {"public": 0, "unlisted": 1, "private": 2}[BigChooser.getActiveOption("video-visibility")];

    const status = await startUploadInParts(
        $videoUploadInput.prop("files")[0],
        percentage => {
            $videoUploadProgressBar
                .css("width", `${percentage}%`)
                .text(`${percentage}%`);
        },
        {
            start: "/media/film/wrzuc/start",
            uploadPart: "/media/film/wrzuc",
            cancel: "/media/film/wrzuc/anuluj"
        },
        {
            name,
            description,
            visibility,
            "thumbnail": currentVideoUploadThumbnail
        }
    );

    switch (status) {
        case "completed":
            location.reload();
            break;
        case "cancelled":
            $videoUploadProgressOverlay.removeClass("d-flex").addClass("d-none");
            break;
        default:
            Toast.show("nie udało się przesłać filmu", "alert", 2);
            $videoUploadProgressOverlay.removeClass("d-flex").addClass("d-none");
            break;
    }
}