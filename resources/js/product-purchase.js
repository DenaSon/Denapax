document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    if (!Alpine) return;

    Alpine.data('productPurchase', (variants, cartQuantities) => ({
        variants: Array.isArray(variants) ? variants : [],
        cartQuantities: { ...(cartQuantities || {}) },
        selectedVariantId: Number(variants?.[0]?.id || 0),
        requestedQuantity: 1,
        busy: false,
        pendingAction: null,
        pendingVariantId: null,
        pendingQuantity: null,
        allowNativeSubmit: false,
        notice: '',
        error: '',
        unsubscribeRequest: null,

        init() {
            const selected = this.$root.querySelector('input[name="variant_id"]:checked');
            if (selected) this.selectedVariantId = Number(selected.value);

            if (typeof window.Livewire?.interceptRequest === 'function') {
                this.unsubscribeRequest = window.Livewire.interceptRequest(({ onError, onFailure }) => {
                    onError(({ response, preventDefault }) => {
                        if (!this.pendingAction) return;

                        preventDefault();
                        this.handleTransportFailure(response?.status === 419
                            ? 'نشست شما منقضی شده است. صفحه را تازه کنید و دوباره وارد شوید.'
                            : 'ارتباط با سرور با خطا مواجه شد. دوباره تلاش کنید.');
                    });

                    onFailure(() => {
                        if (!this.pendingAction) return;

                        this.handleTransportFailure('ارتباط با سرور قطع شد. پس از بازگشت اتصال دوباره تلاش کنید.');
                    });
                });
            }
        },

        get selectedVariant() {
            return this.variants.find((variant) => Number(variant.id) === Number(this.selectedVariantId)) || null;
        },

        get selectedPriceLabel() {
            return this.selectedVariant?.priceLabel || '';
        },

        get selectedCartQuantity() {
            return Math.max(0, Number(this.cartQuantities[this.selectedVariantId] || 0));
        },

        get hasSelectedInCart() {
            return this.selectedCartQuantity > 0;
        },

        useNativeFallback() {
            this.allowNativeSubmit = true;
            this.finishRequest();
        },

        handleAddSubmit(event) {
            if (this.allowNativeSubmit || !window.Livewire?.dispatch) {
                this.allowNativeSubmit = false;
                return;
            }

            event.preventDefault();
            this.addSelected();
        },

        addSelected() {
            if (this.busy || this.hasSelectedInCart) return;

            const variantId = Number(this.selectedVariantId);
            const quantity = Number(this.requestedQuantity);

            if (!Number.isSafeInteger(variantId) || variantId < 1
                || !Number.isSafeInteger(quantity) || quantity < 1 || quantity > 1000) {
                this.error = 'تنوع یا تعداد انتخاب‌شده معتبر نیست.';
                this.notice = '';
                return;
            }

            this.busy = true;
            this.pendingAction = 'add';
            this.pendingVariantId = variantId;
            this.pendingQuantity = quantity;
            this.clearFeedback();

            window.Livewire.dispatch('cart-add-requested', { variantId, quantity });
        },

        setSelectedQuantity(quantity) {
            if (this.busy || !this.hasSelectedInCart) return;

            const variantId = Number(this.selectedVariantId);
            const nextQuantity = Number(quantity);

            if (!Number.isSafeInteger(variantId) || variantId < 1
                || !Number.isSafeInteger(nextQuantity) || nextQuantity < 0 || nextQuantity > 1000) {
                this.error = 'تعداد انتخاب‌شده معتبر نیست.';
                this.notice = '';
                return;
            }

            if (nextQuantity === this.selectedCartQuantity) return;

            this.busy = true;
            this.pendingAction = 'quantity';
            this.pendingVariantId = variantId;
            this.pendingQuantity = nextQuantity;
            this.clearFeedback();

            window.Livewire.dispatch('cart-quantity-requested', {
                variantId,
                quantity: nextQuantity,
            });
        },

        handleCartAdded(event) {
            const detail = event?.detail || {};
            const variantId = Number(detail.variantId ?? this.pendingVariantId ?? 0);
            const quantity = Number(detail.quantity ?? this.pendingQuantity ?? 0);

            if (variantId > 0 && quantity > 0) {
                const current = Number(this.cartQuantities[variantId] || 0);
                this.cartQuantities = { ...this.cartQuantities, [variantId]: current + quantity };
            }

            this.finishRequest();
            this.notice = 'محصول به سبد خرید اضافه شد.';
        },

        handleCartQuantityUpdated(event) {
            const detail = event?.detail || {};
            const variantId = Number(detail.variantId ?? this.pendingVariantId ?? 0);
            const quantity = Number(detail.quantity ?? this.pendingQuantity ?? -1);

            if (variantId > 0 && Number.isSafeInteger(quantity) && quantity >= 0) {
                this.cartQuantities = { ...this.cartQuantities, [variantId]: quantity };
            }

            this.finishRequest();
            this.notice = quantity === 0 ? 'محصول از سبد خرید حذف شد.' : 'تعداد سبد خرید به‌روزرسانی شد.';
        },

        handleCartFailed(event) {
            this.finishRequest();
            this.error = event?.detail?.message || 'به‌روزرسانی سبد خرید انجام نشد. دوباره تلاش کنید.';
        },

        handleTransportFailure(message) {
            this.finishRequest();
            this.notice = '';
            this.error = message;
        },

        finishRequest() {
            this.busy = false;
            this.pendingAction = null;
            this.pendingVariantId = null;
            this.pendingQuantity = null;
        },

        clearFeedback() {
            this.notice = '';
            this.error = '';
        },

        destroy() {
            if (typeof this.unsubscribeRequest === 'function') this.unsubscribeRequest();
            this.unsubscribeRequest = null;
        },
    }));
});
