class Toast {
    static $root = $(".toast-message");
    static $sub = this.$root.find("div");

    /**
     * @param {string} message 
     * @param {?number} timeout 
     */
    static show(message, timeout = null) {
        this.$sub.text(message);
        this.$root.attr("data-show", "1");
        
        if (typeof timeout === "number") {
            setTimeout(() => this.dismiss(), timeout * 1000);
        }
    }

    static dismiss() {
        this.$root.attr("data-show", "0")
    }
}

window.Toast = Toast;