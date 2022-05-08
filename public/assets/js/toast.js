/**
 * @typedef {
 * "heart"|"heart_crossed"|"bin"|"calendar_add"|"alert"|"info"|"clock"|"download"|"crown"|"link"|"thumbnail"|"refresh"|
 * "hourglass"|"check"
 * } IconName
 */

/** @type {Object<IconName, string>} */
const __Toast_icons = {
    heart: `<svg class="icon icon-tabler icon-tabler-heart"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428m0 0a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572"></path></svg>`,
    heart_crossed: `<svg class="icon icon-tabler icon-tabler-heart-off"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><path d="M3 3l18 18"></path><path d="M19.5 12.572l-1.5 1.428m-2 2l-4 4l-7.5 -7.428m0 0a5 5 0 0 1 -1.288 -5.068a4.976 4.976 0 0 1 1.788 -2.504m3 -1c1.56 .003 3.05 .727 4 2.006a5 5 0 1 1 7.5 6.572"></path></svg>`,
    bin: `<svg class="icon icon-tabler icon-tabler-trash"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><line x1=4 x2=20 y1=7 y2=7></line><line x1=10 x2=10 y1=11 y2=17></line><line x1=14 x2=14 y1=11 y2=17></line><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path></svg>`,
    calendar_add: `<svg class="icon icon-tabler icon-tabler-calendar-plus"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><rect height=16 rx=2 width=16 x=4 y=5></rect><line x1=16 x2=16 y1=3 y2=7></line><line x1=8 x2=8 y1=3 y2=7></line><line x1=4 x2=20 y1=11 y2=11></line><line x1=10 x2=14 y1=16 y2=16></line><line x1=12 x2=12 y1=14 y2=18></line></svg>`,
    alert: `<svg class="icon icon-tabler icon-tabler-alert-triangle"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><path d="M12 9v2m0 4v.01"></path><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"></path></svg>`,
    info: `<svg class="icon icon-tabler icon-tabler-info-circle"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><circle cx=12 cy=12 r=9></circle><line x1=12 x2=12.01 y1=8 y2=8></line><polyline points="11 12 12 12 12 16 13 16"></polyline></svg>`,
    clock: `<svg class="icon icon-tabler icon-tabler-clock"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><circle cx=12 cy=12 r=9></circle><polyline points="12 7 12 12 15 15"></polyline></svg>`,
    download: `<svg class="icon icon-tabler icon-tabler-download"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path><polyline points="7 11 12 16 17 11"></polyline><line x1=12 x2=12 y1=4 y2=16></line></svg>`,
    crown: `<svg class="icon icon-tabler icon-tabler-crown"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><path d="M12 6l4 6l5 -4l-2 10h-14l-2 -10l5 4z"></path></svg>`,
    link: `<svg class="icon icon-tabler icon-tabler-link"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5"></path><path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5"></path></svg>`,
    thumbnail: `<svg class="icon icon-tabler icon-tabler-polaroid"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><rect height=16 rx=2 width=16 x=4 y=4></rect><line x1=4 x2=20 y1=16 y2=16></line><path d="M4 12l3 -3c.928 -.893 2.072 -.893 3 0l4 4"></path><path d="M13 12l2 -2c.928 -.893 2.072 -.893 3 0l2 2"></path><line x1=14 x2=14.01 y1=7 y2=7></line></svg>`,
    refresh: `<svg class="icon icon-tabler icon-tabler-refresh"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"></path><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"></path></svg>`,
    hourglass: `<svg class="icon icon-tabler icon-tabler-hourglass"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><path d="M6.5 7h11"></path><path d="M6.5 17h11"></path><path d="M6 20v-2a6 6 0 1 1 12 0v2a1 1 0 0 1 -1 1h-10a1 1 0 0 1 -1 -1z"></path><path d="M6 4v2a6 6 0 1 0 12 0v-2a1 1 0 0 0 -1 -1h-10a1 1 0 0 0 -1 1z"></path></svg>`,
    check: `<svg class="icon icon-tabler icon-tabler-check"fill=none height=24 stroke=currentColor stroke-linecap=round stroke-linejoin=round stroke-width=2 viewBox="0 0 24 24"width=24 xmlns=http://www.w3.org/2000/svg><path d="M0 0h24v24H0z"fill=none stroke=none></path><path d="M5 12l5 5l10 -10"></path></svg>`
};

class Toast {
    static $root = $(".toast-message");
    static $sub = this.$root.find("div");

    /**
     * @param {string} message
     * @param {IconName} icon
     * @param {?number} timeout
     */
    static show(message, icon, timeout = null) {
        this.$sub.html(__Toast_icons[icon] + " " + message);
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