$(".post .open-comments").click(function() {
    const self = $(this);
    const postId = self.parent().parent().data("postid");
    parent.openComments(postId);
});

$(".like-btns").click(function() {
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
                .then(() => {
                    if (response.ok) {
                        self.attr("data-liked", "1");
                        likeCount++;
                    } else {
                        throw new Error();
                    }
                })
                .catch(() => {
                    alert("Nie udało się odlubić posta.");
                });
            break;
        case "1":
            makeReq(`/post/odlub?id=${postId}`)
                .then(() => {
                    if (response.ok) {
                        self.attr("data-liked", "0");
                        likeCount--;
                    } else {
                        throw new Error();
                    }
                })
                .catch(() => {
                    alert("Nie udało się odlubić posta.");
                });
            break;
    }
    
    $counterElement.text(likeCount);
});