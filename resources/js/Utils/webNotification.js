/**
 * Check if sound notifications are enabled.
 * Default is TRUE for all users and roles if not explicitly disabled.
 */
export function isSoundEnabled() {
    if (typeof window === 'undefined') return true;
    try {
        const stored = localStorage.getItem('itdesk_sound_enabled');
        return stored === null ? true : stored === 'true';
    } catch {
        return true;
    }
}

/**
 * Enable or disable sound notifications.
 */
export function setSoundEnabled(enabled) {
    if (typeof window === 'undefined') return;
    try {
        localStorage.setItem('itdesk_sound_enabled', enabled ? 'true' : 'false');
        window.dispatchEvent(new CustomEvent('itdesk:sound-toggled', { detail: { enabled: !!enabled } }));
    } catch {}
}

/**
 * Toggle sound notifications on/off.
 */
export function toggleSound() {
    const next = !isSoundEnabled();
    setSoundEnabled(next);
    return next;
}

/**
 * Web Audio API synthesizer for notification sound (two-tone chime).
 * @param {boolean} force - If true, plays regardless of isSoundEnabled (used for testing).
 */
export function playNotificationChime(force = false) {
    if (!force && !isSoundEnabled()) return;

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
            playNotificationChime(true);
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
    // Always play chime if sound is enabled
    playNotificationChime(false);

    if (!isNotificationSupported() || Notification.permission !== 'granted') {
        return null;
    }

    try {
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

// Expose globally on window for Blade/Alpine.js access
if (typeof window !== 'undefined') {
    window.ITDeskNotification = {
        isSoundEnabled,
        setSoundEnabled,
        toggleSound,
        playNotificationChime,
        isNotificationSupported,
        getNotificationPermission,
        requestNotificationPermission,
        sendBrowserNotification,
    };
}
