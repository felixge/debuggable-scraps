<?php
/**
 * A CakePHP datasource for aggregating google analytics data via web scraping.
 *
 * Copyright 2008, Debuggable, Ltd.
 * Hibiskusweg 26c
 * 13089 Berlin, Germany
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2008, Debuggable, Ltd.
 * @version 1.1
 * @author Felix Geisendörfer <felix@debuggable.com>, Tim Koschützki <tim@debuggable.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * undocumented class
 *
 * @package default
 * @access public
 */
class GoogleAnalyticsSource extends DataSource{
/**
 * Description string for this Database Data Source.
 *
 * @var unknown_type
 */
	var $description = "Google Analytics API";
/**
 * undocumented variable
 *
 * @var unknown
 * @access public
 */
	var $Http = null;
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function __construct($config) {
		parent::__construct($config);
		App::import('HttpSocket');
		$this->Http =& new HttpSocket();
	}
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function connected() {
		return $this->connected;
	}
/**
 * undocumented function
 *
 * @param unknown $user 
 * @param unknown $pass 
 * @return void
 * @access public
 */
	function login($user = null, $password = null) {
		if (empty($user)) {
			extract($this->config);
		}
		if (@empty($user) || @empty($password)) {
			return trigger_error('Please specify a user / password for using this service');
		}
		$post = array(
			'continue' => 'http://www.google.com/analytics/home/?et=reset&hl=en-US',
			'service' => 'analytics',
			'nui' => 'hidden',
			'hl' => 'en-US',
			'GA3T' => 'ouVrvynQwUs',
			'Email' => $user,
			'PersistentCookie'=> 'yes',
			'Passwd' => $password
		);

		$response = $this->Http->post('https://www.google.com/accounts/ServiceLoginBoxAuth', $post);
		if (!strpos($response, 'TokenAuth?continue')) {
			return $this->connected = false;
		}
		$this->config['database'] = $user;
		return $this->connected = true;
	}
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function listSources($refresh = false) {
		if (!$this->connected() && !$this->login()) {
			return false;
		}
		
		$cache = parent::listSources();
		if (!$refresh && $cache != null) {
			return $cache;
		}

		$sources = array();
		$response = $this->Http->get('https://www.google.com/analytics/settings/?et=reset&hl=en-US&ns=100');

		$optionsRegex = '/<option.+?value="([0-9]+)".*?>([^<]+)<\/option>/si';
		preg_match('/<select.+?name="scid".*?>(.+?)<\/select>/is', $response, $accounts);
		if (empty($accounts)) {
			return false;
		}
		preg_match_all($optionsRegex, $accounts[1], $accounts, PREG_SET_ORDER);
		if (empty($accounts)) {
			return false;
		}

		foreach ($accounts as $i => $account) {
			list(,$id, $name) = $account;
			if (empty($id) || !is_numeric($id)) {
				continue;
			}
			$account = array('Account' => compact('id', 'name'));
			if ($i != 0) {
				$response = $this->Http->get('https://www.google.com/analytics/settings/home?scid='.$id.'&ns=100');
			}
			preg_match('/<select.+?name="id".*?>(.+?)<\/select>/is', $response, $profiles);
			if (empty($profiles)) {
				$account['Profile'] = array();
				continue;
			}
			preg_match_all($optionsRegex, $profiles[1], $profiles, PREG_SET_ORDER);
			foreach ($profiles as $profile) {
				list(,$id, $name) = $profile;
				if (empty($id) || !is_numeric($id)) {
					continue;
				}
				$account['Profile'][] = compact('id', 'name');
			}
			$sources[] = $account;
		}
		parent::listSources($sources);
		return $sources;
	}
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function report($conditions = array(), $returnRaw = false) {
		if (!$this->connected() && !$this->login()) {
			return false;
		}
		
		if (is_int($conditions)) {
			$conditions = array('profile' => $conditions);
		} elseif (is_string($conditions)) {
			$conditions = array('report' => $conditions);
		}

		$defaults = array(
			'profile' => null,
			'report'  => 'Dashboard',
			'from'    => date('Y-m-d', time() - 1 * MONTH),
			'to'      => date('Y-m-d'),
			'query'   => array(),
			'tab'     => 0,
			'format'  => 'xml',
			'compute' => 'average',
			'view'    => 0,
		);
		$conditions = am($defaults, $conditions);
		$formats = array('pdf' => 0, 'xml' => 1, 'csv' => 2, 'tsv' => 3);
		
		foreach (array('from', 'to') as $condition) {
			if (is_string($conditions[$condition])) {
				$conditions[$condition] = strtotime($conditions[$condition]);
			}
		}

		$conditions['profile'] = $this->profileId($conditions['profile']);
		$query = array(
			'fmt' => isset($formats[$conditions['format']])
				? $formats[$conditions['format']]
				: $conditions['format'],
			'id' => $conditions['profile'],
			'pdr' => date('Ymd', $conditions['from']).'-'.date('Ymd', $conditions['to']),
			'tab' => $conditions['tab'],
			'cmp' => $conditions['compute'],
			'view' => $conditions['view'],
			'rpt' => $conditions['report'].'Report',
		);
		$query = am($query, $conditions['query']);
		$report = $this->Http->get('https://www.google.com/analytics/reporting/export', $query);

		if ($returnRaw == true || $query['fmt'] != 1) {
			return $report;
		}

		uses('Xml');
		$ReportXml =& new XML($report);
		return $this->xmltoArray($ReportXml);
	}
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function addProfile($profile) {
		if (!is_array($profile)) {
			$profile = array('url' => $profile);
		}
		$profile = am(array(
			'domain' => null,
			'name' => null,
			'account' =>  null,
			'countryCode' => null,
			'timeZone' => null,
			'post' => array()
		), $profile);

		$profile['account'] = $this->accountId($profile['account']);
		if ($profile['domain']) {
			$profile['domain'] = $this->profileId($profile['domain']);
		}
		
		if (is_string($profile['url'])) {
			preg_match('/(http|https)+:\/\/(.+)/', $profile['url'], $match);
			$profile['url'] = array('scheme' => $match[1], 'url' => $match[2]);
		}

		$post = am(array(
			'aid' => 1101,
			'vid' => 1151,
			'rd' => 'vid=1100&scid='.$profile['account'],
			'usac_id' => null,
			'ucpr_web_site_url' => null,
			'uiti_id' => 293,
			'uiti_id_orig' => 1,
			'ubwe_new' => (int)!$profile['domain'],
			'ucpr_protocol' => $profile['url']['scheme'],
			'ucpr_url' => $profile['url']['url'],
			'uswe_id'=> $profile['domain'],
			'ucpr_name' => $profile['name'],
			'ucpt_country_code' => $profile['countryCode'],
			'tzCode' => $profile['timeZone']
		), $profile['post']);

		$response = $this->Http->post('https://www.google.com/analytics/home/admin?scid='.$profile['account'], $post);
		if (!preg_match('/rid=([\d]+)/', @$this->Http->response['header']['Location'], $match)) {
			return false;
		}
		$this->listSources(true);
		return (int)$match[1];
	}
/**
 * undocumented function
 *
 * @param unknown $name 
 * @return void
 * @access public
 */
	function accountId($name = null) {
		if (ctype_digit($name)) {
			return $name;
		}
		$accounts = $this->listSources();
		if (!$name) {
			return $accounts[0]['Account']['id'];
		}
		foreach ($accounts as $account) {
			if ($account['Account']['name'] == $name) {
				return $account['Account']['id'];
			}
		}
		return false;
	}
/**
 * undocumented function
 *
 * @param unknown $name 
 * @return void
 * @access public
 */
	function profileId($name = null) {
		if (ctype_digit($name)) {
			return $name;
		}
		$accounts = $this->listSources();
		if (!$name) {
			return $accounts[0]['Profile'][0]['id'];
		}
		foreach ($accounts as $account) {
			$profiles = Set::combine($account, 'Profile.{n}.name', 'Profile.{n}.id');
			if (isset($profiles[$name])) {
				return $profiles[$name];
			}
		}
		return false;
	}
/**
 * undocumented function
 *
 * @param unknown $node 
 * @return void
 * @access public
 */
	function xmltoArray($node) {
		$array = array();
		foreach ($node->children as $child) {
			if (empty($child->children)) {
				$value = $child->value;
			} else {
				$value = $this->xmltoArray($child);
			}

			$key = $child->name;
			if (!isset($array[$key])) {
				$array[$key] = $value;
			} else {
				if (!is_array($array[$key]) || !isset($array[$key][0])) {
					$array[$key] = array($array[$key]);
				}
				$array[$key][] = $value;
			}
		}

		return $array;
	}
	
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function close() {
		return true;
	}
}

?>