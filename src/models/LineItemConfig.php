<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class LineItemConfig extends Model
{
	/**
	 * Whether the cart shows each line item's SKU.
	 */
	public bool $showLineItemSku = true;

	/**
	 * Whether to show line item options at all. Gates the prefix and the rewrite rules.
	 */
	public bool $enableLineItemOptions = true;

	/**
	 * Options whose name starts with this are not shown. Empty shows every option.
	 */
	public string $hiddenLineItemOptionPrefix = '_';

	/**
	 * Cuts a displayed option value to this many characters. Null shows it whole.
	 */
	public ?int $lineItemOptionValueMaxLength = null;

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($this->upgradeLineItemOptions($config));
	}

	/**
	 * `enableLineItemOptions` was a toggle and a hidden-name prefix in one value.
	 *
	 * @param array<array-key, mixed> $config
	 * @return array<array-key, mixed>
	 */
	private function upgradeLineItemOptions(array $config): array
	{
		$posted = $config['enableLineItemOptions'] ?? null;

		if (! is_string($posted)) {
			return $config;
		}

		$config['enableLineItemOptions'] = true;

		if ($posted !== '' && ! isset($config['hiddenLineItemOptionPrefix'])) {
			$config['hiddenLineItemOptionPrefix'] = $posted;
		}

		return $config;
	}
}
