package services.mea_it.iotairclean;

import android.content.Intent;
import android.net.Uri;
import android.net.wifi.WifiManager;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.text.format.Formatter;
import android.view.View;
import android.widget.Button;
import android.widget.ProgressBar;
import android.widget.TextView;

public class MainActivity extends AppCompatActivity {

    private String iotAirCleanUrl = "";
    private String currentIp = "";
    private String networkIp = "";
    private int clientIp = 0;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
    }

    /**
     * searches the local IoT AirClean Server in currently connected network
     * @param view
     */
    public void searchIoTAirClean(View view){

        Runnable runnable = new Runnable() {
            public void run() {

                // get current used ip, so we can do a search in a Class C Network, Standard for most home networks
                WifiManager wm = (WifiManager) getSystemService(WIFI_SERVICE);
                currentIp = Formatter.formatIpAddress(wm.getConnectionInfo().getIpAddress());

                runOnUiThread(new Runnable() {
                    public void run() {
                        // tell user that we are searching IoT AirClean
                        ((TextView) findViewById(R.id.debugOutput)).setText("Suche gestartet");
                        // now deactivate Button for another Click
                        ((Button) findViewById(R.id.btnSearch)).setEnabled(false);
                        // display own ip
                        ((TextView) findViewById(R.id.tvOwnIp)).setText("Eigene IP: " + currentIp);
                    }});

                try {
                    // parse ip, to create network address
                    String[] partsIp = currentIp.split("\\.");
                    networkIp = partsIp[0] + "." + partsIp[1] + "." + partsIp[2] + ".";
                } catch (Exception ex) {
                    return;
                }

                // now test which ip is used for IoT AirClean
                for (clientIp = 255; clientIp > 0; clientIp--) {
                    try {
                        // progress output for user
                        runOnUiThread(new Runnable() {
                            public void run() {
                                ((TextView) findViewById(R.id.debugOutput)).setText("Prüfe: " + "http://" + networkIp + clientIp);
                                ((ProgressBar) findViewById(R.id.progressSearch)).setProgress((255 - clientIp));
                                ((ProgressBar) findViewById(R.id.progressSearch)).invalidate();
                            }});
                        System.out.println("Prüfe: " + "http://" + networkIp + clientIp);

                        //Some url endpoint that you may have
                        String myUrl = "http://" + networkIp + clientIp + "/ping.txt";
                        //Instantiate new instance of our class
                        HttpGetRequest getRequest = new HttpGetRequest();
                        //Perform the doInBackground method, passing in our url
                        String result = getRequest.execute(myUrl).get();


                        // check if we get needed result, so we found IoT AirClean Server in Network
                        if (result.equals("IoT AirClean")) {
                            iotAirCleanUrl = "http://" + networkIp + clientIp;
                            clientIp = 0;
                            runOnUiThread(new Runnable() {
                                public void run() {
                                    ((TextView) findViewById(R.id.debugOutput)).setText("Bitte verwenden Sie folgende Adresse in Ihrem Browser: " + iotAirCleanUrl);
                                    ((Button) findViewById(R.id.btnGoTo)).setEnabled(true);
                                    ((ProgressBar) findViewById(R.id.progressSearch)).setProgress((255 - clientIp));
                                }});
                        }

                    } catch (Exception ex) {

                    }
                }


            }
        };

        new Thread(runnable).start();
    }

    /**
     * opens Browser with founded URL for current IoT AirClean Server in currently connected network
     * @param view
     */
    public void openIoTAirClean(View view){
        Uri uri = Uri.parse(iotAirCleanUrl); // missing 'http://' will cause crashed
        Intent intent = new Intent(Intent.ACTION_VIEW, uri);
        startActivity(intent);
    }
}
