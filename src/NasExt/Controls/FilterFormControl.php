<?php

/**
 * This file is part of the NasExt extensions of Nette Framework
 *
 * Copyright (c) 2013 Dusan Hudak (http://dusan-hudak.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace NasExt\Controls;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Callback;

/**
 * @author Dusan Hudak <admin@dusan-hudak.com>
 */
class FilterFormControl extends Control
{

	const FILTER_IN = 1;
	const FILTER_OUT = 2;

	/** @persistent */
	public $data;

	/** @var array */
	public $onFilter;

	/** @var array */
	public $onReset;

	/** @var array */
	private $defaultValues = array();

	/** @var  bool */
	private $ajaxRequest;

	/** @var  string */
	private $templateFile;

	/** @var \Closure|callable returning array */
	protected $dataFilter;


	public function __construct()
	{
		parent::__construct();

		$reflection = $this->getReflection();
		$dir = dirname($reflection->getFileName());
		$name = $reflection->getShortName();
		$this->templateFile = $dir . DIRECTORY_SEPARATOR . $name . '.latte';
	}


	/**
	 * @param  \Closure|callable $dataFilter
	 * @return FilterFormControl
	 */
	public function setDataFilter($dataFilter)
	{
		$this->dataFilter = $dataFilter;
		return $this;
	}


	/**
	 * @param bool $value
	 * @return FilterFormControl
	 */
	public function setAjaxRequest($value = TRUE)
	{
		$this->ajaxRequest = $value;
		return $this;
	}


	/**
	 * @param array $values
	 * @return FilterFormControl
	 */
	public function setDefaultValues($values)
	{
		$this->defaultValues = $values;
		return $this;
	}


	/**
	 * @return array $data
	 */
	public function getData()
	{
		$data = array();
		if ($this->data != NULL) {
			parse_str($this->data, $data);
		}

		// add null values
		foreach ($this->getComponent('form')->getComponents() as $key => $value) {
			if (!isset($data[$key]) || $data[$key] === '') {
				$data[$key] = NULL;
			}
		}

		// add default values
		if (!empty($this->defaultValues)) {
			foreach ($this->defaultValues as $key => $value) {
				if (!isset($data[$key])) {
					$data[$key] = $value;
				}
			}
		}

		// Filter out callback
		if ($this->dataFilter) {
			if (!empty($data)) {
				$dataFilter = Callback::invokeArgs($this->dataFilter, array($data, self::FILTER_OUT));
				if ($dataFilter && is_array($dataFilter)) {
					$data = $dataFilter;
				}
			}
		}

		return $data;
	}


	/**
	 * @param array|string $data
	 * @return FilterFormControl
	 */
	public function setData($data)
	{
		$this->saveData($data);
		$this->loadData();
		return $this;
	}


	/**
	 * @param array|string $data
	 * @return FilterFormControl
	 */
	private function saveData($data)
	{
		$filter = array();
		foreach ($data as $key => $value) {
			if ($value !== '') {
				$filter[$key] = $value;
			} elseif (array_key_exists($key, $this->defaultValues)) {
				// add default values
				$filter[$key] = $this->defaultValues[$key];
			}
		}

		// Filter in callback
		if (!empty($filter)) {
			if ($this->dataFilter) {
				$dataFilter = Callback::invokeArgs($this->dataFilter, array($filter, self::FILTER_IN));
				if ($dataFilter && is_array($dataFilter)) {
					$filter = $dataFilter;
				}
			}

			$this->data = http_build_query($filter, '', '&');
		} else {
			$this->data = NULL;
		}
		return $this;
	}


	/**
	 * FORM Filter
	 * @return Form
	 */
	protected function createComponentForm()
	{
		$form = new Form();
		$elementPrototype = $form->getElementPrototype();

		$elementPrototype->class[] = lcfirst($this->reflection->getShortName());
		$elementPrototype->class[] = lcfirst($this->name);
		!$this->ajaxRequest ? : $elementPrototype->class[] = 'ajax';

		$form->addSubmit('filter', 'Filter')
			->onClick[] = Callback::closure($this, 'processSubmit');

		$form->addSubmit('reset', 'Reset')
			->setValidationScope(FALSE)
			->onClick[] = Callback::closure($this, 'processReset');

		return $form;
	}


	/**
	 * PROCESS-SUBMIT-FORM - set filter data to persistent value
	 * @param SubmitButton $button
	 */
	public function processSubmit(SubmitButton $button)
	{
		$values = $button->getForm()->getValues(TRUE);
		$this->saveData($values);
		$this->onFilter($this, $this->getData());

		if (!$this->presenter->isAjax()) {
			$this->presenter->redirect('this');
		}
	}


	/**
	 * PROCESS-RESET-FORM - delete filter data from persistent value
	 * @param SubmitButton $button
	 */
	public function processReset(SubmitButton $button)
	{
		$form = $button->getForm();
		$form->setValues(array(), TRUE);
		$this->data = NULL;
		$this->onReset($this, $this->getData());

		if (!$this->presenter->isAjax()) {
			$this->presenter->redirect('this');
		}
	}


	/**
	 * @return string
	 */
	public function getTemplateFile()
	{
		return $this->templateFile;
	}


	/**
	 * @param string $file
	 * @return FilterFormControl
	 */
	public function setTemplateFile($file)
	{
		if ($file) {
			$this->templateFile = $file;
		}
		return $this;
	}


	private function loadData()
	{
		$data = $this->getData();

		/** @var Form $form */
		$form = $this['form'];

		foreach ($data as $key => $value) {
			if ($value !== '' && isset($form[$key])) {
				$form[$key]->setValue($value);
			}
		}
	}


	public function render()
	{
		$this->loadData();

		$template = $this->template;
		$template->_form = $template->form = $this->getComponent('form');
		$template->setFile($this->getTemplateFile());
		$template->render();
	}
}
