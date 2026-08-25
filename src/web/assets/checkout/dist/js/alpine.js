import Alpine from 'https://esm.sh/alpinejs@3.16.1';
import focus from 'https://esm.sh/@alpinejs/focus@3.16.1';

const isValidEmail = (value) =>
	/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());

const CARD_FIELDS = ['number', 'month', 'year', 'cvv'];

const setErrors = (field, messages) => {
	const next = messages.filter(Boolean);
	if (
		Array.isArray(field.errors) &&
		field.errors.length === next.length &&
		field.errors.every((message, index) => message === next[index])
	) {
		return;
	}

	field.errors = next;
};

const ClearableInput = (props) => {
	return {
		name: props.name,
		value: props.value,
		type: props.type || 'text',
		countryCode: props.countryCode,
		requiredFields: props.requiredFields,
		addressScope: props.addressScope ?? false,
		required: props.required,
		errors: props.errors,
		success: props.success,
		requiredError: props.requiredError || '',
		invalidEmailError: props.invalidEmailError || '',
		showButton: false,
		touched: false,
		props: props,

		input() {
			this.showButton = this.value !== '';
			if (this.touched || String(this.value || '').trim() !== '') {
				this.validate(false);
			}
		},
		focus() {
			this.showButton = this.value !== '';
		},
		blur() {
			this.touched = true;
			this.showButton =
				this.$refs.button === document.activeElement && this.value !== '';
			this.validate(true);
		},
		clear() {
			this.value = '';
			this.touched = true;
			this.showButton = false;
			this.$refs.input.focus();
			this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
		},
		isRequired() {
			if (typeof this.required === 'boolean') {
				return this.required;
			}

			// Only address forms render the country-keyed map these closures read.
			if (!this.addressScope) {
				return false;
			}

			return (
				this.requiredFields()[this.countryCode()]?.includes(this.name) ?? false
			);
		},
		validate(showRequired = false) {
			const value = String(this.value || '').trim();
			const messages = [];
			let valid = true;

			if (this.isRequired() && value === '') {
				valid = false;
				if (showRequired || this.touched) {
					messages.push(this.requiredError);
				}
			} else if (
				this.type === 'email' &&
				value !== '' &&
				!isValidEmail(value)
			) {
				valid = false;
				messages.push(this.invalidEmailError);
			}

			setErrors(this, messages);
			return valid;
		},
	};
};

