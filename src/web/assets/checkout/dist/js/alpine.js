import Alpine from "https://esm.sh/alpinejs@3";
import focus from "https://esm.sh/@alpinejs/focus";

async function callAction(url, body = {}, method = 'POST') {
	return await fetch(url, {
		headers: {
			'Accept': 'application/json',
			'X-Requested-With': 'XMLHttpRequest'
		},
		method,
		body,
	});
}

const ClearableInput = (props) => {
	return {
		name: props.name,
		value: props.value,
		countryCode: props.countryCode,
		requiredFields: props.requiredFields,
		required: props.required,
		errors: props.errors,
		success: props.success,
		showButton: false,
		props: props,

		input() {
			this.showButton = this.value !== '';
			this.errors = [];
		},
		focus() {
			this.showButton = this.value !== '';
		},
		blur() {
			this.showButton = (this.$refs.button === document.activeElement) && (this.value !== '');
		},
		clear() {
			this.value = '';
			this.errors = [];
			this.$refs.input.focus();
		},
		isRequired() {
			// If its a boolean, then we return that, not what the address formatter indicates.
			if (typeof this.required === 'boolean') {
				return this.required;
			}

			// If its a string, then we check if that value is in the requiredFields object
			if (typeof this.required === 'string') {
				return this.requiredFields()[this.countryCode()]?.includes(this.required) ?? false;
			}

			if (!this.requiredFields) {
				return false;
			}

			try {
				let countryCode = this.countryCode() ?? '';
				let requiredFields = this.requiredFields() ?? {};

				// Check if countryCode and requiredFields are defined
				if (!countryCode || !requiredFields) {
					return false;
				}

				// Otherwise we check if the field name is in the requiredFields object
				return requiredFields[countryCode]?.includes(this.name) ?? false;
			} catch (error) {
				// Somehow there is a moment where the countryCode and requiredFields are not defined
				// This is a temporary fix to prevent the error log - no functionality is broken.
				return false;
			}
		}
	};
};

