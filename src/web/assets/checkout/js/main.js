import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import {
	SinglePageCheckout,
	isEmptyValue,
	isValidEmail,
} from './single-page-checkout.js';

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
		label: props.label || '',
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
		touched: (props.errors || []).length > 0,
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
			const input = this.$refs.input;
			this.value = '';
			this.touched = true;
			this.showButton = false;
			input.value = '';
			input.focus();
			input.dispatchEvent(new Event('input', { bubbles: true }));
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
				// A half typed address is not yet wrong, so it only reads as an error once they leave
				if (showRequired || this.touched) {
					messages.push(this.invalidEmailError);
				}
			}

			setErrors(this, messages);
			return valid;
		},
	};
};

/**
 * Ranks exact matches first, then starts-with, then alphabetically.
 */
const matchOptions = (search, options) => {
	if (!search) {
		return options;
	}

	const query = search.toLowerCase();

	return options
		.filter((option) => String(option.label).toLowerCase().includes(query))
		.sort((optionA, optionB) => {
			const labelA = String(optionA.label).toLowerCase();
			const labelB = String(optionB.label).toLowerCase();

			const exactA = labelA === query;
			const exactB = labelB === query;

			const startsA = labelA.startsWith(query);
			const startsB = labelB.startsWith(query);

			if (exactA !== exactB) {
				return exactA ? -1 : 1;
			}

			if (startsA !== startsB) {
				return startsA ? -1 : 1;
			}

			return labelA.localeCompare(labelB);
		});
};

const SearchableSelect = (props) => {
	// Hovering an option writes activeIndex, and Alpine then re-runs every getter in scope. Held
	// outside the returned object so writing it does not itself trigger another pass.
	let filterCache = { search: null, options: null, result: [] };

	return {
		label: props.label || '',
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
		touched: (props.errors || []).length > 0,

		init() {
			const fallbackSelect = this.$refs.fallback;
			if (fallbackSelect) {
				fallbackSelect.remove();
			}

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

					if (!this.selectedOption && updatedOptions.length === 1) {
						this.selectedOption = updatedOptions[0];
					}
				});
			});

			this.$watch('modelValue', (newValue, oldValue) => {
				if (newValue !== oldValue && this.selectedOption?.value !== newValue) {
					this.selectByValue(newValue);
				}
			});

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
			const options = this.options ?? [];

			if (
				filterCache.search === this.search &&
				filterCache.options === options
			) {
				return filterCache.result;
			}

			filterCache = {
				search: this.search,
				options,
				result: matchOptions(this.search, options),
			};

			return filterCache.result;
		},

		get hasOptions() {
			return this.filteredOptions.length > 0;
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

		// Button presses arrive in bursts, so only the final quantity is posted
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

/**
 * Validation for inputs the panel scan reads, where the input itself needs no behavior.
 */
const SimpleField = (props) => {
	return {
		label: props.label || '',
		value: props.value ?? '',
		required: Boolean(props.required),
		requiredError: props.requiredError || '',
		errors: props.errors || [],
		// A field rendered with an error has already been flagged, so validate keeps reporting it
		touched: (props.errors || []).length > 0,

		isRequired() {
			return this.required;
		},

		validate(showRequired = false) {
			if (!this.isRequired() || !isEmptyValue(this.value)) {
				this.errors = [];
				return true;
			}

			this.errors = showRequired || this.touched ? [this.requiredError] : [];
			return false;
		},
	};
};

const ScrollableItems = () => {
	return {
		overflowing: false,
		atEnd: false,

		init() {
			// Thumbnails load after this runs and change the list height, so watch it rather than measure once
			new ResizeObserver(() => this.measure()).observe(this.$refs.items);
		},

		measure() {
			const viewport = this.$refs.viewport;
			this.overflowing = viewport.scrollHeight > viewport.clientHeight;
			this.measurePosition();
		},

		measurePosition() {
			const viewport = this.$refs.viewport;
			this.atEnd =
				viewport.scrollTop + viewport.clientHeight >= viewport.scrollHeight - 4;
		},
	};
};

const RadioInput = (props) => {
	return {
		label: props.label || '',
		name: props.name,
		value: props.value || '',
		required: props.required || false,
		errors: props.errors || [],
		success: props.success || [],
		requiredError: props.requiredError || '',
		touched: (props.errors || []).length > 0,

		isRequired() {
			return Boolean(this.required);
		},
		select() {
			this.touched = true;
			this.validate(true);
		},
		validate(showRequired = false) {
			const messages = [];
			let valid = true;

			if (this.isRequired() && String(this.value || '') === '') {
				valid = false;
				if (showRequired || this.touched) {
					messages.push(this.requiredError);
				}
			}

			setErrors(this, messages);
			return valid;
		},
	};
};

const CheckoutTracking = (props) => {
	return {
		track() {
			const body = new FormData();
			body.append(window.csrfTokenName, window.csrfTokenValue);
			body.append('action', 'klaviyo-connect-plus/api/track');
			body.append(
				'email',
				this.$root.querySelector('[name="email"]')?.value ?? ''
			);
			body.append('event[name]', 'Started Checkout');
			body.append('event[trackOrder]', '1');
			body.append('event[orderId]', String(props.orderId ?? ''));

			fetch(window.location.href, {
				method: 'POST',
				body,
				headers: { Accept: 'application/json' },
				keepalive: true,
			}).catch(() => {
				// Marketing is not worth failing a checkout over
				void 0;
			});
		},
	};
};

Alpine.plugin(focus);
Alpine.data('ScrollableItems', ScrollableItems);
Alpine.data('SimpleField', SimpleField);
Alpine.data('CheckoutTracking', CheckoutTracking);
Alpine.data('ClearableInput', ClearableInput);
Alpine.data('RadioInput', RadioInput);
Alpine.data('SearchableSelect', SearchableSelect);
Alpine.data('LineItem', LineItem);
Alpine.data('SinglePageCheckout', SinglePageCheckout);

window.Alpine = Alpine;
Alpine.start();
