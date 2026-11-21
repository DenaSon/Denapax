// Privacy-friendly first-party page-view beacon.
// Runs only after a page is displayed, never on Livewire hover prefetch.
const visitorKey = 'denapax:analytics:visitor:v1';
const visitorLifetimeMs = 90 * 24 * 60 * 60 * 1000;
let fallbackVisitorId = null;
let lastNavigation = '';
let lastNavigationAt = 0;

function randomVisitorId() {
    const bytes = new Uint8Array(16);
    window.crypto.getRandomValues(bytes);

    return Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
}

function visitorId() {
    const now = Date.now();

    try {
        const stored = JSON.parse(window.localStorage.getItem(visitorKey) || 'null');

        if (stored && /^[a-f0-9]{32}$/.test(stored.id)
            && Number.isFinite(stored.expires) && stored.expires > now) {
            return stored.id;
        }

        const id = randomVisitorId();
        window.localStorage.setItem(visitorKey, JSON.stringify({
            id,
            expires: now + visitorLifetimeMs,
        }));

        return id;
    } catch {
        // Browser storage may be disabled. Keep one ephemeral token per tab.
        fallbackVisitorId ||= randomVisitorId();

        return fallbackVisitorId;
    }
}

function recordDisplayedPage() {
    const page = document.body?.dataset.analyticsPage;
    const endpoint = document.body?.dataset.analyticsEndpoint;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!page || !endpoint || !token || (page !== 'home' && page !== 'product')) return;

    const locationKey = window.location.pathname + window.location.search;
    const now = Date.now();
    // Livewire fires navigated on initial load too. Avoid double-beacons.
    if (lastNavigation === locationKey && now - lastNavigationAt < 1500) return;

    const productId = Number(document.body.dataset.analyticsProductId || '0');
    if (!Number.isSafeInteger(productId) || productId < 0) return;

    lastNavigation = locationKey;
    lastNavigationAt = now;

    try {
        window.fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                page_type: page,
                product_id: productId,
                visitor_id: visitorId(),
            }),
        }).catch(() => {
            // Analytics is best-effort and must never affect shopping/navigation.
        });
    } catch {
        // Ignore blocked beacon APIs and restrictive privacy settings.
    }
}

document.addEventListener('livewire:navigated', recordDisplayedPage);
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', recordDisplayedPage, { once: true });
} else {
    recordDisplayedPage();
}
