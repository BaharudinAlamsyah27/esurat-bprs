package id.bprssragen.esurat_mobile

import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build
import io.flutter.embedding.android.FlutterActivity

class MainActivity : FlutterActivity() {
    override fun onCreate(savedInstanceState: android.os.Bundle?) {
        super.onCreate(savedInstanceState)

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                "disposisi_high_priority",
                getString(R.string.disposition_notification_channel),
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = getString(R.string.disposition_notification_description)
            }

            getSystemService(NotificationManager::class.java)
                .createNotificationChannel(channel)
        }
    }
}
