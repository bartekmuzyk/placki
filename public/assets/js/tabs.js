let _tabs_callback = () => undefined;

$(".tabs > .tab").on("click", function() {
    const self = $(this);
    $(".tabs > .tab.tab-active").removeClass("tab-active");
    self.addClass("tab-active");
    const tabId = self.data("tabid");
    _tabs_callback(tabId);
});

function activateTab(tabId) {
    $(`.tabs > .tab[data-tabid="${tabId}"]`).trigger("click");
}

function setTabCallback(cb) {
    _tabs_callback = cb;
}

$(".tab-content").css("display", "none");

