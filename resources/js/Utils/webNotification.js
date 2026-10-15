/**
 * Web Audio API synthesizer for notification sound (two-tone chime).
 */
export function playNotificationChime() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;

        const ctx = new AudioCtx();
        const now = ctx.currentTime;

        // Tone 1: D5 (587.33Hz)
        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(587.33, now);
        gain1.gain.setValueAtTime(0.15, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(now);
        osc1.stop(now + 0.15);

        // Tone 2: A5 (880Hz)
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(880, now + 0.08);
        gain2.gain.setValueAtTime(0.2, now + 0.08);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(now + 0.08);
        osc2.stop(now + 0.35);
    } catch (e) {
        // Ignore audio context errors if blocked by browser autoplay policy
    }
}

/**
 * Check if browser supports Web Notifications.
 */
export function isNotificationSupported() {
    return typeof window !== 'undefined' && 'Notification' in window;
}

/**
 * Get current notification permission state ('default', 'granted', 'denied').
 */
export function getNotificationPermission() {
    if (!isNotificationSupported()) return 'denied';
    return Notification.permission;
}

/**
 * Request notification permission from user.
 */
export async function requestNotificationPermission() {
    if (!isNotificationSupported()) return 'denied';
    try {
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            playNotificationChime();
        }
        return permission;
    } catch (e) {
        return 'denied';
    }
}

/**
 * Trigger a browser notification and chime.
 */
export function sendBrowserNotification(title, options = {}) {
    if (!isNotificationSupported() || Notification.permission !== 'granted') {
        return null;
    }

    try {
        playNotificationChime();

        const notif = new Notification(title, {
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            ...options,
        });

        if (options.onClick) {
            notif.onclick = (e) => {
                window.focus();
                options.onClick(e);
                notif.close();
            };
        }

        return notif;
    } catch (e) {
        return null;
    }
}