const SearchableSelect = (props) => {
	return {
		id: props.id || `ss-${Math.random().toString(36).slice(2)}`,
		name: props.name || 'select',
		placeholder: props.placeholder || 'Select',
		options: props.options,
		required: props.required || false,
		errors: props.errors || [],
		success: props.success || [],
		modelValue: props.value || null,
		open: false,
		search: '',
		activeIndex: 0,
		selectedOption: null,

		init() {
			// Remove the fallback select element
			const fallbackSelect = this.$refs.fallback;
			if (fallbackSelect) {
				fallbackSelect.remove();
			}

			// Initial sync from modelValue/props to selectedOption
			if (this.modelValue != null) {
				const match = this.options.find(
					(o) => o.value === this.modelValue || o === this.modelValue
				);
				if (match) this.selectedOption = match;
			}

			// parent -> child
			this.$watch('modelValue', (value) => {
				if (value == null) {
					this.selectedOption = null;
					return;
				}
				const match = this.options.find(
					(o) => o.value === value || o === value
				);
				if (match && this.selectedOption !== match) {
					this.selectedOption = match;
				}
			});

			// child -> parent
			this.$watch('selectedOption', (option) => {
				const next = option ? option.value : null;
				if (this.modelValue !== next) {
					this.modelValue = next;
					this.$dispatch('selected', { name: this.name, value: next });
				}
			});
		},

		get buttonLabel() {
			return this.selectedOption ? this.selectedOption.label : this.placeholder;
		},

		get filteredOptions() {
			if (!this.search) return this.options;
			const q = this.search.toLowerCase();

			return this.options
				.filter(o => String(o.label).toLowerCase().includes(q))
				.sort((a, b) => {
					const aLabel = String(a.label).toLowerCase();
					const bLabel = String(b.label).toLowerCase();

					const aExact = aLabel === q;
					const bExact = bLabel === q;

					const aStarts = aLabel.startsWith(q);
					const bStarts = bLabel.startsWith(q);

					// 1) Exact match first
					if (aExact && !bExact) return -1;
					if (!aExact && bExact) return 1;

					// 2) Starts-with next
					if (aStarts && !bStarts) return -1;
					if (!aStarts && bStarts) return 1;

					// 3) Otherwise, both just "includes" and alphabetical
					return aLabel.localeCompare(bLabel);
				});
		},

		get hasOptions() {
			return this.filteredOptions.length > 0;
		},

		isLastPinned(option) {
			const pinned = this.filteredOptions.filter(o => o.pinned);
			return pinned.length && pinned[pinned.length - 1] === option;
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

		optionId(index) {
			return `${this.id}-option-${index}`;
		},

		openListbox() {
			if (this.open) return;
			this.open = true;
			this.resetActiveIndex();
			this.$nextTick(() => {
				if (this.$refs.search) {
					this.$refs.search.focus();
					this.$refs.search.select();
				}
			});
		},

		closeListbox() {
			if (!this.open) return;
			this.open = false;
			this.search = '';
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
			this.$nextTick(() => {
				this.$refs.button.focus();
			});
		},

		resetActiveIndex() {
			if (!this.hasOptions) {
				this.activeIndex = 0;
				return;
			}
			const selectedIdx = this.filteredOptions.findIndex((o) =>
				this.isSelected(o)
			);
			this.activeIndex = selectedIdx === -1 ? 0 : selectedIdx;
		},

		moveActive(step) {
			if (!this.hasOptions) return;
			let next = this.activeIndex + step;
			const max = this.filteredOptions.length - 1;
			if (next < 0) next = max;
			if (next > max) next = 0;
			this.activeIndex = next;
			this.scrollActiveIntoView();
		},

		scrollActiveIntoView() {
			this.$nextTick(() => {
				const list = this.$refs.listbox;
				const active = document.getElementById(this.optionId(this.activeIndex));
				if (!list || !active) return;

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
			if (!this.hasOptions) return;
			const option = this.filteredOptions[this.activeIndex];
			if (option) this.selectOption(option);
		},

		// --- selection ---
		selectOption(option) {
			this.selectedOption = option; // watcher will push value into modelValue
			this.closeAndFocusButton();
		},

		isSelected(option) {
			return (
				this.selectedOption && this.selectedOption.value === option.value
			);
		},

		updateSelect(value) {
			this.selectedOption = this.options.find(o => o.label === value) || null;
		}
	};
};

const LineItem = (props) => {
	return {
		id: props.lineItemId,
		qty: props.qty,
		min: props.min,
		max: props.max,
		stock: props.stock,
		unlimitedStock: props.unlimitedStock,
		showErrorMaxMessage: props.showErrorMaxMessage,
		showErrorMinMessage: props.showErrorMinMessage,
		showErrorStockMessage: props.showErrorStockMessage,
		action: '',
		sending: false,

		input() {
			let q =  this.qty.toString();
			q = q.replace(/\D/g, '');;
			this.qty = q;

			if(this.qty) {
				this.updateQty();
			}

		},
		increment() {
			this.removeMessages();
			this.qty++;
			this.updateQty();
		},
		decrement() {
			this.removeMessages();
			if (this.qty === 1) {
				this.remove();
			} else {
				this.qty = this.qty > 1 ? (this.qty - 1) : 1;
				this.updateQty();
			}
		},
		async remove() {
			const form = document.querySelector(`#lineItemQty-${props.id}`);
			const formData = new FormData(form)
			formData.set(`lineItems[${props.id}][remove]`, true);
			this.action = 'remove';
			this.sending = true;

			try {
				const response = await fetch('/actions/commerce/cart/update-cart', {
					method: 'POST',
					headers: {
						'Accept': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
					},
					body: formData,
				});

				if (!response.ok) {
					throw new Error('Network response was not ok');
				}

				const data = await response.json();

				// TEMP: Reloading here to refresh the cart page instead of updating the data via ajax
				location.reload();

				/*
				// we should only do this if the ajax operation was successful
				const container = form.closest('article');
				container.remove();
				*/

			} catch (error) {
				console.error('Error:', error);
			}
		},
		blur() {
			this.qty = this.qty === '' ? 0 : this.qty;
		},
		removeMessages() {
			this.showErrorMaxMessage = false;
			this.showErrorMinMessage = false;
			this.showErrorStockMessage = false;
		},
		async updateQty() {
			if (this.unlimitedStock === 0 && this.qty > this.stock) {
				this.qty = this.stock;
				this.showErrorStockMessage = true;
			} else if (this.qty < this.min && this.min !== 0) {
				this.qty = this.min;
				this.showErrorMinMessage = true;
			} else if (this.unlimitedStock && this.max && this.qty > this.max) {
				this.qty = this.max;
				this.showErrorMaxMessage = true;
			} else {
				props.qty = this.qty;

				const form = document.querySelector(`#lineItemQty-${props.id}`);
				const formData = new FormData(form)
				formData.set(`lineItems[${props.id}][qty]`, props.qty);
				this.action = 'update';
				this.sending = true;

				try {
					const response = await fetch('/actions/commerce/cart/update-cart', {
						method: 'POST',
						headers: {
							'Accept': 'application/json',
							'X-Requested-With': 'XMLHttpRequest'
						},
						body: formData,
					});

					if (!response.ok) {
						throw new Error('Network response was not ok');
					}

					const data = await response.json();
					location.reload();
				} catch (error){
					console.error('Error:', error);
				}
			}
		}
	};
};

const Payment = (props) => {
	return {
		gatewayId: props.gatewayId,
		billingSameAsShipping: props.billingSameAsShipping,
		addressBookBillingAddress: props.addressBookBillingAddress,
		newBillingAddress: props.newBillingAddress,
		billingAddressId: props.billingAddressId,
		editAddress: props.editAddress,
		async selectSameAsShipping() {
			this.editAddress = 0;
			this.newBillingAddress = 0;
			this.billingSameAsShipping = 1;
			this.addressBookBillingAddress = 0;
			this.billingAddressId = null;

			callAction(
				'/actions/commerce/cart/update-cart',
				new FormData(document.querySelector(`#sameAsShippingForm`))
			);
		},
		async selectAddress() {
			this.editAddress = 0;
			this.newBillingAddress = 0;
			this.billingSameAsShipping = 0;
			this.addressBookBillingAddress = 1;

			callAction(
				'/actions/commerce/cart/update-cart',
				new FormData(document.querySelector(`#addressBookForm`))
			);
		},
	};
};

Alpine.plugin(focus);
Alpine.data('ClearableInput', ClearableInput);
Alpine.data('SearchableSelect', SearchableSelect);
Alpine.data('LineItem', LineItem);
Alpine.data('Payment', Payment);

window.Alpine = Alpine;
Alpine.start();
