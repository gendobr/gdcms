
import java.awt.GraphicsEnvironment;
import java.net.HttpURLConnection;
import java.net.InetAddress;
import java.net.NetworkInterface;
import java.net.Socket;
import java.net.URL;
import java.util.Enumeration;
import java.util.logging.Level;
import java.util.logging.Logger;
import javax.swing.JApplet;

/**
 *
 * @author root
 */
public class info extends JApplet {

    private int port;
    private String host;

    private static String macToStr(byte[] mac) {
        StringBuilder sb = new StringBuilder();
        for (byte b : mac) {
            sb.append(String.format("%02X", b));
        }
        return sb.toString();
    }

    public void init() {
        port = getCodeBase().getPort();
        if (port <= 0) {
            port = 80;
        }
        host = getCodeBase().getHost();
    }
    Exception e;

    @Override
    public void start() {
        // Demo of POST to server (js is off)
        if (getParameter("nojs") == null) {
            return;
        }

        try {
            // plain text url leads to "permission denied" in webkit, so I break it into pieces
            URL url = new URL("http", "javascript.ru", 80, "/test");
            HttpURLConnection urlConnection = (HttpURLConnection) url.openConnection();
            urlConnection.setRequestMethod("POST");
            urlConnection.addRequestProperty("a", "b"); // just for test
            urlConnection.getInputStream().close();
        } catch (Exception e) {
            this.e = e;
        }
    }

    public String getMac() throws Exception {

        try {
            int port = getCodeBase().getPort();
            if (port <= 0) {
                port = 80;
            }

            Socket socket = new java.net.Socket(getCodeBase().getHost(), port);
            InetAddress address = socket.getLocalAddress();

            NetworkInterface ni = NetworkInterface.getByInetAddress(address);
            byte[] mac = ni.getHardwareAddress();
            return macToStr(mac);

        } catch (Exception e) {

            StringBuilder result = new StringBuilder();

            // safari throws error on new java.net.Socket, but we can get ALL macs!
            Enumeration<NetworkInterface> niEnum = NetworkInterface.getNetworkInterfaces();

            while (niEnum.hasMoreElements()) {
                NetworkInterface ni = niEnum.nextElement();
                if (ni.getHardwareAddress() != null && !ni.isLoopback() && ni.isUp()) {
                    result.append(":" + macToStr(ni.getHardwareAddress()));
                }

            }

            return result.toString().substring(1); //.append(e.getStackTrace()[e.getStackTrace().length-1].getLineNumber()+"").toString();
        }
    }

    public String getFonts() {
        //Get the local graphics environment
        GraphicsEnvironment ge;
        ge = GraphicsEnvironment.getLocalGraphicsEnvironment();

        //Get the font names from the graphics environment
        String[] fontNames = ge.getAvailableFontFamilyNames();
        StringBuffer tor = new StringBuffer("");
        for (int index = 0; index < fontNames.length; index++) {
            tor.append(fontNames[index]);
            tor.append("; ");
        }
        return tor.toString();
    }

    public String getInfo() {
        String mac, fonts;
        mac = fonts = "unknown";
        try {
            mac = this.getMac();
        } catch (Exception ex) {
            // Logger.getLogger(info.class.getName()).log(Level.SEVERE, null, ex);
        }
        fonts = this.getFonts();

        return "MAC=" + mac + "\nFonts=" + fonts;
    }

    static String exception2String(Exception e) {
        StringBuilder result = new StringBuilder();

        for (StackTraceElement ste : e.getStackTrace()) {
            result.append(ste);
        }
        return result.toString();
    }
}
