<?php defined('SYSPATH') or die('No direct script access.');

class Jelly_Core_Field_Filterable_ManyToMany extends Jelly_Field_ManyToMany
{

	/**
	 * This is expected to contain the name of the Jelly model representing the intermediate table.
	 *
	 * @var  array
	 */
	public $through = NULL;

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
	 * A string identifying a method to call on the foreign model's builder
	 * which specifies some restriction on the relationship.
	 *
	 * False if no filter is set.
	 *
	 * @var string | FALSE
	 */
	public $filter = FALSE;

	/**
	 * Returns either an array or unexecuted query to find
	 * which columns the model is "in" in the join table
	 *
	 * @param   Jelly_Model    $model
	 * @param   boolean  $as_array
	 * @return  mixed
	 */
	protected function _in($model, $as_array = FALSE)
	{
		$result = parent::_in($model, FALSE);

		if ($this->filter !== FALSE)
		{
			$method = $this->filter;
			$criteria = Jelly::query($this->foreign['model'])
				->$method();

			$alias = $this->name;
			$result->join(array($this->foreign['model'], $alias), 'LEFT')
			       ->on($alias.'.'. Jelly::meta($this->foreign['model'])->primary_key(), '=', $this->through['fields'][1])
			       ->includeCriteria($criteria, $alias);
		}

		if ($this->filter_through !== FALSE)
		{
			$method = $this->filter_through;
			$criteria = Jelly::query($this->through['model'])
				->$method();

			$result->includeCriteria($criteria);
		}

		if ($as_array)
		{
			$result = $result->select($model->meta()->db())
			                 ->as_array(NULL, 'in');
		}

		return $result;
	}

}