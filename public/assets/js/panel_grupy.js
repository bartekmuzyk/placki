setActiveNavTab("/grupy");

const $commentsModal = $("#comments-modal");
const $commentsModalSubtitle = $("#comments-modal-subtitle");
const $commentsFrame = $("#post-comments-iframe");
const $saveGroupLookSettingsBtn = $("#save-group-look-settings-btn");
const $changePicBtn = $("#change-pic-btn");
const $groupPicChooser = $("#group-pic-chooser");
const $picPreview = $("#pic-preview");
const $groupNameEdit = $("#group-name-edit");
const $groupDescriptionEdit = $("#group-description-edit");
const $scrollZoomTip = $("#scroll-zoom-tip");
const $headingPic = $("#heading-pic");
const $headingName = $("#heading-name");
const $joinRequestsCountBadge = $("#join-requests-count-badge");
const $joinRequestsMenuOption = $('.menu-option[data-optionid="join-requests"]');
const $deleteGroupBtn = $("#delete-group-btn");

$(".group-panel-tab-content").css("display", "none");
$("#wall-tab").css("display", "flex");

setSideMenuOptionCallback(optionId => {
    $(".group-panel-tab-content").css("display", "none");
    $(`#${optionId}-tab`).css("display", "flex");
    
    if (optionId === "safe" && !SafeBrowser.loaded) {
        SafeBrowser.load();
    }
});

$commentsFrame.on("load", () => {
    $commentsModalSubtitle.text("");
});

function openComments(postId) {
    $commentsModalSubtitle.text("ładowanie komentarzy...");
    $commentsFrame.attr("src", `/post/komentarze?id=${postId}`);
    $commentsModal.modal("show");
}

class WallPostsBrowser {
    static $groupPostsFrame = $("#group-posts-iframe");
    static currentLimit = 100;
    
    static refresh() {
        this.$groupPostsFrame.attr("src", `/grupy/tablica?id=${GROUP_ID}&limit=${this.currentLimit}`);
    }

    static nextPage() {
        this.currentLimit += 100;

        return new Promise(resolve => {
            this.$groupPostsFrame.on("load", resolve);
            this.refresh();
        });
    }

    /**
     * @param postId {number}
     */
    static scrollToPostWithId(postId) {
        /** @type {HTMLIFrameElement} */
        const iframe = this.$groupPostsFrame.get(0);
        iframe.contentWindow.scrollToPostWithId(postId);
    }
}

WallPostsBrowser.refresh();

/**
 * @param lastPostId {number}
 */
function postBrowserNextPage(lastPostId) {
    WallPostsBrowser.nextPage().then(() => {
        WallPostsBrowser.scrollToPostWithId(lastPostId);
    });
}

class SafeBrowser {
    static $groupSafeFrame = $("#group-safe-iframe");
    static $loggedIntoSafeAlert = $("#logged-into-safe-alert");
    static loaded = false;
    
    static load() {
        const safeId = this.$groupSafeFrame.data("safeid");
        this.$groupSafeFrame.attr("src", `/sejf?id=${safeId}`);
        this.loaded = true;
    }
    
    static onSuccessfulLogin() {
        this.$loggedIntoSafeAlert.css("display", "block");
    }
}

function onSuccessfulSafeLogin() {
    SafeBrowser.onSuccessfulLogin();
}

SafeBrowser.$loggedIntoSafeAlert.find("button").click(function() {
    SafeBrowser.$loggedIntoSafeAlert.css("display", "none");
});

function post() {
    makePostRequest(
        `/grupy/tablica?id=${GROUP_ID}`,
        () => {
            $postContentEditor.val("");
            clearAttachments();
            WallPostsBrowser.refresh();
        },
        () => {
            Toast.show("nie udało się wstawić posta", 2);
        }
    );
}

$(() => void $('[data-toggle="tooltip"]').tooltip());

class UserList {
    /**
     * @param containerSelector {string}
     * @param username {string}
     * @returns {jQuery}
     */
    static selectUserElement(containerSelector, username) {
        return $(`${containerSelector} > .user-list-item[data-username="${username}"]`);
    }

    /**
     * @param containerSelector {string}
     */
    static selectAllUserElements(containerSelector) {
        return $(`${containerSelector} > .user-list-item`);
    }

    /**
     * @param containerSelector {string}
     * @param username {string}
     */
    static hideUser(containerSelector, username) {
        this.selectUserElement(containerSelector, username).attr("data-hidden", "1");
    }

    /**
     * @param containerSelector {string}
     * @param username {string}
     */
    static showUser(containerSelector, username) {
        this.selectUserElement(containerSelector, username).attr("data-hidden", "0");
    }

    /**
     * @param containerSelector {string}
     */
    static hideAllUsers(containerSelector) {
        this.selectAllUserElements(containerSelector).attr("data-hidden", "1");
    }
    /**
     * @param containerSelector {string}
     * @returns {Generator<string, void, *>}
     */
    static * iterateUsers(containerSelector) {
        for (const userListItem of this.selectAllUserElements(containerSelector)) {
            if (userListItem.getAttribute("data-hidden" === "1")) continue;

            yield userListItem.getAttribute("data-username");
        }
    }
}

