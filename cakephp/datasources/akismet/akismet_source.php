<?php
/**
 * A CakePHP datasource for interacting with the Akismet spam protection API.
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
class AkismetSource extends DataSource{
	var $description = "Akismet";
	var $Http = null;

	function __construct($config) {
		parent::__construct($config);
		App::import('HttpSocket');
		$this->Http = new HttpSocket();
	}

	function verifyKey($config = array()) {
		if (is_string($config)) {
			$config = array('key' => $config);
		}
		$config = Set::merge($this->config, $config);
		$r = $this->Http->post('http://rest.akismet.com/1.1/verify-key', array(
			'key' => $config['key'],
			'blog' => $config['blog'],
		));
		return $r === 'valid';
	}

	function isSpam($comment, $config = array()) {
		return $this->submit('check', $comment, $config);
	}

	function submit($what, $comment, $config = array()) {
		$map = array(
			'check' => 'comment-check',
			'spam' => 'submit-spam',
			'ham' => 'submit-ham',
		);
		if (!isset($map[$what])) {
			trigger_error('AkismetSource: Unknown submit method '.$what);
			return false;
		}
		$method = $map[$what];
		
		if (is_string($config)) {
			$config = array('permalink' => $config);
		}
		$config = Set::merge($this->config, $config);
		$comment = $this->normalize($comment);
		$comment = am(array(
			'blog' => $config['blog'],
			'referrer' => env('HTTP_REFERER'),
			'permalink' => $config['permalink'],
			
		), $comment);
		$r = $this->Http->post(sprintf('http://%s.rest.akismet.com/1.1/%s', $config['key'], $method), $comment);
		if ($r === 'invalid') {
			return array('error' => 'Invalid Key!');
		}
		return $r === 'true';
	}

	function normalize($comment, $map = array()) {
		if (!$map) {
			$map = $this->config['map'];
		}
		$r = array();
		foreach ($map as $to => $from) {
			if (isset($comment[$from])) {
				$r[$to] = $comment[$from];
			}
		}
		return $r;
	}

	function close() {
		return true;
	}
}

?>