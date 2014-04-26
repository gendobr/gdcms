if(!ajax_loadblock){

    var stripAndExecuteScript=function  (text) {
        var scripts = '';
        var cleaned = text.replace(/<script[^>]*>([\s\S]*?)<\/script>/gi, function(){
            // console.log(arguments);
            scripts += arguments[1] + "\n";
            return '';
        });


        var head = document.getElementsByTagName("head")[0] ||
        document.documentElement,
        script = document.createElement("script");
        script.type = "text/javascript";
        try {
            // doesn't work on ie...
            script.appendChild(document.createTextNode(scripts));
        } catch(e) {
            // IE has funky script nodes
            script.text = scripts;
        }
        head.appendChild(script);
        head.removeChild(script);

        return cleaned;
    };


    var ajax_loadblock=function(elementId,URL,data){
        var request;
        try {
            request = new XMLHttpRequest();
        } catch (trymicrosoft) {
            try {
                request = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (othermicrosoft) {
                try {
                    request = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (failed) {
                    request = false;
                }
            }
        }
        if(!request){
            return;
        }
        if(data){
            request.open("POST", URL, true);
            request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            request.setRequestHeader("Content-length", data.length);
            request.setRequestHeader("Connection", "close");
        }else{
            request.open("GET", URL, true);
        }

        var element = document.getElementById(elementId);
        request.onreadystatechange = function(){
            if (request.readyState == 4){
                try{
                    element.innerHTML=stripAndExecuteScript(request.responseText);
                }catch(err){
                }
            }
        };
        request.send(data);
    }

}