/**
 * @param count {number}
 */
function updateJoinRequestsCount(count) {
    $joinRequestsCountBadge.text(count.toString());

    if (count === 0) {
        $joinRequestsCountBadge.remove();
    }
}

$(".user-list-item-menu-btn").click(function () {
    const self = $(this);
    const btnName = self.data("btnname");
    const targetUsername = self.parent().data("username");

    switch (btnName) {
        case "give_admin": {
            const sure = confirm(`uprawnienia administratorskie zostaną przekazane użytkownikowi: ${targetUsername}`);
            if (!sure) return;

            Toast.show("przekazywanie uprawnień...");

            const formData = new FormData();
            formData.append("username", targetUsername);

            fetch(`/grupy/dajadmina?id=${GROUP_ID}`, {
                method: "POST",
                body: formData
            })
                .then(response => {
                    if (!response.ok) throw new Error();

                    Toast.show("odświeżanie...");
                    location.reload();
                })
                .catch(() => {
                    Toast.show("nie udało się przekazać uprawnień", 2);
                });
            break;
        }
        case "view_profile": {
            location.assign(`/profil?uzytkownik=${targetUsername}`);
            break;
        }
        case "ban": {
            const sure = confirm(`zbanowany zostanie użytkownik: ${targetUsername}`);
            if (!sure) return;

            Toast.show(`banowanie ${targetUsername}...`);

            const formData = new FormData();
            formData.append("username", targetUsername);

            fetch(`/grupy/zbanuj?id=${GROUP_ID}`, {
                method: "POST",
                body: formData
            })
                .then(response => {
                    if (!response.ok) throw new Error();

                    Toast.show(`zbanowano ${targetUsername}`, 1);
                    UserList.hideUser("#members-tab", targetUsername);
                    UserList.showUser("#bans-tab", targetUsername);
                })
                .catch(() => {
                    Toast.show("nie udało się zbanować użytkownika", 2);
                });
            break;
        }
        case "kick": {
            const sure = confirm(`wyrzucony zostanie użytkownik: ${targetUsername}`);
            if (!sure) return;

            Toast.show(`wyrzucanie ${targetUsername}...`);

            const formData = new FormData();
            formData.append("username", targetUsername);

            fetch(`/grupy/wyrzuc?id=${GROUP_ID}`, {
                method: "POST",
                body: formData
            })
                .then(response => {
                    if (!response.ok) throw new Error();

                    Toast.show(`wyrzucono ${targetUsername}`, 1);
                    UserList.hideUser("#members-tab", targetUsername);
                })
                .catch(() => {
                    Toast.show("nie udało się wyrzucić użytkownika", 2);
                });
            break;
        }
        case "unban": {
            const sure = confirm(`odbanowany zostanie użytkownik: ${targetUsername}`);
            if (!sure) return;

            Toast.show(`odbanowywanie ${targetUsername}...`);

            const formData = new FormData();
            formData.append("username", targetUsername);

            fetch(`/grupy/odbanuj?id=${GROUP_ID}`, {
                method: "POST",
                body: formData
            })
                .then(response => {
                    if (!response.ok) throw new Error();

                    Toast.show(`odbanowano ${targetUsername}`, 1);
                    UserList.hideUser("#bans-tab", targetUsername);
                    UserList.showUser("#members-tab", targetUsername);
                })
                .catch(() => {
                    Toast.show("nie udało się odbanować użytkownika", 2);
                });
            break;
        }
        case "reject_join_request": {
            const sure = confirm(`odrzucony zostanie użytkownik: ${targetUsername}`);
            if (!sure) return;

            Toast.show("odrzucanie prośby...");

            const formData = new FormData();
            formData.append("username", targetUsername);

            fetch(`/grupy/prosba/odrzuc?id=${GROUP_ID}`, {
                method: "POST",
                body: formData
            })
                .then(response => {
                    if (!response.ok) throw new Error();

                    Toast.show(`odrzucono prośbę ${targetUsername}`, 1);
                    UserList.hideUser("#join-requests-tab", targetUsername);
                })
                .catch(() => {
                    Toast.show("nie udało się odrzucić prośby", 2);
                });
            break;
        }
        case "approve_join_request": {
            const sure = confirm(`zatwierdzony zostanie użytkownik: ${targetUsername}`);
            if (!sure) return;

            Toast.show("zatwierdzanie prośby...");

            const formData = new FormData();
            formData.append("username", targetUsername);

            fetch(`/grupy/prosba/zatwierdz?id=${GROUP_ID}`, {
                method: "POST",
                body: formData
            })
                .then(response => {
                    if (!response.ok) throw new Error();

                    Toast.show(`zatwierdzono prośbę ${targetUsername}`, 1);
                    UserList.hideUser("#join-requests-tab", targetUsername);
                    UserList.showUser("#members-tab", targetUsername);
                    updateJoinRequestsCount(--joinRequestsCount);
                })
                .catch(() => {
                    Toast.show("nie udało się zatwierdzić prośby", 2);
                });
            break;
        }
    }
});

