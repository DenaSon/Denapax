// Lightweight product gallery: image selection, arrows and touch swipe only.
const galleryControllers = new Map();

function mountGalleries() {
    document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
        if (galleryControllers.has(gallery)) return;

        const controller = new AbortController();
        const { signal } = controller;
        const thumbs = Array.from(gallery.querySelectorAll('[data-gallery-thumb]'));
        const stage = gallery.querySelector('[data-gallery-stage]');
        const mainImage = gallery.querySelector('[data-gallery-main]');
        const counter = gallery.querySelector('[data-gallery-counter]');

        if (!thumbs.length || !stage || !mainImage) return;

        galleryControllers.set(gallery, controller);

        const photos = thumbs.map((thumb) => ({
            url: thumb.dataset.imageUrl,
            alt: thumb.dataset.imageAlt,
        }));
        const format = new Intl.NumberFormat('fa-IR');
        let currentIndex = 0;
        let swipeStart = null;

        function show(index, scrollThumb = true) {
            if (!Number.isInteger(index) || !photos[index]) return;

            currentIndex = index;
            mainImage.src = photos[index].url;
            mainImage.alt = photos[index].alt;

            thumbs.forEach((thumb, position) => {
                if (position === index) thumb.setAttribute('aria-current', 'true');
                else thumb.removeAttribute('aria-current');
            });

            if (counter) {
                counter.textContent = `${format.format(index + 1)} / ${format.format(photos.length)}`;
            }

            if (scrollThumb && thumbs.length > 1) {
                thumbs[index].scrollIntoView({ block: 'nearest', inline: 'nearest' });
            }
        }

        function step(offset) {
            show((currentIndex + offset + photos.length) % photos.length);
        }

        thumbs.forEach((thumb, index) => {
            thumb.addEventListener('click', () => show(index), { signal });
        });

        gallery.querySelectorAll('[data-gallery-step]').forEach((button) => {
            button.addEventListener('click', () => step(Number(button.dataset.galleryStep)), { signal });
        });

        stage.addEventListener('touchstart', (event) => {
            if (event.touches.length !== 1) {
                swipeStart = null;
                return;
            }

            swipeStart = {
                x: event.touches[0].clientX,
                y: event.touches[0].clientY,
            };
        }, { passive: true, signal });

        stage.addEventListener('touchend', (event) => {
            if (!swipeStart || event.changedTouches.length !== 1 || event.touches.length !== 0) return;

            const dx = event.changedTouches[0].clientX - swipeStart.x;
            const dy = event.changedTouches[0].clientY - swipeStart.y;
            swipeStart = null;

            if (Math.abs(dx) < 45 || Math.abs(dx) < Math.abs(dy) * 1.25) return;

            step(dx < 0 ? 1 : -1);
        }, { passive: true, signal });

        gallery.closest('main')?.querySelectorAll('input[name="variant_id"][data-variant-photo-index]')
            .forEach((radio) => {
                radio.addEventListener('change', () => {
                    if (radio.checked && radio.dataset.variantPhotoIndex !== '') {
                        show(Number(radio.dataset.variantPhotoIndex));
                    }
                }, { signal });
            });

        gallery.classList.add('is-ready');
        show(0, false);
    });
}

document.addEventListener('livewire:navigating', () => {
    galleryControllers.forEach((controller) => controller.abort());
    galleryControllers.clear();
});
document.addEventListener('livewire:navigated', mountGalleries);
mountGalleries();
