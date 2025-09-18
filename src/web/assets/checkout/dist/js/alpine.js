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
	})
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
	}
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
	}
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
	}
};


Alpine.plugin(focus);
Alpine.data('ClearableInput', ClearableInput);
Alpine.data('LineItem', LineItem);
Alpine.data('Payment', Payment);

window.Alpine = Alpine;
Alpine.start();
console.log('AlpineJS version');

