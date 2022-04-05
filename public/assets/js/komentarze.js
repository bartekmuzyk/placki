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