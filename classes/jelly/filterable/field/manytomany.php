<?php defined('SYSPATH') or die('No direct script access.');

class Field_Filterable_ManyToMany extends Jelly_Field_ManyToMany
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
	 * Returns a pre-built Jelly model ready to be loaded
	 *
	 * @param   Jelly_Model  $model
	 * @param   mixed        $value
	 * @param   boolean      $loaded
	 * @return  void
	 */
	public function get($model, $value)
	{
		// If the value hasn't changed, we need to pull from the database
		if ($model->changed($this->name))
		{
			$query = Jelly::select($this->foreign['model'])
					->where($this->foreign['column'], 'IN', $value);
		}
		else
		{

			$join_col1 = $this->through['model'].'.'.$this->through['columns'][1];
			$join_col2 = $this->foreign['model'].'.'.$this->foreign['column'];
			$where_col = $this->through['model'].'.'.$this->through['columns'][0];

			$query = Jelly::select($this->foreign['model'])
						->join($this->through['model'])
						->on($join_col1, '=', $join_col2)
						->where($where_col, '=', $model->id());
		}
		
		if ($this->filter !== FALSE)
		{
			$method = $this->filter;
			$query->$method();
		}
		
		if ($this->filter_through !== FALSE)
		{
			$method = $this->filter_through;
			Jelly::builder($this->through['model'])->$method($query);
		}
		
		return $query;
	}

	/**
	 * Returns either an array or unexecuted query to find
	 * which columns the model is "in" in the join table
	 *
	 * @param   Jelly    $model
	 * @param   boolean  $as_array
	 * @return  mixed
	 */
	protected function _in($model, $as_array = FALSE)
	{
		$result = Jelly::select($this->through['model'])
				->select($this->through['columns'][1])
				->where($this->through['columns'][0], '=', $model->id());

		if ($this->filter !== FALSE)
		{
			$method = $this->filter;
			$result->$method();
		}

		if ($this->filter_through !== FALSE)
		{
			$method = $this->filter_through;
			Jelly::builder($this->through['model'])->$method($result);
		}

		if ($as_array)
		{
			$result = $result
						->execute(Jelly::meta($model)->db())
						->as_array(NULL, $this->through['columns'][1]);
		}

		return $result;
	}


}