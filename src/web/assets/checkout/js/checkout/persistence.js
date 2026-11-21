import { fieldValue, setServerErrors } from './field-errors.js';

// The panel an error with no input belongs to, by the error key's first segment
const ERROR_KEY_PANELS = {
	shippingAddress: 'delivery',
	billingAddress: 'payment',
};

const INPUT_ERROR = {
	placed: 'placed',
	stale: 'stale',
	noInput: 'noInput',
};

// Compare as sorted lists, since a ticked checkbox group posts both an empty value and an array
const valueList = (value) =>
	[]
		.concat(value ?? [])
		.map(String)
		.filter((item) => item !== '')
		.sort();

const sameValue = (current, posted) =>
	valueList(current).join('\u0000') === valueList(posted).join('\u0000');

const keyPrefix = (key) => key.split(/[.[]/)[0];

const errorKeyPanel = (key) => ERROR_KEY_PANELS[keyPrefix(key)] ?? null;

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

		// A store selling to one country has that country already chosen, which is not an address to save
		const hasShippingFields = this.addressGroupHasContent(
			payload,
			'shippingAddress['
		);

		if (this.pickupAvailable) {
			payload.shippingPickup = this.shippingPickup ? '1' : '0';
		}

		if (this.shippingPickup) {
			this.stripAddressGroup(payload, 'shippingAddress[');
			delete payload.shippingAddressId;
			delete payload.useNewAddress;
		} else if (!this.useNewAddress && this.shippingAddressId) {
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

		const hasBillingFields = this.addressGroupHasContent(
			payload,
			'billingAddress['
		);

		if (this.billingSameAsShipping) {
			this.stripAddressGroup(payload, 'billingAddress[');
			payload.billingAddressId = '';
			payload.useNewBillingAddress = '0';
		} else if (
			this.useNewBillingAddress &&
			hasBillingFields &&
			// Leave out a half-typed billing address, since it would fail another panel's save
			this.panelFieldsReady('payment')
		) {
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

		// A refused change on a paid cart that just completed names the confirmation page
		if (!response.ok && typeof data.redirect === 'string') {
			window.location.assign(data.redirect);
		}

		return { response, data, isJson };
	},

	// Posted to the checkout URL, not /actions, so the plugin attaches live totals to the response.
	async applyVoucher() {
		const code = this.voucherCode.trim();

		if (code === '' || this.applyingVoucher) {
			return;
		}

		this.applyingVoucher = true;
		this.voucherError = '';

		const vouchersBefore = this.totals.vouchers.length;

		try {
			const { response, data, isJson } = await this.postForm(this.postUrl(), {
				action: 'foster-checkout/voucher/add-code',
				voucherCode: code,
			});

			if (!isJson || !response.ok || data.success === false) {
				this.voucherError = this.voucherErrorMessage(data, isJson);
				return;
			}

			const cart = data.cart || data.model;
			this.applyCart(cart);

			// A site rule can strip the code after the request reported success
			const applied = this.totals.vouchers.length > vouchersBefore;

			if (!applied) {
				this.voucherError =
					String((cart?.fosterCheckout || {}).voucherCodeError || '') ||
					this.voucherFailedLabel;
				return;
			}

			this.voucherCode = '';
		} finally {
			this.applyingVoucher = false;
		}
	},

	async removeVoucher(code) {
		if (this.removingVoucher !== '') {
			return;
		}

		this.removingVoucher = code;
		this.removeVoucherError = '';

		try {
			const { response, data, isJson } = await this.postForm(this.postUrl(), {
				action: 'gift-voucher/cart/remove-code',
				voucherCode: code,
			});

			if (!isJson || !response.ok || data.success === false) {
				this.removeVoucherError = this.voucherErrorMessage(data, isJson);
				return;
			}

			this.applyCart(data.cart || data.model);
		} finally {
			this.removingVoucher = '';
		}
	},

	voucherErrorMessage(data, isJson) {
		if (!isJson) {
			return this.voucherFailedLabel;
		}

		const fieldErrors = data.errors?.voucherCode;

		return fieldErrors?.[0] || data.error || this.voucherFailedLabel;
	},

	// Wait for a guest's email, since their cart is saved against it
	canSaveCart() {
		return this.loggedIn || this.hasEmail || this.cartHasShippingAddress;
	},

	async saveCart(extra = {}) {
		const panels = extra.panels?.length
			? extra.panels
			: [extra.panel || 'delivery'];

		if (!this.canSaveCart()) {
			this.status = this.emailNeededLabel;
			this.statusTone = 'error';
			panels.forEach((panel) => this.clearSavingPanel(panel));
			this.setPanelStatus('contact', 'error');

			return;
		}

		if (this.saving) {
			// Merge into a full save unless both saves are field-only
			const fieldOnly =
				Boolean(extra.fieldOnly) && (this.nextSave?.fieldOnly ?? true);
			const queued = { ...(this.nextSave || {}) };
			// Drop a queued value under the key's other form, since a checkbox group posts `name` when empty and `name[]` when ticked
			Object.keys(extra).forEach((name) => {
				delete queued[name.endsWith('[]') ? name.slice(0, -2) : `${name}[]`];
			});
			// Keep both saves' panels, since `panels` would otherwise replace a queued `panel`
			const queuedPanels = queued.panels?.length
				? queued.panels
				: [queued.panel].filter(Boolean);
			this.nextSave = {
				...queued,
				...extra,
				fieldOnly,
				panels: [...new Set([...queuedPanels, ...panels])],
			};
			this.setPanelsStatus(panels, 'saving');
			return;
		}

		const payload = { ...extra };
		delete payload.panel;
		delete payload.panels;
		const force = Boolean(payload.force);
		delete payload.force;
		const fieldOnly = Boolean(payload.fieldOnly);
		delete payload.fieldOnly;
		const saved = fieldOnly ? payload : this.buildPayload(payload);
		const noteName = this.$refs.orderNote?.name;
		const savingNotes = Boolean(noteName && Object.hasOwn(payload, noteName));

		if (
			!force &&
			Object.keys(payload).length === 0 &&
			JSON.stringify(saved) === this.lastSaved
		) {
			panels.forEach((panel) => this.clearSavingPanel(panel));
			return;
		}

		this.queuedSavePanels = [];

		this.setPanelsStatus(panels, 'saving');
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
			const subscribe = this.shouldSubscribe(saved);

			const { response, data, isJson } = await this.postForm(
				this.postUrl(),
				fields,
				signal
			);

			if (!isJson) {
				this.status = this.failedLabel;
				this.statusTone = 'error';
				this.setPanelsStatus(panels, 'error');
				this.markNotesError(savingNotes);
				return;
			}

			if (!response.ok || data.success === false) {
				const result = this.applyErrors(data, fields);
				// Include a panel outside the save that has an error, so every error shows in a panel
				const fieldErrorPanels = Object.keys(this.panelStatus).filter((panel) =>
					this.panelHasFieldErrors(panel)
				);
				const ownerPanels = [
					...result.unplaced.map((error) => errorKeyPanel(error.key)),
					...fieldErrorPanels,
				].filter(Boolean);
				// Mark only a panel with an error of its own, since one save posts several panels' fields
				[...new Set([...panels, ...ownerPanels])].forEach((panel) => {
					const errorText = this.panelErrorText(
						result,
						panel,
						panels.includes(panel)
					);
					const hasOwnError =
						errorText ||
						fieldErrorPanels.includes(panel) ||
						(panel === 'coupon' && this.couponError) ||
						(panel === 'notes' && this.notesError);
					const tone = hasOwnError ? 'error' : 'idle';
					this.setPanelStatus(panel, tone, errorText);

					// The coupon and notes panels show their own error text, not a box
					if (panel === 'coupon' && errorText) {
						this.couponError = errorText;
					}

					if (panel === 'notes' && errorText) {
						this.notesError = errorText;
					}
				});
				this.markNotesError(savingNotes);
				return;
			}

			if (subscribe) {
				await this.subscribeToNewsletter(saved, signal);
			}

			const cart = data.cart || data.model || (data.data && data.data.cart);
			this.applyCart(cart, fieldOnly ? '' : this.shippingRateKey(saved));
			cartSynced = Boolean(cart) && typeof cart === 'object';

			const couponError = this.couponRejectedMessage(saved, cart);

			this.clearInputErrors();
			this.status = this.savedLabel;
			this.statusTone = 'saved';
			this.setPanelsStatus(
				panels.filter((panel) => !couponError || panel !== 'coupon'),
				'saved'
			);
			Object.assign(this.savedFieldValues, saved);
			if (!fieldOnly) {
				// Record the cart's method, since it can differ from the posted one
				this.lastSaved = JSON.stringify({
					...saved,
					shippingMethodHandle: cart
						? cart.shippingMethodHandle || ''
						: saved.shippingMethodHandle,
				});
			}
			if (savingNotes) {
				this.notesButtonVisible = false;
			}

			// Mark the coupon only, since the cart accepted the rest of the save
			if (couponError) {
				this.applyFieldErrors({
					couponCode: [couponError],
				});
				this.couponOpen = true;
				this.setPanelStatus('coupon', 'error', '');
				this.status = '';
				this.statusTone = 'idle';
			}
		} catch (error) {
			if (error.name === 'AbortError') {
				return;
			}

			this.status = this.failedLabel;
			this.statusTone = 'error';
			this.setPanelsStatus(panels, 'error');
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

			if (!this.saving && this.resolveSaves) {
				this.resolveSaves();
				this.resolveSaves = null;
			}

			// Redraw PayPal after the queued save, which returns early without redrawing when it has no
			// change to send. Stripe is left out, since applyCart already remounts it.
			if (cartSynced) {
				this.maybeReinitPaypalCheckout();
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

			if (typeof live.customerPickup === 'boolean') {
				this.cartIsPickup = live.customerPickup;
			}

			if (live.paymentNeedsRefresh) {
				this.invalidateStripeCheckout();
				this.invalidatePaypalCheckout();
			}

			if (
				typeof live.partialPaymentsHtml === 'string' &&
				this.$refs.partialPayments
			) {
				this.$refs.partialPayments.innerHTML = live.partialPaymentsHtml;
			}

			// Apply outside the address gate, since book labels don't depend on the cart's address
			if (live.addressLabels && typeof live.addressLabels === 'object') {
				this.addressLabels = live.addressLabels;
			}

			if (live.addressLines) {
				this.addressLines = live.addressLines;
			}

			if (Array.isArray(live.couponMessages)) {
				this.couponMessages = live.couponMessages;
				this.couponName = live.couponName ?? '';
			}

			if (Object.hasOwn(live, 'paymentBlock')) {
				const wasBlocked = Boolean(this.paymentBlock);
				this.paymentBlock = live.paymentBlock ?? '';

				// A payment element mounted while its section was hidden renders blank once shown
				if (wasBlocked && !this.paymentBlock) {
					this.invalidatePaypalCheckout();
					this.invalidateStripeCheckout();
				}
			}

			if (sameAddress) {
				if (typeof live.shippingPreview === 'string') {
					this.shippingPreview = live.shippingPreview;
				}

				if (cart.shippingAddress && typeof cart.shippingAddress === 'object') {
					// Keep only an address the customer typed, since a saved one or the store location would refill the new form
					this.latestShippingAddress =
						cart.sourceShippingAddressId || live.customerPickup
							? null
							: cart.shippingAddress;
					this.rememberAddressFields(
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
						partialPayments: [],
						...live.totals,
					};
				}

				if (live.lineItemTotals && typeof live.lineItemTotals === 'object') {
					this.lineItemTotals = live.lineItemTotals;
				}

				// Replace the Mollie lists alongside the totals, since each list is fetched for the order's amount
				if (live.molliePaymentMethods) {
					this.molliePaymentMethods = live.molliePaymentMethods;
				}
			}

			if (cart.billingAddress && typeof cart.billingAddress === 'object') {
				this.latestBillingAddress = cart.sourceBillingAddressId
					? null
					: cart.billingAddress;
				this.rememberAddressFields(
					cart.sourceBillingAddressId,
					cart.billingAddress
				);
				this.applyStripeBillingDefaults(cart.billingAddress);
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
			this.queuedSavePanels.at(-1) ||
			'delivery';

		if (this.saveTimer) {
			clearTimeout(this.saveTimer);
		}

		// Queue each panel, since autofill can change two panels within one save delay
		if (!this.queuedSavePanels.includes(nextPanel)) {
			this.queuedSavePanels = [...this.queuedSavePanels, nextPanel];
		}
		this.setPanelStatus(nextPanel, 'saving');

		this.saveTimer = setTimeout(() => {
			const queuedPanels = this.queuedSavePanels;
			this.saveTimer = null;
			this.syncPayButtons();
			this.saveCart({ panels: queuedPanels });
		}, delay);

		this.syncPayButtons();
	},

	saveIfValid(panel, event = null) {
		if (this.syncingFromCart) {
			return;
		}

		// Skip a blur from an unchanged field, since opening a dropdown blurs its trigger
		const fieldCell = event?.target?.closest?.('[data-fc-checkout-field]');
		if (
			event?.type === 'blur' &&
			fieldCell &&
			!this.fieldsChanged(this.collectNamedFields(fieldCell))
		) {
			return;
		}

		this.$nextTick(() => {
			if (this.syncingFromCart) {
				return;
			}

			if (panel === 'delivery') {
				this.refreshShippingPreview();
			}

			// Clear the pickup address, since the panel can't save until the address is complete
			if (
				panel === 'delivery' &&
				this.cartIsPickup &&
				!this.shippingPickup &&
				!this.canSavePanel(panel)
			) {
				this.saveCart({
					...this.collectNamedFields(fieldCell),
					shippingPickup: '0',
					panel,
					fieldOnly: true,
				});
				return;
			}

			if (!this.canSavePanel(panel)) {
				this.saveCheckoutField(panel, event);
				return;
			}

			const payload = this.buildPayload();
			if (JSON.stringify(payload) === this.lastSaved) {
				this.restorePaypalIfSkipped();
				this.restoreStripeIfSkipped();
				return;
			}

			this.queueSave(0, event, panel);
		});
	},

	// Save only the custom field while its panel is incomplete, since a full save posts the panel's other inputs
	saveCheckoutField(panel, event) {
		const fieldCell = event?.target?.closest('[data-fc-checkout-field]');
		if (!fieldCell) {
			return;
		}

		const fields = this.collectNamedFields(fieldCell);
		if (!this.fieldsChanged(fields)) {
			return;
		}

		this.saveCart({ ...fields, panel, fieldOnly: true });
	},

	fieldsChanged(fields) {
		return Object.entries(fields).some(
			([name, value]) =>
				JSON.stringify(this.savedFieldValues[name]) !== JSON.stringify(value)
		);
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

		this.rootEl.querySelectorAll('[data-fc-collect]').forEach((scope) => {
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

	findNamedInput(name, scope = this.rootEl) {
		return scope.querySelector(`[name="${this.escapeName(name)}"]`);
	},

	setInputErrors(name, messages, postedValue, scope) {
		const input = this.findNamedInput(name, scope);
		if (!input) {
			return INPUT_ERROR.noInput;
		}

		const root = input.closest('[x-data]');
		if (!root || root === this.rootEl) {
			return INPUT_ERROR.noInput;
		}

		const data = window.Alpine.$data(root);
		if (!data || !Array.isArray(data.errors)) {
			return INPUT_ERROR.noInput;
		}

		// Skip an error for a value the customer has changed since the save posted it
		if (
			postedValue !== undefined &&
			!sameValue(fieldValue(data), postedValue)
		) {
			return INPUT_ERROR.stale;
		}

		setServerErrors(data, messages);

		return INPUT_ERROR.placed;
	},

	clearInputErrors() {
		this.couponError = '';
		this.notesError = '';
		this.rootEl
			.querySelectorAll(
				'[data-fc-collect] [x-data], [data-fc-address-edit] [x-data]'
			)
			.forEach((root) => {
				const data = window.Alpine.$data(root);
				if (data && Array.isArray(data.errors)) {
					setServerErrors(data, []);
				}
			});
	},

	/**
	 * @returns {{unplaced: {key: string, messages: string[]}[], applied: {key: string, messages: string[]}[]}} errors with no input to show them against, and errors shown
	 */
	applyFieldErrors(errors, posted = {}, scope = this.rootEl) {
		this.clearInputErrors();
		const flattened = this.flattenErrors(errors);
		const noteName = this.$refs.orderNote?.name;
		const unplaced = [];
		const applied = [];

		Object.entries(flattened).forEach(([key, messages]) => {
			const name = this.errorKeyToName(key);
			const placed = this.setInputErrors(
				name,
				messages,
				posted[`${name}[]`] ?? posted[name],
				scope
			);

			if (name === 'couponCode') {
				this.couponError = messages.join(' ');
				applied.push({ key, messages });
				return;
			}

			if (noteName && name === noteName) {
				this.notesError = messages.join(' ');
				applied.push({ key, messages });
				return;
			}

			if (placed === INPUT_ERROR.placed) {
				applied.push({ key, messages });
			}

			// An address rejected as a whole names no field, such as one outside the store's market
			if (placed === INPUT_ERROR.noInput) {
				unplaced.push({ key, messages });
			}
		});

		return { unplaced, applied };
	},

	collectResponseErrors(data) {
		return {
			...(data.errors || {}),
			...((data.cart && data.cart.errors) || {}),
		};
	},

	/**
	 * @returns {{unplaced: {key: string, messages: string[]}[], applied: string[], general: string}}
	 */
	applyErrors(data, posted, scope = this.rootEl) {
		const errors = this.collectResponseErrors(data);

		// Show billing errors on the shipping address when the save posted billing as the same
		if (posted.billingAddressSameAsShipping === '1') {
			Object.keys(errors)
				.filter((key) => key.startsWith('billingAddress'))
				.forEach((key) => {
					const shippingKey = `shippingAddress${key.slice('billingAddress'.length)}`;
					errors[shippingKey] = [
						...new Set([
							...[].concat(errors[shippingKey] ?? []),
							...[].concat(errors[key]),
						]),
					];
					delete errors[key];
				});
		}

		const { unplaced, applied } = this.applyFieldErrors(errors, posted, scope);
		// Drop a message a field with the same key prefix already shows
		const unplacedShown = unplaced
			.map((error) => ({
				...error,
				messages: error.messages.filter(
					(message) =>
						!applied.some(
							(placed) =>
								keyPrefix(placed.key) === keyPrefix(error.key) &&
								placed.messages.includes(message)
						)
				),
			}))
			.filter((error) => error.messages.length);
		const appliedMessages = applied.flatMap((placed) => placed.messages);
		const general = data.message || data.error || this.failedLabel;

		// Announce every message in the screen-reader-only status region
		this.status =
			[
				...new Set([
					...unplacedShown.flatMap((error) => error.messages),
					...appliedMessages,
				]),
			].join(' ') || general;
		this.statusTone = 'error';

		return { unplaced: unplacedShown, applied: appliedMessages, general };
	},

	// Box text: unplaced errors routed to this panel; in a save, unrouted ones or the general message
	panelErrorText({ unplaced, applied, general }, panel, inSave) {
		if (!unplaced.length) {
			return inSave && !applied.length ? general : '';
		}

		return unplaced
			.filter((error) => {
				const owner = errorKeyPanel(error.key);
				return owner ? owner === panel : inSave;
			})
			.flatMap((error) => error.messages)
			.join(' ');
	},

	panelHasFieldErrors(panel) {
		return [
			...this.rootEl.querySelectorAll(`[data-fc-panel="${panel}"] [x-data]`),
		].some((root) => window.Alpine.$data(root)?.errors?.length > 0);
	},

	applyCoupon() {
		const code = String(this.couponInput || '').trim();
		if (!code) {
			return;
		}

		// saveCart drops an anonymous cart's changes, so the code would be lost without a word
		if (!this.loggedIn && !this.hasEmail) {
			this.couponError = this.couponNeedsEmailLabel;
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

		const fields = { [input.name]: input.value };
		if (!this.fieldsChanged(fields)) {
			return;
		}

		this.notesError = '';

		return this.saveCart({ ...fields, panel: 'notes', fieldOnly: true });
	},

	markNotesError(savingNotes) {
		if (!savingNotes) {
			return;
		}

		// Fall back to the failed label when the save set no notes error
		if (!this.notesError) {
			this.notesError = this.failedLabel;
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
