<?php
/**
 * This file is part of the Nella Project (http://nella-project.org).
 *
 * Copyright (c) Patrik Votoček (http://patrik.votocek.cz)
 *
 * For the full copyright and license information,
 * please view the file LICENSE.md that was distributed with this source code.
 */

namespace Nella\Forms\DateTime;

use Nette\Forms\Container;
use Nette\Forms\Form;

/**
 * Date input form control
 *
 * @author Patrik Votoček
 *
 * @property string $value
 */
class DateInput extends \Nette\Forms\Controls\BaseControl
{

	const DEFAULT_FORMAT = 'Y-m-d';

	/** @var bool */
	private static $registered = FALSE;

	/** @var string */
	private $format;

	/** @var bool */
	private $strict = FALSE;

	/**
	 * @param string
	 * @param string|NULL
	 */
	public function __construct($format = self::DEFAULT_FORMAT, $label = NULL)
	{
		parent::__construct($label);
		$this->format = $format;

		$this->getControlPrototype()->data('nella-date-format', $format);
	}

	/**
	 * @return \Nella\Forms\DateTime\DateInput
	 */
	public function enableStrict()
	{
		$this->strict = true;
		return $this;
	}

	/**
	 * @return \Nella\Forms\DateTime\DateInput
	 */
	public function disableStrict()
	{
		$this->strict = false;
		return $this;
	}

	/**
	 * @param \DateTimeInterface|NULL
	 * @return \Nella\Forms\DateTime\DateInput
	 */
	public function setValue($value = NULL)
	{
		if ($value === NULL) {
			return parent::setValue(NULL);
		} elseif (!$value instanceof \DateTime) {
			throw new \Nette\InvalidArgumentException('Value must be DateTimeInterface or NULL');
		}

		return parent::setValue($value->format($this->format));
	}

	/**
	 * @return \DateTime|NULL
	 */
	public function getValue()
	{
		if (!$this->isFilled()) {
			return NULL;
		}

		$datetime = \DateTime::createFromFormat($this->format, $this->getRawValue());
		if ($datetime === FALSE || $datetime->format($this->format) !== $this->getRawValue()) {
			return NULL;
		}

		//$datetime->setTime(0, 0, 0);
		return $datetime;
	}

	/**
	 * @return mixed
	 */
	public function getRawValue()
	{
		return parent::getValue();
	}

	public function loadHttpData()
	{
		$input = $this->getHttpData(\Nette\Forms\Form::DATA_TEXT);
		if (empty($input)) {
			parent::setValue(NULL);
			return;
		}

		$datetime = \DateTime::createFromFormat(
			$this->normalizeFormat($this->format),
			$this->normalizeFormat($input)
		);

		if ($datetime !== FALSE
			&& $datetime->format($this->normalizeFormat($this->format)) === $this->normalizeFormat($input)
		) {
			parent::setValue($datetime->format($this->format));
			return;
		}

		parent::setValue('');
	}

	/**
	 * @return \Nette\Utils\Html
	 */
	public function getControl()
	{
		$control = parent::getControl();
		$control->value($this->getRawValue());
		$control->type('text');
		return $control;
	}

	/**
	 * @return bool
	 */
	public function isFilled()
	{
		$value = $this->getRawValue();
		return $value !== NULL;
	}

	/**
	 * @param \Nella\Forms\DateTime\DateInput
	 * @return bool
	 */
	public function validateDate(DateInput $control)
	{
		return $this->isDisabled() || !$this->isFilled() || $this->getValue() !== NULL;
	}

	/**
	 * @param string
	 * @return string
	 */
	private function normalizeFormat($input)
	{
		if ($this->strict) {
			return $input;
		}

		return \Nette\Utils\Strings::replace($input, '~\s+~', '');
	}

	/**
	 * @param string $message
	 * @return \Nella\Forms\DateTime\DateInput
	 */
	public function setRequired($message = TRUE)
	{
		if (!is_string($message)) {
			throw new \Nette\InvalidArgumentException('Message must be string');
		}

		parent::setRequired($message);

		$this->addCondition(Form::FILLED)
			->addRule(function(DateInput $control) {
				return $this->validateDate($control);
			}, $message);

		return $this;
	}

	public static function register()
	{
		if (static::$registered) {
			throw new \Nette\InvalidStateException('DateInput control already registered.');
		}

		static::$registered = TRUE;

		$class = get_called_class();
		$callback = function (
			Container $container,
			$name,
			$label = NULL,
			$format = self::DEFAULT_FORMAT
		) use ($class) {
			$control = new $class($format, $label);
			$container->addComponent($control, $name);
			return $control;
		};

		Container::extensionMethod('addDate', $callback);
	}

}
