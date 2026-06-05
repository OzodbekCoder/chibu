import './bootstrap';

// Capacitor push notifications (only runs inside native app)
import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function registerPush() {
    if (!Capacitor?.isNativePlatform?.()) return;

    PushNotifications.requestPermissions().then((result) => {
        if (result.receive !== 'granted') return;
        PushNotifications.register();
    });

    PushNotifications.addListener('registration', (token) => {
        fetch('/app/device-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({ token: token.value }),
        }).catch(() => {});
    });

    PushNotifications.addListener('pushNotificationActionPerformed', () => {
        window.location.href = '/app/notifications';
    });
}

document.addEventListener('DOMContentLoaded', registerPush);
