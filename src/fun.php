<?php
header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
if (!function_exists('curl_init')) {
	throw new Exception('FUN needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
	throw new Exception('FUN needs the JSON PHP extension.');
}

class FUN
{
	/**
	 * API_URL
	 */
	const API_URL = 'http://api.fun.wayi.com.tw/';

	/**
	 * ���ε{���s��.
	 */
	protected $appId;

	/**
	 * ���ε{���K�_.
	 */
	protected $apiSecret;

	/**
	 * �O����eUser�n�J���A(access_token)
	 */
	protected $session;

	protected $redirectUri;

	/**
	 * �ϥ��ɮפW��
	 */
	protected $fileUploadSupport;

	public function __construct($config) {
		$this->setAppId($config['appId']);
		$this->setApiSecret($config['secret']);
		if(!empty($config['redirect_uri']))
			$this->setRedirectUri($config['redirect_uri']);
	}

	/**
	 * �]�w���ε{���s��.
	 */
	public function setAppId($appId) {
		$this->appId = $appId;
		return $this;
	}

	/**
	 * ���o���ε{���s��.
	 */
	public function getAppId() {
		return $this->appId;
	}

	/**
	 * �]�w���ε{���K�_.
	 */
	public function setApiSecret($apiSecret) {
		$this->apiSecret = $apiSecret;
		return $this;
	}

	/**
	 * ���o���ε{���K�_.
	 */
	public function getApiSecret() {
		return $this->apiSecret;
	}

	/**
	 * �]�w���ε{���K�_.
	 */
	public function setRedirectUri($uri) {
		$this->redirectUri = $uri;
		return $this;
	}

	/**
	 * ���o�n�J���A
	 */
	public function getSession() {
		if ($this->session)
			return $this->session;
		else {
			$session = array();
			//�Q��auth_code����access_token
			if (isset($_GET['code']))
			{
				$params = array(
					'code' 		=> $_GET['code'],
					'grant_type' 	=> 'authorization_code',
					'redirect_uri'	=> $this->redirectUri,
					'client_id' 	=> $this->appId,
					'client_secret' => $this->apiSecret
				);

				$result = json_decode($this->makeRequest(self::API_URL.'oauth/token', $params, $method="GET"));
				if (is_array($result) && isset($result['error'])) {
					$e = new ApiException($result);
					throw $e;
				}else
					$session = $result;

			}else if (isset($_REQUEST['session'])){
				$session = json_decode(
					get_magic_quotes_gpc()
					? stripslashes(urldecode($_REQUEST['session']))
					: urldecode($_REQUEST['session']),
						true
					);
			}else if (isset($_COOKIE[$this->getAppId().'_funsession'])){
				$session = json_decode(
					stripslashes($_COOKIE[$this->getAppId().'_funsession']),
					true
				);

			}

			if($session){
				$this->setSession($session);
				return $session;
			}
			else
				return false;
		}
	}

	/**
	 * �]�w�ϥΪ̵n�J���A
	 *
	 * @param object $session		
	 * @return void
	 */
	public function setSession($session=null) {
		$this->session = $session;
		$sessionName = $this->getAppId().'_funsession';
		$this->setCookie($sessionName, json_encode($this->session));
	}

	/**
	 * ���oaccess_token
	 * @return string
	 */
	public function getAccessToken() {
		$session = $this->getSession();
		if ($session) {
			return $session['access_token'];
		}else{
			return false;
		}
	}

	/**
	 * �]�w�W���ɮת��A
	 *
	 * @param bool $$fileUploadSupport		�]�w���A
	 * @return void
	 */
	public function setFileUploadSupport($fileUploadSupport) {
		$this->fileUploadSupport = $fileUploadSupport;
	}

	/**
	 * �ϥΤW���ɮ�
	 * @return bool
	 */
	public function useFileUploadSupport() {
		return $this->fileUploadSupport;
	}

	/**
	 * ���o�n�J���}
	 * @return string
	 */
	public function getLoginUrl($config=array()) {
		//0.validate
		$clean['redirect_uri'] = (empty($config['redirect_uri']))?$this->redirectUri:$config['redirect_uri'];
		$clean['scope'] =  (empty($config['scope']))?'':$config['scope'];

		$params = array(
			'response_type=code',
			'redirect_uri=' . urlencode($clean['redirect_uri']),
			'client_id=' . urlencode($this->appId),
			'scope=' . urlencode($clean['scope'])
		);
		$params = implode('&', $params);
		return self::API_URL . "oauth/authorize?" . $params;
	}

	/**
	 * x
	 * ����API
	 * @return string
	 */
	public function Api($path, $method = 'GET', $params = array()) {
		$params['method'] = $method;

		if (!isset($params['access_token'])) {
			$params['access_token'] = $this->session['access_token'];
		}

		foreach ($params as $key => $value) {
			if (!is_string($value)) {
				$params[$key] = json_encode($value);
			}
		}
		$result = json_decode($this->makeRequest($this->getUrl($path), $params),true);


		if (is_array($result) && isset($result['error'])) {
			$e = new ApiException($result);
			throw $e;
		}

		return $result;
	}


	protected function makeRequest($url, $params, $method="GET") {
		$ch = curl_init();
		$opts = array(
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 60,
			CURLOPT_USERAGENT      => 'funapi'
		);

		switch ($method) {
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, TRUE);
				if ($this->useFileUploadSupport()) 
					$opts[CURLOPT_POSTFIELDS] = $params;
				else
					$opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
				break;
			case 'DELETE':
				$opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
				break;
			case 'PUT':
				$opts[CURLOPT_PUT] = TRUE;
				break;
		}

		if($method!="POST")
		{
			$url.="?".http_build_query($params, null, '&');
		}
		$opts[CURLOPT_URL] = $url;

		if (isset($opts[CURLOPT_HTTPHEADER])) {
			$existing_headers = $opts[CURLOPT_HTTPHEADER];
			$existing_headers[] = 'Expect:';
			$opts[CURLOPT_HTTPHEADER] = $existing_headers;
		} else {
			$opts[CURLOPT_HTTPHEADER] = array('Expect:');
		}

		curl_setopt_array($ch, $opts);
		$result = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($code != 200) {
			$e = new ApiException(array(
				'error_code' => $code,
				'error_description'=> $result)
			);
			curl_close($ch);
			throw $e;
		}
		curl_close($ch);
		return $result;
	}


	/**
	 * �]�wcookie
	 *
	 * @param string $name	
	 * @param string $value
	 * @return void
	 */
	private function setCookie($name, $value) {
		$mtime = explode(' ', microtime());
		setcookie($name, $value, $mtime[1]+intval(30*60*1000));
	}

	/**
	 * �M��cookie
	 *
	 * @param string $name	
	 * @param string $value
	 * @return void
	 */
	private function clearCookie($name) {
		setcookie($name);
		setcookie($name, "", time() - 3600);
	}

	/**
	 * ����URL
	 *
	 * @param string $path	
	 * @param string $params
	 * @return void
	 */
	protected function getUrl($path='', $params=array()) {
		$url = self::API_URL;
		if ($path) {
			if ($path[0] === '/') {
				$path = substr($path, 1);
			}
			$url .= $path;
		}
		if ($params) {
			$url .= '?' . http_build_query($params, null, '&');
		}
		return $url;
	}
}

class ApiException extends Exception
{
	protected $result;
	public function __construct($result) {
		$this->result = $result;

		$code = isset($result['error_code']) ? $result['error_code'] : 0;

		if (isset($result['error_description'])) {
			$msg = $result['error_description'];
		} else {
			$msg = 'Unknown Error. Check getResult()';
		}
		parent::__construct($msg, $code);
	}

	public function getResult() {
		return $this->result;
	}

	public function printMessage(){
		echo 'Error Code:' .  $this->result['error_code'] . '<br/>';
		echo 'Message:' . $this->getMessage() . '<br/>';
		echo 'Description:' . $this->result['error_description'] . '<br/>';
		echo 'Stacktrace:' . $this->getTraceAsString() . '<br/>';
	}

	public function getType() {
		if (isset($this->result['error'])) {
			$error = $this->result['error'];
			return $error;
		}
		return 'Exception';
	}
}
?>
