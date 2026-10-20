/**
 * Dynamically update the browser tab favicon and touch icons.
 * This removes all existing icon links and appends fresh ones to ensure
 * browsers immediately refresh the tab icon without caching issues.
 */
export function updateBrowserFavicon(url) {
    if (!url || typeof document === 'undefined') return;

    try {
        const existingIcons = document.querySelectorAll("link[rel*='icon']");
        existingIcons.forEach((el) => el.remove());

        const icon = document.createElement('link');
        icon.rel = 'icon';
        icon.href = url;
        document.head.appendChild(icon);

        const shortcut = document.createElement('link');
        shortcut.rel = 'shortcut icon';
        shortcut.href = url;
        document.head.appendChild(shortcut);

        const apple = document.createElement('link');
        apple.rel = 'apple-touch-icon';
        apple.href = url;
        document.head.appendChild(apple);
    } catch (e) {
        // Suppress any DOM manipulation error in SSR or headless
    }
}
