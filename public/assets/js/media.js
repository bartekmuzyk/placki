setActiveNavTab("/media");

let currentTab = "video";

function onTabChange(tabId) {
    $(`#${currentTab}-tab`).css("display", "none");
    $(".press-enter-to-search-message").attr("data-animationenabled", "0");
    $(`#${tabId}-tab`).css("display", "block");
    currentTab = tabId;
}

setTabCallback(onTabChange);
onTabChange(currentTab);

function showAllAlbums() {
    $(".media-photo-item").css({display: ""});
}

function showAlbum(albumName) {
    $(".media-photo-item").css("display", "none");
    $(`.media-photo-item[data-album="${albumName}"]`).css({display: ""});
}

const $albumChooserBtn = $("#album-chooser-btn");

$("#album-chooser-menu > .dropdown-item").click(function() {
    const self = $(this);
    const special = self.data("special");
    const selectedOptionText = self.text();
    
    if (special === "all") {
        showAllAlbums();
    } else {
        const albumName = selectedOptionText;
        showAlbum(albumName);
    }
    
    $albumChooserBtn.text(selectedOptionText);
});