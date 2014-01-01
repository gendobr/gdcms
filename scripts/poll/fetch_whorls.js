function identify_plugins(){
    // fetch and serialize plugins
    var plugins = "";
    // in Mozilla and in fact most non-IE browsers, this is easy
    if (navigator.plugins) {
        var np = navigator.plugins;
        var plist = new Array();
        // sorting navigator.plugins is a right royal pain
        // but it seems to be necessary because their order
        // is non-constant in some browsers
        for (var i = 0; i < np.length; i++) {
            plist[i] = np[i].name + "; ";
            plist[i] += np[i].description + "; ";
            plist[i] += np[i].filename + ";";
            for (var n = 0; n < np[i].length; n++) {
                plist[i] += " (" + np[i][n].description +"; "+ np[i][n].type +
                "; "+ np[i][n].suffixes + ")";
            }
            plist[i] += ". ";
        }
        plist.sort();
        for (i = 0; i < np.length; i++){
            plugins+= "Plugin "+i+": " + plist[i];
        }
    }
    // in IE, things are much harder; we use PluginDetect to get less
    // information (only the plugins listed below & their version numbers)
    if (plugins == "") {
        var pp = new Array();
        pp[0] = "Java";
        pp[1] = "QuickTime";
        pp[2] = "DevalVR";
        pp[3] = "Shockwave";
        pp[4] = "Flash";
        pp[5] = "WindowsMediaplayer";
        pp[6] = "Silverlight";
        pp[7] = "VLC";
        var version;
        for ( p in pp ) {
            version = PluginDetect.getVersion(pp[p]);
            if (version)
                plugins += pp[p] + " " + version + "; "
        }
        plugins += ieAcrobatVersion();
    }
    return plugins;
}

// ==================================================================


function ieAcrobatVersion() {
    // estimate the version of Acrobat on IE using horrible horrible hacks
    if (window.ActiveXObject) {
        for (var x = 2; x < 10; x++) {
            try {
                oAcro=eval("new ActiveXObject('PDF.PdfCtrl."+x+"');");
                if (oAcro)
                    return "Adobe Acrobat version" + x + ".?";
            } catch(ex) {}
        }
        try {
            oAcro4=new ActiveXObject('PDF.PdfCtrl.1');
            if (oAcro4)
                return "Adobe Acrobat version 4.?";
        } catch(ex) {}
        try {
            oAcro7=new ActiveXObject('AcroPDF.PDF.1');
            if (oAcro7)
                return "Adobe Acrobat version 7.?";
        } catch (ex) {}
        return "";
    }
    return "";
}
// ==================================================================


function get_fonts() {
    // Try flash first
    var fonts = "";
    var obj = document.getElementById("flashfontshelper");
    if (obj && typeof(obj.GetVariable) != "undefined") {
        fonts = obj.GetVariable("/:user_fonts");
        fonts = fonts.replace(/,/g,", ");
        fonts += " (via Flash)";
    } else {
        // Try java fonts
        try {
            fonts =getJavaFonts()+" (via Java)";
        } catch (ex) {}
    }
    if ("" == fonts){
        fonts = "No Flash or Java fonts detected";
    }
    return fonts;
}

// ==================================================================

function set_dom_storage(){
    try {
        localStorage.panopticlick = "yea";
        sessionStorage.panopticlick = "yea";
    } catch (ex) { }
}


// ==================================================================

function test_dom_storage(){
    var supported = "";
    try {
        if (localStorage.panopticlick == "yea") {
            supported += "DOM localStorage: Yes";
        } else {
            supported += "DOM localStorage: No";
        }
    } catch (ex) {
        supported += "DOM localStorage: No";
    }

    try {
        if (sessionStorage.panopticlick == "yea") {
            supported += ", DOM sessionStorage: Yes";
        } else {
            supported += ", DOM sessionStorage: No";
        }
    } catch (ex) {
        supported += ", DOM sessionStorage: No";
    }

    return supported;
}


// ==================================================================

