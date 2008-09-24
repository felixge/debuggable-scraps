<?php
/**
 * A CakePHP behavior to retrieve a set of records ready for display in a tag-cloud kind of view
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
class CloudBehavior extends ModelBehavior{
	var $mapMethods = array('/^_findCloud$/' => '_findCloud');

	function setup(&$Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array(
				'scale' => 2,
				'shuffle' => true,
				'query' => array(),
				'countField' => 'count',
			);
		}
		if (!is_array($settings)) {
			$settings = array();
		}
		$Model->_findMethods['cloud'] = true;
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], $settings);
	}

	function _findCloud(&$Model, $method, $state, $query, $results = array()) {
		if ($state == 'before') {
			$query = array_merge($query, $this->settings[$Model->alias]['query']);
			return $query;
		}
		if (empty($results)) {
			return array();
		}

		$countField = $this->settings[$Model->alias]['countField'];
		if (!$countField || !$Model->hasField($countField)) {
			trigger_error('CloudBehavior: You have to configure a valid countField for querying this Model\'s records as a cloud!');
			return array();
		}

		$max = $results[0][$Model->alias][$countField];
		$min = $results[count($results)-1][$Model->alias][$countField];
		$range = $max - $min;
		if (!$range) {
			$range = 1;
		}

		foreach ($results as &$command) {
			$command[$Model->alias]['scale'] = 
				(($command[$Model->alias][$countField] - $min) / $range)
				* $this->settings[$Model->alias]['scale']
				+ 1;
		}
		if ($this->settings[$Model->alias]['shuffle']) {
			srand();
			shuffle($results);
		}
		return $results;
	}
}
?>