window.onload = function () {
    two_replace_backgrounds();
};


function two_replace_backgrounds() {
    let two_elements_list = document.querySelectorAll("*:not(br):not(hr):not(iframe):not(pre)");
    two_elements_list.forEach((elem) => {
        let style = elem.currentStyle || window.getComputedStyle(elem, false);

        let bg_image = style.backgroundImage;

        if (bg_image === 'none' || bg_image.indexOf(window['two_svg_placeholder']) === -1) {
            return;
        }

        bg_image = bg_image.replace(window['two_svg_placeholder'], "");
        if (!bg_image) {
            return;
        }

        elem.classList.add("two_bg");
        elem.classList.add("lazy");
        elem.setAttribute("data-bg-multi", bg_image);
    });

    if (typeof two_lazyLoadInstance === "undefined") {
        var two_lazyLoadInstance = new LazyLoad({
            'callback_applied': function(element, instance){
                let settings = instance._settings;
                var bgDataValue = element.getAttribute("data-" + settings.data_bg_multi);
                if (!bgDataValue) {
                    return;
                }

                if(window.getComputedStyle(element).getPropertyValue("background-image") !== bgDataValue) {
                    let style = element.getAttribute("style");
                    style += "background-image: " + bgDataValue + " !important;";
                    element.setAttribute("style", style);
                }
            }
        });
    } else {
        two_lazyLoadInstance.update();
    }
}

