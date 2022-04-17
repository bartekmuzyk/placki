const $postContentEditor = $("#post-content-editor");
const $postAttachmentList = $("#post-attachment-list");
const $postAttachmentInput = $("#post-attachment-input");
const $fileUploadErrorMessage = $("#file-upload-error-message");

const fileTypeIcons = {
    "text/plain": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-description" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
       <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
       <path d="M9 17h6"></path>
       <path d="M9 13h6"></path>
    </svg>
`,
    "text/javascript": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-javascript" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <path d="M20 4l-2 14.5l-6 2l-6 -2l-2 -14.5z"></path>
       <path d="M7.5 8h3v8l-2 -1"></path>
       <path d="M16.5 8h-2.5a0.5 .5 0 0 0 -.5 .5v3a0.5 .5 0 0 0 .5 .5h1.423a0.5 .5 0 0 1 .495 .57l-.418 2.93l-2 .5"></path>
    </svg>
`,
    "text/csv": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-table" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <rect x="4" y="4" width="16" height="16" rx="2"></rect>
       <line x1="4" y1="10" x2="20" y2="10"></line>
       <line x1="10" y1="4" x2="10" y2="20"></line>
    </svg>
`,
    "text/css": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-paint" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <rect x="5" y="3" width="14" height="6" rx="2"></rect>
       <path d="M19 6h1a2 2 0 0 1 2 2a5 5 0 0 1 -5 5l-5 0v2"></path>
       <rect x="10" y="15" width="4" height="6" rx="1"></rect>
    </svg>
`,
    "text/html": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-html5" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <path d="M20 4l-2 14.5l-6 2l-6 -2l-2 -14.5z"></path>
       <path d="M15.5 8h-7l.5 4h6l-.5 3.5l-2.5 .75l-2.5 -.75l-.1 -.5"></path>
    </svg>
`,
    "application/vnd.ms-excel": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-table" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <rect x="4" y="4" width="16" height="16" rx="2"></rect>
       <line x1="4" y1="10" x2="20" y2="10"></line>
       <line x1="10" y1="4" x2="10" y2="20"></line>
    </svg>
`,
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-table" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <rect x="4" y="4" width="16" height="16" rx="2"></rect>
       <line x1="4" y1="10" x2="20" y2="10"></line>
       <line x1="10" y1="4" x2="10" y2="20"></line>
    </svg>
`,
    "application/zip": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-zip" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <path d="M6 20.735a2 2 0 0 1 -1 -1.735v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2h-1"></path>
       <path d="M11 17a2 2 0 0 1 2 2v2a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1v-2a2 2 0 0 1 2 -2z"></path>
       <line x1="11" y1="5" x2="10" y2="5"></line>
       <line x1="13" y1="7" x2="12" y2="7"></line>
       <line x1="11" y1="9" x2="10" y2="9"></line>
       <line x1="13" y1="11" x2="12" y2="11"></line>
       <line x1="11" y1="13" x2="10" y2="13"></line>
       <line x1="13" y1="15" x2="12" y2="15"></line>
    </svg>
`,
    "application/vnd.rar": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-archive" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <rect x="3" y="4" width="18" height="4" rx="2"></rect>
       <path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-10"></path>
       <line x1="10" y1="12" x2="14" y2="12"></line>
    </svg>
`,
    "image": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-photo" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <line x1="15" y1="8" x2="15.01" y2="8"></line>
       <rect x="4" y="4" width="16" height="16" rx="3"></rect>
       <path d="M4 15l4 -4a3 5 0 0 1 3 0l5 5"></path>
       <path d="M14 14l1 -1a3 5 0 0 1 3 0l2 2"></path>
    </svg>
`,
    "video": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-movie" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <rect x="4" y="4" width="16" height="16" rx="2"></rect>
       <line x1="8" y1="4" x2="8" y2="20"></line>
       <line x1="16" y1="4" x2="16" y2="20"></line>
       <line x1="4" y1="8" x2="8" y2="8"></line>
       <line x1="4" y1="16" x2="8" y2="16"></line>
       <line x1="4" y1="12" x2="20" y2="12"></line>
       <line x1="16" y1="8" x2="20" y2="8"></line>
       <line x1="16" y1="16" x2="20" y2="16"></line>
    </svg>
`,
    "audio": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-music" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <circle cx="6" cy="17" r="3"></circle>
       <circle cx="16" cy="17" r="3"></circle>
       <polyline points="9 17 9 4 19 4 19 17"></polyline>
       <line x1="9" y1="8" x2="19" y2="8"></line>
    </svg>
`,
    "default": `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="56" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
       <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
       <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
       <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
    </svg>
    `
};

const postAttachmentListRemoveOverlayHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="60" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
        <line x1="4" y1="7" x2="20" y2="7"></line>
        <line x1="10" y1="11" x2="10" y2="17"></line>
        <line x1="14" y1="11" x2="14" y2="17"></line>
        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
    </svg>
    <label>usuń</label>
`;

/**
 * @param file {File}
 * @param fileIndex {number}
 * @returns {HTMLDivElement}
 */
function createAttachmentPreviewElement(file, fileIndex) {
    /** @returns {HTMLDivElement} */
    const div = () => document.createElement("div");

    const root = div();
    root.setAttribute("data-fileindex", fileIndex.toString());

    const previewDiv = div();

    switch (file.type) {
        case "image/jpeg":
        case "image/png":
        case "image/bmp":
        case "image/gif":
        case "image/webp":
            const reader = new FileReader();

            reader.onload = ev => {
                previewDiv.style.backgroundImage = `url(${ev.target.result})`;
            };

            reader.readAsDataURL(file);

            break;
        default:
            let icon;

            if (file.type in fileTypeIcons) {
                icon = fileTypeIcons[file.type];
            } else {
                const generalFileType = file.type.split("/", 1)[0];

                icon = fileTypeIcons[generalFileType] ?? fileTypeIcons.default;
            }

            previewDiv.innerHTML = icon + "<br/>" + file.name;

            break;
    }

    root.appendChild(previewDiv);

    const removeOverlay = div();
    removeOverlay.innerHTML = postAttachmentListRemoveOverlayHTML;
    root.appendChild(removeOverlay);

    return root;
}

let currentPostAttachments = [];

function reloadAttachmentsPreview() {
    if (currentPostAttachments.length > 0) {
        $postAttachmentList.attr("data-show", "1");
    } else {
        $postAttachmentList.attr("data-show", "0");
        return;
    }

    const previewElements = [];

    for (const [ fileIndex, file ] of Object.entries(currentPostAttachments)) {
        const el = createAttachmentPreviewElement(file, Number(fileIndex));

        previewElements.push(el);
    }

    $postAttachmentList.html("");
    $postAttachmentList.append(...previewElements);

    $("#post-attachment-list > div > div:nth-child(2)").click(function() {
        /** @type {HTMLDivElement} */
        const attachmentRootElement = this.parentElement;
        const fileIndex = Number(attachmentRootElement.getAttribute("data-fileindex"));

        currentPostAttachments.splice(fileIndex, 1);
        reloadAttachmentsPreview();
    });
}

$postAttachmentInput.change(() => {
    /** @type {File[]} */
    const files = $postAttachmentInput.prop("files");

    currentPostAttachments.push(...files);
    reloadAttachmentsPreview();
});

function pickPostAttachments() {
    $postAttachmentInput.click();
}

function clearAttachments() {
    currentPostAttachments = [];
    reloadAttachmentsPreview();
}

/**
 * @param message {string}
 */
function showFileUploadErrorMessage(message) {
    $fileUploadErrorMessage.text(message);
    $fileUploadErrorMessage.attr("data-animatedshow", "1");

    setTimeout(() => $fileUploadErrorMessage.attr("data-animatedshow", "0"), 3000);
}

/**
 * @param url {string}
 * @param onSuccess {() => void}
 * @param onError {() => void}
 */
function makePostRequest(url, onSuccess, onError) {
    const postContent = $postContentEditor.val().trim();

    if (postContent.length > 0 || currentPostAttachments.length > 0) {
        const formData = new FormData();

        if (postContent.length > 0) {
            formData.append("content", postContent);
        }

        for (const file of currentPostAttachments) {
            formData.append("attachments[]", file, file.name);
        }

        fetch(url, {
            method: "POST",
            body: formData
        })
            .then(response => {
                if (response.ok) {
                    onSuccess();
                } else {
                    response.json().then(fileUploadErrorData => {
                        /** @type {"cannot write to disk"|"too large"} */
                        const error = fileUploadErrorData["error"];
                        /** @type {string} */
                        const filename = fileUploadErrorData["filename"];

                        switch (error) {
                            case "cannot write to disk":
                                showFileUploadErrorMessage(`nie udało się zapisać pliku ${filename} na serwerze`);
                                break;
                            case "too large":
                                showFileUploadErrorMessage(`plik ${filename} jest za duży`);
                                break;
                        }
                    });
                }
            })
            .catch(() => onError())
    }
}