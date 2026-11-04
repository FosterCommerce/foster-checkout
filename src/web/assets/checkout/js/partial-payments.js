export const PartialPaymentControls = () => ({
	get busy() {
		return this.$store.partialPayments.busy;
	},

	get error() {
		return this.$store.partialPayments.error;
	},

	async submitPartialPayment(form) {
		if (this.busy) {
			return;
		}

		this.$store.partialPayments.busy = true;
		this.$store.partialPayments.error = '';

		try {
			const response = await fetch(window.location.href, {
				method: 'POST',
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
				body: new FormData(form),
				credentials: 'same-origin',
			});
			const data = await response.json();

			const error =
				!response.ok || data.success === false
					? data.error || data.message || form.dataset.errorMessage
					: '';
			this.$store.partialPayments.error = error;

			if (!error && data.redirect) {
				window.location.assign(data.redirect);
				return;
			}

			const cart = data.cart || data.model;
			if (cart) {
				this.$dispatch('partial-payment-updated', cart);
				await this.$nextTick();
			}
		} catch (error) {
			console.error(error);
			this.$store.partialPayments.error = form.dataset.errorMessage;
		} finally {
			this.$store.partialPayments.busy = false;
		}
	},
});
