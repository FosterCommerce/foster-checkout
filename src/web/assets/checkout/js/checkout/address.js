/**
 * Reading, writing and formatting the shipping and billing addresses.
 */
export const addressBook = () => ({
	addressLabel(addressId, fallback) {
		return this.addressLabels[String(addressId)] || fallback;
	},

	escapeName(name) {
		return typeof CSS !== 'undefined' && CSS.escape
			? CSS.escape(name)
			: name.replaceAll('"', '\\"');
	},

	addressToFields(address) {
		const fields = {};
		if (!address || typeof address !== 'object') {
			return fields;
		}

		[
			'fullName',
			'firstName',
			'lastName',
			'organization',
			'addressLine1',
			'addressLine2',
			'locality',
			'dependentLocality',
			'administrativeArea',
			'postalCode',
			'countryCode',
		].forEach((key) => {
			if (address[key] != null && address[key] !== '') {
				fields[key] = String(address[key]);
			}
		});

		return fields;
	},

	countryLabel(address, fields, scope) {
		if (address) {
			if (address.countryName) {
				return address.countryName;
			}

			if (address.country && address.country.name) {
				return address.country.name;
			}
		}

		const countrySelector =
			'[name="countryCode"], [name="shippingAddress[countryCode]"], [name="billingAddress[countryCode]"]';
		const countryInput = scope ? scope.querySelector(countrySelector) : null;
		const selectRoot = countryInput ? countryInput.closest('[x-data]') : null;
		const selectData = selectRoot ? window.Alpine.$data(selectRoot) : null;
		if (
			selectData &&
			selectData.selectedOption &&
			selectData.selectedOption.label
		) {
			return selectData.selectedOption.label;
		}

		return (fields && fields.countryCode) || '';
	},

	addressFieldsFromPayload(payload, prefix) {
		const fields = {};

		Object.entries(payload).forEach(([key, value]) => {
			if (!key.startsWith(prefix) || !key.endsWith(']')) {
				return;
			}

			fields[key.slice(prefix.length, -1)] = value;
		});

		return fields;
	},

	refreshShippingPreview() {
		if (!this.useNewAddress && this.shippingAddressId) {
			const label = this.addressLabel(this.shippingAddressId, '');
			if (label) {
				this.shippingPreview = label;
			}
			return;
		}

		const scope = this.$root.querySelector('[data-fc-new-shipping]');
		if (!scope) {
			return;
		}

		this.shippingPreview = this.formatAddress(
			this.addressFieldsFromPayload(
				this.collectNamedFields(scope),
				'shippingAddress['
			),
			scope
		);
	},

	formatAddress(fields, scope, address = null) {
		if (!fields || typeof fields !== 'object') {
			return '';
		}

		return [
			fields.fullName,
			fields.addressLine1,
			fields.addressLine2,
			fields.locality,
			fields.administrativeArea,
			fields.postalCode,
			this.countryLabel(address, fields, scope),
		]
			.map((part) => String(part || '').trim())
			.filter(Boolean)
			.join(', ');
	},

	rememberAddress(addressId, address, fields, scope) {
		if (!addressId) {
			return;
		}

		const id = String(addressId);
		const snapshot = { ...(fields || this.addressToFields(address)) };
		delete snapshot.action;
		delete snapshot.addressId;
		this.addressFields = {
			...this.addressFields,
			[id]: snapshot,
		};
		this.addressLabels = {
			...this.addressLabels,
			[id]: this.formatAddress(snapshot, scope, address),
		};
	},

	writeAddressToScope(scope, fields, prefix = '') {
		if (!scope || !fields) {
			return;
		}

		const formRoot = scope.querySelector('[x-data]');
		const formData = formRoot ? window.Alpine.$data(formRoot) : null;
		if (formData) {
			if (fields.countryCode) {
				formData.countryCode = fields.countryCode;
			}
			if (Object.hasOwn(fields, 'administrativeArea')) {
				formData.administrativeArea = fields.administrativeArea;
			}
		}

		Object.entries(fields).forEach(([name, value]) => {
			const inputName = prefix ? `${prefix}[${name}]` : name;
			const input = scope.querySelector(
				`[name="${this.escapeName(inputName)}"]`
			);
			if (!input) {
				return;
			}

			input.value = value;
			const data = window.Alpine.$data(input.closest('[x-data]'));
			if (!data) {
				return;
			}

			if (Object.hasOwn(data, 'value')) {
				data.value = value;
			}

			if (Object.hasOwn(data, 'modelValue')) {
				data.modelValue = value;
			}
		});
	},

	applyAddressFields(addressId) {
		const stored = this.addressFields[String(addressId)];
		if (!stored) {
			return;
		}

		this.$nextTick(() => {
			const scope = this.$root.querySelector(
				`[data-fc-address-edit="${addressId}"]`
			);
			this.writeAddressToScope(scope, stored);
		});
	},

	applyDraftAddress(kind) {
		const address =
			kind === 'billing'
				? this.latestBillingAddress
				: this.latestShippingAddress;
		if (!address) {
			return;
		}

		const prefix = kind === 'billing' ? 'billingAddress' : 'shippingAddress';
		const selector =
			kind === 'billing' ? '[data-fc-new-billing]' : '[data-fc-new-shipping]';

		this.$nextTick(() => {
			const scope = this.$root.querySelector(selector);
			this.writeAddressToScope(scope, this.addressToFields(address), prefix);
			if (kind === 'billing') {
				this.refreshNewBillingContent();
			}

			if (kind === 'shipping') {
				this.refreshShippingPreview();
			}
		});
	},

	async saveAddressBook(addressId) {
		if (!addressId) {
			return;
		}

		if (this.saveTimer) {
			clearTimeout(this.saveTimer);
			this.saveTimer = null;
		}

		const scope = this.$root.querySelector(
			`[data-fc-address-edit="${addressId}"]`
		);

		// Unlike a panel save this posts straight to Craft, so nothing else checks the form first
		if (!this.fieldsReadyIn(scope, true)) {
			return;
		}

		const fields = {
			action: 'users/save-address',
			...this.collectNamedFields(scope),
			addressId: String(addressId),
		};

		const panel =
			parseInt(this.editBillingAddressId, 10) === parseInt(addressId, 10)
				? 'payment'
				: 'delivery';

		const isCurrentShipping =
			parseInt(this.shippingAddressId, 10) === parseInt(addressId, 10);
		const isCurrentBilling =
			!this.billingSameAsShipping &&
			parseInt(this.billingAddressId, 10) === parseInt(addressId, 10);

		if (isCurrentShipping || isCurrentBilling) {
			this.invalidatePaypalCheckout();
			this.invalidateStripeCheckout();
		}

		this.pending += 1;
		this.status = this.savingLabel;
		this.statusTone = 'saving';
		this.setPanelStatus(panel, 'saving');
		this.syncPayButtons();

		try {
			const { response, data, isJson } = await this.postForm(
				this.postUrl(),
				fields
			);
			if (!isJson || !response.ok || data.success === false) {
				if (isJson) {
					this.applyErrors(data);
				} else {
					this.status = this.failedLabel;
					this.statusTone = 'error';
				}
				this.setPanelStatus(panel, 'error');
				this.restorePaypalIfSkipped();
				this.restoreStripeIfSkipped();
				return;
			}

			this.rememberAddress(addressId, null, fields, scope);
			if (parseInt(this.shippingAddressId, 10) === parseInt(addressId, 10)) {
				this.shippingPreview = this.addressLabels[String(addressId)];
			}

			await this.saveCart({
				panel,
				force: isCurrentShipping || isCurrentBilling,
			});
			if (this.statusTone !== 'error') {
				this.editExistingAddress = 0;
				this.editBillingAddressId = 0;
			} else {
				this.setPanelStatus(panel, 'error');
			}
		} catch {
			this.status = this.failedLabel;
			this.statusTone = 'error';
			this.setPanelStatus(panel, 'error');
			this.restorePaypalIfSkipped();
			this.restoreStripeIfSkipped();
		} finally {
			this.pending = Math.max(0, this.pending - 1);
			this.syncPayButtons();
		}
	},

	addressFieldHandle(data) {
		const name = String(data.name || '');
		const match = name.match(/\[([^\]]+)\]$/);

		return match ? match[1] : name;
	},

	addressGroupHasValues(payload, prefix) {
		return Object.keys(payload).some(
			(key) =>
				key.startsWith(prefix) && String(payload[key] || '').trim() !== ''
		);
	},

	addressGroupHasContent(payload, prefix) {
		return ['fullName', 'addressLine1', 'locality', 'postalCode'].some(
			(field) => String(payload[`${prefix}${field}]`] || '').trim() !== ''
		);
	},

	newBillingHasContent() {
		const scope = this.$root
			? this.$root.querySelector('[data-fc-new-billing]')
			: null;

		return this.addressGroupHasContent(
			this.collectNamedFields(scope),
			'billingAddress['
		);
	},

	refreshNewBillingContent() {
		this.hasNewBillingContent = this.newBillingHasContent();
		this.syncPayButtons();
	},

	stripAddressGroup(payload, prefix) {
		Object.keys(payload).forEach((key) => {
			if (key.startsWith(prefix)) {
				delete payload[key];
			}
		});
	},
});
