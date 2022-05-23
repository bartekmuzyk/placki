setActiveNavTab("/media");

const $likeCount = $("#like-count");
const $downloadBtn = $("#download-btn");
const $videoPlayer = $("video");

const params = new URLSearchParams(location.search);
const isFallbackPlayer = params.get("fallback") === "1";

function switchToFallbackPlayer() {
    localStorage.setItem("useFallbackPlayer", "1");
    location.search = new URLSearchParams([
        ...params.entries(),
        ["fallback", "1"]
    ]).toString();
}

class ChromePlayerWarningComponent extends StatefulComponent {
    render() {
        return !isFallbackPlayer && bowser.parse(navigator.userAgent).browser.name === "Chrome" ? `
            <div class="alert alert-danger" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-alert-triangle" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                   <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                   <path d="M12 9v2m0 4v.01"></path>
                   <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"></path>
                </svg>
                odtwarzacz może nie działać poprawnie w przeglądarce Chrome. zalecane jest użycie domyślnego odtwarzacza przeglądarki.
                <br/>
                <button class="btn btn-sm btn-primary mt-2" onclick="switchToFallbackPlayer();">użyj rezerwowego odtwarzacza</button>
            </div>
`
            : "";
    }
}

const chromePlayerWarningComponent = new ChromePlayerWarningComponent("chrome-player-warning");
chromePlayerWarningComponent.reRender();

let likeCount = parseInt($likeCount.text());
if (isNaN(likeCount)) {
    likeCount = 0;
}

$(".like-btns").on("click", function() {
    const self = $(this);
    const likeStatus = self.attr("data-liked");
    
    const makeReq = actionUrl => fetch(actionUrl, { method: "POST" });
    
    switch (likeStatus) {
        default:
        case "0":
            makeReq(`/media/film/polub?id=${VIDEO_ID}`)
                .then(response => {
                    if (!response.ok) throw new Error();

                    self.attr("data-liked", "1");
                    likeCount++;
                    $likeCount.text(likeCount);
                    Toast.show("dodano film do ulubionych.", "heart", 2);
                })
                .catch(() => {
                    Toast.show("nie udało się polubić filmu.", "alert", 4);
                });
            break;
        case "1":
            makeReq(`/media/film/odlub?id=${VIDEO_ID}`)
                .then(response => {
                    if (!response.ok) throw new Error();

                    self.attr("data-liked", "0");
                    likeCount--;
                    $likeCount.text(likeCount);
                    Toast.show("usunięto film z ulubionych.", "heart_crossed", 2);
                })
                .catch(() => {
                    Toast.show("nie udało się odlubić filmu.", "alert", 4);
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
    
    // noinspection JSUnusedGlobalSymbols
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

$("#delete-video-btn").on("click", () => {
    const sure = confirm("na pewno chcesz usunąć ten film? ta akcja jest nieodwracalna.");

    if (!sure) return;

    Toast.show("usuwanie filmu...", "bin");

    fetch(`/media/film?id=${VIDEO_ID}`, { method: "DELETE" })
        .then(response => {
            if (!response.ok) throw new Error();

            Toast.show("film usunięty, proszę czekać", "bin");
            location.assign("/media");
        })
        .catch(() => {
            Toast.show("nie udało się usunąć filmu", "alert", 2);
        });
});