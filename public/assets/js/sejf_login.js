const $subtitle = $("#subtitle");

function getPinInputDot(n) {
    return $(`#pin-input > div:nth-child(${n})`);
}

let currentInput = "";
let loggingIn = false;

function resetInput() {
    currentInput = "";
    $("#pin-input > div").attr("data-entered", "0");
}

$(document).on("keydown", ev => {
    if (!loggingIn) {
        const enteredKey = ev.originalEvent.key;
        const currentLength = currentInput.length;

        if (isNaN(parseInt(enteredKey))) {
            if (enteredKey === "Backspace" && currentInput.length > 0) {
                currentInput = currentInput.slice(0, -1);
                getPinInputDot(currentLength).attr("data-entered", "0");
            }
        } else if (currentLength < 4) {
            currentInput += enteredKey;
            getPinInputDot(currentInput.length).attr("data-entered", "1");
        }
        
        if (currentInput.length === 4) {
            loggingIn = true;
            $subtitle.text("Autoryzowanie...");
            
            const formData = new FormData();
            formData.append("pin", currentInput);
            
            fetch("", {
                method: "POST",
                body: formData
            })
                .then(response => response.text())
                .then(status => {
                    if (status === "ok") {
                        $subtitle.text("PIN poprawny! Przekierowywanie do sejfu...");
                        parent.onSuccessfulSafeLogin();
                        location.reload();
                    } else {
                        $subtitle.text("Spróbuj ponownie.");
                        resetInput();
                        loggingIn = false;
                    }
                })
                .catch(() => {
                    $subtitle.text("Wystąpił nieznany problem z sefjem. Spróbuj ponownie później.");
                    resetInput();
                    loggingIn = false;
                });
        }
    }
});