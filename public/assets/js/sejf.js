const $fileTableBody = $("#file-table-body");
const $folderGrid = $("#folder-grid");
const $uploadModal = $("#upload-modal");
const $uploadingFileMessage = $("#uploading-file-message");
/** @type {HTMLInputElement} */
const $fileUploadInput = $('#upload-modal input[type="file"]').get(0);
const $uploadFileButton = $("#upload-file-button");

function refresh() {
    Toast.show("odświeżanie listy plików...");
    setTimeout(() => location.reload(), 1000);
}

/**
 * @param {HTMLButtonElement} button 
 */
function uploadFile(button) {
    const files = $fileUploadInput.files;
    
    if (files.length > 0) {
        $fileUploadInput.style.display = "none";
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
                Toast.show("nie udało się wrzucić pliku", 3);
            })
            .finally(() => {
                $fileUploadInput.style.display = "block";
                $uploadingFileMessage.css("display", "none");
                setUploadButtonEnabledState(true);
                $fileUploadInput.parentElement.reset();
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

$fileUploadInput.onchange = () => setUploadButtonEnabledState($fileUploadInput.files.length > 0);
$fileUploadInput.onchange();