const SearchableSelect = (props) => {
	return {
		id: props.id || `ss-${Math.random().toString(36).slice(2)}`,
		name: props.name || 'select',
		placeholder: props.placeholder || 'Select',
		options: props.options ?? [],
		required: props.required || false,
		errors: props.errors || [],
		success: props.success || [],
		requiredError: props.requiredError || '',
		modelValue: props.value || null,
		tmpInputEventValue: null, // This is set when the hidden input's input event fires
		open: false,
		search: '',
		activeIndex: 0,
		selectedOption: null,
		lastPinned: null,
		touched: false,

		init() {
			// Remove the fallback select element
			const fallbackSelect = this.$refs.fallback;
			if (fallbackSelect) {
				fallbackSelect.remove();
			}

			// Initial sync from modelValue/props to selectedOption
			if (this.modelValue != null) {
				const match = this.options.find(
					(option) =>
						option.value === this.modelValue || option === this.modelValue
				);
				if (match) {
					this.selectedOption = match;
				}
			}

			this.$watch('options', (updatedOptions) => {
				this.selectedOption = null;

				if (this.tmpInputEventValue) {
					const value = this.tmpInputEventValue;
					this.tmpInputEventValue = null;

					this.selectByValue(value);
				}

				this.$nextTick(() => {
					const input = this.$refs.button;
					if (input && input.value && !this.selectedOption) {
						this.selectByValue(input.value);
					}

					// Auto-select if there's only one option
					if (!this.selectedOption && updatedOptions.length === 1) {
						this.selectedOption = updatedOptions[0];
					}
				});
			});

			// parent -> child
			this.$watch('modelValue', (newValue, oldValue) => {
				if (newValue !== oldValue && this.selectedOption?.value !== newValue) {
					this.selectByValue(newValue);
				}
			});

			// child -> parent
			this.$watch('selectedOption', (option) => {
				const next = option ? option.value : null;
				if (this.modelValue !== next) {
					this.modelValue = next;
					this.$dispatch('selected', { name: this.name, value: next });
				}

				if (this.selectedOption) {
					this.touched = true;
					this.errors = [];
					this.$nextTick(() => {
						// Make sure that the value is set on the hidden input in the next tick so that everything else
						// in the current tick has completed.
						this.$refs.hiddenValue.value = this.selectedOption.value;
					});
				}
			});

			if (!this.selectedOption && this.options.length === 1) {
				// Once the watchers have all been initialized...
				// If there is only a single available option, then select it automatically
				this.selectedOption = this.options[0];
			}
		},

		/**
		 * Called when the trigger input receives an `input` event.
		 * Handles two scenarios:
		 *   a) Browser autofill just set the value, so match it to an option
		 *   b) User is typing directly, so open dropdown and pipe text into search
		 */
		onTriggerInput(event) {
			if (this._keydownHandled) {
				this._keydownHandled = false;
				event.target.value = this.selectedOption
					? this.selectedOption.label
					: '';
				return;
			}

			const val = event.target.value;

			// Try autofill match first
			if (this.selectByValue(val)) {
				return;
			}

			// If value was stored as pending (options not loaded yet), don't open dropdown
			if (this.tmpInputEventValue) {
				return;
			}

			// Not an autofill match, so the user is typing.
			// Open the dropdown and forward their text into the search field.
			if (!this.open) {
				this.open = true;
				this.resetActiveIndex();
			}

			this.$nextTick(() => {
				if (this.$refs.search) {
					this.$refs.search.value = val;
					this.search = val;
				}
				// Reset the trigger input to the current selection
				event.target.value = this.selectedOption
					? this.selectedOption.label
					: '';
			});
		},

		/**
		 * When the user presses a printable key on the trigger input,
		 * open the dropdown so typing flows into the search field.
		 */
		onTriggerKeydown(event) {
			if (
				event instanceof KeyboardEvent &&
				event.key.length === 1 &&
				!event.ctrlKey &&
				!event.metaKey &&
				!event.altKey
			) {
				if (!this.open) {
					this._keydownHandled = true;
					this.openListbox();
					// x-trap activates on a 15ms timer, so focus only reaches the search
					// box after that; seeding it any sooner loses the opening keystroke.
					setTimeout(() => {
						if (this.$refs.search) {
							this.$refs.search.value = event.key;
							this.search = event.key;
							const caret = this.$refs.search.value.length;
							this.$refs.search.setSelectionRange(caret, caret);
						}
					}, 50);
				}
			}
		},

		get buttonLabel() {
			return this.selectedOption ? this.selectedOption.label : this.placeholder;
		},

		get filteredOptions() {
			if (!this.search) {
				return this.options ?? [];
			}
			const query = this.search.toLowerCase();

			return this.options
				.filter((option) => String(option.label).toLowerCase().includes(query))
				.sort((optionA, optionB) => {
					const labelA = String(optionA.label).toLowerCase();
					const labelB = String(optionB.label).toLowerCase();

					const exactA = labelA === query;
					const exactB = labelB === query;

					const startsA = labelA.startsWith(query);
					const startsB = labelB.startsWith(query);

					// 1) Exact match first
					if (exactA && !exactB) {
						return -1;
					}
					if (!exactA && exactB) {
						return 1;
					}

					// 2) Starts-with next
					if (startsA && !startsB) {
						return -1;
					}
					if (!startsA && startsB) {
						return 1;
					}

					// 3) Otherwise, both just "includes" and alphabetical
					return labelA.localeCompare(labelB);
				});
		},

		get hasOptions() {
			// TODO This fires multiple times when a component is initialized, and it also fires whenever a listitem is hovered over
			return (this.filteredOptions?.length ?? 0) > 0;
		},

		isLastPinned(option) {
			const pinned = this.filteredOptions.filter((option) => option.pinned);
			return pinned?.length && pinned[pinned.length - 1] === option;
		},

		labelId() {
			return `${this.id}-label`;
		},

		buttonId() {
			return `${this.id}-button`;
		},

		listboxId() {
			return `${this.id}-listbox`;
		},

		errorId() {
			return `${this.id}-error`;
		},

		isRequired() {
			return Boolean(this.required) && this.options.length > 0;
		},

		validate(showRequired = false) {
			const value = String(this.modelValue || '').trim();
			const messages = [];
			let valid = true;

			if (this.isRequired() && value === '') {
				valid = false;
				if (showRequired || this.touched) {
					messages.push(this.requiredError);
				}
			}

			setErrors(this, messages);
			return valid;
		},

		optionId(index) {
			return `${this.id}-option-${index}`;
		},

		openListbox() {
			if (this.open) {
				return;
			}
			this.open = true;
			this.resetActiveIndex();
			this.lastPinned = this.options.find(
				(option) => option.isLastPinned === true
			);
		},

		closeListbox() {
			if (!this.open) {
				return;
			}
			this.open = false;
			this.search = '';
			if (this.lastPinned) {
				this.lastPinned.isLastPinned = true;
			}
			this.touched = true;
			this.validate(true);
		},

		toggleListbox() {
			if (this.open) {
				this.closeListbox();
			} else {
				this.openListbox();
			}
		},

		closeAndFocusButton() {
			this.closeListbox();
			this.$refs.button.focus();
		},

		resetActiveIndex() {
			if (!this.hasOptions) {
				this.activeIndex = 0;
				return;
			}
			const selectedIdx = this.filteredOptions.findIndex((option) =>
				this.isSelected(option)
			);
			this.activeIndex = selectedIdx === -1 ? 0 : selectedIdx;
		},

		moveActive(step) {
			if (!this.hasOptions) {
				return;
			}

			let next = this.activeIndex + step;
			const max = this.filteredOptions.length - 1;
			if (next < 0) {
				next = max;
			}
			if (next > max) {
				next = 0;
			}
			this.activeIndex = next;
			this.scrollActiveIntoView();
		},

		scrollActiveIntoView() {
			this.$nextTick(() => {
				const list = this.$refs.listbox;
				const active = document.getElementById(this.optionId(this.activeIndex));
				if (!list || !active) {
					return;
				}

				const listRect = list.getBoundingClientRect();
				const activeRect = active.getBoundingClientRect();

				if (activeRect.top < listRect.top) {
					list.scrollTop -= listRect.top - activeRect.top;
				} else if (activeRect.bottom > listRect.bottom) {
					list.scrollTop += activeRect.bottom - listRect.bottom;
				}
			});
		},

		selectActiveOption() {
			if (!this.hasOptions) {
				return;
			}

			const option = this.filteredOptions[this.activeIndex];
			if (option) {
				this.selectOption(option);
			}
		},

		// --- selection ---
		selectOption(option) {
			const wasOpen = this.open;
			this.selectedOption = option; // watcher will push value into modelValue
			if (wasOpen) {
				this.closeAndFocusButton();
			} else {
				this.closeListbox();
			}
		},

		isSelected(option) {
			return this.selectedOption && this.selectedOption.value === option.value;
		},

		selectByValue(value) {
			// When the form is autofilled, we can't assume the options will be immediately available if they've
			// been changed based on some other field's value. So we set a temporary value if we don't match
			// anything at this point.
			if (!value) {
				return null;
			}

			const searchValue = String(value).toLowerCase().trim();

			// Exact match on label
			let selectedOption = this.options.find(
				(option) => String(option.label).toLowerCase() === searchValue
			);

			// Exact match on value/code (e.g. "CA", "US")
			if (!selectedOption) {
				selectedOption = this.options.find(
					(option) => String(option.value).toLowerCase() === searchValue
				);
			}

			// Fuzzy: starts-with on label
			if (!selectedOption) {
				selectedOption = this.options.find((option) =>
					String(option.label).toLowerCase().startsWith(searchValue)
				);
			}

			// Fuzzy: includes on label
			if (!selectedOption) {
				selectedOption = this.options.find((option) =>
					String(option.label).toLowerCase().includes(searchValue)
				);
			}

			if (selectedOption) {
				// If we found the option, we can clear this value
				this.tmpInputEventValue = null;
				this.selectedOption = selectedOption;
				return selectedOption;
			}

			// No match, so store as pending for when options load (e.g. state autofilled before country)
			this.tmpInputEventValue = value;

			return null;
		},

		updateLastPinned(event) {
			if (this.lastPinned) {
				if (event.target.value === '') {
					this.lastPinned.isLastPinned = true;
				} else {
					this.lastPinned.isLastPinned = false;
				}
			}
		},
	};
};

