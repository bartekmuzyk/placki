function setActiveNavTab(href) {
    $(`.navbar .nav-link[href="${href}"]`).addClass("active");
}

$(".press-enter-to-search-message[data-depends]").each((index, el) => {
    const $el = $(el);
    const depends = $el.data("depends");
    
    $el.attr("data-animationenabled", "0");
    
    if (depends) {
        const dependencies = $(depends);
        
        dependencies.focus(() => {
            $el.attr("data-animationenabled", "1");
            $el.attr("data-animatedshow", "1");
        });
        
        dependencies.blur(() => {
            $el.attr("data-animatedshow", "0");
        });
    }
});