const $fileTableBody = $("#file-table-body");
const $folderGrid = $("#folder-grid");
const $uploadModal = $("#upload-modal");
const $uploadingFileMessage = $("#uploading-file-message");
/** @type {HTMLInputElement} */
const fileUploadInputElement = $('#upload-modal input[type="file"]').get(0);
const $uploadFileButton = $("#upload-file-button");

function refresh() {
    Toast.show("odświeżanie listy plików...", "refresh");
    setTimeout(() => location.reload(), 1000);
}

/**
 * @param {HTMLButtonElement} button 
 */
function uploadFile(button) {
    const files = fileUploadInputElement.files;
    
    if (files.length > 0) {
        fileUploadInputElement.style.display = "none";
        $uploadingFileMessage.css("display", "block");
        
        const formData = new FormData();
        formData.append("safe_id", button.getAttribute("data-safeid"));
        formData.append("file", files[0]);
        
        fetch("/sejf/plik", {
            method: "POST",
            body: formData
        })
            .then(response => {
                if (response.ok) refresh();
                else throw new Error();
            })
            .catch(() => {
                Toast.show("nie udało się wrzucić pliku", "alert", 3);
            })
            .finally(() => {
                fileUploadInputElement.style.display = "block";
                $uploadingFileMessage.css("display", "none");
                setUploadButtonEnabledState(true);
                fileUploadInputElement.parentElement.reset();
                $uploadModal.modal("hide");
            });
    }
}

/**
 * @param {boolean} state 
 */
function setUploadButtonEnabledState(state) {
    $uploadFileButton.prop("disabled", !state);

    if (state) $uploadFileButton.removeClass("disabled");
    else $uploadFileButton.addClass("disabled");
}

fileUploadInputElement.onchange = () => setUploadButtonEnabledState(fileUploadInputElement.files.length > 0);
fileUploadInputElement.onchange();