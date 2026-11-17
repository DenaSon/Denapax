document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    if (!Alpine) return;

    Alpine.data('otpChallenge', () => ({
        currentMobile: '',
        mobile: '',
        expiresAt: null,
        resendAt: null,
        codeUsable: false,
        now: Date.now(),
        timer: null,

        init() {
            this.currentMobile = this.$root.dataset.otpCurrentMobile || '';
            this.mobile = this.$root.dataset.otpEnteredMobile || this.currentMobile;
            this.expiresAt = Number(this.$root.dataset.otpExpiresAt) || null;
            this.resendAt = Number(this.$root.dataset.otpResendAt) || null;
            this.codeUsable = this.$root.dataset.otpCodeUsable === '1';
            this.tick();
            this.timer = window.setInterval(() => this.tick(), 1000);
        },

        tick() {
            this.now = Date.now();
        },

        get sameMobile() {
            return this.normalizeMobile(this.mobile) === this.normalizeMobile(this.currentMobile);
        },

        get resendRemaining() {
            return this.resendAt ? Math.max(0, this.resendAt - this.now) : 0;
        },

        get requestDisabled() {
            return this.sameMobile && this.resendRemaining > 0;
        },

        get expiryMessage() {
            if (!this.codeUsable || !this.expiresAt) {
                return 'کد فعلی منقضی یا قابل استفاده نیست؛ کد تازه دریافت کنید.';
            }

            const remaining = this.expiresAt - this.now;
            if (remaining <= 0) return 'اعتبار کد پایان یافته است؛ کد تازه دریافت کنید.';

            return 'اعتبار کد: ' + this.formatRemaining(remaining) + '.';
        },

        get resendMessage() {
            if (!this.sameMobile) {
                return 'شماره تغییر کرده است؛ با ثبت فرم، کد برای شماره جدید درخواست می‌شود.';
            }

            if (this.resendRemaining > 0) {
                return 'ارسال مجدد برای همین شماره تا ' + this.formatRemaining(this.resendRemaining) + ' دیگر فعال می‌شود.';
            }

            return 'اکنون می‌توانید برای همین شماره کد تازه دریافت کنید.';
        },

        formatRemaining(milliseconds) {
            const totalSeconds = Math.max(0, Math.ceil(milliseconds / 1000));
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            const number = (value) => new Intl.NumberFormat('fa-IR', {
                minimumIntegerDigits: 2,
                useGrouping: false,
            }).format(value);

            return number(minutes) + ':' + number(seconds);
        },

        normalizeMobile(value) {
            const digitMap = {
                '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
                '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
                '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
                '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
            };

            let normalized = String(value || '').trim().replace(/[۰-۹٠-٩]/g, (digit) => digitMap[digit]);
            if (normalized.startsWith('+98')) normalized = '0' + normalized.slice(3);
            else if (normalized.startsWith('98') && normalized.length === 12) normalized = '0' + normalized.slice(2);

            return normalized;
        },

        destroy() {
            if (this.timer !== null) window.clearInterval(this.timer);
            this.timer = null;
        },
    }));
});
