<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class BrandingConfig extends Model
{
	public string $color = '#1F2937';

	public string $headerBgColor = '#F3F3F3';

	/**
	 * Color of anything drawn on the header bar, which the brand color cannot serve since a store
	 * may set both to the same value.
	 */
	public string $headerTextColor = '#1F2937';

	/**
	 * The Google web font (https://fonts.google.com/) family name you want to use
	 * (ex. 'Roboto Slab')
	 */
	public string $font = 'Rubik';

	/**
	 * The relative path from the web root of the logo file
	 * (ex. '/assets/images/logo.svg')
	 */
	public string $logo = '';

	/**
	 * Height of the logo in the header, in pixels.
	 */
	public int $logoHeight = 40;

	public string $style = 'rounded';

	public string $labelStyle = 'floating';

	/**
	 * The first part of the text in the title meta tag.
	 * Leave blank to use the Craft's siteName
	 */
	public string $title = '';

	/**
	 *  Array of favicons to be used in the checkout
	 * @var array<non-empty-string, string>
	 */
	public array $faviconConfig = [];

	/**
	 * @return array<array-key, mixed>
	 */
	#[\Override]
	protected function defineRules(): array
	{
		return [
			...parent::defineRules(),
			// A height of zero renders the logo at no height, and a large one grows the header past the rest of the bar
			[
				'logoHeight',
				'integer',
				'min' => 40,
				'max' => 200,
			],
		];
	}
}
