// Progressive gallery: native image links still work if JS is unavailable.
// No third-party slider, DOM HTML injection or client-side pricing logic.
// The script is evaluated only once by Vite. Remount for every wire:navigate
// visit, and abort document-level handlers before Livewire swaps the page.
const galleryControllers = new Set();

function mountGalleries() {
document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
    if (gallery.dataset.galleryMounted === 'true') return;
    gallery.dataset.galleryMounted = 'true';
    const controller = new AbortController();
    galleryControllers.add({ controller, gallery });
    const thumbs = Array.from(gallery.querySelectorAll('[data-gallery-thumb]'));
    const mainLink = gallery.querySelector('[data-gallery-open]');
    const mainImage = gallery.querySelector('[data-gallery-main]');
    const count = gallery.querySelector('[data-gallery-counter]');
    const dialog = gallery.querySelector('[data-gallery-lightbox]');
    const zoomImage = dialog?.querySelector('[data-lightbox-image]');
    const viewport = dialog?.querySelector('[data-lightbox-viewport]');
    const dialogCount = dialog?.querySelector('[data-lightbox-counter]');

    if (!thumbs.length || !mainLink || !mainImage || !dialog || !zoomImage || !viewport) return;

    const photos = thumbs.map((thumb) => ({
        url: thumb.dataset.imageUrl,
        fullUrl: thumb.dataset.imageFullUrl,
        alt: thumb.dataset.imageAlt,
    }));
    const formatIndex = (index) => new Intl.NumberFormat('fa-IR').format(index + 1);
    const formatCount = new Intl.NumberFormat('fa-IR').format(photos.length);

    let currentIndex = 0;
    let zoom = 1;
    let panX = 0;
    let panY = 0;
    let drag = null;
    let pinch = null;
    let wasPinching = false;
    let lastTap = null;
    let priorOverflow = '';

    function updateZoom() {
        if (zoom <= 1) {
            zoom = 1;
            panX = 0;
            panY = 0;
        }
        const maxX = Math.max(0, (zoom - 1) * viewport.clientWidth / 2);
        const maxY = Math.max(0, (zoom - 1) * viewport.clientHeight / 2);
        panX = Math.min(maxX, Math.max(-maxX, panX));
        panY = Math.min(maxY, Math.max(-maxY, panY));
        zoomImage.style.transform = `translate3d(${panX}px, ${panY}px, 0) scale(${zoom})`;
        viewport.classList.toggle('is-zoomed', zoom > 1);
        dialog.querySelector('[data-lightbox-zoom-in]').disabled = zoom >= 4;
        dialog.querySelector('[data-lightbox-zoom-out]').disabled = zoom <= 1;
        dialog.querySelector('[data-lightbox-zoom-reset]').disabled = zoom <= 1;
    }

    function setZoom(value) {
        zoom = Math.min(4, Math.max(1, Math.round(value * 100) / 100));
        updateZoom();
    }

    function show(index, scrollThumb = true) {
        if (!Number.isInteger(index) || !photos[index]) return;

        const changed = currentIndex !== index;
        currentIndex = index;
        const photo = photos[index];
        mainImage.src = photo.url;
        mainImage.alt = photo.alt;
        mainLink.href = photo.fullUrl;
        mainLink.setAttribute('aria-label', `نمایش بزرگ ${photo.alt}`);

        thumbs.forEach((thumb, position) => {
            if (position === index) thumb.setAttribute('aria-current', 'true');
            else thumb.removeAttribute('aria-current');
        });

        const status = `${formatIndex(index)} / ${formatCount}`;
        if (count) count.textContent = status;
        if (dialogCount) dialogCount.textContent = status;

        if (dialog.open) {
            zoomImage.src = photo.fullUrl;
            zoomImage.alt = photo.alt;
            if (changed) setZoom(1);
        }

        if (scrollThumb && thumbs.length > 1) {
            thumbs[index].scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
    }

    function step(offset) {
        show((currentIndex + offset + photos.length) % photos.length);
    }

    function openLightbox(event) {
        if (event && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0)) return;
        if (typeof dialog.showModal !== 'function') return;

        event?.preventDefault();
        priorOverflow = document.documentElement.style.overflow;
        zoomImage.src = photos[currentIndex].fullUrl;
        zoomImage.alt = photos[currentIndex].alt;
        dialog.showModal();
        document.documentElement.style.overflow = 'hidden';
        setZoom(1);
        dialog.querySelector('[data-lightbox-close]').focus();
    }

    mainLink.addEventListener('click', openLightbox);
    thumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', (event) => {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;
            event.preventDefault();
            show(index);
        });
    });

    gallery.querySelectorAll('[data-gallery-step]').forEach((button) => {
        button.addEventListener('click', () => step(Number(button.dataset.galleryStep)));
    });

    dialog.querySelector('[data-lightbox-close]').addEventListener('click', () => dialog.close());
    dialog.querySelectorAll('[data-lightbox-step]').forEach((button) => {
        button.addEventListener('click', () => step(Number(button.dataset.lightboxStep)));
    });
    dialog.querySelector('[data-lightbox-zoom-in]').addEventListener('click', () => setZoom(zoom + 0.5));
    dialog.querySelector('[data-lightbox-zoom-out]').addEventListener('click', () => setZoom(zoom - 0.5));
    dialog.querySelector('[data-lightbox-zoom-reset]').addEventListener('click', () => setZoom(1));
    zoomImage.addEventListener('dblclick', () => setZoom(zoom > 1 ? 1 : 2));

    // The dialog's backdrop, native Escape behavior and focus trap remain intact.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', () => {
        document.documentElement.style.overflow = priorOverflow;
        setZoom(1);
        mainLink.focus({ preventScroll: true });
    });
    document.addEventListener('keydown', (event) => {
        if (!dialog.open) return;
        if (event.key === 'ArrowLeft') { event.preventDefault(); step(1); }
        if (event.key === 'ArrowRight') { event.preventDefault(); step(-1); }
        if (event.key === '+' || event.key === '=') { event.preventDefault(); setZoom(zoom + 0.5); }
        if (event.key === '-') { event.preventDefault(); setZoom(zoom - 0.5); }
    }, { signal: controller.signal });

    const touchDistance = (touches) => Math.hypot(
        touches[0].clientX - touches[1].clientX,
        touches[0].clientY - touches[1].clientY,
    );

    // In the large main image, horizontal swipe changes the photo while
    // vertical page scrolling is left to the browser.
    let swipeStart = null;
    mainLink.addEventListener('touchstart', (event) => {
        if (event.touches.length !== 1) return;
        swipeStart = { x: event.touches[0].clientX, y: event.touches[0].clientY };
    }, { passive: true });
    mainLink.addEventListener('touchend', (event) => {
        if (!swipeStart || event.changedTouches.length !== 1 || event.touches.length !== 0) return;
        const dx = event.changedTouches[0].clientX - swipeStart.x;
        const dy = event.changedTouches[0].clientY - swipeStart.y;
        swipeStart = null;
        if (Math.abs(dx) < 45 || Math.abs(dx) < Math.abs(dy) * 1.25) return;
        // RTL: swipe left advances through the image strip.
        event.preventDefault();
        step(dx < 0 ? 1 : -1);
    }, { passive: false });

    // Modal supports one-finger swipe at 1x, pinch zoom and panning when zoomed.
    viewport.addEventListener('touchstart', (event) => {
        if (event.touches.length === 2) {
            wasPinching = true;
            drag = null;
            pinch = { distance: Math.max(1, touchDistance(event.touches)), initialZoom: zoom };
        } else if (event.touches.length === 1) {
            drag = {
                x: event.touches[0].clientX,
                y: event.touches[0].clientY,
                originalPanX: panX,
                originalPanY: panY,
            };
            wasPinching = false;
        }
    }, { passive: true });

    viewport.addEventListener('touchmove', (event) => {
        if (event.touches.length === 2 && pinch) {
            event.preventDefault();
            setZoom(pinch.initialZoom * touchDistance(event.touches) / pinch.distance);
        } else if (event.touches.length === 1 && drag && zoom > 1) {
            event.preventDefault();
            panX = drag.originalPanX + event.touches[0].clientX - drag.x;
            panY = drag.originalPanY + event.touches[0].clientY - drag.y;
            updateZoom();
        }
    }, { passive: false });

    viewport.addEventListener('touchend', (event) => {
        if (event.touches.length > 0) return;
        if (drag && !wasPinching && zoom === 1 && event.changedTouches.length === 1) {
            const dx = event.changedTouches[0].clientX - drag.x;
            const dy = event.changedTouches[0].clientY - drag.y;
            if (Math.abs(dx) >= 45 && Math.abs(dx) > Math.abs(dy) * 1.25) {
                step(dx < 0 ? 1 : -1);
            } else if (Math.abs(dx) < 15 && Math.abs(dy) < 15) {
                const now = Date.now();
                if (lastTap && now - lastTap.at < 320
                    && Math.abs(lastTap.x - event.changedTouches[0].clientX) < 30
                    && Math.abs(lastTap.y - event.changedTouches[0].clientY) < 30) {
                    setZoom(2);
                    lastTap = null;
                } else {
                    lastTap = { at: now, x: event.changedTouches[0].clientX, y: event.changedTouches[0].clientY };
                }
            }
        }
        drag = null;
        pinch = null;
        wasPinching = false;
    }, { passive: true });

    gallery.closest('main')?.querySelectorAll('input[name="variant_id"][data-variant-photo-index]')
        .forEach((radio) => {
            radio.addEventListener('change', () => {
                if (!radio.checked || radio.dataset.variantPhotoIndex === '') return;
                show(Number(radio.dataset.variantPhotoIndex));
            });
        });

    gallery.classList.add('is-ready');
    updateZoom();
});
}

document.addEventListener('livewire:navigating', () => {
    for (const { controller, gallery } of galleryControllers) {
        const dialog = gallery.querySelector('[data-gallery-lightbox]');
        if (dialog?.open) dialog.close();
        controller.abort();
    }
    galleryControllers.clear();
});
document.addEventListener('livewire:navigated', mountGalleries);
mountGalleries();
