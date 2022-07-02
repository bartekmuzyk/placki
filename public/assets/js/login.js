// noinspection JSDeprecatedSymbols
if (
    !(/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent))
    &&
    !(/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.platform))
) {
    $("#mobile-app-banner").removeClass("d-flex").css("display", "none");
}

const $recoveryCodeInput = $("#recovery-code-input");
const $invalidRecoveryCodeError = $("#invalid-recovery-code-error");
const $newPasswordInput = $("#new-password-input");
const $resetPasswordModalRecoveryCodeStep = $("#reset-password-modal-recovery-code-step");
const $resetPasswordModalSetPasswordStep = $("#reset-password-modal-set-password-step");
const $resetPasswordModalNextBtn = $("#reset-password-modal-next-btn");

let currentRecoveryStep = "code";
let recoveryCode = "";

$resetPasswordModalNextBtn.on("click", async function() {
    if (currentRecoveryStep === "code") {
        $resetPasswordModalNextBtn.prop("disabled", true);
        $invalidRecoveryCodeError.addClass("d-none");

        recoveryCode = $recoveryCodeInput.val().trim();
        if (recoveryCode === "") {
            $resetPasswordModalNextBtn.prop("disabled", false);
            return;
        }

        const verificationRequestAborter = new AbortController();
        $("#reset-password-modal .btn-close").on("click", () => verificationRequestAborter.abort());
        const verificationResponse = await fetch(`/weryfikuj_kod_odzyskiwania?kod=${recoveryCode}`, {
            signal: verificationRequestAborter.signal
        });

        if (!verificationResponse.ok) {
            $invalidRecoveryCodeError.removeClass("d-none");
            $resetPasswordModalNextBtn.prop("disabled", false);
            return;
        }

        /** @type {{username: string, profilePic: string}} */
        const userData = await verificationResponse.json();
        $("#reset-password-modal img").attr("src", userData.profilePic);
        $("#reset-password-modal h1").text(userData.username);

        $resetPasswordModalNextBtn.prop("disabled", false);
        $resetPasswordModalNextBtn.text("ustaw nowe hasło");
        $resetPasswordModalNextBtn.blur();
        currentRecoveryStep = "pass";
        $resetPasswordModalRecoveryCodeStep.addClass("d-none");
        $resetPasswordModalSetPasswordStep.removeClass("d-none");
    } else if (currentRecoveryStep === "pass") {
        $resetPasswordModalNextBtn.prop("disabled", true);

        const newPassword = $newPasswordInput.val().trim();
        if (newPassword === "") {
            $resetPasswordModalNextBtn.prop("disabled", false);
            return;
        }

        const formData = new FormData();
        formData.set("code", recoveryCode);
        formData.set("pass", newPassword);
        const passwordChangeResponse = await fetch("/zmien_haslo", {
            method: "POST",
            body: formData
        });

        if (passwordChangeResponse.ok) {
            $("#reset-password-modal").modal("hide");
            Toast.show("hasło zostało zmienione.", "check", 2);
        } else {
            Toast.show("nie udało się zmienić hasła.", "alert", 2);
            $resetPasswordModalNextBtn.prop("disabled", false);
        }
    }
});

$('a[data-bs-target="#reset-password-modal"]').on("click", () => {
    $resetPasswordModalNextBtn.text("dalej");
    currentRecoveryStep = "code";
    $resetPasswordModalRecoveryCodeStep.removeClass("d-none");
    $resetPasswordModalSetPasswordStep.addClass("d-none");
});