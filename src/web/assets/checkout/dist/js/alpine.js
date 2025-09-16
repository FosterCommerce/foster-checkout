import Alpine from "https://esm.sh/alpinejs@3";
import focus from "https://esm.sh/@alpinejs/focus";

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



Alpine.plugin(focus);
Alpine.data('LineItem', LineItem);

window.Alpine = Alpine;
Alpine.start();
console.log('AlpineJS version');

