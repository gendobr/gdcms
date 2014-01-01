
function getMacSimple() {
    if (!window.java) return false

    try {
        var location = window.location
        var address = (new java.net.Socket(location.host, location.port || 80)).getLocalAddress();

        var mac = java.net.NetworkInterface.getByInetAddress(address).getHardwareAddress()
        var s = ''
        // convert byte[] to nice string
        for(i=0;i<mac.length;i++) {
            var n = mac[i]
            if (n<0) n = 256+n
            s += n.toString(16)
        }
        // var ip = address.getHostAddress();
        return s;
    } catch(e) {
        return false;
    }
}

function getMacJava() {
    if (!navigator.javaEnabled) return;
    var applet = document.getElementById('info_applet');
    var ipmac=null;
    try {
        var ipmac = applet.getMac()
    } catch(e) {
        return null;
    }
    return ipmac;
}

function getJavaMac(){
    var mac;
    mac= getMacSimple();
    if (! mac) {
        mac= getMacJava();
    }
    return mac;
}

function getJavaFonts(){
    if (!navigator.javaEnabled) return;
    var applet = document.getElementById('info_applet');
    var fonts=null;
    try {
        var fonts = applet.getFonts()
    } catch(e) {
        return null;
    }
    return fonts;
}
