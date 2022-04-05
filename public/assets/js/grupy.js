setActiveNavTab("/grupy");

setTabCallback(tabId => {
    switch (tabId) {
        case "all":
            $('.group').css("display", "block");
            break;
        case "mine":
            $('.group').css("display", "none");
            $('.group[data-mine="1"]').css("display", "block");
            break;
        case "joined":
            $('.group').css("display", "none");
            $('.group[data-joined="1"]').css("display", "block");
            break;
    }
});