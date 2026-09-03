const FIELDS = [
	'addressLine1',
	'addressLine2',
	'locality',
	'administrativeArea',
	'postalCode',
	'countryCode',
];

// Avalara appends ZIP+4 and expands abbreviations on every address
const normalize = (value, attribute) => {
	const text =
		attribute === 'postalCode'
			? String(value ?? '')
					.trim()
					.replace(/^(\d{5})-\d{4}$/, '$1')
			: String(value ?? '');

	return text
		.toUpperCase()
		.replace(/[^A-Z0-9 ]/g, ' ')
		.replace(/\s+/g, ' ')
		.trim();
};

export const AddressVerification = (props) => ({
	prefix: props.prefix ?? '',
	suggestion: null,
	verifying: false,
	dismissed: '',
	chosen: false,
	timer: null,

	init() {
		this.dismissed = localStorage.getItem(this.storageKey()) ?? '';
	},

	storageKey() {
		return window.FC_ADDRESS_DISMISSED_KEY;
	},

	input(attribute) {
		const name = this.prefix ? `${this.prefix}[${attribute}]` : attribute;

		return this.$root.querySelector(`[name="${CSS.escape(name)}"]`);
	},

	entered() {
		return Object.fromEntries(
			FIELDS.map((attribute) => [attribute, this.input(attribute)?.value ?? ''])
		);
	},

	signature(address) {
		return FIELDS.map((attribute) =>
			normalize(address[attribute], attribute)
		).join('|');
	},

	// Avalara resolves against a whole address
	isComplete(address) {
		return ['addressLine1', 'locality', 'postalCode', 'countryCode'].every(
			(attribute) => address[attribute].trim() !== ''
		);
	},

	queueVerify() {
		clearTimeout(this.timer);
		this.timer = setTimeout(() => this.verify(), 600);
	},

	async verify() {
		const entered = this.entered();

		if (this.chosen) {
			this.chosen = false;
			this.remember(entered);
			this.suggestion = null;
			return;
		}

		if (
			!this.isComplete(entered) ||
			this.signature(entered) === this.dismissed
		) {
			this.suggestion = null;
			return;
		}

		this.verifying = true;

		try {
			const body = new FormData();
			body.set(window.csrfTokenName, window.csrfTokenValue);
			body.set('action', 'avatax/json/validate-address');
			FIELDS.forEach((attribute) => body.set(attribute, entered[attribute]));

			const url = window.location.pathname + window.location.search;

			const response = await fetch(url, {
				method: 'POST',
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
				body,
				credentials: 'same-origin',
			});

			this.suggestion = this.suggestionFrom(await response.json(), entered);
		} catch {
			// Verification never blocks checkout, so any failure leaves the address as typed
			this.suggestion = null;
		} finally {
			this.verifying = false;
		}
	},

	suggestionFrom(data, entered) {
		const validated = data?.response?.validatedAddresses?.[0];

		if (data?.success !== true || !validated) {
			return null;
		}

		const suggested = {
			addressLine1: validated.line1 ?? '',
			addressLine2: validated.line2 ?? '',
			locality: validated.city ?? '',
			administrativeArea: validated.region ?? '',
			postalCode: validated.postalCode ?? '',
			countryCode: validated.country ?? '',
		};

		if (this.signature(suggested) === this.signature(entered)) {
			return null;
		}

		return {
			entered,
			suggested,
		};
	},

	useSuggested() {
		const { suggested } = this.suggestion;

		FIELDS.forEach((attribute) => {
			const input = this.input(attribute);

			if (!input || suggested[attribute] === '') {
				return;
			}

			// A searchable select rebinds its hidden input from modelValue, so a direct write is overwritten
			if (input.type === 'hidden') {
				window.dispatchEvent(
					new CustomEvent('setvalue', {
						detail: {
							name: input.name,
							value: suggested[attribute],
						},
					})
				);
				return;
			}

			input.value = suggested[attribute];
			input.dispatchEvent(new Event('input', { bubbles: true }));
			input.dispatchEvent(new Event('change', { bubbles: true }));
		});

		this.dismissed = this.signature(this.entered());
		this.suggestion = null;
	},

	dismiss() {
		this.remember(this.entered());
		this.suggestion = null;
	},

	remember(address) {
		this.dismissed = this.signature(address);
		localStorage.setItem(this.storageKey(), this.dismissed);
	},

	formatted(address) {
		return [
			address.addressLine1,
			address.addressLine2,
			address.locality,
			address.administrativeArea,
			address.postalCode,
		]
			.map((part) => String(part || '').trim())
			.filter(Boolean)
			.join(', ');
	},
});
