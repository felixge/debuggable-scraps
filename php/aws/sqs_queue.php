<?php
/**
 * Copyright 2009, Debuggable Limited (http://www.debuggable.com/)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * Standalone & overall pretty PHP SQS Libary.
 * 
 * Features:
 * - Exponential backoff
 * - Unlike other implementations out there uses CURL for reliability
 * - Completely Unit Tested
 * 
 * Limitations:
 * - Only support for latest API (2008-01-01)
 * - Only support for SignatureVersion 2 w/ HmacSHA256
 * 
 * @version 1.0 BETA
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 * @author Felix Geisendörfer (felix@debuggable.com)
 */
class SqsQueue implements Countable {
	const VERSION = '2008-01-01';

	public $name = null;

	public static $serviceUrl = 'http://queue.amazonaws.com/';
	public static $userAgent = 'Debuggable.com SQS PHP5 Library';
	public static $serializer = array('json_encode', 'json_decode');
	public static $retries = 3;

	protected $_credentials = array(
		'key' => null,
		'secretKey' => null,
	);

/**
 * Sets up a new SqsQueue instance
 *
 * @param mixed $name
 * @param array $credentials
 * @return void
 */
	public function __construct($name, $credentials = array()) {
		$this->name = $name;
		$this->_credentials = $credentials;
	}
/**
 * Implements SQS::CreateQueue
 *
 * @param array $options
 * @return mixed
 */
	public function create($options = array()) {
		$defaults = array(
			'name' => $this->name,
			'timeout' => 30,
		);
		$options = $options + $defaults;

		$request = array(
			'query' => array(
				'Action' => 'CreateQueue',
				'QueueName' => $options['name'],
				'DefaultVisibilityTimeout' => $options['timeout'],
			)
		);
		if (($response = $this->rest($request)) === false) {
			return false;
		}

		return (string)$response->CreateQueueResult->QueueUrl;
	}
/**
 * Implements SQS::DeleteQueue
 *
 * @return boolean
 */
	public function delete() {
		$request = array(
			'url' => $this->name,
			'query' => array(
				'Action' => 'DeleteQueue',
			)
		);
		if (($response = $this->rest($request)) === false) {
			return false;
		}
		return true;
	}
/**
 * Implements SQS:ListQueues
 *
 * @param array $credentials
 * @param array $options
 * @return mixed
 */
	public static function listAll($credentials, $options = array()) {
		$defaults = array(
			'prefix' => null,
		);
		$options = $options + $defaults;

		$request = array(
			'query' => array(
				'Action' => 'ListQueues',
				'QueueNamePrefix' => $options['prefix']
			)
		);

		if (($response = self::rest($request, $credentials)) === false) {
			return false;
		}

		$r = array();
		foreach ($response->ListQueuesResult->QueueUrl as $url) {
			$r[] = (string)$url;
		}
		return $r;
	}
/**
 * Implements SQS:SetQueueAttributes and SQS::GetQueueAttributes
 *
 * @param string $key
 * @param string $val
 * @return mixed
 */
	public function attributes($key = 'All', $val = null) {
		if (is_null($val)) {
			$request = array(
				'url' => $this->name,
				'query' => array(
					'Action' => 'GetQueueAttributes',
					'AttributeName' => $key
				)
			);

			if (($response = $this->rest($request)) === false) {
				return false;
			}

			$r = array();
			foreach ($response->GetQueueAttributesResult->Attribute as $attribute) {
				$r[(string)$attribute->Name] = (string)$attribute->Value;
			}
			return $r;
		}

		$request = array(
			'url' => $this->name,
			'query' => array(
				'Action' => 'SetQueueAttributes',
				'Attribute.Name' => $key,
				'Attribute.Value' => $val,
			)
		);

		if (($response = $this->rest($request)) === false) {
			return false;
		}
		return array($key => $val);
	}
/**
 * Implements SQS::SendMessage
 *
 * @param mixed $message
 * @param array $options
 * @return mixed
 */
	public function sendMessage($message, $options = array()) {
		$defaults = array(
			'name' => $this->name,
			'message' => $message,
		);
		$options = $options + $defaults;

		if (is_array($options['message'])) {
			$options['message'] = call_user_func(self::$serializer[0], $options['message']);
		}

		$request = array(
			'url' => $options['name'],
			'query' => array(
				'Action' => 'SendMessage',
				'MessageBody' => $options['message'],
			)
		);
		if (($response = $this->rest($request)) === false) {
			return false;
		}

		if (md5($options['message']) !== (string)$response->SendMessageResult->MD5OfMessageBody) {
			trigger_error('SqsQueue: Wrong md5 returned to sendMessage', E_USER_WARNING);
			return false;
		}
		return (string)$response->SendMessageResult->MessageId;
	}
/**
 * Implements SQS::ReceiveMessage
 *
 * @param array $options
 * @return mixed
 */
	public function receiveMessage($options = array()) {
		$defaults = array(
			'name' => $this->name,
			'max' => 1,
			'timeout' => 0,
		);
		$options = $options + $defaults;

		$request = array(
			'url' => $options['name'],
			'query' => array(
				'Action' => 'ReceiveMessage',
				'MaxNumberOfMessages' => $options['max'],
				'VisibilityTimeout' => $options['timeout'],
			)
		);
		if (($response = $this->rest($request)) === false) {
			return false;
		}

		$r = array();
		foreach ($response->ReceiveMessageResult->Message as $message) {
			$body = (string)$message->Body;
			if ($deserialzed = call_user_func(self::$serializer[1], $body)) {
				$body = $deserialzed;
			}

			$r[] = array(
				'id' => (string)$message->MessageId,
				'handle' => (string)$message->ReceiptHandle,
				'md5' => (string)$message->MD5OfBody,
				'body' => $body,
			);
		}
		return ($options['max'] == 1 && $r)
			? $r[0]
			: $r;
	}
/**
 * Implements SQS::DeleteMessage
 *
 * @param mixed $handle
 * @param array $options
 * @return mixed
 */
	public function deleteMessage($handle, $options = array()) {
		$defaults = array(
			'name' => $this->name,
			'handle' => $handle,
		);
		$options = $options + $defaults;

		if (is_array($options['handle'])) {
			$options['handle'] = $options['handle']['handle'];
		}

		$request = array(
			'url' => $options['name'],
			'query' => array(
				'Action' => 'DeleteMessage',
				'ReceiptHandle' => $options['handle'],
			)
		);
		if (($response = $this->rest($request)) === false) {
			return false;
		}
		return true;
	}
/**
 * Convenience method to check if a queue exists by checking if it has a count
 *
 * @return boolean
 */
	public function exists() {
		return count($this) >= 0;
	}
/**
 * Convenience method for self::attributes() to check or set a single attribute
 *
 * @param string $key
 * @param string $val
 * @return mixed
 */
	public function attribute($key, $val = null) {
		$r = $this->attributes($key, $val);
		if (!$r || !isset($r[$key])) {
			return false;
		}

		return (ctype_digit($r[$key]))
			? (int)$r[$key]
			: $r[$key];
	}
/**
 * Convenience method for self::attribute('ApproximateNumberOfMessages')
 *
 * @return integer
 */
	public function count() {
		$count = $this->attribute('ApproximateNumberOfMessages');
		return ($count !== false)
			? $count
			: -1;
	}
/**
 * Does a REST request to SQS as well as retries with exponential backoff.
 *
 * @param array $options
 * @param array $credentials
 * @return mixed
 */
	public function rest($options, $credentials = array()) {
		$defaults = array(
			'method' => 'POST',
			'url' => self::$serviceUrl,
			'query' => array(),
		);
		$options = $options + $defaults;

		if (empty($credentials)) {
			$credentials = $this->_credentials;
		}

		$options['query'] = $options['query'] + array(
			'Version' => self::VERSION,
			'Timestamp' => gmdate('Y-m-d\TH:i:s.\\0\\0\\0\\Z'),
			'SignatureVersion' => '2',
			'SignatureMethod' => 'HmacSHA256',
			'AWSAccessKeyId' => $credentials['key']
		);

		$canonicalQuery = self::_awsBuildQuery($options['query']);

		$options['url'] = self::url($options['url']);

		$uri = parse_url($options['url']);
		$stringToSign = sprintf(
			"%s\n%s\n%s\n%s",
			$options['method'],
			strtolower($uri['host']),
			$uri['path'],
			$canonicalQuery
		);

		$signature = base64_encode(hash_hmac(
			'sha256',
			$stringToSign,
			$credentials['secretKey'],
			true
		));

		$options['query']['Signature'] = $signature;
		$query = self::_awsBuildQuery($options['query']);

		$attempt = 0;
		while (true) {
			$attempt++;

			$curl = curl_init($options['url']);
			curl_setopt_array($curl, array(
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $query,
				CURLOPT_USERAGENT => self::$userAgent
			));

			$r = curl_exec($curl);
			if ($error = curl_error($curl)) {
				trigger_error('SqsQueue: curl error: ' . curl_error($curl), E_USER_WARNING);
				if (!self::_exponentialBackoff($attempt, self::$retries)) {
					return false;
				}
				continue;
			}
			break;
		}

		$xml = new SimpleXMLElement($r);
		return (isset($xml->Errors) || isset($xml->Error))
			? false
			: $xml;
	}
/**
 * Returns the queue url for a given queue name
 *
 * @param string $name
 * @return string
 */
	public static function url($name) {
		if (preg_match('/^https?:/', $name)) {
			return $name;
		}
		return self::$serviceUrl . $name;
	}
/**
 * Builds the query to AWS liking
 *
 * @param array $query
 * @return string
 */
	protected static function _awsBuildQuery($query) {
		uksort($query, 'strcmp');
		$r = array();
		foreach ($query as $key => $value) {
			$r[] = $key . '=' . str_replace('%7E', '~', rawurlencode($value));
		}
		return join('&', $r);
	}

	protected static function _exponentialBackoff($current, $max) {
		if ($current <= $max) {
			$delay = (int)(pow(2, $current) * 100000);
			usleep($delay);
			return true;
		}
		return false;
	}
}


?>