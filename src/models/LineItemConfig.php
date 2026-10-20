<?php

namespace fostercommerce\fostercheckout\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;

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
	 * TODO: Build save for later, which has no button yet. Kept so stored settings and config files still load.
	 */
	public bool $enableSaveForLater = false;

	/**
	 * What transforms line item images: `auto`, `craft`, `imager-x` or `smallpics`. `auto` uses Imager X when it is
	 * enabled and Small Pics is not, and Craft's transforms otherwise.
	 */
	public string $imageTransformer = 'auto';

	/**
	 * Params passed to Imager X or Small Pics with each line item image. String values can name an environment variable.
	 *
	 * @var ?array<non-empty-string, mixed>
	 */
	public ?array $imageTransformConfig = null;

	private bool $loggedDisabledImageTransformer = false;

	private bool $loggedImagerXConfigRead = false;

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($this->upgradeLineItemOptions($config));
	}

	/**
	 * Handle of the plugin that transforms line item images, or `craft` for Craft's own transforms.
	 */
	public function imageTransformerInUse(): string
	{
		$plugins = Craft::$app->getPlugins();

		if ($this->imageTransformer === 'auto') {
			return $plugins->isPluginEnabled('imager-x') && ! $plugins->isPluginEnabled('smallpics') ? 'imager-x' : 'craft';
		}

		// Use Craft's transforms when the chosen plugin is disabled, since its Twig variable is then missing
		if ($this->imageTransformer !== 'craft' && ! $plugins->isPluginEnabled($this->imageTransformer)) {
			// Log once per request, since every line item image calls this
			if (! $this->loggedDisabledImageTransformer) {
				Craft::warning("Line item images use Craft's transforms because `{$this->imageTransformer}` is not an enabled plugin, so the image transform params are not applied.", __METHOD__);
				$this->loggedDisabledImageTransformer = true;
			}

			return 'craft';
		}

		return $this->imageTransformer;
	}

	/**
	 * Turn the params table's posted rows into a map of param name to value, dropping rows without a name.
	 *
	 * @return array<non-empty-string, string>
	 */
	public static function imageTransformConfigFromRows(mixed $paramRows): array
	{
		$imageTransformConfig = [];

		foreach ((array) $paramRows as $paramRow) {
			if (! is_array($paramRow)) {
				continue;
			}

			$paramName = is_string($paramRow['name'] ?? null) ? trim($paramRow['name']) : '';

			if ($paramName === '') {
				continue;
			}

			$paramValue = $paramRow['value'] ?? '';
			$imageTransformConfig[$paramName] = is_string($paramValue) ? trim($paramValue) : '';
		}

		return $imageTransformConfig;
	}

	/**
	 * @return array<non-empty-string, mixed>
	 */
	public function imageTransformParams(): array
	{
		$params = $this->imageTransformConfig ?? [];

		// Type the values, since Imager X reads the string `false` as true
		array_walk_recursive($params, static function (mixed &$paramValue): void {
			if (is_string($paramValue)) {
				$paramValue = App::normalizeValue(App::parseEnv($paramValue));
			}
		});

		return $params;
	}

	/**
	 * @return ?array<non-empty-string, mixed>
	 * @deprecated Use `imageTransformConfig` instead.
	 */
	public function getImagerXConfig(): ?array
	{
		// Log once per request, since every line item image reads this
		if (! $this->loggedImagerXConfigRead) {
			// Log a warning instead of calling the deprecator, which throws when throwExceptions is set
			Craft::warning('`LineItemConfig::getImagerXConfig()` is deprecated. Use `imageTransformConfig` instead.', 'deprecation-error');
			$this->loggedImagerXConfigRead = true;
		}

		return $this->imageTransformConfig;
	}

	/**
	 * @param ?array<non-empty-string, mixed> $imageTransformConfig
	 * @deprecated Use `imageTransformConfig` instead.
	 */
	public function setImagerXConfig(?array $imageTransformConfig): void
	{
		// Log a warning instead of calling the deprecator, which throws when throwExceptions is set
		Craft::warning('`LineItemConfig::setImagerXConfig()` is deprecated. Use `imageTransformConfig` instead.', 'deprecation-error');
		$this->imageTransformConfig = $imageTransformConfig;
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
			[
				'imageTransformer',
				'in',
				'range' => ['auto', 'craft', 'imager-x', 'smallpics'],
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
