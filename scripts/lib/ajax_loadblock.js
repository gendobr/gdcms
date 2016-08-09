if(!ajax_loadblock){


    var stripAndExecuteScript=function  (text) {

        var scripts = [];
        var cleaned = text.replace(/<script([^>]*)>([\s\S]*?)<\/script>/gi, function(){
            // console.log(arguments);
            scripts.push({url:arguments[1], src:arguments[2]});
            return '';
        });

        if( scripts.length==0){
	    return {html:cleaned,script:null};
        }

        var head = document.getElementsByTagName("head")[0] || document.documentElement;
	var script;
	var regexp = /src="([^"]*)"|src='([^']*)'|src=([^ >]*)[ >]/i;

	var next=null;
	var url=null;
	for(var i = scripts.length - 1; i>=0; i--){
	    if(scripts[i].src){
		var runnableScript = {
		    code:scripts[i].src,
		    next:next,
		    run:function(){
			var script = document.createElement("script");
			script.type = "text/javascript";
		        try {
			    // doesn't work on ie...
			    script.appendChild(document.createTextNode(this.code));
			} catch(e) {
			    // IE has funky script nodes
			    script.text = this.code;
			}
			head.appendChild(script);
			if(this.next){
			    this.next.run();
			}
		    }
		};
		next = runnableScript;
	    }else if ( url = regexp.exec(scripts[i].url) ){
		var runnableScript = {
		    url:url[1],
		    next:next,
		    run:function(){
			var script = document.createElement("script");
			script.type = "text/javascript";
			script.src = this.url;
			var self=this;
			script.onload=function(){
			    if(self.next){
				self.next.run();
			    }
			};
			head.appendChild(script);
		    }
		};
		next = runnableScript;
	    }
	}
        return {html:cleaned,script:next};
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
		    var filtered=stripAndExecuteScript(request.responseText);
                    element.innerHTML=filtered.html;
		    if(filtered.script){
			filtered.script.run();
		    }
                }catch(err){
                }
            }
        };
        request.send(data);
    }

}
