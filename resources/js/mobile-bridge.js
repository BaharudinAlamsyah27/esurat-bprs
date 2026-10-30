/**
 * E-Surat BPRS Sragen - Mobile Bridge
 * Menghubungkan WebView Filament dengan plugin Capacitor di HP Android.
 */
document.addEventListener('DOMContentLoaded', async () => {
    // Hanya berjalan bila dibuka dari dalam APK Capacitor
    if (!window.Capacitor) {
        return;
    }

    const { PushNotifications } = window.Capacitor.Plugins || {};
    const { App } = window.Capacitor.Plugins || {};

    // 1. Tangani Tombol Kembali Hardware Android
    if (App) {
        let lastBackPress = 0;
        App.addListener('backButton', ({ canGoBack }) => {
            const currentPath = window.location.pathname;
            const isRootPage = currentPath === '/' || currentPath === '/admin' || currentPath.endsWith('/dashboard');

            if (canGoBack && !isRootPage) {
                window.history.back();
            } else {
                const now = Date.now();
                if (now - lastBackPress < 2000) {
                    App.exitApp();
                } else {
                    lastBackPress = now;
                    // Tampilkan toast atau hint sederhana
                    console.log('Tekan sekali lagi untuk keluar');
                }
            }
        });
    }

    // 2. Daftarkan Notifikasi FCM
    if (PushNotifications) {
        try {
            let permStatus = await PushNotifications.checkPermissions();

            if (permStatus.receive === 'prompt') {
                permStatus = await PushNotifications.requestPermissions();
            }

            if (permStatus.receive === 'granted') {
                await PushNotifications.register();
            }

            // Menerima Token FCM dari Google
            PushNotifications.addListener('registration', async (token) => {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) return;

                try {
                    await fetch('/device-tokens', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            token: token.value,
                            platform: 'android',
                            app_version: '1.0.0',
                            device_name: navigator.userAgent.split('(')[1]?.split(')')[0] || 'Android Device',
                        }),
                    });
                } catch (e) {
                    console.warn('Gagal mendaftarkan token HP:', e);
                }
            });

            // Menangani bila notifikasi ditekan pegawai (Deep Linking)
            PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
                const path = notification.notification.data?.path;
                // Validasi path aman (hanya boleh route internal disposisi-saya)
                if (path && typeof path === 'string' && path.startsWith('/disposisi-saya')) {
                    window.location.href = path;
                }
            });
        } catch (err) {
            console.warn('Inisialisasi push notification error:', err);
        }
    }
});
