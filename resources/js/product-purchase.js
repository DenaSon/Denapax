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
        pendingPreviousQuantity: null,
        allowNativeSubmit: false,
        notice: '',
        error: '',
        unsubscribeRequest: null,
        needsReconciliation: false,
        requiresReload: false,
        failedVariantId: null,

        init() {
            const selected = this.$root.querySelector('input[name="variant_id"]:checked');
            if (selected) this.selectedVariantId = Number(selected.value);

            if (typeof window.Livewire?.interceptRequest === 'function') {
                this.unsubscribeRequest = window.Livewire.interceptRequest(({ onError, onFailure }) => {
                    onError(({ response, preventDefault }) => {
                        if (!this.pendingAction) return;

                        preventDefault();
                        const expired = response?.status === 419;
                        this.handleTransportFailure(expired
                            ? 'نشست شما منقضی شده است. صفحه را تازه کنید و دوباره وارد شوید.'
                            : 'ارتباط با سرور با خطا مواجه شد. وضعیت سبد را بررسی کنید.', {
                                reconcile: !expired,
                                reload: expired,
                            });
                    });

                    onFailure(() => {
                        if (!this.pendingAction) return;

                        this.handleTransportFailure('ارتباط با سرور قطع شد. پس از بازگشت اتصال، وضعیت سبد را بررسی کنید.');
                    });
                });
            }
        },

        get selectedVariant() {
            return this.variants.find((variant) => Number(variant.id) === Number(this.selectedVariantId)) || null;
        },

        get selectedQuantity() {
            if (this.pendingAction === 'quantity' && this.pendingQuantity !== null) {
                return this.pendingQuantity;
            }
            if (this.selectedCartQuantity > 0) return this.selectedCartQuantity;
            const quantity = Number(this.requestedQuantity);
            return Number.isSafeInteger(quantity) && quantity > 0 ? Math.min(1000, quantity) : 1;
        },

        get selectedPriceLabel() {
            const unitPrice = Number(this.selectedVariant?.priceToman);
            if (!Number.isFinite(unitPrice) || unitPrice < 0) return '';
            return new Intl.NumberFormat('en-US', { maximumFractionDigits: 1 })
                .format(unitPrice * this.selectedQuantity);
        },

        get selectedVariantTitle() {
            return this.selectedVariant?.title || 'بسته انتخابی';
        },

        get selectedQuantityLabel() {
            return new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 0 }).format(this.selectedQuantity);
        },

        get selectedCartQuantity() {
            return Math.max(0, Number(this.cartQuantities[this.selectedVariantId] || 0));
        },

        get hasSelectedInCart() {
            return this.selectedCartQuantity > 0;
        },

        adjustRequestedQuantity(delta) {
            if (this.busy || this.needsReconciliation || this.hasSelectedInCart) return;

            const current = Number(this.requestedQuantity);
            const base = Number.isSafeInteger(current) ? current : 1;
            const next = base + Number(delta);

            this.requestedQuantity = Math.min(1000, Math.max(1, next));
            this.clearFeedback();
        },

        useNativeFallback() {
            this.allowNativeSubmit = true;
            this.finishRequest();
        },

        handleAddSubmit(event) {
            if (this.needsReconciliation) {
                event.preventDefault();
                return;
            }

            if (this.allowNativeSubmit || !window.Livewire?.dispatch) {
                this.allowNativeSubmit = false;
                return;
            }

            event.preventDefault();
            this.addSelected();
        },

        addSelected() {
            if (this.busy || this.needsReconciliation || this.hasSelectedInCart) return;

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
            if (this.busy || this.needsReconciliation || !this.hasSelectedInCart) return;

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
            this.pendingPreviousQuantity = this.selectedCartQuantity;
            this.cartQuantities = { ...this.cartQuantities, [variantId]: nextQuantity };
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
            this.needsReconciliation = false;
            this.requiresReload = false;
            this.failedVariantId = null;
            this.notice = 'محصول به سبد خرید اضافه شد.';
        },

        handleCartQuantityUpdated(event) {
            const detail = event?.detail || {};
            const variantId = Number(detail.variantId ?? this.pendingVariantId ?? 0);
            const quantity = Number(detail.quantity ?? this.pendingQuantity ?? -1);

            if (variantId > 0 && Number.isSafeInteger(quantity) && quantity >= 0) {
                this.cartQuantities = { ...this.cartQuantities, [variantId]: quantity };
            }

            const action = this.pendingAction;
            this.finishRequest();
            this.needsReconciliation = false;
            this.requiresReload = false;
            this.failedVariantId = null;
            this.notice = action === 'refresh' ? 'وضعیت سبد خرید به‌روز شد.' : '';
        },

        handleCartFailed(event) {
            this.rollbackPendingQuantity();
            this.finishRequest();
            this.needsReconciliation = false;
            this.requiresReload = false;
            this.failedVariantId = null;
            this.error = event?.detail?.message || 'به‌روزرسانی سبد خرید انجام نشد. دوباره تلاش کنید.';
        },

        handleTransportFailure(message, { reconcile = true, reload = false } = {}) {
            const failedVariantId = Number(this.pendingVariantId || this.selectedVariantId || 0);
            this.rollbackPendingQuantity();
            this.finishRequest();
            this.needsReconciliation = reconcile && failedVariantId > 0;
            this.requiresReload = reload;
            this.failedVariantId = this.needsReconciliation ? failedVariantId : null;
            this.notice = '';
            this.error = message;
        },

        refreshSelectedQuantity() {
            if (this.busy || !this.needsReconciliation || !this.failedVariantId) return;

            if (!window.Livewire?.dispatch) {
                this.handleTransportFailure('برای بررسی وضعیت سبد، صفحه را تازه کنید.', {
                    reconcile: false,
                    reload: true,
                });
                return;
            }

            const variantId = this.failedVariantId;
            this.busy = true;
            this.pendingAction = 'refresh';
            this.pendingVariantId = variantId;
            this.pendingQuantity = null;
            this.clearFeedback();

            window.Livewire.dispatch('cart-state-requested', { variantId });
        },

        rollbackPendingQuantity() {
            if (this.pendingAction !== 'quantity' || !this.pendingVariantId
                || this.pendingPreviousQuantity === null) return;

            this.cartQuantities = {
                ...this.cartQuantities,
                [this.pendingVariantId]: this.pendingPreviousQuantity,
            };
        },

        finishRequest() {
            this.busy = false;
            this.pendingAction = null;
            this.pendingVariantId = null;
            this.pendingQuantity = null;
            this.pendingPreviousQuantity = null;
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
