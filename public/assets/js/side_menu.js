let _menu_option_callback = () => undefined;

$(".side-menu > .menu-option").click(function() {
    const self = $(this);
    $(".side-menu > .menu-option.menu-option-active").removeClass("menu-option-active");
    self.addClass("menu-option-active");
    const optionId = self.data("optionid");
    _menu_option_callback(optionId);
});

function setSideMenuOptionCallback(cb) {
    _menu_option_callback = cb;
}