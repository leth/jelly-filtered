<?php defined('SYSPATH') or die('No direct script access.');

abstract class Jelly_Core_Field_Filterable_HasMany extends Jelly_Field_HasMany
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
	 * Returns a Jelly model that, when load()ed will return a database
	 * result of the models that this field has.
	 *
	 * @param   Jelly_Model  $model
	 * @param   mixed        $value
	 * @param   boolean      $loaded
	 * @return  Jelly
	 */
	public function get($model, $value)
	{
		$query = parent::get($model, $value);

		if ($this->filter !== FALSE)
		{
			$method = $this->filter;
			$query->$method();
		}

		return $query;
	}

	/**
	 * Implementation of Jelly_Field_Supports_Has.
	 *
	 * @param   Jelly_Model  $model
	 * @param   mixed        $models
	 * @return  boolean
	 */
	public function has($model, $models)
	{
		$query = Jelly::query($this->foreign['model'])
			->where($this->foreign['model'].'.'.$this->foreign['field'], '=', $model->id())
			->where($this->foreign['model'].'.'.':primary_key', 'IN', $this->_ids($models));

		if ($this->filter !== FALSE)
		{
			$method = $this->filter;
			$query->$method();
		}

		return (bool) $query->count();
	}
}