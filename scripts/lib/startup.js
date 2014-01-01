var cs_prev_menu;
var cs_href=false;
function change_state(cid)
{
    var lay=document.getElementById(cid);
    if (lay.style.display=="none")
    {
       if(cs_prev_menu) cs_prev_menu.style.display="none";
       lay.style.display="block";
       cs_prev_menu=lay;
       cs_href=true;
       //alert('cs_href='+cs_href);
    }
    else
    {
       lay.style.display="none";
       cs_prev_menu=null;
    }

}

function cs_hide_menu()
{
  var lg='';
  //lg=lg+"cs_hide_menu:\n";
  //if(cs_prev_menu) lg=lg+"cs_prev_menu - OK \n";
  //if(!cs_href) lg=lg+"!cs_href - OK \n";
  if(cs_prev_menu && !cs_href) cs_prev_menu.style.display="none";
  cs_href=false;
  //alert(lg);
}


function chast(cid)
{
    var lay=document.getElementById(cid);
    if (lay.style.display=="none")
    {
       lay.style.display="block";
    }
    else
    {
       lay.style.display="none";
    }
}

$(document).click(cs_hide_menu);



// =============================================================================

var win_count=Math.floor(10000*Math.random());

function popup(url)
{
  var width  = Math.round(screen.availWidth*0.8);
  var height = Math.round(screen.availHeight*0.8);
  var DemoWindow, left=0, top=0;
  if (document.all || document.layers)
  {
    left = Math.round((screen.availWidth-width)/2);
    top  = Math.round((screen.availHeight-height)/2);
  }

  DemoWindow=window.open(url,"DemoWindow"+win_count,"height="+height+",width="+width+",scrollbars=1,resizable=1,menubar=0,status=1,left="+left+",top="+top+",screenX="+left+",screenY="+top);
  if (DemoWindow)
  {
   DemoWindow.focus();
   DemoWindow.resizeTo(width,height);
   DemoWindow.moveTo(left,top);
  }
  return DemoWindow;
}



// =============================================================================







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
            console.log(currentObject.request);
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




// =============================================================================

function setCookie (name, value, expires, path, domain, secure) {
    document.cookie = name + "=" + escape(value) +
    ((expires) ? "; expires=" + expires : "") +
    ((path) ? "; path=" + path : "") +
    ((domain) ? "; domain=" + domain : "") +
    ((secure) ? "; secure" : "");
}

function getCookie(name) {
    var cookie = " " + document.cookie;
    var search = " " + name + "=";
    var setStr = null;
    var offset = 0;
    var end = 0;
    if (cookie.length > 0) {
        offset = cookie.indexOf(search);
        if (offset != -1) {
            offset += search.length;
            end = cookie.indexOf(";", offset)
            if (end == -1) {
                end = cookie.length;
            }
            setStr = unescape(cookie.substring(offset, end));
        }
    }
    return(setStr);
}


// =============================================================================



function popupDialogClosed(){
    $('#popupDialogContent').attr('src','about:blank');
    window.location.reload();
}

// відкриває діалог, після закриття якого сторінка перезавантажується
function popupDialogAndReload(url, title){

    var w=$(window).width();
    var h=$(window).height();

    $("#popupDialog").dialog({
        position: ["15%","top"],
        title:title,
        modal: true,
        show: 'slide',
        close:popupDialogClosed,
        width:Math.round(w*0.6)+'px'
    });
    //, height:'450px'
    $('#popupDialogContent').attr('src',url);



    $("#popupDialog").css('height', Math.round(h*0.9-2)+'px');
    $('div.ui-dialog').css('height', (h-20)+'px');
}
// відкриває діалог, після закриття якого нічого не відбувається
function popupDialog(url, title){
    var w=$(window).width();
    var h=$(window).height();
    $("#popupDialog").dialog({
        position: ["15%","top"],
        title:title,
        modal: true,
        show: 'slide',
        width:Math.round(w*0.6)+'px'
    //close:popupDialogClosed,
    });
    //, height:'450px'
    $('#popupDialogContent').attr('src',url);

    $("#popupDialog").css('height', Math.round(h*0.9-2)+'px');
    $('div.ui-dialog').css('height', (h-20)+'px');
}


$(document).ready(function(){
    $('body').append('<div id="popupDialog"><iframe id="popupDialogContent"></iframe></div>');
});
