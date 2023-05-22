let two_css_length = 0;
let two_connected_css_length = 0;
self.addEventListener("message", function(e) {
    two_css_length = e.data.css.length;
    if(e.data.font.length>0){
        two_fetch_inbg(e.data.font, "font");
    }
    if(e.data.js.length>0){
        two_fetch_inbg(e.data.js, "js");
    }
    if(e.data.excluded_js.length>0){
        two_fetch_inbg(e.data.excluded_js, "js" , true);
    }
    if(e.data.css.length>0){
        two_fetch_inbg(e.data.css, "css");
    }
}, false);

function two_fetch_inbg(data, type, excluded_js = false) {
    for(let i in data){
        if(typeof data[i].url != "undefined"){
            fetch(data[i].url, {mode:'no-cors',redirect: 'follow'}).then((r) => {

                if (!r.ok || r.status!==200) {
                    throw Error(r.statusText);
                }
                return (r.blob());
            }).then((content_) => {
                let sheetURL = URL.createObjectURL(content_);
                var modifiedScript = null;
                if(type == "css"){
                    modifiedScript = {
                        id: i,
                        type: type,
                        status: 'ok',
                        media: data[i].media,
                        url: sheetURL,
                        uid:data[i].uid,
                        response: content_,
                        original_url: data[i].url
                    };
                }else if(type == "js"){
                    modifiedScript = {
                        id: i,
                        status: 'ok',
                        type: type,
                        url: sheetURL,
                        uid:data[i].uid
                    };
                }else if(type == "font"){
                    modifiedScript = {
                        status: 'ok',
                        type: type,
                        main_url: data[i].url,
                        url:sheetURL,
                        font_face:data[i].font_face
                    };
                }
                if(excluded_js){
                    modifiedScript.excluded_from_delay = true;
                }
                two_send_worker_data(modifiedScript);
            }).catch(function(error) {
                console.log("error in fetching: "+error.toString()+", bypassing "+data[i].url);
                fetch(data[i].url, {redirect: 'follow'}).then((r) => {
                    if (!r.ok || r.status!==200) {
                        throw Error(r.statusText);
                    }
                    return (r.blob());
                }).then((content_) => {
                    let sheetURL = URL.createObjectURL(content_);
                    var modifiedScript = null;
                    if(type == "css"){
                        modifiedScript = {
                            id: i,
                            type: type,
                            status: 'ok',
                            media: data[i].media,
                            url: sheetURL,
                            uid:data[i].uid,
                            response: content_,
                            original_url: data[i].url
                        };
                    }else if(type == "js"){
                        modifiedScript = {
                            id: i,
                            status: 'ok',
                            type: type,
                            url: sheetURL,
                            uid:data[i].uid
                        };
                    }else if(type == "font"){
                        modifiedScript = {
                            status: 'ok',
                            type: type,
                            main_url: data[i].url,
                            url:sheetURL,
                            font_face:data[i].font_face
                        };
                    }
                    if(excluded_js){
                        modifiedScript.excluded_from_delay = true;
                    }
                    two_send_worker_data(modifiedScript);
                }).catch(function(error) {
                    console.log("error in fetching no-cors: "+error.toString()+", bypassing "+data[i].url);
                    try {
                        console.log("error in fetching: "+error.toString()+", sending XMLHttpRequest"+data[i].url);
                        let r = new XMLHttpRequest;
                        r.responseType = "blob";
                        if(r.status !== 200){
                            throw Error(r.statusText);
                        }
                        r.onload = function (content_) {
                            console.log("error in fetching: "+error.toString()+", XMLHttpRequest success "+data[i].url);
                            let modifiedScript = null;
                            if(type == "css"){
                                modifiedScript = {
                                    id: i,
                                    type: type,
                                    status: 'ok',
                                    media: data[i].media,
                                    url: URL.createObjectURL(content_.target.response),
                                    uid:data[i].uid
                                };
                            }else if(type == "js"){
                                modifiedScript = {
                                    id: i,
                                    type: type,
                                    status: 'ok',
                                    url: URL.createObjectURL(content_.target.response),
                                    uid:data[i].uid
                                };
                            }else if(type == "font"){
                                modifiedScript = {
                                    type: type,
                                    status: 'ok',
                                    main_url: data[i].url,
                                    url:URL.createObjectURL(content_.target.response),
                                    font_face:data[i].font_face
                                };
                            }
                            if(excluded_js){
                                modifiedScript.excluded_from_delay = true;
                            }
                            two_send_worker_data(modifiedScript);
                        };
                        r.onerror = function () {
                            console.log("error in fetching: "+error.toString()+", XMLHttpRequest failed "+data[i].url);
                            var modifiedScript = null;
                            if(type == "css" || type == "js"){
                                modifiedScript = {
                                    id: i,
                                    type: type,
                                    status: 'error',
                                    url: data[i].url,
                                    uid:data[i].uid
                                };
                            }else if(type == "font"){
                                modifiedScript = {
                                    type: type,
                                    status: 'error',
                                    url: data[i].url,
                                    font_face:data[i].font_face
                                };
                            }
                            if(excluded_js){
                                modifiedScript.excluded_from_delay = true;
                            }
                            two_send_worker_data(modifiedScript);
                        };
                        r.open("GET", data[i].url, true);
                        r.send();
                    } catch (e) {
                        console.log("error in fetching: "+e.toString()+", running fallback for "+data[i].url);
                        var modifiedScript = null;
                        if(type == "css" || type == "js"){
                            modifiedScript = {
                                id: i,
                                type: type,
                                status: 'error',
                                url: data[i].url,
                                uid:data[i].uid
                            };
                        }else if(type == "font"){
                            modifiedScript = {
                                type: type,
                                status: 'error',
                                url: data[i].url,
                                font_face:data[i].font_face
                            };
                        }
                        if(excluded_js){
                            modifiedScript.excluded_from_delay = true;
                        }
                        two_send_worker_data(modifiedScript);
                    }
                });
            });
        }
    }
}


function two_send_worker_data(data){
    if(data.type == "css"){
        two_connected_css_length++;
        data.length = two_css_length;
        data.connected_length = two_connected_css_length;
    }
    self.postMessage(data)
}