function test_ie_userdata(){
    try {
        oPersistDiv.setAttribute("remember", "remember this value");
        oPersistDiv.save("oXMLStore");
        oPersistDiv.setAttribute("remember", "overwritten!");
        oPersistDiv.load("oXMLStore");
        if ("remember this value" == (oPersistDiv.getAttribute("remember"))) {
            return ", IE userData: Yes";
        } else {
            return ", IE userData: No";
        }
    } catch (ex) {
        return ", IE userData: No";
    }
}
// ==================================================================



// ==================================================================

/*
 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
 * Digest Algorithm, as defined in RFC 1321.
 * Copyright (C) Paul Johnston 1999 - 2000.
 * Updated by Greg Holt 2000 - 2001.
 * See http://pajhome.org.uk/site/legal.html for details.
 */

/*
 * Convert a 32-bit number to a hex string with ls-byte first
 */
var hex_chr = "0123456789abcdef";
function rhex(num)
{
    str = "";
    for(j = 0; j <= 3; j++)
        str += hex_chr.charAt((num >> (j * 8 + 4)) & 0x0F) +
        hex_chr.charAt((num >> (j * 8)) & 0x0F);
    return str;
}

/*
 * Convert a string to a sequence of 16-word blocks, stored as an array.
 * Append padding bits and the length, as described in the MD5 standard.
 */
function str2blks_MD5(str)
{
    nblk = ((str.length + 8) >> 6) + 1;
    blks = new Array(nblk * 16);
    for(i = 0; i < nblk * 16; i++) blks[i] = 0;
    for(i = 0; i < str.length; i++)
        blks[i >> 2] |= str.charCodeAt(i) << ((i % 4) * 8);
    blks[i >> 2] |= 0x80 << ((i % 4) * 8);
    blks[nblk * 16 - 2] = str.length * 8;
    return blks;
}



/*
 * Add integers, wrapping at 2^32. This uses 16-bit operations internally
 * to work around bugs in some JS interpreters.
 */
function add(x, y)
{
    var lsw = (x & 0xFFFF) + (y & 0xFFFF);
    var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
    return (msw << 16) | (lsw & 0xFFFF);
}



/*
 * Bitwise rotate a 32-bit number to the left
 */
function rol(num, cnt)
{
    return (num << cnt) | (num >>> (32 - cnt));
}

/*
 * These functions implement the basic operation for each round of the
 * algorithm.
 */
function cmn(q, a, b, x, s, t)
{
    return add(rol(add(add(a, q), add(x, t)), s), b);
}
function ff(a, b, c, d, x, s, t)
{
    return cmn((b & c) | ((~b) & d), a, b, x, s, t);
}
function gg(a, b, c, d, x, s, t)
{
    return cmn((b & d) | (c & (~d)), a, b, x, s, t);
}
function hh(a, b, c, d, x, s, t)
{
    return cmn(b ^ c ^ d, a, b, x, s, t);
}
function ii(a, b, c, d, x, s, t)
{
    return cmn(c ^ (b | (~d)), a, b, x, s, t);
}

/*
 * Take a string and return the hex representation of its MD5.
 */
