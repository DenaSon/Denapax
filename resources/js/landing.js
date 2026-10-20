import Alpine from 'alpinejs';

Alpine.data('landingPage', () => ({
    menuOpen: false,
    hasScrolled: false,
    scrollProgress: 0,
    activeSection: 'top',
    scrollFrame: null,
    pointerFrame: null,
    revealObserver: null,
    sectionObserver: null,
    productPreviews: {},
    catalogLoading: false,
    catalogRequest: null,
    onCatalogPopState: null,
    preview: null,
    previewImageIndex: 0,
    previewVariantIndex: 0,
    previewReturnFocus: null,
    priorScrollLock: '',
    onScroll: null,
    onResize: null,

    init() {
        const catalog = document.getElementById('landing-product-data');
        if (catalog?.textContent) this.productPreviews = JSON.parse(catalog.textContent);

        const updateScroll = () => {
            this.scrollFrame = null;
            this.hasScrolled = window.scrollY > 400;

            const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
            this.scrollProgress = scrollableHeight > 0
                ? Math.min(1, Math.max(0, window.scrollY / scrollableHeight))
                : 0;
        };

        this.onScroll = () => {
            if (this.scrollFrame !== null) return;

            this.scrollFrame = window.requestAnimationFrame(updateScroll);
        };

        this.onResize = () => {
            this.onScroll();
            if (window.innerWidth >= 1024) this.closeMenu();
        };

        updateScroll();
        window.addEventListener('scroll', this.onScroll, { passive: true });
        window.addEventListener('resize', this.onResize, { passive: true });
        this.onCatalogPopState = () => this.loadCatalog(window.location.href);
        window.addEventListener('popstate', this.onCatalogPopState);

        this.$nextTick(() => {
            this.observeReveals();
            this.observeChapters();
        });
    },

    observeReveals() {
        this.revealObserver?.disconnect();
        const elements = this.$root.querySelectorAll('[data-reveal], [data-topography]');

        if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            elements.forEach((element) => element.classList.add('is-visible'));
            return;
        }

        this.$root.classList.add('motion-ready');

        this.revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    this.revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px 32px 0px' });

        elements.forEach((element) => this.revealObserver.observe(element));
    },

    observeChapters() {
        if (!('IntersectionObserver' in window)) return;

        const chapters = this.$root.querySelectorAll('[data-chapter]');
        this.sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) this.activeSection = entry.target.id;
            });
        }, { rootMargin: '-10% 0px -72% 0px', threshold: 0 });

        chapters.forEach((chapter) => this.sectionObserver.observe(chapter));
    },

    toggleMenu() {
        if (this.menuOpen) {
            this.closeMenu();
            return;
        }

        this.menuOpen = true;
        this.$nextTick(() => this.$refs.mobileNav?.querySelector('a')?.focus());
    },

    closeMenu(restoreFocus = false) {
        if (!this.menuOpen) return;

        this.menuOpen = false;
        if (restoreFocus) this.$nextTick(() => this.$refs.menuToggle?.focus());
    },

    onMobileNavKeydown(event) {
        if (event.key !== 'Tab') return;

        const links = [...this.$refs.mobileNav.querySelectorAll('a[href]')];
        if (!links.length) return;

        if (event.shiftKey && document.activeElement === links[0]) {
            event.preventDefault();
            links[links.length - 1].focus();
        } else if (!event.shiftKey && document.activeElement === links[links.length - 1]) {
            event.preventDefault();
            links[0].focus();
        }
    },

    onHeroMove(event) {
        if (event.pointerType === 'touch'
            || window.matchMedia('(pointer: coarse), (prefers-reduced-motion: reduce)').matches
            || !this.$refs.landscape) return;

        const rect = event.currentTarget.getBoundingClientRect();
        const x = (((event.clientX - rect.left) / rect.width - 0.5) * 14).toFixed(1) + 'px';
        const y = (((event.clientY - rect.top) / rect.height - 0.5) * 10).toFixed(1) + 'px';

        if (this.pointerFrame !== null) window.cancelAnimationFrame(this.pointerFrame);

        this.pointerFrame = window.requestAnimationFrame(() => {
            this.$refs.landscape.style.setProperty('--landscape-x', x);
            this.$refs.landscape.style.setProperty('--landscape-y', y);
            this.pointerFrame = null;
        });
    },

    resetHero() {
        if (!this.$refs.landscape) return;
        if (this.pointerFrame !== null) window.cancelAnimationFrame(this.pointerFrame);

        this.pointerFrame = null;
        this.$refs.landscape.style.setProperty('--landscape-x', '0px');
        this.$refs.landscape.style.setProperty('--landscape-y', '0px');
    },

    handleCatalogClick(event) {
        const link = event.target.closest?.('a[data-catalog-link]');
        if (!link || event.defaultPrevented || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const target = new URL(link.href, window.location.href);
        if (target.origin !== window.location.origin || target.pathname !== window.location.pathname) return;

        event.preventDefault();
        const isPagination = Boolean(link.closest('nav[aria-label="صفحات محصولات"]'));
        this.loadCatalog(target.href, true, isPagination);
    },

    async loadCatalog(url, updateHistory = false, scrollToProducts = false) {
        this.catalogRequest?.abort();
        const request = new AbortController();
        this.catalogRequest = request;
        this.catalogLoading = true;

        try {
            // Fresh server-rendered catalog: includes the selected category,
            // correct pagination, public prices and modal preview data.
            const response = await fetch(url, {
                signal: request.signal,
                credentials: 'same-origin',
                headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('Catalog request failed');

            const markup = await response.text();
            if (request.signal.aborted) return;

            const nextDocument = new DOMParser().parseFromString(markup, 'text/html');
            const replacement = nextDocument.getElementById('products');
            const previewPayload = nextDocument.getElementById('landing-product-data');
            const current = this.$root.querySelector('#products');

            if (!replacement || !previewPayload || !current) throw new Error('Invalid catalog response');

            const previews = JSON.parse(previewPayload.textContent);
            current.innerHTML = replacement.innerHTML;
            this.productPreviews = previews;

            if (updateHistory) window.history.pushState({ denapaxCatalog: true }, '', url);

            this.$nextTick(() => {
                this.observeReveals();
                if (scrollToProducts) {
                    current.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
                } else if (updateHistory) {
                    current.querySelector('nav[aria-label="فیلتر دسته‌بندی محصولات"] a[aria-current="page"]')
                        ?.focus({ preventScroll: true });
                }
                this.onScroll?.();
            });
        } catch (error) {
            if (error.name !== 'AbortError') {
                // Network outages and unexpected HTML must not leave the
                // catalog unusable. Real filter links are still navigable.
                window.location.assign(url);
            }
        } finally {
            if (this.catalogRequest === request) {
                this.catalogRequest = null;
                this.catalogLoading = false;
            }
        }
    },

    openProduct(id, event) {
        const item = this.productPreviews[String(id)];
        // Keep true links for crawlers, no-JS visitors, modifier clicks and
        // browsers without native dialog support.
        if (event && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0)) return;
        if (!item || !item.variants?.length || !this.$refs.productDialog
            || typeof this.$refs.productDialog.showModal !== 'function') return;

        event?.preventDefault();
        this.closeMenu();
        this.preview = item;
        this.previewImageIndex = 0;
        this.previewVariantIndex = 0;
        this.previewReturnFocus = event?.currentTarget ?? document.activeElement;

        this.$nextTick(() => {
            const dialog = this.$refs.productDialog;
            if (dialog.open) return;
            this.priorScrollLock = document.documentElement.style.overflow;
            dialog.showModal();
            document.documentElement.style.overflow = 'hidden';
            dialog.querySelector('[data-product-close]')?.focus();
        });
    },

    closeProduct() {
        if (this.$refs.productDialog?.open) this.$refs.productDialog.close();
    },

    onProductClosed() {
        document.documentElement.style.overflow = this.priorScrollLock;
        this.preview = null;
        this.previewImageIndex = 0;
        this.previewVariantIndex = 0;
        this.$nextTick(() => {
            this.previewReturnFocus?.focus?.();
            this.previewReturnFocus = null;
        });
    },

    selectVariant(index) {
        const variant = this.preview?.variants?.[index];
        if (!variant) return;
        this.previewVariantIndex = index;

        if (variant.image) {
            const imageIndex = this.preview.images.findIndex((photo) => photo.url === variant.image);
            if (imageIndex !== -1) this.previewImageIndex = imageIndex;
        }
    },

    selectImage(index) {
        if (index >= 0 && index < (this.preview?.images?.length ?? 0)) this.previewImageIndex = index;
    },

    nextImage(step) {
        const count = this.preview?.images?.length ?? 0;
        if (count > 1) this.previewImageIndex = (this.previewImageIndex + step + count) % count;
    },

    destroy() {
        // A navigation/unmount must never leave a locked page behind.
        if (this.$refs.productDialog?.open) this.$refs.productDialog.close();
        document.documentElement.style.overflow = this.priorScrollLock;
        window.removeEventListener('scroll', this.onScroll);
        window.removeEventListener('resize', this.onResize);
        window.removeEventListener('popstate', this.onCatalogPopState);
        this.catalogRequest?.abort();
        if (this.scrollFrame !== null) window.cancelAnimationFrame(this.scrollFrame);
        if (this.pointerFrame !== null) window.cancelAnimationFrame(this.pointerFrame);

        this.revealObserver?.disconnect();
        this.sectionObserver?.disconnect();
    },
}));

window.Alpine = Alpine;
Alpine.start();
