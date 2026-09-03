const CARD_FIELDS = ['number', 'month', 'year', 'cvv'];

const AUTHORIZE_ERROR_FIELDS = {
	E_WC_04: 'number',
	E_WC_05: 'number',
	E_WC_06: 'month',
	E_WC_07: 'year',
	E_WC_08: 'year',
	E_WC_15: 'cvv',
};

/**
 * Payment gateway forms, and the card fields Authorize.net injects.
 *
 * Single-page saves the cart as the customer types, so a mounted Stripe or PayPal form holds a
 * stale total and has to be torn down and rebuilt.
 */
export const gatewayHandling = () => ({
	async onPaySubmit(event) {
		if (event.submitter?.id?.endsWith('authorizeSubmit')) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		if (!this.canPay) {
			return;
		}

		if (!this.deliveryReadyForPay) {
			this.panelFieldsReady('delivery', null, true);
			return;
		}

		if (
			!this.hasEmail ||
			!this.hasShippingSelection ||
			!this.cartHasShippingAddress ||
			!this.hasShippingMethod ||
			!this.hasBilling
		) {
			this.panelFieldsReady('delivery', null, true);
			return;
		}

		const payloadChanged =
			JSON.stringify(this.buildPayload()) !== this.lastSaved;

		this.paying = true;

		if (this.saveTimer || payloadChanged) {
			if (this.saveTimer) {
				clearTimeout(this.saveTimer);
				this.saveTimer = null;
			}

			await this.saveCart({ panel: 'payment' });
			if (this.statusTone === 'error') {
				this.paying = false;
				return;
			}
		}

		this.lastSaved = JSON.stringify(this.buildPayload());
		this.$refs.paymentForm?.submit();
	},

	bindPayOverlay() {
		const form = this.$refs.paymentForm;
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
	},

	wrapAuthorizeHandler() {
		if (!this.cardInput('authorizeSubmit')) {
			return;
		}

		this.wrapCardFields();
		const checkout = this;

		if (
			!this.originalSendPayment &&
			typeof window.sendPaymentDataToAnet === 'function'
		) {
			this.originalSendPayment = window.sendPaymentDataToAnet;
			window.sendPaymentDataToAnet = async function (...args) {
				if (!checkout.canPay || !checkout.checkCard()) {
					checkout.paying = false;
					return;
				}

				checkout.paying = true;

				if (checkout.saveTimer) {
					clearTimeout(checkout.saveTimer);
					checkout.saveTimer = null;
				}

				const payloadChanged =
					JSON.stringify(checkout.buildPayload()) !== checkout.lastSaved;

				if (payloadChanged || checkout.saving) {
					await checkout.saveCart({ panel: 'payment' });
					if (checkout.statusTone === 'error') {
						checkout.paying = false;
						return;
					}
				}

				return checkout.originalSendPayment.apply(this, args);
			};
		}

		if (
			this.originalAuthorizeHandler ||
			typeof window.responseHandler !== 'function'
		) {
			return;
		}

		this.originalAuthorizeHandler = window.responseHandler;
		window.responseHandler = function (...args) {
			const response = args[0];
			if (response?.messages?.resultCode === 'Error') {
				checkout.paying = false;
				checkout.showAuthorizeErrors(response);
				return;
			}

			return checkout.originalAuthorizeHandler.apply(this, args);
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
			if (!input?.parentElement || input.parentElement.dataset.fcCardField) {
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
		const cvv = String(this.cardInput('cvv')?.value || '').replace(/\s+/g, '');
		const month = parseInt(monthValue, 10);
		const errors = {};

		if (!/^\d{13,19}$/.test(number)) {
			errors.number = this.cardNumberError;
		}

		if (!/^\d{1,2}$/.test(monthValue) || month < 1 || month > 12) {
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
		const labels = {
			E_WC_05: this.cardNumberError,
			E_WC_06: this.cardMonthError,
			E_WC_07: this.cardYearError,
			E_WC_08: this.cardExpiredError,
			E_WC_15: this.cardCvvError,
		};
		const errors = {};

		(response.messages.message || []).forEach((item) => {
			const name = AUTHORIZE_ERROR_FIELDS[item.code] || 'number';
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
		const label = this.payButtonLabel;
		form
			.querySelectorAll(
				'button[type="submit"], [id$="authorizeSubmit"], .stripe-payment-elements-submit-button'
			)
			.forEach((button) => {
				button.disabled = !allowed;
				if (label && !button.closest('.paypal-rest-form')) {
					button.textContent = label;
				}
			});
	},

	invalidatePaypalCheckout() {
		const wrapper = this.$root.querySelector('.paypal-rest-form');
		const renderDiv = wrapper?.firstElementChild;
		if (!wrapper || !renderDiv) {
			return;
		}

		if (
			!renderDiv.childElementCount &&
			!String(renderDiv.innerHTML || '').trim()
		) {
			return;
		}

		renderDiv.innerHTML = '';
		delete wrapper.dataset.fcPaypalInit;
		this.paypalInvalidated = true;
	},

	maybeReinitPaypalCheckout() {
		if (!this.paypalInvalidated) {
			return;
		}

		this.paypalInvalidated = false;

		if (!this.$root.querySelector('.paypal-rest-form')) {
			return;
		}

		this.initPaypal();
	},

	restorePaypalIfSkipped() {
		if (this.paypalInvalidated && !this.saving && !this.saveTimer) {
			this.maybeReinitPaypalCheckout();
		}
	},

	invalidateStripeCheckout() {
		const form = this.$root.querySelector('.stripe-payment-elements-form');
		if (!form) {
			return;
		}

		const paymentElement = form.querySelector('.stripe-payment-element');
		const hasMounted =
			Boolean(paymentElement?.childElementCount) ||
			Boolean(String(paymentElement?.innerHTML || '').trim());
		const hasHandler = Boolean(form.handlerInstance);

		if (hasMounted || hasHandler) {
			const clone = form.cloneNode(true);
			const clonePayment = clone.querySelector('.stripe-payment-element');
			if (clonePayment) {
				clonePayment.innerHTML = '';
			}

			const cloneError = clone.querySelector('.stripe-error-message');
			if (cloneError) {
				cloneError.textContent = '';
			}

			form.replaceWith(clone);
		}

		this.stripeInvalidated = true;
	},

	scheduleStripeReinit() {
		if (!this.canPay || this.paying) {
			return;
		}

		if (!this.$root.querySelector('.stripe-payment-elements-form')) {
			return;
		}

		this.invalidateStripeCheckout();
		this.stripeInvalidated = true;
		this.maybeReinitStripeCheckout();
	},

	maybeReinitStripeCheckout() {
		if (!this.stripeInvalidated) {
			return;
		}

		this.stripeInvalidated = false;

		if (!this.$root.querySelector('.stripe-payment-elements-form')) {
			return;
		}

		if (typeof initStripe !== 'function') {
			return;
		}

		initStripe();
		this.$nextTick(() => this.syncPayButtons());
	},

	restoreStripeIfSkipped() {
		if (this.stripeInvalidated && !this.saving && !this.saveTimer) {
			this.scheduleStripeReinit();
		}
	},

	retryStripeIfNeeded() {
		const error = this.$refs.paymentForm?.querySelector(
			'.stripe-error-message'
		);
		if (!error?.textContent?.trim() || typeof initStripe !== 'function') {
			return;
		}

		error.textContent = '';
		initStripe();
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
});