const LineItem = (options) => {
	return {
		...options,
		sending: false,
		action: 'update',
		postTimer: null,
		justClickedButton: false,

		get maxAllowed() {
			const hardCap = this.max || Infinity;
			if (this.unlimitedStock) {
				return hardCap;
			}

			return Math.min(hardCap, this.stock ?? 0);
		},

		// Keep qty within min, max and stock, and flag which limit was hit
		clamp(value) {
			let quantity = Number.isFinite(+value) ? +value : this.min || 1;

			this.showErrorMinMessage = !!(this.min && quantity < this.min);
			if (this.showErrorMinMessage) {
				quantity = this.min;
			}

			const stockLimit = this.unlimitedStock ? Infinity : (this.stock ?? 0);
			const maxLimit = this.max || Infinity;

			this.showErrorStockMessage =
				Number.isFinite(stockLimit) && quantity > stockLimit;
			this.showErrorMaxMessage =
				Number.isFinite(maxLimit) &&
				quantity > maxLimit &&
				!this.showErrorStockMessage;

			const limit = this.maxAllowed;
			if (Number.isFinite(limit) && quantity > limit) {
				quantity = limit;
			}

			return quantity;
		},

		// Left control removes the item at qty 1, otherwise decrements
		leftClick() {
			if (this.qty <= 1) {
				this.remove();
				return;
			}

			this.qty = this.clamp(this.qty - 1);
			this.schedulePost();
		},

		increment() {
			this.qty = this.clamp(this.qty + 1);
			this.schedulePost();
		},

		input() {
			this.qty = this.clamp(this.qty);
		},

		blur() {
			if (this.justClickedButton) {
				this.justClickedButton = false;
				return;
			}

			this.clearPending();
			this.qty = this.clamp(this.qty);
			this.post('update');
		},

		onButtonMouseDown() {
			this.justClickedButton = true;
		},

		remove() {
			this.clearPending();
			this.qty = 0;
			this.post('remove');
		},

		// Button presses land in bursts, so only the final quantity is posted
		schedulePost(delay = 500) {
			this.clearPending();
			this.postTimer = setTimeout(() => this.post('update'), delay);
		},

		clearPending() {
			if (!this.postTimer) {
				return;
			}

			clearTimeout(this.postTimer);
			this.postTimer = null;
		},

		post(actionType) {
			this.action = actionType;
			this.$nextTick(() => {
				const quantityInput = this.$root.querySelector(
					`input[name="lineItems[${this.id}][qty]"]`
				);
				quantityInput.value = this.qty;
				this.$root.querySelector('form').requestSubmit();
			});
		},
	};
};

