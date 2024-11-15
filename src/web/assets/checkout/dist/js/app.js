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

function debounce(fn, delay) {
	let timeout;
	return function (...args) {
		clearTimeout(timeout);
		// We have access to this
		console.log(this);
		timeout = setTimeout(() => fn.apply(...args), delay);
	};
}

const LineItem = (props) => {
	return {
		id: props.lineItemId,
		qty: props.qty,
		min: props.min,
		max: props.max,
		stock: props.stock,
		lineSubtotal: props.lineSubtotal,
		unlimitedStock: props.unlimitedStock,
		showErrorMaxMessage: props.showErrorMaxMessage,
		showErrorMinMessage: props.showErrorMinMessage,
		showErrorStockMessage: props.showErrorStockMessage,
		action: '',
		sending: false,
		input: debounce(() => {
			// this is undefined
			this.qty = this.qty.replace(/\D/g, '');

			if(this.qty) {
				this.updateQty();
				console.log('update quantity');
			}
		}, 700),
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
						/*
					 let item = data.cart.lineItems.filter((lineItem) => lineItem.id === props.id)
					 this.lineSubtotal = item[0].subtotalAsCurrency;
					 */
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
