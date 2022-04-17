setActiveNavTab("/glowna");

const $commentsModal = $("#comments-modal");
const $commentsModalSubtitle = $("#comments-modal-subtitle");
const $commentsFrame = $("#post-comments-iframe");

$commentsFrame.on("load", () => {
    $commentsModalSubtitle.text("");
});

function openComments(postId) {
    $commentsModalSubtitle.text("ładowanie komentarzy...");
    $commentsFrame.attr("src", `/post/komentarze?id=${postId}`);
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

        return new Promise(resolve => {
            this.$postsFrame.on("load", resolve);
            this.refresh();
        });
    }

    /**
     * @param postId {number}
     */
    static scrollToPostWithId(postId) {
        /** @type {HTMLIFrameElement} */
        const iframe = this.$postsFrame.get(0);
        iframe.contentWindow.scrollToPostWithId(postId);
    }
}

PostBrowser.refresh();

/**
 * @param lastPostId {number}
 */
function postBrowserNextPage(lastPostId) {
    PostBrowser.nextPage().then(() => {
        PostBrowser.scrollToPostWithId(lastPostId);
    });
}

function post() {
    makePostRequest(
        "/posty",
        () => {
            $postContentEditor.val("");
            clearAttachments();
            PostBrowser.refresh();
        },
        () => {
            Toast.show("nie udało się wstawić posta", 2);
        }
    );
}

$(() => void $('[data-toggle="tooltip"]').tooltip());