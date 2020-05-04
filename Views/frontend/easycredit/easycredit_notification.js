
var easycreditParent = document.getElementsByClassName("ratenkauf by easyCredit")[0].parentElement.parentElement;
var easycreditInput = easycreditParent.getElementsByTagName('input')[0];
if (document.getElementById('easycredit_error_message') !== null) {
    easycreditParent.style.backgroundColor = "#eee";
    easycreditParent.style.display = "block";
    easycreditParent.style.cursor = "not-allowed";

    easycreditInput.disabled = true;
    easycreditInput.checked = false;
} else {
    if (easycreditInput.checked === true) {
        if (document.getElementById("toc_easycredit").checked === false) {
            document.querySelectorAll('.confirm--actions ').forEach(elem => {
                buttonCheckout = elem.getElementsByTagName("button");

                buttonCheckout[0].disabled = true;

            });
        }
    }
}

window.onload = function () {
    if (document.getElementById('easycredit_error_message') !== null) {
        easycreditParent.style.backgroundColor = "#eee";
        easycreditParent.style.display = "block";
        easycreditParent.style.cursor = "not-allowed";

        easycreditInput.disabled = true;
        easycreditInput.checked = false;
    } else {
        if (easycreditInput.checked === true) {
            if (document.getElementById("toc_easycredit").checked === false) {
                document.querySelectorAll('.confirm--actions ').forEach(elem => {
                    buttonCheckout = elem.getElementsByTagName("button");

                    buttonCheckout[0].disabled = true;

                });
            }
        }
    }
};

if (document.getElementById('toc_easycredit') !== null) {
    document.getElementById("toc_easycredit").addEventListener("click", function () {
        if (easycreditInput.checked === true) {
            if (document.getElementById("toc_easycredit").checked === false) {
                document.querySelectorAll('.confirm--actions ').forEach(elem => {
                    buttonCheckout = elem.getElementsByTagName("button");

                    buttonCheckout[0].disabled = true;

                });
            } else {
                document.querySelectorAll('.confirm--actions ').forEach(elem => {
                    buttonCheckout = elem.getElementsByTagName("button");

                    buttonCheckout[0].disabled = false;

                });
            }
        }
    });

}