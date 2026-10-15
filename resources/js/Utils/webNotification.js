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

export const SOUND_TONES = [
    { id: 'chime', name: '🔔 กระดิ่งกังวาน 3 โน้ต (ชัดเจน กังวาน - แนะนำ)' },
    { id: 'melody', name: '✨ ท่วงทำนองดนตรี 4 โน้ต (นุ่มนวล พรีเมียม)' },
    { id: 'desk_bell', name: '🛎️ กระดิ่งเคาน์เตอร์ Service Desk (คมชัด)' },
    { id: 'radar', name: '🚨 สัญญาณเรดาร์เตือน (ฉุกเฉิน ตื่นตัว)' },
    { id: 'classic', name: '🎵 คลาสสิก ดิ่ง-ด่อง (ปรับเสียงดังขึ้น)' },
];

/**
 * Get current selected sound tone. Defaults to 'chime'.
 */
export function getSelectedSoundTone() {
    if (typeof window === 'undefined') return 'chime';
    try {
        return localStorage.getItem('itdesk_sound_tone') || 'chime';
    } catch {
        return 'chime';
    }
}

/**
 * Set sound tone and notify listeners.
 */
export function setSelectedSoundTone(tone) {
    if (typeof window === 'undefined') return;
    try {
        localStorage.setItem('itdesk_sound_tone', tone);
        window.dispatchEvent(new CustomEvent('itdesk:sound-tone-changed', { detail: { tone } }));
    } catch {}
}

/**
 * Synthesize and play the specified notification sound tone.
 * @param {string|null} toneName - Tone id or null to use selected tone.
 * @param {boolean} force - If true, plays regardless of isSoundEnabled (for testing).
 */
export function playNotificationTone(toneName = null, force = false) {
    if (!force && !isSoundEnabled()) return;

    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;

        const ctx = new AudioCtx();
        const now = ctx.currentTime;
        const tone = toneName || getSelectedSoundTone();

        const playNote = (freq, startTime, duration, gainLevel = 0.38, type = 'sine') => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, startTime);
            gain.gain.setValueAtTime(gainLevel, startTime);
            gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(startTime);
            osc.stop(startTime + duration);
        };

        if (tone === 'melody') {
            // 4-note melodic chime: G4 -> C5 -> E5 -> G5 (Rich, soft, premium)
            playNote(392.00, now, 0.22, 0.35);
            playNote(523.25, now + 0.12, 0.25, 0.38);
            playNote(659.25, now + 0.24, 0.30, 0.40);
            playNote(783.99, now + 0.36, 0.55, 0.45);
        } else if (tone === 'desk_bell') {
            // Service desk bell: Crisp double ding
            playNote(1318.51, now, 0.25, 0.45, 'triangle');
            playNote(1567.98, now + 0.16, 0.45, 0.48, 'triangle');
        } else if (tone === 'radar') {
            // Radar alert: Crisp dual ping
            playNote(880.00, now, 0.20, 0.42);
            playNote(1174.66, now + 0.16, 0.35, 0.48);
        } else if (tone === 'classic') {
            // Classic two-tone: D5 -> A5 (Louder, longer decay)
            playNote(587.33, now, 0.28, 0.38);
            playNote(880.00, now + 0.14, 0.50, 0.45);
        } else {
            // Default 'chime': 3-note ascending bright chord (C5 -> E5 -> G5)
            playNote(523.25, now, 0.30, 0.38);
            playNote(659.25, now + 0.14, 0.35, 0.42);
            playNote(783.99, now + 0.28, 0.55, 0.48);
        }
    } catch (e) {
        // Ignore audio context errors if blocked by browser autoplay policy
    }
}

/**
 * Web Audio API synthesizer for notification sound (defaults to current chosen tone).
 * @param {boolean} force - If true, plays regardless of isSoundEnabled (used for testing).
 */
export function playNotificationChime(force = false) {
    playNotificationTone(null, force);
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
        getSelectedSoundTone,
        setSelectedSoundTone,
        playNotificationTone,
        playNotificationChime,
        SOUND_TONES,
        isNotificationSupported,
        getNotificationPermission,
        requestNotificationPermission,
        sendBrowserNotification,
    };
}
