<?php

namespace fostercommerce\fostercheckout\models;

use Craft;
use craft\base\ElementInterface;
use craft\base\Model;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\errors\InvalidFieldException;
use Stringable;

class TextConfig extends Model implements Stringable
{
	public ?string $elementHandle = null;

	public ?string $fieldHandle = null;

	/**
	 * @var callable(): string|string|null
	 */
	public mixed $value = null;

	private ?string $memo = null;

	/**
	 * @param string|array<array-key, mixed> $config
	 */
	public function __construct($config = [])
	{
		if ($config !== null && $config !== []) {
			if (is_string($config) || is_callable($config)) {
				$config = [
					'value' => $config,
				];
			} elseif (array_key_exists('elementHandle', $config) && array_key_exists('fieldHandle', $config)) {
				$config = [
					'elementHandle' => $config['elementHandle'],
					'fieldHandle' => $config['fieldHandle'],
				];
			}
		}

		parent::__construct($config);
	}

	public function __toString(): string
	{
		if ($this->memo !== null) {
			return $this->memo;
		}

		$value = null;

		if (is_string($this->value)) {
			$value = Craft::$app->getView()->renderString($this->value);
		} elseif (is_callable($this->value)) {
			$callable = $this->value;
			/** @var string $value */
			$value = $callable();
		} elseif ($this->elementHandle !== null && $this->fieldHandle !== null) {
			$elementHandle = trim($this->elementHandle);
			$fieldHandle = trim($this->fieldHandle);

			$global = GlobalSet::find()->handle($elementHandle)->one();
			$element = $global ?? Entry::find()->section($elementHandle)->one();

			// Get the content field data and parse it if necessary (for rich text fields like Redactor)
			/** @var ?ElementInterface $element */
			if ($element !== null) {
				try {
					/** @var string $value */
					$value = $element->getFieldValue($fieldHandle);
				} catch (InvalidFieldException) {
					// Do nothing
				}
			}
		}

		if ($value === null) {
			return '';
		}

		$this->memo = $value;

		return (string) $value;
	}

	/**
	 * @param array<array-key, mixed> $config
	 */
	public static function fromConfig(string $key, $config): self
	{
		if (isset($config[$key])) {
			/** @var array<array-key, mixed> $textConfig */
			$textConfig = $config[$key];
			return new self($textConfig);
		}

		return new self();
	}
}
