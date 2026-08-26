export const isValidEmail = (value) =>
	/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());

const asList = (value) =>
	Array.isArray(value) ? value : Object.values(value ?? {});

const CARD_FIELDS = ['number', 'month', 'year', 'cvv'];

const AUTHORIZE_ERROR_FIELDS = {
	E_WC_04: 'number',
	E_WC_05: 'number',
	E_WC_06: 'month',
	E_WC_07: 'year',
	E_WC_08: 'year',
	E_WC_15: 'cvv',
};

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
		latestShippingAddress: props.shippingAddress ?? null,
		latestBillingAddress: props.billingAddress ?? null,
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
		noAddressLabel: props.noAddressLabel ?? '',
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
		panelStatusTimers: {},
		queuedSavePanel: null,
		pending: 0,
		saveTimer: null,
		saveAbort: null,
		saving: false,
		nextSave: null,
		lastSaved: '',
		paypalInitTimer: null,
		paying: false,
		onPageShow: null,
		originalAuthorizeHandler: null,
		originalSendPayment: null,
		cardHolderPrefill: { firstName: '', lastName: '' },

		init() {
			this.$watch('email', () => {
				if (!this.syncingFromCart) {
					this.syncPayButtons();
				}
			});
			this.$watch('shippingAddressId', () => {
				this.onSelectionChange('delivery');
				this.$nextTick(() => this.prefillAuthorizeCardHolder());
			});
			this.$watch('useNewAddress', () => {
				this.onSelectionChange('delivery');
				this.$nextTick(() => this.prefillAuthorizeCardHolder());
			});
			this.$watch('shippingMethodHandle', () => {
				if (!this.syncingFromCart) {
					this.applySelectedMethodTotals();
					this.syncPayButtons();
					this.saveIfValid('shipping');
					this.closePaypalInlineCheckout();
				}
			});
			this.$watch('billingSameAsShipping', () => {
				this.onSelectionChange('payment');
				this.$nextTick(() => this.prefillAuthorizeCardHolder());
			});
			this.$watch('billingAddressId', () => {
				this.onSelectionChange('payment');
				this.$nextTick(() => this.prefillAuthorizeCardHolder());
			});
			this.$watch('useNewBillingAddress', () => {
				this.onSelectionChange('payment');
				this.$nextTick(() => {
					this.refreshNewBillingContent();
					this.prefillAuthorizeCardHolder();
				});
			});
			this.$watch('gatewayId', () => {
				this.$nextTick(() => this.prefillAuthorizeCardHolder());
			});
			this.$nextTick(() => this.refreshNewBillingContent());
			this.syncPayButtons();
			this.$watch('paying', () => this.syncPayButtons());
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
				this.prefillAuthorizeCardHolder();
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

		get canPay() {
			return (
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

		get processingMessage() {
			const selected = Number(this.gatewayId);
			const isManual = this.manualGatewayIds.some(
				(gatewayId) => Number(gatewayId) === selected
			);

			return isManual ? this.placingOrderLabel : this.processingLabel;
		},

		formatMoney(amount) {
			try {
				return new Intl.NumberFormat(undefined, {
					style: 'currency',
					currency: this.totals.currency || 'USD',
				}).format(amount);
			} catch {
				return String(amount);
			}
		},

		applySelectedMethodTotals() {
			const method = this.shippingMethods.find(
				(item) => item.handle === this.shippingMethodHandle
			);
			if (!method) {
				return;
			}

			const nextShipping = Number(method.price);
			const prevShipping = Number(this.totals.shipping);
			const prevTotal = Number(this.totals.total);
			const nextTotal =
				Number.isFinite(nextShipping) &&
				Number.isFinite(prevShipping) &&
				Number.isFinite(prevTotal)
					? prevTotal - prevShipping + nextShipping
					: null;

			const totals = {
				...this.totals,
				shipping: Number.isFinite(nextShipping)
					? nextShipping
					: this.totals.shipping,
				shippingAsCurrency: method.priceAsCurrency,
			};

			if (nextTotal !== null) {
				totals.total = nextTotal;
				totals.totalAsCurrency = this.formatMoney(nextTotal);
			}

			this.totals = totals;
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

		addressFieldHandle(data) {
			const name = String(data.name || '');
			const match = name.match(/\[([^\]]+)\]$/);

			return match ? match[1] : name;
		},

		panelFieldsReady(panel, handles = null, showRequired = false) {
			const scope = this.panelScope(panel);
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
				return true;
			}

			if (panel === 'payment' && this.billingSameAsShipping) {
				return true;
			}

			if (
				panel === 'payment' &&
				!this.useNewBillingAddress &&
				this.billingAddressId
			) {
				return true;
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

		saveIfValid(panel, event = null) {
			if (this.syncingFromCart) {
				return;
			}

			this.$nextTick(() => {
				if (this.syncingFromCart) {
					return;
				}

				if (panel === 'delivery') {
					this.refreshShippingPreview();
				}

				if (!this.canSavePanel(panel)) {
					return;
				}

				const payload = this.buildPayload();
				if (JSON.stringify(payload) === this.lastSaved) {
					return;
				}

				if (panel === 'delivery' && !this.deliveryNeedsSave(payload)) {
					return;
				}

				this.queueSave(0, event, panel);
			});
		},

		queueSave(delay = 400, event = null, panel = null) {
			if (
				event &&
				event.target &&
				event.target.closest('[data-fc-address-edit]')
			) {
				return;
			}

			if (this.useNewBillingAddress) {
				this.refreshNewBillingContent();
			}

			const fromEvent = event?.target?.closest('[data-fc-panel]');
			const nextPanel =
				(fromEvent && fromEvent.getAttribute('data-fc-panel')) ||
				panel ||
				this.queuedSavePanel ||
				'delivery';

			if (this.saveTimer) {
				clearTimeout(this.saveTimer);
			}

			if (this.queuedSavePanel && this.queuedSavePanel !== nextPanel) {
				this.clearSavingPanel(this.queuedSavePanel);
			}

			this.queuedSavePanel = nextPanel;
			this.setPanelStatus(nextPanel, 'saving');

			this.saveTimer = setTimeout(() => {
				const queuedPanel = this.queuedSavePanel;
				this.saveTimer = null;
				this.syncPayButtons();
				this.saveCart({ panel: queuedPanel });
			}, delay);

			this.syncPayButtons();
		},

		collectNamedFields(scope) {
			const payload = {};

			if (!scope) {
				return payload;
			}

			scope
				.querySelectorAll('input[name], select[name], textarea[name]')
				.forEach((element) => {
					const name = element.getAttribute('name');
					const type = element.type;
					const skipped = element.closest(
						'[data-fc-skip-collect], [data-fc-address-edit]'
					);

					if (!name || element.disabled) {
						return;
					}

					if (skipped && skipped !== scope) {
						return;
					}

					if (type === 'button' || type === 'submit') {
						return;
					}

					if ((type === 'checkbox' || type === 'radio') && !element.checked) {
						return;
					}

					if (name.endsWith('_radio') || name.endsWith('_display')) {
						return;
					}

					payload[name] = element.value;
				});

			return payload;
		},

		collectDetails() {
			const payload = {};

			this.$root.querySelectorAll('[data-fc-collect]').forEach((scope) => {
				Object.assign(payload, this.collectNamedFields(scope));
			});

			return payload;
		},

		addressGroupHasValues(payload, prefix) {
			return Object.keys(payload).some(
				(key) =>
					key.startsWith(prefix) && String(payload[key] || '').trim() !== ''
			);
		},

		addressGroupHasContent(payload, prefix) {
			return ['fullName', 'addressLine1', 'locality', 'postalCode'].some(
				(field) => String(payload[`${prefix}${field}]`] || '').trim() !== ''
			);
		},

		newBillingHasContent() {
			const scope = this.$root
				? this.$root.querySelector('[data-fc-new-billing]')
				: null;

			return this.addressGroupHasContent(
				this.collectNamedFields(scope),
				'billingAddress['
			);
		},

		refreshNewBillingContent() {
			this.hasNewBillingContent = this.newBillingHasContent();
			this.syncPayButtons();
		},

		stripAddressGroup(payload, prefix) {
			Object.keys(payload).forEach((key) => {
				if (key.startsWith(prefix)) {
					delete payload[key];
				}
			});
		},

		buildPayload(extra = {}) {
			const payload = {
				...this.collectDetails(),
				shippingMethodHandle: this.shippingMethodHandle || '',
				billingAddressSameAsShipping: this.billingSameAsShipping ? '1' : '0',
				...extra,
			};

			if (!this.loggedIn) {
				this.email = this.guestEmail();
				payload.email = this.email;
			}

			const hasShippingFields = this.addressGroupHasValues(
				payload,
				'shippingAddress['
			);

			if (!this.useNewAddress && this.shippingAddressId) {
				this.stripAddressGroup(payload, 'shippingAddress[');
				payload.shippingAddressId = String(this.shippingAddressId);
				payload.useNewAddress = '0';
			} else if (hasShippingFields) {
				delete payload.shippingAddressId;
				payload.useNewAddress = '1';
			} else {
				this.stripAddressGroup(payload, 'shippingAddress[');
				delete payload.shippingAddressId;
				delete payload.useNewAddress;
			}

			const hasBillingFields = this.addressGroupHasValues(
				payload,
				'billingAddress['
			);

			if (this.billingSameAsShipping) {
				this.stripAddressGroup(payload, 'billingAddress[');
				payload.billingAddressId = '';
				payload.useNewBillingAddress = '0';
			} else if (this.useNewBillingAddress && hasBillingFields) {
				payload.useNewBillingAddress = '1';
				payload.billingAddressId = '';
			} else if (this.billingAddressId) {
				this.stripAddressGroup(payload, 'billingAddress[');
				payload.billingAddressId = String(this.billingAddressId);
				payload.useNewBillingAddress = '0';
			} else {
				this.stripAddressGroup(payload, 'billingAddress[');
				delete payload.useNewBillingAddress;
				delete payload.billingAddressId;
			}

			return payload;
		},

		postUrl() {
			return window.location.pathname + window.location.search;
		},

		async postForm(url, fields, signal) {
			const body = new FormData();
			body.set(window.csrfTokenName, window.csrfTokenValue);

			Object.entries(fields).forEach(([key, value]) => {
				if (value === undefined || value === null) {
					return;
				}

				body.set(key, String(value));
			});

			const response = await fetch(url, {
				method: 'POST',
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
				body,
				credentials: 'same-origin',
				signal,
			});

			const text = await response.text();
			let data = {};
			let isJson = false;

			try {
				data = text ? JSON.parse(text) : {};
				isJson = true;
			} catch {
				isJson = false;
			}

			return { response, data, isJson };
		},

		flattenErrors(source, prefix = '') {
			const flattened = {};

			if (!source || typeof source !== 'object' || Array.isArray(source)) {
				return flattened;
			}

			Object.entries(source).forEach(([key, value]) => {
				const path = prefix ? `${prefix}.${key}` : key;

				if (Array.isArray(value)) {
					flattened[path] = value.map((item) => String(item));
					return;
				}

				if (value && typeof value === 'object') {
					Object.assign(flattened, this.flattenErrors(value, path));
					return;
				}

				if (value) {
					flattened[path] = [String(value)];
				}
			});

			return flattened;
		},

		errorKeyToName(key) {
			if (key.includes('[')) {
				return key;
			}

			const parts = key.split('.');
			if (parts.length === 1) {
				return key;
			}

			return `${parts[0]}[${parts.slice(1).join('][')}]`;
		},

		findNamedInput(name) {
			return this.$root.querySelector(`[name="${this.escapeName(name)}"]`);
		},

		setInputErrors(name, messages) {
			const input = this.findNamedInput(name);
			if (!input) {
				return;
			}

			const root = input.closest('[x-data]');
			if (!root || root === this.$root) {
				return;
			}

			const data = window.Alpine.$data(root);
			if (data && Array.isArray(data.errors)) {
				data.errors = messages;
			}
		},

		clearInputErrors() {
			this.couponError = '';
			this.notesError = '';
			this.$root
				.querySelectorAll(
					'[data-fc-collect] [x-data], [data-fc-address-edit] [x-data]'
				)
				.forEach((root) => {
					const data = window.Alpine.$data(root);
					if (data && Array.isArray(data.errors)) {
						data.errors = [];
					}
				});
		},

		applyFieldErrors(errors) {
			this.clearInputErrors();
			const flattened = this.flattenErrors(errors);
			const noteName = this.$refs.orderNote?.name;

			Object.entries(flattened).forEach(([key, messages]) => {
				const name = this.errorKeyToName(key);
				this.setInputErrors(name, messages);

				if (name === 'couponCode') {
					this.couponError = messages.join(' ');
				}

				if (noteName && name === noteName) {
					this.notesError = messages.join(' ');
				}
			});
		},

		collectResponseErrors(data) {
			return {
				...(data.errors || {}),
				...((data.cart && data.cart.errors) || {}),
			};
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

		applyErrors(data) {
			this.status = data.message || data.error || this.failedLabel;
			this.statusTone = 'error';
			this.applyFieldErrors(this.collectResponseErrors(data));
		},

		async saveCart(extra = {}) {
			if (!this.loggedIn && !this.hasEmail && !this.cartHasShippingAddress) {
				this.clearSavingPanel(extra.panel || this.queuedSavePanel);
				return;
			}

			if (this.saving) {
				this.nextSave = { ...(this.nextSave || {}), ...extra };
				this.setPanelStatus(
					extra.panel || this.queuedSavePanel || 'delivery',
					'saving'
				);
				return;
			}

			const panel = extra.panel || this.queuedSavePanel || 'delivery';
			const payload = { ...extra };
			delete payload.panel;
			const saved = this.buildPayload(payload);
			const noteName = this.$refs.orderNote?.name;
			const savingNotes = Boolean(noteName && Object.hasOwn(payload, noteName));

			if (
				Object.keys(payload).length === 0 &&
				JSON.stringify(saved) === this.lastSaved
			) {
				this.clearSavingPanel(panel);
				return;
			}

			this.queuedSavePanel = null;

			this.setPanelStatus(panel, 'saving');
			this.saving = true;
			this.saveAbort = new AbortController();
			const { signal } = this.saveAbort;

			this.pending += 1;
			this.status = this.savingLabel;
			this.statusTone = 'saving';
			this.syncPayButtons();

			try {
				const fields = {
					action: 'commerce/cart/update-cart',
					...saved,
				};
				const trackCheckout = this.shouldTrackCheckout(saved);
				const subscribe = this.shouldSubscribe(saved);
				const useKlaviyo = trackCheckout || subscribe;

				if (useKlaviyo) {
					fields.action = 'klaviyo-connect-plus/api/track';
					fields.forward = '/commerce/cart/update-cart';

					if (trackCheckout) {
						fields['event[name]'] = 'Started Checkout';
						fields['event[trackOrder]'] = '1';
						fields['event[orderId]'] = String(this.cartId || '');
					}
				}

				const { response, data, isJson } = await this.postForm(
					this.postUrl(),
					fields,
					signal
				);

				if (!isJson) {
					this.status = this.failedLabel;
					this.statusTone = 'error';
					this.setPanelStatus(panel, 'error');
					this.markNotesError(savingNotes);
					return;
				}

				if (!response.ok || data.success === false) {
					this.applyErrors(data);
					this.setPanelStatus(panel, 'error');
					this.markNotesError(savingNotes);
					return;
				}

				if (trackCheckout) {
					this.trackedCheckout = true;
				}

				if (subscribe) {
					this.subscribed = true;
				}

				const cart = data.cart || data.model || (data.data && data.data.cart);
				this.applyCart(cart, this.shippingRateKey(saved));

				const couponError = this.couponRejectedMessage(saved, cart);
				if (couponError) {
					this.applyFieldErrors({
						couponCode: [couponError],
					});
					this.couponOpen = true;
					this.setPanelStatus(panel, 'error');
					this.status = '';
					this.statusTone = 'idle';
					return;
				}

				this.clearInputErrors();
				this.status = this.savedLabel;
				this.statusTone = 'saved';
				this.setPanelStatus(panel, 'saved');
				this.lastSaved = JSON.stringify(saved);
				if (savingNotes) {
					this.notesButtonVisible = false;
				}
			} catch (error) {
				if (error.name === 'AbortError') {
					return;
				}

				this.status = this.failedLabel;
				this.statusTone = 'error';
				this.setPanelStatus(panel, 'error');
				this.markNotesError(savingNotes);
			} finally {
				this.saving = false;
				this.pending = Math.max(0, this.pending - 1);
				this.syncPayButtons();

				const next = this.nextSave;
				this.nextSave = null;
				if (next) {
					await this.saveCart(next);
				}
			}
		},

		applyCoupon() {
			const code = String(this.couponInput || '').trim();
			if (!code) {
				return;
			}

			this.couponError = '';
			this.closePaypalInlineCheckout();

			return this.saveCart({
				couponCode: code,
				panel: 'coupon',
			});
		},

		removeCoupon() {
			this.couponInput = '';
			this.couponError = '';
			this.closePaypalInlineCheckout();

			return this.saveCart({
				couponCode: '',
				panel: 'coupon',
			});
		},

		saveNotes() {
			const input = this.$refs.orderNote;
			if (!input?.name) {
				return;
			}

			this.notesError = '';

			return this.saveCart({
				[input.name]: input.value,
				panel: 'notes',
			});
		},

		markNotesError(savingNotes) {
			if (!savingNotes) {
				return;
			}

			if (!this.notesError) {
				this.notesError = this.status || this.failedLabel;
			}

			this.notesButtonVisible = true;
		},

		couponRejectedMessage(saved, cart) {
			if (saved.couponCode === undefined || saved.couponCode === '') {
				return '';
			}

			const live = (cart && cart.fosterCheckout) || {};
			if (live.couponCodeError) {
				return String(live.couponCodeError);
			}

			const applied = String((cart && cart.couponCode) || '');
			if (applied.toLowerCase() === String(saved.couponCode).toLowerCase()) {
				return '';
			}

			return this.failedLabel;
		},

		addressLabel(addressId, fallback) {
			return this.addressLabels[String(addressId)] || fallback;
		},

		escapeName(name) {
			return typeof CSS !== 'undefined' && CSS.escape
				? CSS.escape(name)
				: name.replaceAll('"', '\\"');
		},

		addressToFields(address) {
			const fields = {};
			if (!address || typeof address !== 'object') {
				return fields;
			}

			[
				'fullName',
				'firstName',
				'lastName',
				'organization',
				'addressLine1',
				'addressLine2',
				'locality',
				'dependentLocality',
				'administrativeArea',
				'postalCode',
				'countryCode',
			].forEach((key) => {
				if (address[key] != null && address[key] !== '') {
					fields[key] = String(address[key]);
				}
			});

			return fields;
		},

		countryLabel(address, fields, scope) {
			if (address) {
				if (address.countryName) {
					return address.countryName;
				}

				if (address.country && address.country.name) {
					return address.country.name;
				}
			}

			const countrySelector =
				'[name="countryCode"], [name="shippingAddress[countryCode]"], [name="billingAddress[countryCode]"]';
			const countryInput = scope ? scope.querySelector(countrySelector) : null;
			const selectRoot = countryInput ? countryInput.closest('[x-data]') : null;
			const selectData = selectRoot ? window.Alpine.$data(selectRoot) : null;
			if (
				selectData &&
				selectData.selectedOption &&
				selectData.selectedOption.label
			) {
				return selectData.selectedOption.label;
			}

			return (fields && fields.countryCode) || '';
		},

		addressFieldsFromPayload(payload, prefix) {
			const fields = {};

			Object.entries(payload).forEach(([key, value]) => {
				if (!key.startsWith(prefix) || !key.endsWith(']')) {
					return;
				}

				fields[key.slice(prefix.length, -1)] = value;
			});

			return fields;
		},

		refreshShippingPreview() {
			if (!this.useNewAddress && this.shippingAddressId) {
				const label = this.addressLabel(this.shippingAddressId, '');
				if (label) {
					this.shippingPreview = label;
				}
				return;
			}

			const scope = this.$root.querySelector('[data-fc-new-shipping]');
			if (!scope) {
				return;
			}

			const preview = this.formatAddress(
				this.addressFieldsFromPayload(
					this.collectNamedFields(scope),
					'shippingAddress['
				),
				scope
			);

			if (preview) {
				this.shippingPreview = preview;
			}
		},

		formatAddress(fields, scope, address = null) {
			if (!fields || typeof fields !== 'object') {
				return '';
			}

			return [
				fields.fullName,
				fields.addressLine1,
				fields.addressLine2,
				fields.locality,
				fields.administrativeArea,
				fields.postalCode,
				this.countryLabel(address, fields, scope),
			]
				.map((part) => String(part || '').trim())
				.filter(Boolean)
				.join(', ');
		},

		rememberAddress(addressId, address, fields, scope) {
			if (!addressId) {
				return;
			}

			const id = String(addressId);
			const snapshot = { ...(fields || this.addressToFields(address)) };
			delete snapshot.action;
			delete snapshot.addressId;
			this.addressFields = {
				...this.addressFields,
				[id]: snapshot,
			};
			this.addressLabels = {
				...this.addressLabels,
				[id]: this.formatAddress(snapshot, scope, address),
			};
		},

		writeAddressToScope(scope, fields, prefix = '') {
			if (!scope || !fields) {
				return;
			}

			const formRoot = scope.querySelector('[x-data]');
			const formData = formRoot ? window.Alpine.$data(formRoot) : null;
			if (formData) {
				if (fields.countryCode) {
					formData.countryCode = fields.countryCode;
				}
				if (Object.hasOwn(fields, 'administrativeArea')) {
					formData.administrativeArea = fields.administrativeArea;
				}
			}

			Object.entries(fields).forEach(([name, value]) => {
				const inputName = prefix ? `${prefix}[${name}]` : name;
				const input = scope.querySelector(
					`[name="${this.escapeName(inputName)}"]`
				);
				if (!input) {
					return;
				}

				input.value = value;
				const data = window.Alpine.$data(input.closest('[x-data]'));
				if (!data) {
					return;
				}

				if (Object.hasOwn(data, 'value')) {
					data.value = value;
				}

				if (Object.hasOwn(data, 'modelValue')) {
					data.modelValue = value;
				}
			});
		},

		applyAddressFields(addressId) {
			const stored = this.addressFields[String(addressId)];
			if (!stored) {
				return;
			}

			this.$nextTick(() => {
				const scope = this.$root.querySelector(
					`[data-fc-address-edit="${addressId}"]`
				);
				this.writeAddressToScope(scope, stored);
			});
		},

		applyDraftAddress(kind) {
			const address =
				kind === 'billing'
					? this.latestBillingAddress
					: this.latestShippingAddress;
			if (!address) {
				return;
			}

			const prefix = kind === 'billing' ? 'billingAddress' : 'shippingAddress';
			const selector =
				kind === 'billing' ? '[data-fc-new-billing]' : '[data-fc-new-shipping]';

			this.$nextTick(() => {
				const scope = this.$root.querySelector(selector);
				this.writeAddressToScope(scope, this.addressToFields(address), prefix);
				if (kind === 'billing') {
					this.refreshNewBillingContent();
				}

				if (kind === 'shipping') {
					this.refreshShippingPreview();
				}
			});
		},

		applyCart(cart, savedKey = '') {
			if (!cart || typeof cart !== 'object') {
				return;
			}

			const previousHandle = this.shippingMethodHandle;
			const cartHandle = cart.shippingMethodHandle || '';
			const sameAddress =
				!savedKey || savedKey === this.shippingRateKey(this.buildPayload());
			this.syncingFromCart = true;

			try {
				if (!this.loggedIn && cart.email) {
					this.email = cart.email;
				}

				if (Object.hasOwn(cart, 'couponCode')) {
					const nextCode = cart.couponCode || '';
					const keepDraft = this.couponInput !== this.couponCode;
					this.couponCode = nextCode;
					if (!keepDraft) {
						this.couponInput = nextCode;
					}
				}

				if ('shippingAddressId' in cart || cart.shippingAddress) {
					this.cartHasShippingAddress = Boolean(
						cart.shippingAddressId || cart.shippingAddress
					);
				}

				const live = cart.fosterCheckout || {};

				if (sameAddress) {
					if (typeof live.shippingPreview === 'string') {
						this.shippingPreview = live.shippingPreview;
					}

					if (
						cart.shippingAddress &&
						typeof cart.shippingAddress === 'object'
					) {
						this.latestShippingAddress = cart.shippingAddress;
						this.rememberAddress(
							cart.sourceShippingAddressId,
							cart.shippingAddress
						);
					}

					if (Array.isArray(live.shippingMethods)) {
						this.shippingMethods = live.shippingMethods;
					}

					const handles = this.shippingMethods.map((method) => method.handle);
					const liveHandle =
						typeof live.shippingMethodHandle === 'string'
							? live.shippingMethodHandle
							: cartHandle;

					if (previousHandle && handles.includes(previousHandle)) {
						this.shippingMethodHandle = previousHandle;
					} else if (handles.includes(liveHandle)) {
						this.shippingMethodHandle = liveHandle;
					} else {
						this.shippingMethodHandle = handles[0] || '';
					}

					if (live.totals && typeof live.totals === 'object') {
						this.totals = {
							discounts: [],
							vouchers: [],
							...live.totals,
						};
					}
				}

				if (cart.billingAddress && typeof cart.billingAddress === 'object') {
					this.latestBillingAddress = cart.billingAddress;
					this.rememberAddress(
						cart.sourceBillingAddressId,
						cart.billingAddress
					);
				}
			} finally {
				this.syncingFromCart = false;
			}

			this.syncPayButtons();
			this.$nextTick(() => this.prefillAuthorizeCardHolder());
		},

		namePartsFromAddress(address) {
			if (!address || typeof address !== 'object') {
				return { firstName: '', lastName: '' };
			}

			let firstName = String(
				address.firstName || address.givenName || ''
			).trim();
			let lastName = String(
				address.lastName || address.familyName || ''
			).trim();

			if (!firstName && !lastName) {
				const fullName = String(address.fullName || '').trim();
				if (fullName) {
					const parts = fullName.split(/\s+/);
					firstName = parts[0] || '';
					lastName = parts.slice(1).join(' ');
				}
			}

			return { firstName, lastName };
		},

		cardHolderNameSource() {
			if (this.billingSameAsShipping) {
				if (this.latestShippingAddress) {
					return this.namePartsFromAddress(this.latestShippingAddress);
				}

				if (!this.useNewAddress && this.shippingAddressId) {
					return this.namePartsFromAddress(
						this.addressFields[String(this.shippingAddressId)]
					);
				}

				const scope = this.$root.querySelector('[data-fc-new-shipping]');
				return this.namePartsFromAddress(
					this.addressFieldsFromPayload(
						this.collectNamedFields(scope),
						'shippingAddress['
					)
				);
			}

			if (this.latestBillingAddress) {
				return this.namePartsFromAddress(this.latestBillingAddress);
			}

			if (!this.useNewBillingAddress && this.billingAddressId) {
				return this.namePartsFromAddress(
					this.addressFields[String(this.billingAddressId)]
				);
			}

			const scope = this.$root.querySelector('[data-fc-new-billing]');
			return this.namePartsFromAddress(
				this.addressFieldsFromPayload(
					this.collectNamedFields(scope),
					'billingAddress['
				)
			);
		},

		canReplaceCardHolderValue(input, prefilled) {
			if (!input) {
				return false;
			}

			const current = String(input.value || '').trim();
			return current === '' || current === prefilled;
		},

		prefillAuthorizeCardHolder() {
			const form = this.$refs.paymentForm;
			if (!form) {
				return;
			}

			const firstInput = form.querySelector('.card-holder-first-name');
			const lastInput = form.querySelector('.card-holder-last-name');
			if (!firstInput && !lastInput) {
				return;
			}

			const { firstName, lastName } = this.cardHolderNameSource();
			if (!firstName && !lastName) {
				return;
			}

			const previous = this.cardHolderPrefill;
			const nextPrefill = { ...previous };

			if (
				this.canReplaceCardHolderValue(firstInput, previous.firstName) &&
				firstName
			) {
				firstInput.value = firstName;
				nextPrefill.firstName = firstName;
			}

			if (
				this.canReplaceCardHolderValue(lastInput, previous.lastName) &&
				lastName
			) {
				lastInput.value = lastName;
				nextPrefill.lastName = lastName;
			}

			this.cardHolderPrefill = nextPrefill;
		},

		async saveAddressBook(addressId) {
			if (!addressId) {
				return;
			}

			if (this.saveTimer) {
				clearTimeout(this.saveTimer);
				this.saveTimer = null;
			}

			const scope = this.$root.querySelector(
				`[data-fc-address-edit="${addressId}"]`
			);
			const fields = {
				action: 'users/save-address',
				...this.collectNamedFields(scope),
				addressId: String(addressId),
			};

			const panel =
				parseInt(this.editBillingAddressId, 10) === parseInt(addressId, 10)
					? 'payment'
					: 'delivery';

			this.pending += 1;
			this.status = this.savingLabel;
			this.statusTone = 'saving';
			this.setPanelStatus(panel, 'saving');
			this.syncPayButtons();

			try {
				const { response, data, isJson } = await this.postForm(
					this.postUrl(),
					fields
				);
				if (!isJson || !response.ok || data.success === false) {
					if (isJson) {
						this.applyErrors(data);
					} else {
						this.status = this.failedLabel;
						this.statusTone = 'error';
					}
					this.setPanelStatus(panel, 'error');
					return;
				}

				this.rememberAddress(addressId, null, fields, scope);
				if (parseInt(this.shippingAddressId, 10) === parseInt(addressId, 10)) {
					this.shippingPreview = this.addressLabels[String(addressId)];
				}

				await this.saveCart({ panel });
				if (this.statusTone !== 'error') {
					this.editExistingAddress = 0;
					this.editBillingAddressId = 0;
				} else {
					this.setPanelStatus(panel, 'error');
				}
			} catch {
				this.status = this.failedLabel;
				this.statusTone = 'error';
				this.setPanelStatus(panel, 'error');
			} finally {
				this.pending = Math.max(0, this.pending - 1);
				this.syncPayButtons();
			}
		},

		async onPaySubmit(event) {
			if (event.submitter?.id?.endsWith('authorizeSubmit')) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();

			if (!this.deliveryReadyForPay) {
				this.panelFieldsReady('delivery', null, true);
				return;
			}

			if (
				!this.hasEmail ||
				!this.hasShippingSelection ||
				!this.cartHasShippingAddress ||
				!this.hasShippingMethod ||
				!this.hasBilling
			) {
				this.panelFieldsReady('delivery', null, true);
				return;
			}

			const payloadChanged =
				JSON.stringify(this.buildPayload()) !== this.lastSaved;

			this.paying = true;

			if (this.saveTimer || payloadChanged) {
				if (this.saveTimer) {
					clearTimeout(this.saveTimer);
					this.saveTimer = null;
				}

				await this.saveCart({ panel: 'payment' });
				if (this.statusTone === 'error') {
					this.paying = false;
					return;
				}
			}

			this.lastSaved = JSON.stringify(this.buildPayload());
			this.$refs.paymentForm?.submit();
		},

		bindPayOverlay() {
			const form = this.$refs.paymentForm;
			if (!form || form.dataset.fcPayOverlay === 'true') {
				return;
			}

			form.dataset.fcPayOverlay = 'true';
			form.addEventListener(
				'click',
				(event) => {
					const button = event.target.closest('[id$="authorizeSubmit"]');
					if (!button || !this.canPay) {
						return;
					}

					this.wrapAuthorizeHandler();
				},
				true
			);
			form.addEventListener('input', (event) => {
				const input = event.target;
				if (
					input &&
					CARD_FIELDS.some((name) => input.id && input.id.endsWith(name))
				) {
					this.clearCardError(input);
				}
			});
		},

		wrapAuthorizeHandler() {
			if (!this.cardInput('authorizeSubmit')) {
				return;
			}

			this.wrapCardFields();
			const checkout = this;

			if (
				!this.originalSendPayment &&
				typeof window.sendPaymentDataToAnet === 'function'
			) {
				this.originalSendPayment = window.sendPaymentDataToAnet;
				window.sendPaymentDataToAnet = async function (...args) {
					if (!checkout.checkCard()) {
						checkout.paying = false;
						return;
					}

					checkout.paying = true;

					if (checkout.saveTimer) {
						clearTimeout(checkout.saveTimer);
						checkout.saveTimer = null;
					}

					const payloadChanged =
						JSON.stringify(checkout.buildPayload()) !== checkout.lastSaved;

					if (payloadChanged || checkout.saving) {
						await checkout.saveCart({ panel: 'payment' });
						if (checkout.statusTone === 'error') {
							checkout.paying = false;
							return;
						}
					}

					return checkout.originalSendPayment.apply(this, args);
				};
			}

			if (
				this.originalAuthorizeHandler ||
				typeof window.responseHandler !== 'function'
			) {
				return;
			}

			this.originalAuthorizeHandler = window.responseHandler;
			window.responseHandler = function (...args) {
				const response = args[0];
				if (response?.messages?.resultCode === 'Error') {
					checkout.paying = false;
					checkout.showAuthorizeErrors(response);
					return;
				}

				return checkout.originalAuthorizeHandler.apply(this, args);
			};
		},

		cardInput(name) {
			const form = this.$refs.paymentForm;
			if (!form) {
				return null;
			}

			return form.querySelector(`[id$="${name}"]`);
		},

		wrapCardFields() {
			CARD_FIELDS.forEach((name) => {
				const input = this.cardInput(name);
				if (!input?.parentElement || input.parentElement.dataset.fcCardField) {
					return;
				}

				const wrap = document.createElement('div');
				wrap.dataset.fcCardField = name;
				wrap.className = 'min-w-0';
				input.parentElement.insertBefore(wrap, input);
				wrap.appendChild(input);
			});
		},

		clearCardError(input) {
			if (!input) {
				return;
			}

			input.classList.remove('border-red-500');
			input.removeAttribute('aria-invalid');
			const wrap = input.parentElement;
			const note =
				wrap && wrap.dataset.fcCardField
					? wrap.querySelector('[data-fc-card-error]')
					: input.nextElementSibling;
			if (note && note.dataset.fcCardError) {
				note.remove();
			}
		},

		clearCardErrors() {
			CARD_FIELDS.forEach((name) => {
				this.clearCardError(this.cardInput(name));
			});
		},

		showCardErrors(errors) {
			this.clearCardErrors();
			this.wrapCardFields();

			let first = null;
			Object.entries(errors).forEach(([name, message]) => {
				const input = this.cardInput(name);
				if (!input || !message) {
					return;
				}

				input.classList.add('border-red-500');
				input.setAttribute('aria-invalid', 'true');
				const note = document.createElement('p');
				note.dataset.fcCardError = name;
				note.className = 'mt-1 text-sm leading-snug text-red-500';
				note.textContent = message;
				input.parentElement.appendChild(note);
				if (!first) {
					first = input;
				}
			});

			if (first) {
				first.focus();
			}
		},

		checkCard() {
			const number = String(this.cardInput('number')?.value || '').replace(
				/\s+/g,
				''
			);
			const monthValue = String(this.cardInput('month')?.value || '').trim();
			const yearValue = String(this.cardInput('year')?.value || '').trim();
			const cvv = String(this.cardInput('cvv')?.value || '').replace(
				/\s+/g,
				''
			);
			const month = parseInt(monthValue, 10);
			const errors = {};

			if (!/^\d{13,19}$/.test(number)) {
				errors.number = this.cardNumberError;
			}

			if (!monthValue || month < 1 || month > 12) {
				errors.month = this.cardMonthError;
			}

			if (!/^\d{2}$|^\d{4}$/.test(yearValue)) {
				errors.year = this.cardYearError;
			} else if (!errors.month) {
				const year =
					yearValue.length === 2
						? 2000 + parseInt(yearValue, 10)
						: parseInt(yearValue, 10);
				const expires = new Date(year, month, 0, 23, 59, 59);
				if (expires < new Date()) {
					errors.year = this.cardExpiredError;
				}
			}

			if (!/^\d{3,4}$/.test(cvv)) {
				errors.cvv = this.cardCvvError;
			}

			this.showCardErrors(errors);
			return Object.keys(errors).length === 0;
		},

		showAuthorizeErrors(response) {
			const labels = {
				E_WC_05: this.cardNumberError,
				E_WC_06: this.cardMonthError,
				E_WC_07: this.cardYearError,
				E_WC_08: this.cardExpiredError,
				E_WC_15: this.cardCvvError,
			};
			const errors = {};

			(response.messages.message || []).forEach((item) => {
				const name = AUTHORIZE_ERROR_FIELDS[item.code] || 'number';
				errors[name] = labels[item.code] || item.text || this.cardNumberError;
			});

			this.showCardErrors(errors);
		},

		syncPayButtons() {
			const form = this.$refs.paymentForm;
			if (!form) {
				return;
			}

			const allowed = this.canPay && !this.paying;
			form
				.querySelectorAll('button[type="submit"], [id$="authorizeSubmit"]')
				.forEach((button) => {
					button.disabled = !allowed;
				});
		},

		closePaypalInlineCheckout() {
			const wrapper = this.$root.querySelector('.paypal-rest-form');
			const renderDiv = wrapper?.firstElementChild;
			if (
				!wrapper ||
				!renderDiv ||
				typeof window.initPaypalCheckout !== 'function'
			) {
				return;
			}

			if (
				!renderDiv.childElementCount &&
				!String(renderDiv.innerHTML || '').trim()
			) {
				return;
			}

			renderDiv.innerHTML = '';
			window.initPaypalCheckout();
		},

		initPaypal(attempt = 0) {
			if (this.paypalInitTimer) {
				clearTimeout(this.paypalInitTimer);
				this.paypalInitTimer = null;
			}

			const wrapper = this.$root.querySelector('.paypal-rest-form');
			if (!wrapper) {
				if (attempt >= 5) {
					return;
				}

				this.paypalInitTimer = setTimeout(() => {
					this.initPaypal(attempt + 1);
				}, 200);
				return;
			}

			if (wrapper.dataset.fcPaypalInit === 'true') {
				return;
			}

			if (
				typeof window.paypal_checkout_sdk === 'undefined' ||
				typeof window.initPaypalCheckout !== 'function' ||
				!wrapper.firstElementChild
			) {
				if (attempt >= 50) {
					return;
				}

				this.paypalInitTimer = setTimeout(() => {
					this.initPaypal(attempt + 1);
				}, 200);
				return;
			}

			wrapper.dataset.fcPaypalInit = 'true';
			window.initPaypalCheckout();
		},
	};
};
