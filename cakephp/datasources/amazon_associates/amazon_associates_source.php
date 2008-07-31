<?php
/**
 * A CakePHP datasource for interacting with the amazon associates API.
 *
 * Copyright 2008, Debuggable, Ltd.
 * Hibiskusweg 26c
 * 13089 Berlin, Germany
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2008, Debuggable, Ltd.
 * @version 0.1
 * @author Felix Geisendörfer <felix@debuggable.com>, Tim Koschützki <tim@debuggable.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Xml');
class AmazonAssociatesSource extends DataSource{
	var $description = "AmazonAssociates Data Source";

	function __construct($config) {
		parent::__construct($config);
		App::import('HttpSocket');
		$this->Http = new HttpSocket();
	}

	function find($type, $query = array()) {
		if (is_array($type)) {
			$query = $type;
		} else {
			$query['type'] = $type;
		}
		if (!is_array($query)) {
			$query = array('Title' => $query);
		}
		$map = array(
			'info' => 'ResponseGroup',
			'type' => 'SearchIndex',
		);
		foreach ($map as $old => $new) {
			$query[$new] = $query[$old];
			unset($query[$old]);
		}
		foreach ($query as $key => $val) {
			if (preg_match('/^[a-z]/', $key)) {
				$query[Inflector::camelize($key)] = $val;
				unset($query[$key]);
			}
		}
		$query = am(array(
			'Service' => 'AWSECommerceService',
			'AWSAccessKeyId' => $this->config['key'],
			'Operation' => 'ItemSearch',
			'Version' => '2008-06-28',
		), $query);
		$r = $this->Http->get('http://ecs.amazonaws.com/onca/xml', $query);
		$r = Set::reverse(new Xml($r));
		return $r;
	}

	function close() {
		return true;
	}
}



?>