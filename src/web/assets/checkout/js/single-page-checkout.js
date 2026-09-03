import { addressBook } from './checkout/address.js';
import { cartPersistence } from './checkout/persistence.js';
import { gatewayHandling } from './checkout/gateways.js';

export const isValidEmail = (value) =>
	/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());

// Checkboxes hold a list where every other field holds a string
export const isEmptyValue = (value) =>
	Array.isArray(value) ? value.length === 0 : String(value ?? '').trim() === '';

const asList = (value) =>
	Array.isArray(value) ? value : Object.values(value ?? {});

export const SinglePageCheckout = (props) => {
	return {
		loggedIn: props.loggedIn,
		email: props.email ?? '',
		cartId: props.cartId ?? null,
		klaviyoEnabled: Boolean(props.klaviyoEnabled),
		trackedCheckout: false,
		subscribed: false,
		shippingAddressId: props.shippingAddressId,
		useNewAddress: props.useNewAddress ?? false,
		shippingMethodHandle: props.shippingMethodHandle ?? '',
		requireShippingMethod: props.requireShippingMethod ?? false,
		billingSameAsShipping: props.billingSameAsShipping ?? true,
		billingAddressId: props.billingAddressId,
		useNewBillingAddress: props.useNewBillingAddress ?? false,
		cartHasShippingAddress: props.hasShippingAddress ?? false,
		couponCode: props.couponCode ?? '',
		couponInput: props.couponCode ?? '',
		couponOpen: Boolean(props.couponCode),
		couponError: '',
		notesError: '',
		notesButtonVisible: false,
		addressLabels: {},
		addressFields: {},
		shippingPreview: props.shippingPreview ?? '',
		latestShippingAddress: null,
		latestBillingAddress: null,
		shippingMethods: asList(props.shippingMethods),
		totals: props.totals ?? {
			itemsAsCurrency: '',
			shipping: 0,
			shippingAsCurrency: '',
			taxAsCurrency: '',
			total: 0,
			totalAsCurrency: '',
			currency: 'USD',
			discounts: [],
			vouchers: [],
		},
		payButtonText: props.payButtonText ?? '',
		processingLabel: props.processingLabel ?? '',
		placingOrderLabel: props.placingOrderLabel ?? '',
		manualGatewayIds: asList(props.manualGatewayIds),
		cardNumberError: props.cardNumberError ?? '',
		cardMonthError: props.cardMonthError ?? '',
		cardYearError: props.cardYearError ?? '',
		cardExpiredError: props.cardExpiredError ?? '',
		cardCvvError: props.cardCvvError ?? '',
		syncingFromCart: false,
		hasNewBillingContent: false,
		editExistingAddress: 0,
		editBillingAddressId: 0,
		gatewayId: props.gatewayId,
		savingLabel: props.savingLabel,
		savedLabel: props.savedLabel,
		failedLabel: props.failedLabel,
		status: '',
		statusTone: 'idle',
		panelStatus: {
			contact: 'idle',
			delivery: 'idle',
			shipping: 'idle',
			payment: 'idle',
			coupon: 'idle',
			notes: 'idle',
		},
		panelErrors: {},
		panelStatusTimers: {},
		queuedSavePanel: null,
		pending: 0,
		saveTimer: null,
		saveAbort: null,
		saving: false,
		nextSave: null,
		lastSaved: '',
		paypalInitTimer: null,
		paypalInvalidated: false,
		stripeInvalidated: false,
		paying: false,
		onPageShow: null,
		originalAuthorizeHandler: null,
		originalSendPayment: null,

		...addressBook(),
		...cartPersistence(),
		...gatewayHandling(),

		init() {
			this.$watch('email', () => {
				if (!this.syncingFromCart) {
					this.syncPayButtons();
				}
			});
			this.$watch('shippingAddressId', () =>
				this.onSelectionChange('delivery')
			);
			this.$watch('useNewAddress', () => this.onSelectionChange('delivery'));
			this.$watch('shippingMethodHandle', () => {
				if (!this.syncingFromCart) {
					this.applySelectedMethodTotals();
					this.syncPayButtons();
					this.invalidatePaypalCheckout();
					this.invalidateStripeCheckout();
					this.saveIfValid('shipping');
				}
			});
			this.$watch('billingSameAsShipping', () =>
				this.onSelectionChange('payment')
			);
			this.$watch('billingAddressId', () => this.onSelectionChange('payment'));
			this.$watch('useNewBillingAddress', () => {
				this.onSelectionChange('payment');
				this.$nextTick(() => this.refreshNewBillingContent());
			});
			this.$watch('gatewayId', () => {
				this.$nextTick(() => {
					this.syncPayButtons();
					this.scheduleStripeReinit();
				});
			});
			this.$watch('canPay', () => {
				if (this.syncingFromCart) {
					return;
				}

				this.$nextTick(() => this.scheduleStripeReinit());
			});
			this.$nextTick(() => this.refreshNewBillingContent());
			this.syncPayButtons();
			this.$watch('paying', () => this.syncPayButtons());
			this.$watch(
				() => Number(this.totals.total) === 0,
				() => this.$nextTick(() => this.ensureAvailableGateway())
			);
			this.onPageShow = (event) => {
				if (event.persisted) {
					this.paying = false;
				}
			};
			window.addEventListener('pageshow', this.onPageShow);
			this.$nextTick(() => {
				if (this.cartHasShippingAddress) {
					this.lastSaved = JSON.stringify(this.buildPayload());
				}
				this.bindPayOverlay();
			});

			if (!this.cartHasShippingAddress && this.shippingAddressId) {
				this.saveIfValid('delivery');
			}
		},

		destroy() {
			if (this.saveTimer) {
				clearTimeout(this.saveTimer);
				this.saveTimer = null;
			}

			if (this.paypalInitTimer) {
				clearTimeout(this.paypalInitTimer);
				this.paypalInitTimer = null;
			}

			this.nextSave = null;
			this.saving = false;

			if (this.saveAbort) {
				this.saveAbort.abort();
				this.saveAbort = null;
			}

			if (this.onPageShow) {
				window.removeEventListener('pageshow', this.onPageShow);
				this.onPageShow = null;
			}

			if (this.originalAuthorizeHandler) {
				window.responseHandler = this.originalAuthorizeHandler;
				this.originalAuthorizeHandler = null;
			}

			if (this.originalSendPayment) {
				window.sendPaymentDataToAnet = this.originalSendPayment;
				this.originalSendPayment = null;
			}

			Object.values(this.panelStatusTimers).forEach((timer) => {
				clearTimeout(timer);
			});
			this.panelStatusTimers = {};
		},

		guestEmail() {
			const input = this.$root.querySelector('#email');
			if (input) {
				return String(input.value || '').trim();
			}

			return String(this.email || '').trim();
		},

		get hasShippingMethods() {
			return this.shippingMethods.length > 0;
		},

		get loadingShippingMethods() {
			return this.panelStatus.delivery === 'saving';
		},

		get hasEmail() {
			if (this.loggedIn) {
				return true;
			}

			return isValidEmail(this.email);
		},

		get hasShippingMethod() {
			if (!this.hasShippingMethods) {
				return !this.requireShippingMethod;
			}

			return Boolean(this.shippingMethodHandle);
		},

		get hasBilling() {
			if (this.billingSameAsShipping) {
				return true;
			}

			if (this.useNewBillingAddress) {
				return this.hasNewBillingContent;
			}

			return Boolean(this.billingAddressId);
		},

		get hasShippingSelection() {
			return Boolean(this.useNewAddress) || Boolean(this.shippingAddressId);
		},

		get deliveryReadyForPay() {
			if (!this.useNewAddress && this.shippingAddressId) {
				return true;
			}

			return this.panelFieldsReady('delivery');
		},

		// The pay button is disabled, so nothing submits to raise these the usual way

		get missingRequiredLabels() {
			const labels = [];

			this.$root.querySelectorAll('[data-fc-field]').forEach((element) => {
				const data = window.Alpine.$data(element);

				// validate() writes the field's errors, so the state is read rather than re-run
				if (!data?.label || !data.isRequired?.()) {
					return;
				}

				if (isEmptyValue(data.value ?? data.modelValue)) {
					labels.push(data.label);
				}
			});

			return labels;
		},

		// Required fields can be configured in any panel, so payment checks them all

		get checkoutFieldsReady() {
			return [...this.$root.querySelectorAll('[data-fc-panel]')].every(
				(section) => this.fieldsReadyIn(section)
			);
		},

		get canPay() {
			return (
				this.checkoutFieldsReady &&
				this.pending === 0 &&
				!this.saveTimer &&
				this.statusTone !== 'error' &&
				this.hasEmail &&
				this.hasShippingSelection &&
				this.cartHasShippingAddress &&
				this.hasShippingMethod &&
				this.hasBilling &&
				this.deliveryReadyForPay &&
				!this.loadingShippingMethods
			);
		},

		get payButtonLabel() {
			return `${this.payButtonText} ${this.totals.totalAsCurrency || ''}`.trim();
		},

		gatewayFits(el) {
			const availableAtZero = el?.dataset?.availableAtZero === '1';
			const zeroOnly = el?.dataset?.zeroOnly === '1';

			if (Number(this.totals.total) === 0) {
				return availableAtZero;
			}

			return !zeroOnly;
		},

		ensureAvailableGateway() {
			const form = this.$refs.paymentForm;
			if (!form) {
				return;
			}

			const rows = Array.from(form.querySelectorAll('[data-fc-gateway]'));
			const available = rows.filter((row) => this.gatewayFits(row));
			const currentOk = available.some(
				(row) =>
					Number(row.querySelector('input[name="gatewayId"]')?.value) ===
					Number(this.gatewayId)
			);
			if (currentOk) {
				return;
			}

			const next = available[0]?.querySelector('input[name="gatewayId"]');
			if (next) {
				this.gatewayId = Number(next.value);
			}
		},

		get processingMessage() {
			const selected = Number(this.gatewayId);
			const isManual = this.manualGatewayIds.some(
				(gatewayId) => Number(gatewayId) === selected
			);

			return isManual ? this.placingOrderLabel : this.processingLabel;
		},

		// The order total is left to the save that follows, which returns it formatted for the store

		applySelectedMethodTotals() {
			const method = this.shippingMethods.find(
				(item) => item.handle === this.shippingMethodHandle
			);
			if (!method) {
				return;
			}

			this.totals = {
				...this.totals,
				shipping: Number(method.price),
				shippingAsCurrency: method.priceAsCurrency,
			};
		},

		panelStatusLabel(panel) {
			const tone = this.panelStatus[panel];
			if (tone === 'saving') {
				return this.savingLabel;
			}

			if (tone === 'saved') {
				return this.savedLabel;
			}

			return '';
		},

		clearSavingPanel(panel) {
			if (!panel || this.panelStatus[panel] !== 'saving') {
				return;
			}

			this.setPanelStatus(panel, 'idle');
		},

		setPanelStatus(panel, tone) {
			if (!panel) {
				return;
			}

			if (this.panelStatusTimers[panel]) {
				clearTimeout(this.panelStatusTimers[panel]);
				this.panelStatusTimers = {
					...this.panelStatusTimers,
					[panel]: null,
				};
			}

			this.panelStatus = {
				...this.panelStatus,
				[panel]: tone,
			};

			// The page level status is screen reader only, so the panel keeps its own copy to show
			this.panelErrors = {
				...this.panelErrors,
				[panel]: tone === 'error' ? this.status : '',
			};

			if (tone !== 'saved') {
				return;
			}

			const timer = setTimeout(() => {
				if (this.panelStatus[panel] === 'saved') {
					this.panelStatus = {
						...this.panelStatus,
						[panel]: 'idle',
					};
				}
			}, 2500);

			this.panelStatusTimers = {
				...this.panelStatusTimers,
				[panel]: timer,
			};
		},

		onSelectionChange(panel = 'delivery') {
			if (this.syncingFromCart) {
				return;
			}

			if (panel === 'payment') {
				this.refreshNewBillingContent();
				this.syncPayButtons();
				return;
			}

			this.invalidatePaypalCheckout();
			this.invalidateStripeCheckout();
			this.saveIfValid(panel);
		},

		onDetailsChange(event) {
			if (event.target && event.target.name === 'email') {
				this.email = event.target.value;
			}
		},

		panelScope(panel) {
			if (panel === 'delivery' && this.useNewAddress) {
				return this.$root.querySelector('[data-fc-new-shipping]');
			}

			if (panel === 'payment' && this.useNewBillingAddress) {
				return this.$root.querySelector('[data-fc-new-billing]');
			}

			const section = this.$root.querySelector(`[data-fc-panel="${panel}"]`);
			return section
				? section.querySelector('[data-fc-collect]') || section
				: null;
		},

		panelFieldsReady(panel, handles = null, showRequired = false) {
			return this.fieldsReadyIn(this.panelScope(panel), showRequired, handles);
		},

		// panelScope narrows to the address being edited, which leaves a position's own fields unread

		panelSectionReady(panel, showRequired = false) {
			return this.fieldsReadyIn(
				this.$root.querySelector(`[data-fc-panel="${panel}"]`),
				showRequired
			);
		},

		fieldsReadyIn(scope, showRequired = false, handles = null) {
			if (!scope) {
				return true;
			}

			let ready = true;
			scope.querySelectorAll('[data-fc-field]').forEach((element) => {
				if (element === this.$root) {
					return;
				}

				const data = window.Alpine.$data(element);
				if (!data || typeof data.validate !== 'function') {
					return;
				}

				if (handles && !handles.has(this.addressFieldHandle(data))) {
					return;
				}

				if (!data.validate(showRequired)) {
					ready = false;
				}
			});

			return ready;
		},

		canSavePanel(panel) {
			if (
				panel === 'delivery' &&
				!this.useNewAddress &&
				this.shippingAddressId
			) {
				return this.panelSectionReady(panel);
			}

			if (panel === 'payment' && this.billingSameAsShipping) {
				return this.panelSectionReady(panel);
			}

			if (
				panel === 'payment' &&
				!this.useNewBillingAddress &&
				this.billingAddressId
			) {
				return this.panelSectionReady(panel);
			}

			if (panel === 'shipping') {
				return Boolean(this.shippingMethodHandle);
			}

			if (panel === 'delivery') {
				return this.panelFieldsReady(
					panel,
					new Set([
						'countryCode',
						'fullName',
						'addressLine1',
						'locality',
						'administrativeArea',
						'postalCode',
					])
				);
			}

			return this.panelFieldsReady(panel);
		},

		shippingRateKey(payload) {
			return [
				payload.shippingAddressId ?? '',
				payload.useNewAddress ?? '',
				payload['shippingAddress[countryCode]'] ?? '',
				payload['shippingAddress[administrativeArea]'] ?? '',
				payload['shippingAddress[postalCode]'] ?? '',
			].join('|');
		},

		deliveryNeedsSave(payload) {
			if (!this.lastSaved) {
				return true;
			}

			try {
				return (
					this.shippingRateKey(payload) !==
					this.shippingRateKey(JSON.parse(this.lastSaved))
				);
			} catch {
				return true;
			}
		},

		shouldTrackCheckout(saved) {
			return (
				this.klaviyoEnabled &&
				!this.loggedIn &&
				!this.trackedCheckout &&
				isValidEmail(saved.email || this.email)
			);
		},

		shouldSubscribe(saved) {
			return (
				this.klaviyoEnabled &&
				!this.loggedIn &&
				!this.subscribed &&
				String(saved.subscribe || '') === '1' &&
				Boolean(saved.list) &&
				isValidEmail(saved.email || this.email)
			);
		},

		async trackCheckoutStarted(saved, signal) {
			try {
				await this.postForm(
					this.postUrl(),
					{
						action: 'klaviyo-connect-plus/api/track',
						email: saved.email || this.email,
						'event[name]': 'Started Checkout',
						'event[trackOrder]': '1',
						'event[orderId]': String(this.cartId || ''),
					},
					signal
				);
			} catch {
				// Marketing is not worth failing a checkout over
				void 0;
			}
		},

		async subscribeToKlaviyo(saved, signal) {
			try {
				const { response, isJson } = await this.postForm(
					this.postUrl(),
					{
						action: 'klaviyo-connect-plus/api/track',
						email: saved.email || this.email,
						list: saved.list,
						subscribe: '1',
					},
					signal
				);
				if (isJson && response.ok) {
					this.subscribed = true;
				}
			} catch {
				// Marketing is not worth failing a checkout over
				void 0;
			}
		},
	};
};
