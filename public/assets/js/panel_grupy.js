setActiveNavTab("/grupy");

const $commentsModal = $("#comments-modal");
const $commentsModalSubtitle = $("#comments-modal-subtitle");
const $commentsFrame = $("#post-comments-iframe");

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
    $commentsModalSubtitle.text("Ładowanie komentarzy...");
    $commentsFrame.attr("src", `/posty/komentarze?post=${postId}`);
    $commentsModal.modal("show");
}

class WallPostsBrowser {
    static $groupPostsFrame = $("#group-posts-iframe");
    
    static refresh() {
        const groupId = this.$groupPostsFrame.data("groupid");
        this.$groupPostsFrame.attr("src", `/grupy/tablica?id=${groupId}`);
    }
}

WallPostsBrowser.refresh();

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