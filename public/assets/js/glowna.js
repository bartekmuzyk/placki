setActiveNavTab("/glowna");

const $commentsModal = $("#comments-modal");
const $commentsModalSubtitle = $("#comments-modal-subtitle");
const $commentsFrame = $("#post-comments-iframe");

$commentsFrame.on("load", () => {
    $commentsModalSubtitle.text("");
});

function openComments(postId) {
    $commentsModalSubtitle.text("Ładowanie komentarzy...");
    $commentsFrame.attr("src", `/posty/komentarze?post=${postId}`);
    $commentsModal.modal("show");
}

class PostBrowser {
    static $postsFrame = $("#post-browser");
    static currentLimit = 100;
    
    static refresh() {
        this.$postsFrame.attr("src", `/posty?limit=${this.currentLimit}`);
    }
    
    static nextPage() {
        this.currentLimit += 100;
        this.refresh();
    }
}

PostBrowser.refresh();