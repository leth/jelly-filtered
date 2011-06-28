<?php defined('SYSPATH') or die('No direct script access.');

abstract class Jelly_Core_Field_HasOneThrough extends Jelly_Field implements Jelly_Field_Supports_With
{
	/**
	 * @var  boolean  False, since this field does not map directly to a column
	 */
	public $in_db = FALSE;

	/**
	 * @var  boolean
	 */
	public $allow_null = FALSE;

	/**
	 * @var  array  default is an empty array
	 */
	public $default = 0;

	/**
	 * @var  string  a string pointing to the foreign model and (optionally, a
	 *               field, column, or meta-alias).
	 */
	public $foreign = NULL;

	/**
	 * @var mixed  a string or array that references the through table and
	 *             fields we're using to connect the two models.
	 */
	public $through = NULL;

	/**
	 * @var  boolean  empty values are converted by default
	 */
	public $convert_empty = TRUE;

	/**
	 * @var  int  empty values are converted to 0, not NULL
	 */
	public $empty_value = 0;

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

		// Default to the name of the column
		if (empty($this->foreign))
		{
			$this->foreign = inflector::singular($this->name).'.'.$this->name.':primary_key';
		}
		// Is it model.field?
		elseif (FALSE === strpos($this->foreign, '.'))
		{
			$this->foreign = $this->foreign.'.'.$this->foreign.':primary_key';
		}

		// Create an array from them for easier access
		$this->foreign = array_combine(array('model', 'field'), explode('.', $this->foreign, 2));

		// Create the default through connection
		if (empty($this->through))
		{
			// Find the join table based on the two model names pluralized,
			// sorted alphabetically and with an underscore separating them
			$through = array(
				inflector::plural($this->foreign['model']),
				inflector::plural($model)
			);

			sort($through);
			$this->through = implode('_', $through);
		}

		if (is_string($this->through))
		{
			$this->through = array(
				'model' => $this->through,
				'fields' => array(
					inflector::singular($model).':foreign_key',
					inflector::singular($this->foreign['model']).':foreign_key',
				)
			);
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

		return Jelly::query($this->foreign['model'])
			->join($this->through['model'], 'LEFT')
			->on($this->through['model'].'.'.$this->through['fields'][1], '=', $this->foreign['field'])
			->where($this->through['model'].'.'.$this->through['fields'][0], '=', $id)
			->limit(1);
	}

	/**
	 * Implementation of Jelly_Field_Supports_With.
	 *
	 * @param   Jelly_Builder  $builder
	 * @return  void
	 */
	public function with($builder)
	{
		$through_alias = $this->name;
		$foreign_alias = $this->model.':'.$this->name;

		return $builder
			->join(array($this->through['model'], $through_alias), 'LEFT')
			->on($this->model.':primary_key', '=', $through_alias.'.'.$this->through['fields'][0])
			->join(array($this->foreign['model'], $foreign_alias), 'LEFT')
			->on($through_alias.'.'.$this->through['fields'][1], '=', $foreign_alias.'.'.$this->foreign['field']);
	}

}