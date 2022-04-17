setActiveNavTab("/grupy");

const $groups = $(".group");
const $createGroupBtn = $("#create-group-btn");
const $createGroupModal = $("#create-group-modal");
const $confirmGroupCreationBtn = $("#confirm-group-creation-btn");
const $groupCreationError = $("#group-creation-error");
const $newGroupNameInput = $("#new-group-name-input");

setTabCallback(tabId => {
    switch (tabId) {
        case "all":
            $groups.removeClass("d-none").addClass("d-block");
            break;
        case "mine":
            $groups.removeClass("d-block").addClass("d-none");
            $('.group[data-mine="1"]').removeClass("d-none").addClass("d-block");
            break;
        case "joined":
            $groups.removeClass("d-block").addClass("d-none");
            $('.group[data-mine="0"][data-joined="1"]').removeClass("d-none").addClass("d-block");
            break;
    }
});

$createGroupBtn.click(() => $createGroupModal.modal("show"));

/**
 * @param message {string}
 */
function showGroupCreationError(message) {
    $groupCreationError.css("display", "block");
    $groupCreationError.text(message);
}

function hideGroupCreationError() {
    $groupCreationError.css("display", "none");
}

$confirmGroupCreationBtn.click(function() {
    const self = $(this);
    const restoreControls = () => {
        self.prop("disabled", false);
        self.text("utwórz grupę");
        $newGroupNameInput.prop("disabled", false);
    };

    hideGroupCreationError();

    self.prop("disabled", true);
    self.text("tworzenie grupy...");

    const newGroupName = $newGroupNameInput.val().trim();

    if (newGroupName.length === 0) {
        showGroupCreationError("nazwa grupy nie może być pusta");
        restoreControls();
        return;
    }

    $newGroupNameInput.val(newGroupName);
    $newGroupNameInput.prop("disabled", true);

    const formData = new FormData();
    formData.append("name", newGroupName);

    fetch(location.href, {
        method: "POST",
        body: formData
    })
        .then(response => {
            if (response.ok) {
                response.text().then(groupId => location.assign(`/grupy/panel?id=${groupId}`));
            } else {
                throw new Error();
            }
        })
        .catch(() => {
            showGroupCreationError("nie udało się utworzyć grupy");
            restoreControls();
        });
});