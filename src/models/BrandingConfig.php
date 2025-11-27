<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class BrandingConfig extends Model
{
	/**
	 * The brand primary custom color in HEX color
	 */
	public string $color = '#1F2937';

	/**
	 * The background colour of the header in HEX color
	 */
	public string $headerBgColor = '#F3F3F3';

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
	 * The general component styles. Either 'rounded' (default) or 'flat'
	 */
	public string $style = 'rounded';

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
}
