const $commentContent = $("#comment-content");

$("#comment-btn").click(function() {
    const self = $(this);
    
    self.prop("disabled", true);
    self.text("postowanie komentarza...");
    
    const formData = new FormData();
    formData.append("content", $commentContent.val());
    
    fetch(location.href, {
        method: "POST",
        body: formData
    })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                parent.Toast.show("nie udało się wysłać komentarza.", 2);
            }
        })
        .catch(() => {
            parent.Toast.show("nie udało się wysłać komentarza.", 2);
        })
        .finally(() => {
            self.prop("disabled", false);
            self.text("skomentuj");
        });
});

$(".delete-comment-button").click(function() {
    const self = $(this);
    const commentElement = self.parent().parent().parent();
    const commentId = commentElement.data("commentid");
    const commentsEndpointUrl = location.href.replace(location.search, "");

    fetch(`${commentsEndpointUrl}?id=${commentId}`, {method: "DELETE"})
        .then(response => {
            if (response.ok) {
                commentElement.remove();
                parent.Toast.show("usunięto komentarz", 2);
            } else {
                parent.Toast.show("nie udało się usunąć komentarza", 2);
            }
        })
        .catch(() => {
            parent.Toast.show("nie udało się usunąć komentarza", 2);
        });
});