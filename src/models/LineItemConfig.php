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
	 * Whether the cart shows how many of a line item are left in stock.
	 */
	public bool $showLineItemStock = true;

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
	 * Width of a cart line item's image in pixels, at desktop widths. Narrower screens use half of it.
	 */
	public int $imageSize = 150;

	/**
	 * How an image fills its square box: `contain` letterboxes the whole image, `cover` crops it to fill.
	 */
	public string $imageFit = 'contain';

	/**
	 * Whether a line item with no image shows a "No Image" placeholder.
	 */
	public bool $enablePlaceholderImages = false;

	/**
	 * Whether each line item offers a "save for later" button.
	 */
	public bool $enableSaveForLater = false;

	/**
	 * Transform config passed to Imager X, when that plugin renders the line item images.
	 *
	 * @var ?array<non-empty-string, mixed>
	 */
	public ?array $imagerXConfig = null;

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($this->upgradeLineItemOptions($config));
	}

	/**
	 * @return array<array-key, mixed>
	 */
	#[\Override]
	protected function defineRules(): array
	{
		return [
			...parent::defineRules(),
			// A width of zero is dropped from a transform, and a large one pushes the rest of the line item off screen
			[
				'imageSize',
				'integer',
				'min' => 40,
				'max' => 200,
			],
		];
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

		// Read the switch value, since the legacy setting also held the hidden-name prefix
		if (in_array($posted, ['', '0', '1'], true)) {
			$config['enableLineItemOptions'] = $posted === '1';

			return $config;
		}

		$config['enableLineItemOptions'] = true;
		$config['hiddenLineItemOptionPrefix'] ??= $posted;

		return $config;
	}
}
