export function updateFavicon(): void {
    if (typeof document === 'undefined') {
        return;
    }

    const styles = getComputedStyle(document.documentElement);
    const bg = styles.getPropertyValue('--sidebar-primary').trim();
    const fg = styles.getPropertyValue('--sidebar-primary-foreground').trim();

    if (!bg || !fg) {
        return;
    }

    const svg =
        `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">` +
        `<rect width="32" height="32" rx="8" fill="${bg}"/>` +
        `<g transform="translate(4 4)" fill="none" stroke="${fg}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">` +
        `<path d="M17 21a1 1 0 0 0 1-1v-5.35c0-.457.316-.844.727-1.041a4 4 0 0 0-2.134-7.589 5 5 0 0 0-9.186 0 4 4 0 0 0-2.134 7.588c.411.198.727.585.727 1.041V20a1 1 0 0 0 1 1Z"/>` +
        `<path d="M6 17h12"/>` +
        `</g></svg>`;

    const encoded = `data:image/svg+xml,${encodeURIComponent(svg)}`;

    const link = document.querySelector<HTMLLinkElement>(
        'link[rel="icon"][type="image/svg+xml"]',
    );

    if (link) {
        link.href = encoded;
    }
}
