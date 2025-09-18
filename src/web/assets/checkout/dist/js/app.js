import {createApp, reactive} from 'https://unpkg.com/petite-vue?module';

const App = reactive({
	loaded: false,

	init() {
		this.loaded = true;
		Hide();
	}
});

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
		input() {
			this.showButton = this.value !== '';
			this.errors = [];
		},
		focus() {
			this.showButton = this.value !== '';
		},
		blur($el) {
			const button = $el.closest('div').querySelector('button');
			this.showButton = (button === document.activeElement) && (this.value !== '');
		},
		clear($el) {
			this.value = '';
			this.errors = [];
			const input = $el.closest('div').querySelector('input.js-clearable, textarea.js-clearable');
			input.focus();
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
	}
};

async function callAction(url, body = {}, method = 'POST') {
	return await fetch(url, {
		headers: {
			'Accept': 'application/json',
			'X-Requested-With': 'XMLHttpRequest'
		},
		method,
		body,
	})
}

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
	}
};

const debounce = (wait, callback) => {
	let timeoutId = null;

	return function (...args) {
		window.clearTimeout(timeoutId);

		function execFn() {
			callback.apply(this, ...args);
		}

		timeoutId = window.setTimeout(() => execFn.apply(this), wait);
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
		input: debounce(700, function () {
			this.qty = this.qty.replace(/\D/g, '');
			console.log(`input ${this.qty}`);
			if(this.qty) {
				console.log('do update');
				this.updateQty();
			}
		}),
		increment() {
			this.removeMessages();
			this.qty++;
			this.updateQty();
		},
		decrement() {
			this.removeMessages()
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

			await fetch('/actions/commerce/cart/update-cart', {
				method: 'POST',
				headers: {
					'Accept': 'application/json',
					'X-Requested-With': 'XMLHttpRequest'
				},
				body: formData,
			})
				.then(response => {
					if (!response.ok) {
						throw new Error('Network response was not ok');
					}
					return response.json();
				})
				.then(data => {
					// TEMP: Reloading here to refresh the cart page instead of updating the data via ajax
					location.reload();
					/*
					// we should only do this if the ajax operation was successful
					const container = form.closest('article');
					container.remove();
					*/
				})
				.catch(error => {
					console.error('Error:', error);
				});

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
			} else if (this.qty < this.min && this.min != 0) {
				this.qty = this.min;
				this.showErrorMinMessage = true;
			} else if (this.unlimitedStock && this.max && this.qty > this.max) {
				this.qty = this.max;
				this.showErrorMaxMessage = true;
			} else {
				props.qty = this.qty;
				// UpdateQty(props);

				const form = document.querySelector(`#lineItemQty-${props.id}`);
				const formData = new FormData(form)
				formData.set(`lineItems[${props.id}][qty]`, props.qty);
				this.action = 'update';
				this.sending = true;

				await fetch('/actions/commerce/cart/update-cart', {
					method: 'POST',
					headers: {
						'Accept': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
					},
					body: formData,
				})
					.then(response => {
						if (!response.ok) {
							throw new Error('Network response was not ok');
						}
						return response.json();
					})
					.then(data => {
						// TEMP: Reloading here to refresh the cart page instead of updating the data via ajax
						location.reload();
					})
					.catch(error => {
						console.error('Error:', error);
					});
			}
		}
	}
};


const FocusModal = (props) => {
	return {
		isOpen: props.isOpen,
		toggleModal() {
			this.isOpen = !this.isOpen;
		},
		mounted() {
			const modal = this.$refs.modal;
			const focusableEls = modal.querySelectorAll('a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]), select:not([disabled])');
			const firstFocusableEl = focusableEls[0];
			const lastFocusableEl = focusableEls[focusableEls.length - 1];
			const KEYCODE_TAB = 9;

			modal.addEventListener('keydown', (evt) => {
				const isTab = (evt.key === 'Tab' || evt.keyCode === KEYCODE_TAB);
				if (isTab) {
					if (evt.shiftKey) {
						if (document.activeElement === firstFocusableEl) {
							lastFocusableEl.focus();
							evt.preventDefault();
						}
					} else {
						if (document.activeElement === lastFocusableEl) {
							firstFocusableEl.focus();
							evt.preventDefault();
						}
					}
				}
			});
		}
	}
};

// Hide any elements that we only show if JS doesn't load
const Hide = () => {
	const els = document.querySelectorAll('.js-hide')
	els.forEach((el) => el.classList.add('hidden'));
}

// Initialize Petite Vue and mount
createApp({
	// Set delimiters in Petite Vue so they do not interfere with Twig delimiters
	$delimiters: ['${', '}'],

	// Include the modules above
	App,
	ClearableInput,
	FocusModal,
	LineItem,
	Hide,
	Payment,
}).mount();
