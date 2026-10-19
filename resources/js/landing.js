import Alpine from 'alpinejs';

Alpine.data('landingPage', () => ({
    menuOpen: false,
    hasScrolled: false,
    init() {
        const updateScroll = () => {
            this.hasScrolled = window.scrollY > 400;
        };
        updateScroll();
        window.addEventListener('scroll', updateScroll, { passive: true });

        this.$nextTick(() => {
            const elements = document.querySelectorAll('[data-reveal]');
            if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                elements.forEach((element) => element.classList.add('is-visible'));
                return;
            }

            document.documentElement.classList.add('motion-ready');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px 28px 0px' });
            elements.forEach((element) => observer.observe(element));
        });
    },
    onHeroMove(event) {
        if (window.matchMedia('(pointer: coarse), (prefers-reduced-motion: reduce)').matches || !this.$refs.landscape) {
            return;
        }
        const rect = event.currentTarget.getBoundingClientRect();
        this.$refs.landscape.style.setProperty('--landscape-x', (((event.clientX - rect.left) / rect.width - 0.5) * 14).toFixed(1) + 'px');
        this.$refs.landscape.style.setProperty('--landscape-y', (((event.clientY - rect.top) / rect.height - 0.5) * 10).toFixed(1) + 'px');
    },
    resetHero() {
        if (!this.$refs.landscape) return;
        this.$refs.landscape.style.setProperty('--landscape-x', '0px');
        this.$refs.landscape.style.setProperty('--landscape-y', '0px');
    },
}));

window.Alpine = Alpine;
Alpine.start();
