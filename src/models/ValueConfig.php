<?php

namespace fostercommerce\fostercheckout\models;

use Craft;
use craft\base\ElementInterface;
use craft\base\Model;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\errors\InvalidFieldException;
use Stringable;
use yii\base\InvalidConfigException;

class ValueConfig extends Model implements Stringable
{
	public ?string $elementHandle = null;

	public ?string $fieldHandle = null;

	/**
	 * @var callable(?array<array-key, mixed>): mixed|mixed
	 */
	public mixed $value = null;

	private mixed $fieldData = null;

	/**
	 * @param string|array<array-key, mixed> $config
	 */
	public function __construct($config = [])
	{
		if ($config !== null && $config !== []) {
			if (is_array($config)) {
				if (array_key_exists('elementHandle', $config) && array_key_exists('fieldHandle', $config)) {
					$config = [
						'elementHandle' => $config['elementHandle'],
						'fieldHandle' => $config['fieldHandle'],
					];
				} else {
					throw new InvalidConfigException('Invalid config, element and field handles are required');
				}
			} else {
				$config = [
					'value' => $config,
				];
			}
		}

		parent::__construct($config);
	}

	/**
	 * @throws InvalidFieldException
	 */
	public function __toString(): string
	{
		$value = $this->getValue();

		if (is_string($value)) {
			return $value;
		}

		if ($value instanceof Stringable) {
			return (string) $value;
		}

		return '';
	}

	/**
	 * @param array<non-empty-string, mixed> $context additional context to pass to the callable or twig template
	 * @throws InvalidFieldException
	 */
	public function getValue(array $context = []): mixed
	{
		if ($this->fieldData !== null) {
			return $this->fieldData;
		}

		$this->fieldData = null;

		if (is_string($this->value)) {
			// If it's a string, then we render it as a twig template
			$this->fieldData = Craft::$app->getView()->renderString($this->value, $context);
		} elseif (is_callable($this->value)) {
			// If it's a callable, then we call it with the context
			$callable = $this->value;
			// TODO we probably want to ensure that this is returning the correct data
			$this->fieldData = $callable($context);
		} elseif (is_array($this->value)) {
			$this->fieldData = $this->parseArrayValue($this->value);
		} elseif ($this->elementHandle !== null && $this->fieldHandle !== null) {
			// If it's and element and field handle, then we get the field value
			$elementHandle = trim($this->elementHandle);
			$fieldHandle = trim($this->fieldHandle);

			$global = GlobalSet::find()->handle($elementHandle)->one();
			/** @var ?ElementInterface $element */
			$element = $global ?? Entry::find()->section($elementHandle)->one();

			if ($element === null) {
				throw new InvalidConfigException('Invalid config, element not found');
			}

			// Get the content field data and parse it if necessary (for rich text fields like Redactor)
			$fieldData = $element->getFieldValue($fieldHandle);
			$this->fieldData = is_array($fieldData) ? $this->parseArrayValue($fieldData) : $fieldData;
		} else {
			$this->fieldData = $this->value;
		}

		return $this->fieldData;
	}

	/**
	 * @param array<array-key, mixed> $config
	 */
	public static function fromConfig(string $key, $config): self
	{
		if (isset($config[$key])) {
			/** @var array<array-key, mixed> $valueConfig */
			$valueConfig = $config[$key];
			return new self($valueConfig);
		}

		return new self();
	}

	/**
	 * @param array<array-key, mixed> $value
	 * @return array<array-key, mixed>
	 * @throws InvalidConfigException
	 */
	private function parseArrayValue(array $value): array
	{
		$isArrayOfLinks = array_reduce(
			$value,
			static fn ($memo, $item): bool => $memo && is_array($item) && (array_key_exists('text', $item) || array_key_exists('url', $item)),
			true,
		);

		if ($isArrayOfLinks) {
			/** @var array<array-key, array{text: non-empty-string, url: non-empty-string}> $value */
			return array_map(
				static fn ($item): array => [
					'text' => $item['text'],
					'url' => $item['url'],
				],
				$value
			);
		}

		throw new InvalidConfigException('Invalid config, field data is not a valid format');
	}
}
