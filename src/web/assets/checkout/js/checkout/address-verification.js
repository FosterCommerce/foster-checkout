const FIELDS = [
	'addressLine1',
	'addressLine2',
	'locality',
	'administrativeArea',
	'postalCode',
	'countryCode',
];

// Avalara returns the USPS spelling, so a suffix the customer wrote in full is not a correction.
// Standard abbreviations from USPS Publication 28, Appendix C1.
const STREET_SUFFIXES = {
	ALLEY: 'ALY',
	AVENUE: 'AVE',
	BOULEVARD: 'BLVD',
	CENTER: 'CTR',
	CIRCLE: 'CIR',
	COURT: 'CT',
	COVE: 'CV',
	CREEK: 'CRK',
	CRESCENT: 'CRES',
	CROSSING: 'XING',
	DRIVE: 'DR',
	ESTATE: 'EST',
	EXPRESSWAY: 'EXPY',
	EXTENSION: 'EXT',
	FREEWAY: 'FWY',
	GARDEN: 'GDN',
	GROVE: 'GRV',
	HEIGHTS: 'HTS',
	HIGHWAY: 'HWY',
	HILL: 'HL',
	ISLAND: 'IS',
	JUNCTION: 'JCT',
	LAKE: 'LK',
	LANDING: 'LNDG',
	LANE: 'LN',
	LOOP: 'LOOP',
	MANOR: 'MNR',
	MEADOW: 'MDW',
	MOUNT: 'MT',
	MOUNTAIN: 'MTN',
	PARKWAY: 'PKWY',
	PASS: 'PASS',
	PLACE: 'PL',
	PLAZA: 'PLZ',
	POINT: 'PT',
	RIDGE: 'RDG',
	RIVER: 'RIV',
	ROAD: 'RD',
	ROUTE: 'RTE',
	SQUARE: 'SQ',
	STATION: 'STA',
	STREET: 'ST',
	SUMMIT: 'SMT',
	TERRACE: 'TER',
	TRAIL: 'TRL',
	TURNPIKE: 'TPKE',
	VALLEY: 'VLY',
	VIEW: 'VW',
	VILLAGE: 'VLG',
	WAY: 'WAY',
};

const DIRECTIONS = {
	NORTH: 'N',
	SOUTH: 'S',
	EAST: 'E',
	WEST: 'W',
	NORTHEAST: 'NE',
	NORTHWEST: 'NW',
	SOUTHEAST: 'SE',
	SOUTHWEST: 'SW',
};

// Avalara appends ZIP+4 and abbreviates street words on every address
const normalize = (value, attribute) => {
	const text =
		attribute === 'postalCode'
			? String(value ?? '')
					.trim()
					.replace(/^(\d{5})-\d{4}$/, '$1')
			: String(value ?? '');

	const cleaned = text
		.toUpperCase()
		.replace(/[^A-Z0-9 ]/g, ' ')
		.replace(/\s+/g, ' ')
		.trim();

	if (attribute !== 'addressLine1' && attribute !== 'addressLine2') {
		return cleaned;
	}

	return cleaned
		.split(' ')
		.map((word) => STREET_SUFFIXES[word] ?? DIRECTIONS[word] ?? word)
		.join(' ');
};

export const AddressVerification = (props) => ({
	prefix: props.prefix ?? '',
	suggestion: null,
	verifying: false,
	dismissed: '',
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

			// SearchableSelect derives its label from modelValue, so a direct write leaves the old one showing
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
		this.dismissed = this.signature(this.entered());
		localStorage.setItem(this.storageKey(), this.dismissed);
		this.suggestion = null;
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
