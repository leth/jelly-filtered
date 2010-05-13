<?php defined('SYSPATH') or die('No direct script access.');

class Field_Filterable_HasMany extends Jelly_Field_HasMany
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
		if ($model->changed($this->name))
		{
			// Return a real object
			$query = Jelly::select($this->foreign['model'])
					->where(':primary_key', 'IN', $value);
			
		}
		else
		{
			$query = Jelly::select($this->foreign['model'])
					->where($this->foreign['column'], '=', $model->id());
		}
		
		if ($this->filter !== FALSE)
		{
			$method = $this->filter;
			$query->$method();
		}
		
		return $query;
	}

	/**
	 * Implementation of Jelly_Field_Behavior_Haveable
	 *
	 * @param   Jelly  $model
	 * @param   array  $ids
	 * @return  void
	 */
	public function has($model, $ids)
	{
		$query = Jelly::select($this->foreign['model'])
			->where($this->foreign['column'], '=', $model->id())
			->where(':primary_key', 'IN', $ids);

		if ($this->filter !== FALSE)
		{
			$method = $this->filter;
			$query->$method();
		}
		
		return (bool) $query->count();
	}

}