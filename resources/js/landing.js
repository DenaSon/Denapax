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
    onScroll: null,
    onResize: null,

    init() {
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

        this.$nextTick(() => {
            this.observeReveals();
            this.observeChapters();
        });
    },

    observeReveals() {
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

    destroy() {
        window.removeEventListener('scroll', this.onScroll);
        window.removeEventListener('resize', this.onResize);
        if (this.scrollFrame !== null) window.cancelAnimationFrame(this.scrollFrame);
        if (this.pointerFrame !== null) window.cancelAnimationFrame(this.pointerFrame);

        this.revealObserver?.disconnect();
        this.sectionObserver?.disconnect();
    },
}));

window.Alpine = Alpine;
Alpine.start();
