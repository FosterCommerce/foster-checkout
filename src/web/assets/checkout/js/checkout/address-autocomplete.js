const FIELDS = [
	'addressLine1',
	'addressLine2',
	'locality',
	'administrativeArea',
	'postalCode',
	'countryCode',
];

const MIN_QUERY = 3;

export const AddressAutocomplete = (props) => ({
	prefix: props.prefix ?? '',
	suggestions: [],
	open: false,
	activeIndex: -1,
	container: null,
	session: '',
	timer: null,
	filling: false,
	latestRequest: 0,

	listboxId() {
		return `${this.prefix || 'address'}-suggestions`;
	},

	optionId(index) {
		return `${this.listboxId()}-${index}`;
	},

	// The address line is rendered by the field layout, so its combobox state is set from here
	syncField() {
		const field = this.input('addressLine1');

		if (!field) {
			return;
		}

		field.setAttribute('role', 'combobox');
		field.setAttribute('aria-autocomplete', 'list');
		field.setAttribute('aria-expanded', this.open ? 'true' : 'false');
		field.setAttribute('aria-controls', this.listboxId());

		if (this.activeIndex >= 0) {
			field.setAttribute(
				'aria-activedescendant',
				this.optionId(this.activeIndex)
			);
			return;
		}

		field.removeAttribute('aria-activedescendant');
	},

	input(attribute) {
		const name = this.prefix ? `${this.prefix}[${attribute}]` : attribute;

		return this.$root.querySelector(`[name="${CSS.escape(name)}"]`);
	},

	postUrl() {
		return window.location.pathname + window.location.search;
	},

	// Google bills an autocomplete session as one lookup, keyed on this token
	sessionToken() {
		if (this.session === '') {
			this.session = crypto.randomUUID();
		}

		return this.session;
	},

	async post(action, fields) {
		const body = new FormData();
		body.set(window.csrfTokenName, window.csrfTokenValue);
		body.set('action', `foster-checkout/address-lookup/${action}`);
		body.set('session', this.sessionToken());

		Object.entries(fields).forEach(([key, value]) => body.set(key, value));

		try {
			const response = await fetch(this.postUrl(), {
				method: 'POST',
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
				body,
				credentials: 'same-origin',
			});

			return response.ok ? await response.json() : {};
		} catch {
			return {};
		}
	},

	queueSuggest() {
		// Filling the fields dispatches input, which would search for the address just chosen
		if (this.filling) {
			return;
		}

		clearTimeout(this.timer);
		this.container = null;
		this.timer = setTimeout(() => this.suggest(), 300);
	},

	async suggest() {
		const query = this.input('addressLine1')?.value ?? '';

		if (query.trim().length < MIN_QUERY) {
			this.close();
			return;
		}

		this.latestRequest += 1;
		const request = this.latestRequest;

		const data = await this.post('suggest', {
			query,
			countryCode: this.input('countryCode')?.value ?? '',
			container: this.container ?? '',
		});

		// An earlier request can answer last, for a query the customer has moved on from
		if (request !== this.latestRequest) {
			return;
		}

		this.suggestions = data.suggestions ?? [];
		this.activeIndex = -1;
		this.open = this.suggestions.length > 0;
	},

	// A street or postal district narrows the next search rather than filling the form
	async choose(suggestion) {
		clearTimeout(this.timer);

		if (!suggestion.isFinal) {
			this.container = suggestion.id;
			await this.suggest();
			return;
		}

		const data = await this.post('retrieve', {
			id: suggestion.id,
		});

		this.close();
		this.session = '';

		if (data.address) {
			this.fill(data.address);
		}
	},

	fill(address) {
		this.filling = true;

		FIELDS.forEach((attribute) => {
			const input = this.input(attribute);

			if (!input) {
				return;
			}

			const value = address[attribute] ?? '';

			// A searchable select rebinds its hidden input from modelValue, so a direct write is overwritten
			if (input.type === 'hidden') {
				window.dispatchEvent(
					new CustomEvent('setvalue', {
						detail: {
							name: input.name,
							value,
						},
					})
				);
				return;
			}

			input.value = value;
			input.dispatchEvent(new Event('input', { bubbles: true }));
			input.dispatchEvent(new Event('change', { bubbles: true }));
		});

		this.filling = false;

		// The provider already resolved this address, so verification has nothing to add
		window.dispatchEvent(new CustomEvent('addresschosen'));
	},

	move(step) {
		if (!this.open) {
			return;
		}

		const last = this.suggestions.length - 1;
		this.activeIndex = Math.min(Math.max(this.activeIndex + step, 0), last);
	},

	close() {
		clearTimeout(this.timer);
		this.open = false;
		this.suggestions = [];
		this.activeIndex = -1;
		this.container = null;
	},
});
