$(".post .open-comments").on("click", function() {
    const self = $(this);
    const postId = self.parent().parent().data("postid");
    parent.openComments(postId);
});

$(".like-btns").on("click", function() {
    const self = $(this);
    const likeStatus = self.attr("data-liked");
    const postId = self.parent().parent().data("postid");
    const $counterElement = $(`.post[data-postid="${postId}"] .post-like-count`);
    let likeCount = parseInt($counterElement.text());
    
    if (isNaN(likeCount)) {
        likeCount = 0;
    }
    
    const makeReq = actionUrl => fetch(actionUrl, { method: "POST" });
    
    switch (likeStatus) {
        default:
        case "0":
            makeReq(`/post/polub?id=${postId}`)
                .then(response => {
                    if (!response.ok) throw new Error();

                    self.attr("data-liked", "1");
                    likeCount++;
                    $counterElement.text(likeCount);
                })
                .catch(() => {
                    parent.Toast.show("nie udało się polubić posta", "alert", 2);
                });
            break;
        case "1":
            makeReq(`/post/odlub?id=${postId}`)
                .then(response => {
                    if (!response.ok) throw new Error();

                    self.attr("data-liked", "0");
                    likeCount--;
                    $counterElement.text(likeCount);
                })
                .catch(() => {
                    parent.Toast.show("nie udało się odlubić posta", "alert", 2);
                });
            break;
    }
});

$(".delete-post-button").on("click", function() {
    const self = $(this);
    const postElement = self.parent().parent().parent();
    const postId = postElement.data("postid");
    const postsEndpointUrl = location.href.replace(location.search, "");

    fetch(`${postsEndpointUrl}?id=${postId}`, {method: "DELETE"})
        .then(response => {
            if (response.ok) {
                postElement.remove();
                parent.Toast.show("usunięto post", "bin", 2);
            } else {
                parent.Toast.show("nie udało się usunąć posta", "alert", 2);
            }
        })
        .catch(() => {
            parent.Toast.show("nie udało się usunąć posta", "alert", 2);
        });
});

$("#load-more-posts-btn").on("click", function() {
    this.innerText = "wczytywanie...";
    this.disabled = true;

    parent.postBrowserNextPage(this.getAttribute("data-lastpostid"));
});

/**
 * @param postId {number}
 */
function scrollToPostWithId(postId) {
    $(`.post[data-postid="${postId}"]`).get(0).scrollIntoView();
}