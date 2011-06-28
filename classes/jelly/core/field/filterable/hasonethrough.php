<?php defined('SYSPATH') or die('No direct script access.');

class Jelly_Core_Field_Filterable_HasOneThrough extends Jelly_Field_HasOneThrough
{
	/**
	 * A string identifying a method to call on the foreign model's builder
	 * which specifies some restriction on the relationship.
	 *
	 * False if no filter is set.
	 *
	 * @var string | FALSE
	 */
	public $filter = FALSE;

	/**
	 * A string identifying a method to call on the intermediary model's builder
	 * which specifies some restriction on the relationship.
	 * The query object is passed as a parameter to this call.
	 * If the intermediary table has no model, setting this option will cause an exception to be thrown.
	 *
	 * False if no filter is set.
	 *
	 * @var string
	 */
	public $filter_through = FALSE;

	/**
	 * Sets up foreign and through properly.
	 *
	 * @param   string  $model
	 * @param   string  $column
	 * @return  void
	 */
	public function initialize($model, $column)
	{
		parent::initialize($model, $column);

		$this->through['alias'] = $this->name;
		$this->foreign['alias'] = $this->model.':'.$this->name;

		if ( ! empty($filter))
		{
			if ( ! ($this->foreign['model'] instanceof Jelly_Model))
				throw new Kohana_Exception('Error in '.$model.'.'. $this->name.'. For filtering on the foreign model, the foreign model must be an instance of Jelly_Model.');
			elseif( ! method_exists(Jelly::builder($this->foreign['model']), $filter))
				throw new Kohana_Exception('Error in '.$model.'.'. $this->name.'. For filtering on the foreign model, the foreign model must have a builder with the method \''.$filter .'\'');
		}

		if ( ! empty($filter_through))
		{
			if ( ! ($this->through['model'] instanceof Jelly_Model))
				throw new Kohana_Exception('Error in '.$model.'.'. $this->name.'. For filtering on the foreign model, the foreign model must be an instance of Jelly_Model.');
			elseif( ! method_exists(Jelly::builder($this->through['model']), $through_filter))
				throw new Kohana_Exception('Error in '.$model.'.'. $this->name.'. For filtering on the foreign model, the foreign model must have a builder with the method \''.$through_filter .'\'');
		}
	}

	/**
	 * Returns the record that the model has.
	 *
	 * @param   Jelly_Model  $model
	 * @param   mixed        $value
	 * @return  mixed
	 */
	public function get($model, $value)
	{
		$id =  $model->changed($this->name)
			? $value
			: $model->id();

		$query = Jelly::query($this->foreign['model'])
			->join($this->through['model'], 'LEFT')
			->on($this->through['model'].'.'.$this->through['fields'][1], '=', $this->foreign['field'])
			->where($this->through['model'].'.'.$this->through['fields'][0], '=', $id)
			->limit(1);

		if ($this->filter !== FALSE)
		{
			$method = $this->filter;
			$query->$filter();
		}

		if ($this->filter_through !== FALSE)
		{
			$method = $this->filter_through;
			$criteria = Jelly::query($this->through['model'])
				->$method();

			$query->includeCriteria($criteria);
		}

		return $query;
	}

	/**
	 * Implementation of Jelly_Field_Supports_With.
	 *
	 * @param   Jelly_Builder  $builder
	 * @return  void
	 */
	public function with($builder)
	{
		$builder = $builder
			->join(array($this->through['model'], $this->through['alias']), 'LEFT')
			->on($this->model.':primary_key', '=', $this->through['alias'].'.'.$this->through['fields'][0])
			->join(array($this->foreign['model'], $this->foreign['alias']), 'LEFT')
			->on($this->through['alias'].'.'.$this->through['fields'][1], '=', $this->foreign['alias'].'.'.$this->foreign['field']);

		if ($this->filter !== FALSE)
		{
			$method = $this->filter;
			$criteria = Jelly::query($this->foreign['model'])
				->$method();

			$builder->includeCriteria($criteria, $this->foreign['alias']);
		}

		if ($this->filter_through !== FALSE)
		{
			$method = $this->filter_through;
			$criteria = Jelly::query($this->through['model'])
				->$method();

			$builder->includeCriteria($criteria, $this->through['alias']);
		}
	}
}