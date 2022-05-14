function setActiveNavTab(href) {
    $(`.navbar .nav-link[href="${href}"]`).addClass("active");
}

$(".press-enter-to-search-message[data-depends]").each((index, el) => {
    const $el = $(el);
    const depends = $el.data("depends");
    
    $el.attr("data-animationenabled", "0");
    
    if (depends) {
        const dependencies = $(depends);
        
        dependencies.on("focus", () => {
            $el.attr("data-animationenabled", "1");
            $el.attr("data-animatedshow", "1");
        });
        
        dependencies.on("blur", () => {
            $el.attr("data-animatedshow", "0");
        });
    }
});

const __Base64_toUtf8 = function (text) {
    const surrogate = encodeURIComponent(text);
    let result = '';
    for (let i = 0; i < surrogate.length;) {
        const character = surrogate[i];
        i += 1;
        if (character === '%') {
            const hex = surrogate.substring(i, i += 2);
            if (hex) {
                result += String.fromCharCode(parseInt(hex, 16));
            }
        } else {
            result += character;
        }
    }
    return result;
};

const __Base64_fromUtf8 = function (text) {
    let result = '';
    for (let i = 0; i < text.length; ++i) {
        const code = text.charCodeAt(i);
        result += '%';
        if (code < 16) {
            result += '0';
        }
        result += code.toString(16);
    }
    return decodeURIComponent(result);
};

const Base64 = {
    /**
     * @param text {string}
     * @returns {string}
     */
    encode: text => btoa(__Base64_toUtf8(text)),
    /**
     * @param base64 {string}
     * @returns {string}
     */
    decode: base64 => __Base64_fromUtf8(atob(base64))
};

/**
 * @param unsafe {string}
 * @returns {string}
 */
const escapeHtml = unsafe => {
    return unsafe.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
}