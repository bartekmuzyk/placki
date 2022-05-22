setActiveNavTab("/ludzie");

const $peopleDivs = $("div[data-searchfriendlyusername]");

$("#person-search-input").on("input", function() {
    const query = this.value.toLowerCase();

    if (!query) {
        $peopleDivs.removeClass("d-none").addClass("d-inline-flex");
        return;
    }

    const queryParts = query.split(" ");

    $peopleDivs.each(function() {
        const searchFriendlyUsername = this.getAttribute("data-searchfriendlyusername");
        let show = false;

        for (const part of queryParts) {
            if (searchFriendlyUsername.indexOf(part) > -1) {
                show = true;
                break;
            }
        }

        if (show) {
            this.classList.remove("d-none");
            this.classList.add("d-inline-block");
        } else {
            this.classList.remove("d-inline-block");
            this.classList.add("d-none");
        }
    });
});