$groupPicChooser.change(ev => {
    /** @type {HTMLInputElement} */
    const input = ev.target;
    const pictureFile = input.files[0];
    const reader = new FileReader();

    reader.onload = ev => {
        const src = ev.target.result;

        $picPreview.croppie("bind", { url: src });
    }

    reader.readAsDataURL(pictureFile);
});

$changePicBtn.click(() => {
    $groupPicChooser.click();
});

$saveGroupLookSettingsBtn.click(() => {
    const groupName = $groupNameEdit.val().trim();

    if (groupName.length === 0) {
        Toast.show("nazwa grupy nie może być pusta", 2);
        return;
    }

    Toast.show("aktualizowanie ustawień...");

    /** @type {Promise<Blob>} */
    const resultPromise = $picPreview.croppie("result", {
        type: "blob",
        size: "original",
        circle: false
    });

    resultPromise
        .then(result => {
            const formData = new FormData();

            formData.append("pic", result);
            formData.append("name", groupName);
            formData.append("description", $groupDescriptionEdit.val().trim());

            return fetch(`/grupy/ustaw/wyglad?id=${GROUP_ID}`, {
                method: "POST",
                body: formData
            });
        })
        .then(response => {
            if (response.ok) {
                Toast.show("zaktualizowano ustawienia", 1);

                /** @type {Promise<{picSrc: string, name: string}>} */
                const newGroupLookDataPromise = response.json();

                newGroupLookDataPromise.then(newGroupLookData => {
                    // evil cache trick
                    $headingPic.attr("src", `${newGroupLookData.picSrc}?${Date.now()}`);
                    $headingName.text(newGroupLookData.name);
                });
            } else {
                Toast.show("nie udało się zaktualizować ustawień", 2);
            }
        })
        .catch(() => {
            Toast.show("nie udało się zaktualizować ustawień", 2);
        });
});

const groupPicSrc = $picPreview.data("src");

$picPreview.croppie({
    viewport: { width: 120, height: 120, type: "circle" },
    showZoomer: false
});

$picPreview.find(".cr-boundary").on("wheel", () => {
    $scrollZoomTip.attr("data-animatedshow", "0");
    setTimeout(() => $scrollZoomTip.remove(), 1000);
});

$('.menu-option[data-optionid="manage"]').click(() => {
    $picPreview.croppie("bind", { url: groupPicSrc });
});

function showGroupAccessLevelInChooser() {
    switch (GROUP_ACCESS_LEVEL) {
        case 0: BigChooser.setActiveOption("join-policy", "public"); break;
        case 1: BigChooser.setActiveOption("join-policy", "needs-permission"); break;
        case 2: BigChooser.setActiveOption("join-policy", "invite-only"); break;
    }
}

showGroupAccessLevelInChooser();

if (GROUP_ACCESS_LEVEL !== 1) {  // checks if the group join policy is public or invite-only
    $joinRequestsMenuOption.css("display", "none");
}

BigChooser.setOnSwitch("join-policy", joinPolicy => {
    Toast.show("aktualizowanie polityki...");

    const formData = new FormData();
    formData.append("policy", joinPolicy);

    fetch(`/grupy/ustaw/polityka_przyjmowania_czlonkow?id=${GROUP_ID}`, {
        method: "POST",
        body: formData
    })
        .then(response => {
            if (response.ok) {
                GROUP_ACCESS_LEVEL = {"public": 0, "needs-permission": 1, "invite-only": 2}[joinPolicy];

                if (joinPolicy === "public") {
                    for (const username of UserList.iterateUsers("#join-requests-tab")) {
                        UserList.showUser("#members-tab", username);
                    }
                }

                $joinRequestsMenuOption.css("display", joinPolicy === "needs-permission" ? "flex" : "none");

                UserList.hideAllUsers("#join-requests-tab");
                updateJoinRequestsCount(0);
                Toast.show("polityka zaktualizowana", 1);
            } else {
                Toast.show("nie udało się zaktualizować polityki", 2);
            }
        })
        .catch(() => {
            Toast.show("nie udało się zaktualizować polityki", 2);
        })
        .finally(showGroupAccessLevelInChooser);
});

$deleteGroupBtn.click(function() {
    const self = $(this);

    self.prop("disabled", true);
    self.text("usuwanie grupy...");

    fetch(location.href, {method: "DELETE"})
        .then(response => {
            if (!response.ok) throw new Error();

            Toast.show("grupa została usunięta. proszę czekać...");
            location.assign("/grupy");
        })
        .catch(() => {
            Toast.show("nie udało się usunąć grupy", 2);
            self.prop("disabled", false);
            self.text("jestem pewien");
        });
});