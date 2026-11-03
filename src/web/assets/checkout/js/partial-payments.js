export const PartialPaymentControls = (singlePage = false) => ({
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
			if (singlePage) {
				if (cart) {
					this.$dispatch('partial-payment-updated', cart);
					await this.$nextTick();
				}
			} else if (cart || !error) {
				await this.refreshPaymentPage();
			}
		} catch {
			this.$store.partialPayments.error = form.dataset.errorMessage;
		} finally {
			this.$store.partialPayments.busy = false;
		}
	},

	async refreshPaymentPage() {
		const response = await fetch(window.location.href, {
			credentials: 'same-origin',
			headers: { Accept: 'text/html' },
		});

		if (!response.ok) {
			throw new Error('Unable to refresh checkout.');
		}

		const page = new DOMParser().parseFromString(
			await response.text(),
			'text/html'
		);
		if (new URL(response.url).origin !== window.location.origin) {
			throw new Error('Checkout refresh changed origin.');
		}

		const current = document.querySelector('[data-fc-checkout-content]');
		const replacement = page.querySelector('[data-fc-checkout-content]');

		if (!current || !replacement) {
			throw new Error('Checkout response is missing the payment page.');
		}

		current
			.querySelectorAll('.stripe-payment-elements-form')
			.forEach((element) => {
				element.handlerInstance?.elements?.destroy();
			});
		const content = document.importNode(replacement, true);
		current.replaceWith(content);
		// Gateway markup may contain inline setup such as refreshed CSRF tokens.
		for (const source of content.querySelectorAll('script:not([src])')) {
			if (
				source.type &&
				!['text/javascript', 'application/javascript'].includes(source.type)
			) {
				continue;
			}

			const script = document.createElement('script');
			for (const attribute of source.attributes) {
				script.setAttribute(attribute.name, attribute.value);
			}
			script.textContent = source.textContent;
			source.replaceWith(script);
		}
		await this.$nextTick();

		// Removing full credit can reveal a gateway whose script was absent on the original page.
		const loadedScripts = new Set(
			Array.from(
				document.querySelectorAll('script[src]'),
				(script) => script.src
			)
		);
		for (const source of page.querySelectorAll('script[src]')) {
			const url = new URL(source.getAttribute('src'), response.url).href;
			if (loadedScripts.has(url)) {
				continue;
			}

			await new Promise((resolve, reject) => {
				const script = document.createElement('script');
				for (const attribute of source.attributes) {
					script.setAttribute(attribute.name, attribute.value);
				}
				script.src = url;
				script.onload = resolve;
				script.onerror = reject;
				document.head.append(script);
			});
			loadedScripts.add(url);
		}
	},
});