function calcMD5(str)
{
    x = str2blks_MD5(str);
    a =  1732584193;
    b = -271733879;
    c = -1732584194;
    d =  271733878;

    for(i = 0; i < x.length; i += 16)
    {
        olda = a;
        oldb = b;
        oldc = c;
        oldd = d;

        a = ff(a, b, c, d, x[i+ 0], 7 , -680876936);
        d = ff(d, a, b, c, x[i+ 1], 12, -389564586);
        c = ff(c, d, a, b, x[i+ 2], 17,  606105819);
        b = ff(b, c, d, a, x[i+ 3], 22, -1044525330);
        a = ff(a, b, c, d, x[i+ 4], 7 , -176418897);
        d = ff(d, a, b, c, x[i+ 5], 12,  1200080426);
        c = ff(c, d, a, b, x[i+ 6], 17, -1473231341);
        b = ff(b, c, d, a, x[i+ 7], 22, -45705983);
        a = ff(a, b, c, d, x[i+ 8], 7 ,  1770035416);
        d = ff(d, a, b, c, x[i+ 9], 12, -1958414417);
        c = ff(c, d, a, b, x[i+10], 17, -42063);
        b = ff(b, c, d, a, x[i+11], 22, -1990404162);
        a = ff(a, b, c, d, x[i+12], 7 ,  1804603682);
        d = ff(d, a, b, c, x[i+13], 12, -40341101);
        c = ff(c, d, a, b, x[i+14], 17, -1502002290);
        b = ff(b, c, d, a, x[i+15], 22,  1236535329);

        a = gg(a, b, c, d, x[i+ 1], 5 , -165796510);
        d = gg(d, a, b, c, x[i+ 6], 9 , -1069501632);
        c = gg(c, d, a, b, x[i+11], 14,  643717713);
        b = gg(b, c, d, a, x[i+ 0], 20, -373897302);
        a = gg(a, b, c, d, x[i+ 5], 5 , -701558691);
        d = gg(d, a, b, c, x[i+10], 9 ,  38016083);
        c = gg(c, d, a, b, x[i+15], 14, -660478335);
        b = gg(b, c, d, a, x[i+ 4], 20, -405537848);
        a = gg(a, b, c, d, x[i+ 9], 5 ,  568446438);
        d = gg(d, a, b, c, x[i+14], 9 , -1019803690);
        c = gg(c, d, a, b, x[i+ 3], 14, -187363961);
        b = gg(b, c, d, a, x[i+ 8], 20,  1163531501);
        a = gg(a, b, c, d, x[i+13], 5 , -1444681467);
        d = gg(d, a, b, c, x[i+ 2], 9 , -51403784);
        c = gg(c, d, a, b, x[i+ 7], 14,  1735328473);
        b = gg(b, c, d, a, x[i+12], 20, -1926607734);

        a = hh(a, b, c, d, x[i+ 5], 4 , -378558);
        d = hh(d, a, b, c, x[i+ 8], 11, -2022574463);
        c = hh(c, d, a, b, x[i+11], 16,  1839030562);
        b = hh(b, c, d, a, x[i+14], 23, -35309556);
        a = hh(a, b, c, d, x[i+ 1], 4 , -1530992060);
        d = hh(d, a, b, c, x[i+ 4], 11,  1272893353);
        c = hh(c, d, a, b, x[i+ 7], 16, -155497632);
        b = hh(b, c, d, a, x[i+10], 23, -1094730640);
        a = hh(a, b, c, d, x[i+13], 4 ,  681279174);
        d = hh(d, a, b, c, x[i+ 0], 11, -358537222);
        c = hh(c, d, a, b, x[i+ 3], 16, -722521979);
        b = hh(b, c, d, a, x[i+ 6], 23,  76029189);
        a = hh(a, b, c, d, x[i+ 9], 4 , -640364487);
        d = hh(d, a, b, c, x[i+12], 11, -421815835);
        c = hh(c, d, a, b, x[i+15], 16,  530742520);
        b = hh(b, c, d, a, x[i+ 2], 23, -995338651);

        a = ii(a, b, c, d, x[i+ 0], 6 , -198630844);
        d = ii(d, a, b, c, x[i+ 7], 10,  1126891415);
        c = ii(c, d, a, b, x[i+14], 15, -1416354905);
        b = ii(b, c, d, a, x[i+ 5], 21, -57434055);
        a = ii(a, b, c, d, x[i+12], 6 ,  1700485571);
        d = ii(d, a, b, c, x[i+ 3], 10, -1894986606);
        c = ii(c, d, a, b, x[i+10], 15, -1051523);
        b = ii(b, c, d, a, x[i+ 1], 21, -2054922799);
        a = ii(a, b, c, d, x[i+ 8], 6 ,  1873313359);
        d = ii(d, a, b, c, x[i+15], 10, -30611744);
        c = ii(c, d, a, b, x[i+ 6], 15, -1560198380);
        b = ii(b, c, d, a, x[i+13], 21,  1309151649);
        a = ii(a, b, c, d, x[i+ 4], 6 , -145523070);
        d = ii(d, a, b, c, x[i+11], 10, -1120210379);
        c = ii(c, d, a, b, x[i+ 2], 15,  718787259);
        b = ii(b, c, d, a, x[i+ 9], 21, -343485551);

        a = add(a, olda);
        b = add(b, oldb);
        c = add(c, oldc);
        d = add(d, oldd);
    }
    return rhex(a) + rhex(b) + rhex(c) + rhex(d);
}






























