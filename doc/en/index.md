NasExt/FilterFormControl
===========================

Data filter control for Nette Framework.

Requirements
------------

NasExt/FilterFormControl requires PHP 5.3.2 or higher.

- [Nette Framework](https://github.com/nette/nette)

Installation
------------

The best way to install NasExt/FilterFormControl is using  [Composer](http://getcomposer.org/):

```sh
$ composer require nasext/filter-form-control
```


## Usage

```php
class FooPresenter extends Presenter
{

	public function renderDefault()
	{
		/** @var NasExt\Controls\FilterFormControl $filter */
		$filter = $this['filter'];
		$filterData = $filter->getData(); // data for filter
	}


	/**
	 * @return NasExt\Controls\FilterFormControl
	 */
	protected function createComponentFilter($name)
	{
		$control = new NasExt\Controls\FilterFormControl($this, $name);

		$control->setDefaultValues(array('code' => 'some default value for code input'));

		/** @var  Form $form */
		$form = $control['form'];

		$form->addText('code', 'Code');
		$form->addText('name', 'Name');

		return $control;
	}
}
```


###Use dataFilter callback for filtering data in persistent param in url
If you need use special filter input use object in input, like Nette\Datetime,
so you must translate this object to timestamp before set persistent parameter for url, and after load data from persistent parameter
you need transform timestamp to Nette\Datetime object. For this process use setDataFilter().
- FILTER_IN: use when transform object to string in process set persistent parameter for url
- FILTER_OUT: use when transform string from persistent parameter to object
```php
	/**
	 * @return NasExt\Controls\FilterFormControl
	 */
	protected function createComponentFilter($name)
	{
		$control = new NasExt\Controls\FilterFormControl($this, $name);

		$control->setDataFilter(function ($values, $filterType) {
			if (array_key_exists('date', $values) && !empty($values['date'])) {
				if ($filterType == FilterFormControl::FILTER_IN) {
					if ($values['date'] instanceof DateTime) {
						$values['date'] = $values['date']->getTimestamp();
					}else{
						$values['date'] = strtotime($values['date']);
					}
				} elseif ($filterType == FilterFormControl::FILTER_OUT) {
					if (!$values['date'] instanceof DateTime) {
						$date = new DateTime();
						$values['date'] = $date->setTimestamp($values['date']);
					}
				}
			}

			return $values;
		});

		/** @var  Form $form */
		$form = $control['form'];

		$form->addText('date', 'Date');

		return $control;
	}
}
```

###FilterFormControl with ajax
For use FilterFormControl with ajax use setAjaxRequest() and use events onFilter[], onReset[] for invalidateControl
```php
	/**
	 * @return NasExt\Controls\FilterFormControl
	 */
	protected function createComponentFilter($name)
	{
		$control = new NasExt\Controls\FilterFormControl($this, $name);
		// enable ajax request, default is false
		$control->setAjaxRequest();

		$that = $this;
		$invalidateControl = function ($component, $values) use ($that) {
			if ($that->isAjax()) {
				$that->invalidateControl();
			}
		};

		$control->onFilter[] = $invalidateControl;
		$control->onReset[] = $invalidateControl;

		/** @var  Form $form */
		$form = $control['form'];

		$form->addText('code', 'Code');
		$form->addText('name', 'Name');

		return $control;
	}
```

###Set templateFile for FilterFormControl
For set templateFile use setTemplateFile()
```php
	/**
	 * @return NasExt\Controls\FilterFormControl
	 */
	protected function createComponentFilter($name)
	{
		$control = new NasExt\Controls\FilterFormControl($this, $name);
		$control->setTemplateFile('myTemplate.latte');

		/** @var  Form $form */
		$form = $control['form'];

		$form->addText('code', 'Code');
		$form->addText('name', 'Name');

		return $control;
	}
```

-----

Repository [http://github.com/nasext/filterformcontrol](http://github.com/nasext/filterformcontrol).