two_worker_styles_list = [];

var two_script_list = typeof two_worker_data_js === "undefined" ? [] : two_worker_data_js.js;
var two_excluded_js_list = typeof two_worker_data_excluded_js === "undefined" ? [] : two_worker_data_excluded_js.js;
var excluded_count = two_excluded_js_list.filter((el) => {return !!el['url']}).length;
var two_css_list = typeof two_worker_data_css === "undefined" ? [] : two_worker_data_css.css;
var two_fonts_list = typeof two_worker_data_font === "undefined" ? [] : two_worker_data_font.font;
var wcode = new Blob([
    document.querySelector("#two_worker").textContent
], { type: "text/javascript" });

var two_worker = new Worker(window.URL.createObjectURL(wcode) );
var two_worker_data = {"js" : two_script_list, "excluded_js":two_excluded_js_list , "css": two_css_list , "font":two_fonts_list}
two_worker.postMessage(two_worker_data);
two_worker.addEventListener("message",function(e) {
    var data = e.data;
    if(data.type === "css" && data.status === "ok"){
        if(window.two_page_loaded && two_fonts_are_loaded()){
            two_connect_style(data);
        }else{
            two_worker_styles_list.push(data);
        }
    } else if(data.type === "js"){
        if(data.status === "ok") {
            if(data.excluded_from_delay){
                two_excluded_js_list[data.id].old_url = two_excluded_js_list[data.id].url;
                two_excluded_js_list[data.id].url = data.url;
                two_excluded_js_list[data.id].success = true;
                excluded_count--;
                if(excluded_count === 0) {
                    two_connect_script(0, two_excluded_js_list)
                }
            }else{
                two_script_list[data.id].old_url = two_script_list[data.id].url;
                two_script_list[data.id].url = data.url;
                two_script_list[data.id].success = true;
            }
        }
    } else if (data.type === "css" && data.status === "error") {
        console.log("error in fetching, connecting style now")
        two_connect_failed_style(data);
    } else if(data.type === "font"){
        two_connect_font(data);
    }
});

function UpdateQueryString(key, value, url) {
    if (!url) url = window.location.href;
    var re = new RegExp("([?&])" + key + "=.*?(&|#|$)(.*)", "gi"),
        hash;

    if (re.test(url)) {
        if (typeof value !== "undefined" && value !== null) {
            return url.replace(re, "$1" + key + "=" + value + "$2$3");
        }
        else {
            hash = url.split("#");
            url = hash[0].replace(re, "$1$3").replace(/(&|\?)$/, "");
            if (typeof hash[1] !== "undefined" && hash[1] !== null) {
                url += "#" + hash[1];
            }
            return url;
        }
    }
    else {
        if (typeof value !== "undefined" && value !== null) {
            var separator = url.indexOf("?") !== -1 ? "&" : "?";
            hash = url.split("#");
            url = hash[0] + separator + key + "=" + value;
            if (typeof hash[1] !== "undefined" && hash[1] !== null) {
                url += "#" + hash[1];
            }
            return url;
        }
        else {
            return url;
        }
    }
}

function two_connect_failed_style(data) {
    var link  = document.createElement("link");
    link.className = "fallback_two_worker";
    link.rel  = "stylesheet";
    link.type = "text/css";

    link.href = data.url;
    link.media = "none";
    link.onload =  function () { if(this.media==="none"){ if (data.media) {this.media=data.media;}else{this.media="all";}console.log(data.media);} if(data.connected_length == data.length && typeof two_replace_backgrounds != "undefined"){two_replace_backgrounds();};};
    document.getElementsByTagName("head")[0].appendChild(link);
    if(data.connected_length == data.length){
        two_replace_backgrounds();
    }
}

function two_connect_style(data, fixed_google_font=false) {

    if(fixed_google_font === false && typeof two_merge_google_fonts !== "undefined" && data['original_url'] && data['original_url'].startsWith('https://fonts.googleapis.com/css')){
        data['response'].text().then(function (content){
            content = two_merge_google_fonts(content)

            let blob = new Blob([content], { type: data['response'].type });
            data['url'] = URL.createObjectURL(blob);
            two_connect_style(data, true);
        });

        return;
    }

    var link  = document.createElement("link");
    link.className = "loaded_two_worker";
    link.rel  = "stylesheet";
    link.type = "text/css";
    link.href = data.url;
    link.media = data.media;
    link.onload = function () {if(data.connected_length == data.length && typeof two_replace_backgrounds != "undefined"){two_replace_backgrounds();};};
    document.getElementsByTagName("head")[0].appendChild(link);
}

function two_fonts_are_loaded(){
    if(typeof window["two_fonts_loaded"] === "undefined"){
        return true; // option is disabled
    }

    return window["two_fonts_loaded"];
}


var two_event ;

function two_connect_script(i, scripts_list=null) {

    if(i === 0){
        two_event = event;
    }

    if(scripts_list === null){
        scripts_list = two_script_list;
    }

    if(typeof scripts_list[i] !== "undefined"){
        let data_uid =    "[data-two_delay_id=\""+scripts_list[i].uid+"\"]";
        let current_script = document.querySelector(data_uid);

        let script = document.createElement("script");
        script.type = "text/javascript";
        script.async = false;
        if(scripts_list[i].inline){
            var js_code =atob(scripts_list[i].code);
            var blob = new Blob([js_code], {type : "text/javascript"});
            scripts_list[i].url = URL.createObjectURL(blob);
        }
        if(current_script != null && typeof scripts_list[i].url != "undefined"){
            script.dataset.src= scripts_list[i].url;
            current_script.parentNode.insertBefore(script, current_script);
            current_script.getAttributeNames().map(
                function (name){
                    let value = current_script.getAttribute(name);
                    script.setAttribute(name, value);
                }
            );
            current_script.remove();
            script.classList.add("loaded_two_worker_js");
        }
        i++;
        two_connect_script(i, scripts_list);

    } else {
        document.querySelectorAll(".loaded_two_worker_js").forEach((elem) => {
            if (elem.dataset.src){
                elem.setAttribute("src",elem.dataset.src);
            }
        });
    }
}

function two_connect_font(data){
    let font_face = data.font_face;

    if(font_face.indexOf("font-display")>=0){
        const regex = /font-display:[ ]*[a-z]*[A-Z]*;/g;
        while ((m = regex.exec(font_face)) !== null) {
            if (m.index === regex.lastIndex) {
                regex.lastIndex++;
            }

            m.forEach((match, groupIndex) => {
                console.log(match);
                font_face.replace(match, "font-display: swap;");
            });
        }
    }else{
        font_face = font_face.replace("}", ";font-display: swap;}");
    }
    if(typeof data.main_url != "undefined"){
        font_face = font_face.replace(data.main_url, data.url);
    }
    var newStyle = document.createElement("style");
    newStyle.className = "two_critical_font";
    newStyle.appendChild(document.createTextNode(font_face));
    document.head.appendChild(newStyle);
}
let connect_stile_timeout = setInterval(function (){
    console.log(window.two_page_loaded);
    if(window.two_page_loaded && two_fonts_are_loaded()){
        clearInterval(connect_stile_timeout);
        two_worker_styles_list.forEach(function(item, index){
            two_connect_style(item);
        });
        two_worker_styles_list = [];
    }
},500);