////// ==================================================================
////function getXMLHttpRequest(){
////    var request;
////    try {
////        request = new XMLHttpRequest();
////    } catch (trymicrosoft) {
////        try {
////            request = new ActiveXObject("Msxml2.XMLHTTP");
////        } catch (othermicrosoft) {
////            try {
////                request = new ActiveXObject("Microsoft.XMLHTTP");
////            } catch (failed) {
////                request = false;
////            }
////        }
////    }
////    if (!request) alert("Error initializing XMLHttpRequest!");
////    return request;
////}
////
////// ==================================================================
////function ajax_load(url, data,onreadystatechange)
////{
////    var request=getXMLHttpRequest();
////    if(!request) return false;
////    request.open("POST", url, true);
////    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
////    request.setRequestHeader("Content-length", data.length);
////    request.setRequestHeader("Connection", "close");
////    request.onreadystatechange = onreadystatechange;
////    request.send(data);
////    return request;
////}
////// ==================================================================


function fetch_client_whorls(){
    // fetch client-side vars
    var whorls = new Object();

    // this is a backup plan
    // setTimeout("retry_post()",1100);

    try {
        whorls['plugins'] = identify_plugins();
    } catch(ex) {
        whorls['plugins'] = "permission denied";
    }

    whorls['fonts'] = get_fonts();

    try {
        whorls['timezone'] = new Date().getTimezoneOffset();
    } catch(ex) {
        whorls['timezone'] = "permission denied";
    }

    try {
        whorls['video'] = screen.width+"x"+screen.height+"x"+screen.colorDepth;
    } catch(ex) {
        whorls['video'] = "permission denied";
    }

    whorls['supercookies'] = test_dom_storage() + test_ie_userdata();

    whorls['mac'] = getJavaMac();

    // send to server for logging / calculating
    // and fetch results
    var panopticlick = "";
    for (prop in whorls){
        panopticlick += prop +"=" + whorls[prop] + ";\n";
    }
    var HTMLstring = "";
    HTMLstring = "navigator.appCodeName="+navigator.appCodeName+"\n";
    HTMLstring+= "navigator.appName=" + navigator.appName+"\n";
    HTMLstring+= "navigator.appVersion="+ navigator.appVersion + navigator.cookieEnabled +"\n";
    HTMLstring+= "navigator.language"+ navigator.language + "\n";
    HTMLstring+= "navigator.onLine="+ navigator.onLine + "\n";
    HTMLstring+= "navigator.platform="+ navigator.platform  +"\n";
    HTMLstring+= "navigator.userAgent="+ navigator.userAgent  +"\n";
    HTMLstring+= "navigator.userLanguage="+ navigator.userLanguage  +"\n";
    HTMLstring+= "navigator.appMinorVersion="+ navigator.appMinorVersion  +"\n";
    HTMLstring+= "navigator.browserLanguage="+ navigator.browserLanguage  +"\n";
    HTMLstring+= "navigator.javaEnabled()="+ navigator.javaEnabled()  +"\n";
    HTMLstring+= "navigator.systemLanguage="+ navigator.systemLanguage  +"\n";
    HTMLstring+= panopticlick;

    //if(debug) alert(HTMLstring);
    //----------------------

    data = escape(calcMD5(HTMLstring));



// send data to server
//    request=ajax_load("index.php?action=statistica/md5",
//        data,
//        function (){
//            if (request.readyState == 4) {
//                var response = request.responseText;
//                document.getElementById('w').innerHTML=response;
//            }
//        });
    return data;
}

// ==================================================================

$("#flashcontent").flash(
{
    "src": "scripts/poll/fonts2.swf",
    "width": "1",
    "height": "1",
    "swliveconnect": "true",
    "id": "flashfontshelper",
    "name": "flashfontshelper"
},
{
    update: false
}
);



var success = 0;
var retries = 10;
var result  = "";
var interval;
var debug=false;

set_dom_storage();

$(document).ready(function(){
    // wait some time for the flash font detection:
    interval=setInterval(
    function(){
        if( (retries--)>0) result=fetch_client_whorls();
        else clearInterval(interval);
    }
    ,500);
    
});

// ==================================================================