const SinglePageCheckout = (props) => {
	return {
		loggedIn: props.loggedIn,
		email: props.email ?? '',
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
		addressLabels: {},
		addressFields: {},
		shippingPreview: props.shippingPreview ?? '',
		latestShippingAddress: null,
		latestBillingAddress: null,
		shippingMethods: props.shippingMethods ?? [],
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
			summary: 'idle',
		},
		panelStatusTimers: {},
		queuedSavePanel: null,
		pending: 0,
		saveTimer: null,
		saveAbort: null,
		queuedSaveExtra: {},
		saving: false,
		nextSave: null,
		lastSaved: '',
		paypalInitTimer: null,
		paying: false,
		onPageShow: null,
		originalAuthorizeHandler: null,
		originalSendPayment: null,

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
			this.$nextTick(() => this.refreshNewBillingContent());
			this.syncPayButtons();
			this.$watch('paying', () => this.syncPayButtons());
			this.onPageShow = (event) => {
				if (event.persisted) {
					this.paying = false;
				}
			};
			window.addEventListener('pageshow', this.onPageShow);
			this.$nextTick(() => this.bindPayOverlay());

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

				this.queueSave(0, event, {}, panel);
			});
		},

		queueSave(delay = 400, event = null, extra = {}, panel = null) {
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
				extra.panel ||
				this.queuedSavePanel ||
				'delivery';

			if (this.saveTimer) {
				clearTimeout(this.saveTimer);
			}

			this.queuedSaveExtra = extra;

			if (this.queuedSavePanel && this.queuedSavePanel !== nextPanel) {
				this.clearSavingPanel(this.queuedSavePanel);
			}

			this.queuedSavePanel = nextPanel;
			this.setPanelStatus(nextPanel, 'saving');

			this.saveTimer = setTimeout(() => {
				const queued = this.queuedSaveExtra;
				const queuedPanel = this.queuedSavePanel;
				this.saveTimer = null;
				this.queuedSaveExtra = {};
				this.syncPayButtons();
				this.saveCart({ ...queued, panel: queuedPanel });
			}, delay);

			this.syncPayButtons();
		},

		saveNow() {
			if (this.saveTimer) {
				clearTimeout(this.saveTimer);
				this.saveTimer = null;
			}

			const queued = this.queuedSaveExtra;
			const panel = this.queuedSavePanel;
			this.queuedSaveExtra = {};

			return this.saveCart({ ...queued, panel });
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

			Object.entries(flattened).forEach(([key, messages]) => {
				const name = this.errorKeyToName(key);
				this.setInputErrors(name, messages);

				if (name === 'couponCode') {
					this.couponError = messages.join(' ');
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

			if (
				payload.couponCode === undefined &&
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

				const { response, data, isJson } = await this.postForm(
					this.postUrl(),
					fields,
					signal
				);

				if (!isJson) {
					this.status = this.failedLabel;
					this.statusTone = 'error';
					this.setPanelStatus(panel, 'error');
					return;
				}

				if (!response.ok || data.success === false) {
					this.applyErrors(data);
					this.setPanelStatus(panel, 'error');
					return;
				}

				const cart = data.cart || data.model || (data.data && data.data.cart);
				this.applyCart(cart, this.shippingRateKey(saved));

				this.clearInputErrors();
				this.status = this.savedLabel;
				this.statusTone = 'saved';
				this.setPanelStatus(panel, 'saved');
				this.lastSaved = JSON.stringify(saved);
			} catch (error) {
				if (error.name === 'AbortError') {
					return;
				}

				this.status = this.failedLabel;
				this.statusTone = 'error';
				this.setPanelStatus(panel, 'error');
			} finally {
				this.saving = false;
				this.pending = Math.max(0, this.pending - 1);
				this.syncPayButtons();

				const next = this.nextSave;
				this.nextSave = null;
				if (next) {
					this.saveCart(next);
				}
			}
		},

		applyCoupon() {
			const code = String(this.couponInput || '').trim();
			if (!code) {
				return;
			}

			return this.saveCart({
				couponCode: code,
				panel: 'summary',
			});
		},

		removeCoupon() {
			this.couponInput = '';
			return this.saveCart({
				couponCode: '',
				panel: 'summary',
			});
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

		onPaySubmit(event) {
			const payloadChanged =
				JSON.stringify(this.buildPayload()) !== this.lastSaved;

			if (this.saveTimer || payloadChanged) {
				event.preventDefault();
				event.stopPropagation();
				this.paying = false;
				if (!this.deliveryReadyForPay) {
					this.panelFieldsReady('delivery', null, true);
					return;
				}

				this.saveNow();
				return;
			}

			if (this.canPay) {
				this.paying = true;
				return;
			}

			this.paying = false;
			this.panelFieldsReady('delivery', null, true);
			event.preventDefault();
			event.stopPropagation();
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
			this.wrapAuthorizeHandler();
		},

		wrapAuthorizeHandler() {
			this.wrapCardFields();
			const checkout = this;

			if (
				!this.originalSendPayment &&
				typeof window.sendPaymentDataToAnet === 'function'
			) {
				this.originalSendPayment = window.sendPaymentDataToAnet;
				window.sendPaymentDataToAnet = function () {
					if (!checkout.checkCard()) {
						checkout.paying = false;
						return;
					}

					checkout.paying = true;
					return checkout.originalSendPayment.apply(this, arguments);
				};
			}

			if (
				this.originalAuthorizeHandler ||
				typeof window.responseHandler !== 'function'
			) {
				return;
			}

			this.originalAuthorizeHandler = window.responseHandler;
			window.responseHandler = function (response) {
				if (
					response &&
					response.messages &&
					response.messages.resultCode === 'Error'
				) {
					checkout.paying = false;
					checkout.showAuthorizeErrors(response);
					checkout.syncPayButtons();
					return;
				}

				return checkout.originalAuthorizeHandler.apply(this, arguments);
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
				if (!input || input.parentElement.dataset.fcCardField) {
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
			const fields = {
				E_WC_04: 'number',
				E_WC_05: 'number',
				E_WC_06: 'month',
				E_WC_07: 'year',
				E_WC_08: 'year',
				E_WC_15: 'cvv',
			};
			const labels = {
				E_WC_05: this.cardNumberError,
				E_WC_06: this.cardMonthError,
				E_WC_07: this.cardYearError,
				E_WC_08: this.cardExpiredError,
				E_WC_15: this.cardCvvError,
			};
			const errors = {};

			(response.messages.message || []).forEach((item) => {
				const name = fields[item.code] || 'number';
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

Alpine.plugin(focus);
Alpine.data('ClearableInput', ClearableInput);
Alpine.data('SearchableSelect', SearchableSelect);
Alpine.data('LineItem', LineItem);
Alpine.data('SinglePageCheckout', SinglePageCheckout);

window.Alpine = Alpine;
Alpine.start();
