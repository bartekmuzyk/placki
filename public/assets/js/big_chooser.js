const $bigChooserOptions = $(".big-chooser > div");
const _choosers = {};
const _listeners = {};

$bigChooserOptions.each((i, el) => {
    const $el = $(el);

    $el.css({
        "background-color": $el.data("bg"),
        "color": $el.data("fg")
    });
    $el.attr("data-chosen", "0");

    const parentChooserId = $el.parent().data("chooserid");

    if (!(parentChooserId in _choosers)) {
        _choosers[parentChooserId] = null;
    }
});

$bigChooserOptions.click(function() {
    const self = $(this);
    const optionChooserId = self.data("chooser");
    const optionId = self.data("optionid");

    $(
        `.big-chooser[data-chooserid="${optionChooserId}"] > div[data-chooser="${optionChooserId}"][data-optionid="${_choosers[optionChooserId]}"]`
    ).attr("data-chosen", "0");
    self.attr("data-chosen", "1");

    if (optionChooserId in _listeners && _choosers[optionChooserId] !== optionId) _listeners[optionChooserId](optionId);

    _choosers[optionChooserId] = optionId;
});

class BigChooser {
    /**
     * @param chooser {string}
     * @param optionId {string}
     */
    static setActiveOption(chooser, optionId) {
        $(
            `.big-chooser[data-chooserid="${chooser}"] > div[data-chooser="${chooser}"][data-optionid="${_choosers[chooser]}"]`
        ).attr("data-chosen", "0");
        $(
            `.big-chooser[data-chooserid="${chooser}"] > div[data-chooser="${chooser}"][data-optionid="${optionId}"]`
        ).attr("data-chosen", "1");
        _choosers[chooser] = optionId;
    }

    /**
     * @param chooser {string}
     * @return {?string}
     */
    static getActiveOption(chooser) {
        return _choosers[chooser] ?? null;
    }

    /**
     * @param chooserId {string}
     * @param callback {(optionId: string) => void}
     */
    static setOnSwitch(chooserId, callback) {
        _listeners[chooserId] = callback;
    }
}