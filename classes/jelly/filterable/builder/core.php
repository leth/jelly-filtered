<?php defined('SYSPATH') or die('No direct script access.');

class Jelly_Filterable_Builder extends Jelly_Builder_Core
{

	/**
	 * This is an internal method used for aliasing only things coming
	 * to the query builder, since they can come in so many formats.
	 *
	 * $value is passed so the :unique_key meta alias can be used.
	 *
	 * @param   string   $field
	 * @param   boolean  $join
	 * @param   mixed    $value
	 * @return  string
	 */
	protected function _column($field, $join = TRUE, $value = NULL)
	{
		$model = NULL;

		// Check for functions
		if (strpos($field, '"') !== FALSE)
		{
			// Quote the column in FUNC("ident") identifiers
			return preg_replace('/"(.+?)"/e', '"\\"".$this->_column("$1")."\\""', $field);
		}

		// Test for Database Expressions
		if ($field instanceof Database_Expression)
		{
			return $field;
		}

		// Set if we find this is a reference to a joined field
		$join_table_alias = FALSE;

		// Field has no model
		if (strpos($field, '.') === FALSE)
		{
			// If we have a meta alias with no model use this model to resolve it
			// or if we have a valid field for this model assume that's what we mean
			if (strpos($field, ':') !== FALSE OR ($this->_meta AND $this->_meta->fields($field)))
			{
				$field = $this->_model.'.'.$field;
			}
			else
			{
				// This is not a model field or meta alias, so don't bother trying to alias it and
				// return it as it is
				return $field;
			}
		}
		else
		{
			list($model, $field) = explode('.', $field, 2);

			// Check to see if the 'model' passed is actually a relationship alias
			if ($field_object = $this->_meta->fields($model) AND $field_object instanceof Jelly_Field_Behavior_Joinable)
			{
				// The model specified looks like a relationship alias in this context
				// that means we alias the field name to a column but use the join alias for the table
				$join_table_alias = Jelly::join_alias($field_object);

				// Change the field to use the appropriate model so it can be properly aliased
				$field = $field_object->foreign['model'].'.'.$field;
			}
			else
			{
				// Put field back together
				$field = $model.'.'.$field;
			}
		}

		$alias = Jelly::alias($field, $value);

		if ($join_table_alias)
		{
			// Replace the actual table with the join alias
			$alias['table'] = $join_table_alias;
		}

		if ($join)
		{
			return implode('.', $alias);
		}
		else
		{
			return $alias['column'];
		}
	}

}
