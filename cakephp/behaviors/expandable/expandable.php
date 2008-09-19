<?php

/**
 * undocumented class
 *
 * @todo Add support for negative field inclusion rules
 * @package default
 * @access public
 */
class ExpandableBehavior extends ModelBehavior {
	var $settings = array();

	function setup(&$Model, $settings = array()) {
		$default = array('schema' => $Model->schema());
		if (isset($settings['with'])) {
			return $this->settings[$Model->alias] = am($default, $settings);
		}
		foreach ($Model->hasMany as $assoc => $option) {
			if (strpos($assoc, 'Field') !== false) {
				return $this->settings[$Model->alias] = am($default, array('with' => $assoc), $settings);
			}
		}
	}

	function afterFind(&$Model, $results, $primary) {
		extract($this->settings[$Model->alias]);
		if (!Set::matches('/'.$with, $results)) {
			return;
		}
		foreach ($results as $i => $item) {
			foreach ($item[$with] as $field) {
				$results[$i][$Model->alias][$field['key']] = $field['val'];
			}
		}
		return $results;
	}

	function afterSave(&$Model) {
		extract($this->settings[$Model->alias]);
		$fields = array_diff_key($Model->data[$Model->alias], $schema);
		$id = $Model->id;
		foreach ($fields as $key => $val) {
			$field = $Model->{$with}->find('first', array(
				// refactor file_id
				'fields' => array($with.'.id'),
				'conditions' => array($with.'.file_id' => $id, $with.'.key' => $key),
				'recursive' => -1,
			));
			if ($field) {
				$Model->{$with}->create();
				$Model->{$with}->id = $field[$with]['id'];
				$Model->{$with}->save(array('val' => $val));
			}
		}
	}
}

?>