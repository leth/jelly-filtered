<?php defined('SYSPATH') or die('No direct script access.');

abstract class Jelly_Core_Field_Filterable_HasOne extends Jelly_Field implements Jelly_Field_Supports_With {
{
	/**
	 * @var  boolean  Ffalse, since this field does not map directly to a column
	 */
	public $in_db = FALSE;

	/**
	 * @var  boolean  null values are not allowed since an empty array expresses no relationships
	 */
	public $allow_null = FALSE;

	/**
	 * @var  array  default is an empty array
	 */
	public $default = 0;

	/**
	 * @var  array  the default to set on foreign fields when removing the relationship
	 */
	public $foreign_default = 0;

	/**
	 * @var  string  a string pointing to the foreign model and (optionally, a
	 *               field, column, or meta-alias).
	 */
	public $foreign = '';

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
	 * @var  boolean  empty values are converted by default
	 */
	public $convert_empty = TRUE;

	/**
	 * @var  int  empty values are converted to 0, not NULL
	 */
	public $empty_value = 0;

	/**
	 * Determines the actual foreign model and field that the
	 * relationship is tied to.
	 *
	 * @param   string  $model
	 * @param   string  $column
	 * @return  void
	 */
	public function initialize($model, $column)
	{
		parent::initialize($model, $column);

		// Empty? The model defaults to the the singularized name
		// of this field, and the field defaults to this field's model's foreign key
		if (empty($this->foreign))
		{
			$this->foreign = inflector::singular($this->name).'.'.$model.':foreign_key';
		}
		// We have a model? Default the field to this field's model's foreign key
		elseif (FALSE === strpos($this->foreign, '.'))
		{
			$this->foreign = $this->foreign.'.'.$model.':foreign_key';
		}

		// Create an array fo easier access to the separate parts
		$this->foreign = array_combine(array('model', 'field'), explode('.', $this->foreign));
	}

	/**
	 * Sets a relationship on the field.
	 *
	 * @param   mixed  $value
	 * @return  mixed
	 */
	public function set($value)
	{
		// Convert models to their id
		if ($value instanceof Jelly_Model)
		{
			$value = $value->id();
		}

		list($value, $return) = $this->_default($value);

		if ( ! $return)
		{
			$value = is_numeric($value) ? (int) $value : (string) $value;
		}

		return $value;
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
		$query= Jelly::query($this->foreign['model'])

		if ($model->changed($this->name))
		{
			$query->where($this->foreign['model'].'.'.':primary_key', '=', $value)
			      ->limit(1);
		}
		else
		{
			$query->where($this->foreign['model'].'.'.$this->foreign['field'], '=', $model->id())
			      ->limit(1);
		}
		
		if ($this->filter !== FALSE)
		{
			$method = $this->filter;
			$query->$method();
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
		$builder->join(':'.$this->name, 'LEFT')
		        ->on($this->model.'.:primary_key', '=', ':'.$this->name.'.'.$this->foreign['field']);
		
		if ($this->filter !== FALSE)
		{
			$method = $this->filter;
			$criteria = Jelly::query($this->foreign['model'])
				->$method();

			$builder->include_criteria($criteria, ':'.$this->name);
		}
	}

} // End Jelly_Core_Field_HasOne