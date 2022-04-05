setActiveNavTab("/media");

const $likeCount = $("#like-count");
const $downloadBtn = $("#download-btn");
const $videoPlayer = $("video");

let likeCount = parseInt($likeCount.text());
if (isNaN(likeCount)) {
    likeCount = 0;
}

$(".like-btns").click(function() {
    const self = $(this);
    const likeStatus = self.attr("data-liked");
    
    const makeReq = actionUrl => fetch(actionUrl, { method: "POST" });
    
    switch (likeStatus) {
        default:
        case "0":
            makeReq(`/media/film/polub?id=${VIDEO_ID}`)
                .then(response => {
                    if (response.ok) {
                        self.attr("data-liked", "1");
                        likeCount++;
                        $likeCount.text(likeCount);
                        Toast.show("dodano film do ulubionych.", 2);
                    } else {
                        throw new Error();
                    }
                })
                .catch(() => {
                    Toast.show("nie udało się polubić filmu.", 4);
                });
            break;
        case "1":
            makeReq(`/media/film/odlub?id=${VIDEO_ID}`)
                .then(response => {
                    if (response.ok) {
                        self.attr("data-liked", "0");
                        likeCount--;
                        $likeCount.text(likeCount);
                        Toast.show("usunięto film z ulubionych.", 2);
                    } else {
                        throw new Error();
                    }
                })
                .catch(() => {
                    Toast.show("nie udało się odlubić filmu.", 4);
                });
            break;
    }
});

class VideoCommentsFrame {
    static $commentsFrame = $("#video-comments-iframe");
    
    static adjustHeightOnLoad() {
        const height = this.$commentsFrame.get(0).contentWindow.document.documentElement.offsetHeight + "px";
        this.$commentsFrame.css("height", height);
    }
    
    static adjustHeight(height) {
        this.$commentsFrame.css("height", parseInt(height) + "px");
    }
    
    static refresh() {
        this.$commentsFrame.attr("src", `/media/film/komentarze?id=${VIDEO_ID}`);
    }
}

VideoCommentsFrame.$commentsFrame.on("load", () => {
    VideoCommentsFrame.adjustHeightOnLoad();
});
VideoCommentsFrame.refresh();

fetch($videoPlayer.attr("src"))
    .then(response => response.blob())
    .then(blob => {
        const url = URL.createObjectURL(blob);

        $downloadBtn.attr("href", url);
        $downloadBtn.attr("download", $downloadBtn.data("filename"));
        $downloadBtn.html(
            $downloadBtn.html().replace("przygotowywanie linku do pobrania...", "pobierz film")
        );
    });