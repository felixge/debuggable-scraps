<?php
/**
 * A CakePHP behavior to easily look up the id's of records or create them if they do not exist yet. Useful when working with lots
 * of lookup / status tables.
 *
 * Copyright 2008, Debuggable, Ltd.
 * Hibiskusweg 26c
 * 13089 Berlin, Germany
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2008, Debuggable, Ltd.
 * @version 1.0
 * @author Felix Geisendörfer <felix@debuggable.com>, Tim Koschützki <tim@debuggable.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class LookupableBehavior extends ModelBehavior {
	function lookup(&$model, $conditions, $field = 'id', $create = true) {
		if (!is_array($conditions)) {
			$conditions = array ($model->displayField => $conditions);
		}

		if (!empty($field)) {
			$fieldValue = $model->field($field, $conditions);
		} else {
			$fieldValue = $model->find($conditions);
		}
		if ($fieldValue !== false) {
			return $fieldValue;
		}
		if (!$create) {
			return false;
		}
		$model->create($conditions);
		if (!$model->save()) {
			return false;
		}
		$conditions[$model->primaryKey] = $model->id;
		if (empty($field)) {
			return $model->read();
		}
		return $model->field($field, $conditions);
	}
}

?>