/**
 * @param seconds {number}
 * @returns {string}
 * @private
 */
function __seek_time_display_secondsToHumanRepresentation(seconds) {
    let minutes = 0;

    while (seconds >= 60) {
        minutes++;
        seconds -= 60;
    }

    let timeString = `${minutes}:`;

    if (seconds < 10) {
        timeString += "0";
    }

    timeString += seconds.toString();

    return timeString;
}

$("vds-media").each(function() {
    const self = $(this);

    const timeSlider = self.find("vds-time-slider").get(0);
    const seekTimeDisplayWrapper = self.find(`.video-seek-time-display`);
    const seekTimeDisplaySpan = seekTimeDisplayWrapper.find("span");

    timeSlider.onpointerenter = () => {
        console.log("enter");
        seekTimeDisplayWrapper.attr("data-animatedshow", "1");
    }

    timeSlider.onpointermove = function() {
        let seconds = parseInt(this.style.getPropertyValue("--vds-pointer-value"));
        seekTimeDisplaySpan.text(__seek_time_display_secondsToHumanRepresentation(seconds));
    };

    timeSlider.onpointerleave = () => {
        seekTimeDisplayWrapper.attr("data-animatedshow", "0");
    }
});