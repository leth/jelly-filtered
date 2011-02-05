<?php defined('SYSPATH') or die('No direct script access.');

class Jelly_Core_Filterable_Builder extends Jelly_Core_Builder
{
	public function includeCriteria(Jelly_Filterable_Builder $criteria, $alias = NULL)
	{
		foreach ($criteria->_where as $condition) {
			if ($alias !== NULL)
			{
				foreach ($condition as $key => $value) {
					if ($alias == '')
					{
						list(, $condition[$key][0]) = explode('.', $value[0], 2);
					}
					else
					{
						list(,$field) = explode('.', $value[0], 2);
						$condition[$key][0] = $alias.'.'.$field;
					}
				}
			}
			$this->_where[] = $condition;
		};

		return $this;
	}
}
