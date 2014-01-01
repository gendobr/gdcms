function getXMLHttpRequest()
{
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
    if (!request) alert("Error initializing XMLHttpRequest!");
    return request;
}

function ajax_load(url, data,onreadystatechange)
{
    var request=getXMLHttpRequest();
    if(!request) return false;
    if(data){
        request.open("POST", url, true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.setRequestHeader("Content-length", data.length);
        request.setRequestHeader("Connection", "close");
    }else{
        request.open("GET", url, true);
    }
    request.onreadystatechange = onreadystatechange;
    request.send(data);
    return request;
}


function ajax(url, data,onLoad){
    var currentObject=this;
    
    this.onLoadFunction=onLoad;
    this.request=getXMLHttpRequest();
    if(!this.request) return false;
    if(data){
        this.request.open("POST", url, true);
        this.request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        this.request.setRequestHeader("Content-length", data.length);
        this.request.setRequestHeader("Connection", "close");
    }else{
        this.request.open("GET", url, true);
    }
    this.request.onreadystatechange = function(){
        if (currentObject.request.readyState == 4){
            //console.log(currentObject.request);
            try{
                currentObject.onLoadFunction(currentObject.request.responseText);
            }catch(err){
                
            }
        }
    };
    this.request.send(data);
}
/*
sample call is
    request=ajax_load(url,
              data,
              function (){
                   if (request.readyState == 4) {
                         var response = request.responseText;
                         var block=document.getElementById("ec_user_saved_info");
                         
                         block.style.display='block';
                         if(response.match(/<!-- No_saved_info -->/))
                         {
                            block.innerHTML = response;
                         }
                         else
                         {
                            block.innerHTML = draw_response(response);
                            document.getElementById("ec_user_login_form").style.display='none';
                            document.getElementById("ec_user_logout_form").style.display='block';
                         }
                   }
              });
or
  request=new ajax(url,data, function (responseText){})
*/
