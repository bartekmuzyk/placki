// noinspection JSDeprecatedSymbols
if (
    !(/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent))
    &&
    !(/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.platform))
) {
    $("#mobile-app-banner").removeClass("d-flex").css("display", "none");
}