document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    if (!Alpine) return;

    Alpine.data('storeSearch', () => ({
        submit(event) {
            const form = event.currentTarget;
            if (!(form instanceof HTMLFormElement)) return;

            const query = String(new FormData(form).get('q') ?? '').trim();
            const url = new URL(form.action, window.location.href);
            url.search = '';

            if (query) url.searchParams.set('q', query);

            if (typeof window.Livewire?.navigate === 'function') {
                window.Livewire.navigate(url.toString());
            } else {
                form.submit();
            }
        },
    }));

    Alpine.data('landingPage', () => ({
        menuOpen: false,
        hasScrolled: false,
        headerCompact: false,
        scrollFrame: null,
        revealObserver: null,
        onScroll: null,
        onResize: null,

        init() {
            const updateScroll = () => {
                this.scrollFrame = null;
                this.hasScrolled = window.scrollY > 400;
                this.headerCompact = window.scrollY > 48;
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
            this.$nextTick(() => this.observeReveals());
        },

        observeReveals() {
            this.revealObserver?.disconnect();
            const elements = this.$root.querySelectorAll('[data-reveal]');

            if (!('IntersectionObserver' in window)
                || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
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

        destroy() {
            window.removeEventListener('scroll', this.onScroll);
            window.removeEventListener('resize', this.onResize);
            if (this.scrollFrame !== null) window.cancelAnimationFrame(this.scrollFrame);
            this.revealObserver?.disconnect();
        },
    }));
});
