/**
 * Building the cart payload, posting it, and applying what comes back.
 */
export const cartPersistence = () => ({
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

			if (Array.isArray(value)) {
				value.forEach((entry) => body.append(key, String(entry)));
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
		const force = Boolean(payload.force);
		delete payload.force;
		const saved = this.buildPayload(payload);
		const noteName = this.$refs.orderNote?.name;
		const savingNotes = Boolean(noteName && Object.hasOwn(payload, noteName));

		if (
			!force &&
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

		let cartSynced = false;

		try {
			const fields = {
				action: 'commerce/cart/update-cart',
				...saved,
			};
			const trackCheckout = this.shouldTrackCheckout(saved);
			const subscribe = this.shouldSubscribe(saved);

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
				this.trackCheckoutStarted(saved, signal);
			}

			if (subscribe) {
				await this.subscribeToKlaviyo(saved, signal);
			}

			const cart = data.cart || data.model || (data.data && data.data.cart);
			this.applyCart(cart, this.shippingRateKey(saved));
			cartSynced = true;

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
			} else if (cartSynced) {
				this.maybeReinitPaypalCheckout();
				this.scheduleStripeReinit();
			}
		}
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

				if (cart.shippingAddress && typeof cart.shippingAddress === 'object') {
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
				this.rememberAddress(cart.sourceBillingAddressId, cart.billingAddress);
			}
		} finally {
			this.syncingFromCart = false;
		}

		this.syncPayButtons();
		this.$nextTick(() => {
			this.ensureAvailableGateway();
			this.scheduleStripeReinit();
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
				this.restorePaypalIfSkipped();
				this.restoreStripeIfSkipped();
				return;
			}

			if (panel === 'delivery' && !this.deliveryNeedsSave(payload)) {
				this.restorePaypalIfSkipped();
				this.restoreStripeIfSkipped();
				return;
			}

			this.queueSave(0, event, panel);
		});
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

				if (name.endsWith('[]')) {
					payload[name] = [...(payload[name] ?? []), element.value];
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
		// Craft reports a custom field error against field:handle; its input is named fields[handle]
		if (key.startsWith('field:')) {
			return `fields[${key.slice(6)}]`;
		}

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

	applyErrors(data) {
		this.status = data.message || data.error || this.failedLabel;
		this.statusTone = 'error';
		this.applyFieldErrors(this.collectResponseErrors(data));
	},

	applyCoupon() {
		const code = String(this.couponInput || '').trim();
		if (!code) {
			return;
		}

		this.couponError = '';
		this.invalidatePaypalCheckout();
		this.invalidateStripeCheckout();

		return this.saveCart({
			couponCode: code,
			panel: 'coupon',
		});
	},

	removeCoupon() {
		this.couponInput = '';
		this.couponError = '';
		this.invalidatePaypalCheckout();
		this.invalidateStripeCheckout();

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
});
