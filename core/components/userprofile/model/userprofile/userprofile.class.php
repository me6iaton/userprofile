<?php

/**
 * The base class for userprofile.
 */
class userprofile
{
	/* @var modX $modx */
	public $modx;

	public $namespace = 'userprofile';
	public $cache = null;
	public $config = array();

	public $active = false;
	public $defaultTypeId = 0;

	/* @var pdoTools $pdoTools */
	public $pdoTools;

	/**
	 * @param modX $modx
	 * @param array $config
	 */
	function __construct(modX &$modx, array $config = array())
	{
		$this->modx =& $modx;

		$this->namespace = $this->getOption('userprofile', $config, 'userprofile');
		$corePath = $this->modx->getOption('userprofile_core_path', $config, $this->modx->getOption('core_path') . 'components/userprofile/');
		$assetsUrl = $this->modx->getOption('userprofile_assets_url', $config, $this->modx->getOption('assets_url') . 'components/userprofile/');
		$connectorUrl = $assetsUrl . 'connector.php';

		$this->config = array_merge(array(
			'assetsUrl' => $assetsUrl,
			'cssUrl' => $assetsUrl . 'css/',
			'jsUrl' => $assetsUrl . 'js/',
			'imagesUrl' => $assetsUrl . 'images/',
			'connectorUrl' => $connectorUrl,

			'corePath' => $corePath,
			'modelPath' => $corePath . 'model/',
			'chunksPath' => $corePath . 'elements/chunks/',
			'templatesPath' => $corePath . 'elements/templates/',

			'chunkSuffix' => '.chunk.tpl',
			'snippetsPath' => $corePath . 'elements/snippets/',
			'processorsPath' => $corePath . 'processors/',

			'ctx' => 'web',
			'json_response' => 0,
			'dateFormat' => 'd F Y, H:i',
			'dateNow' => 10,
			'dateDay' => 'day H:i',
			'dateMinutes' => 59,
			'dateHours' => 10,

			'gravatarUrl' => 'https://www.gravatar.com/avatar/',
			'gravatarSize' => 300,
			'gravatarIcon' => 'mm',

			'disabledTabs' => 'activity',

		), $config);

		$this->modx->addPackage('userprofile', $this->config['modelPath']);
		$this->modx->lexicon->load('userprofile:default');

		$this->active = $this->modx->getOption('userprofile_active', $config, false);
		// default type_id
		if ($extSetting = $this->modx->getObject('upExtendedSetting', array('active' => 1, 'default' => 1))) {
			$this->defaultTypeId = $extSetting->get('id');
		}
		else {
			$this->modx->log(modX::LOG_LEVEL_ERROR, 'UserProfile error get default TypeId.');
		}

	}

	/**
	 * @param $key
	 * @param array $config
	 * @param null $default
	 * @return mixed|null
	 */
	public function getOption($key, $config = array(), $default = null)
	{
		$option = $default;
		if (!empty($key) && is_string($key)) {
			if ($config != null && array_key_exists($key, $config)) {
				$option = $config[$key];
			} elseif (array_key_exists($key, $this->config)) {
				$option = $this->config[$key];
			} elseif (array_key_exists("{$this->namespace}.{$key}", $this->modx->config)) {
				$option = $this->modx->getOption("{$this->namespace}.{$key}");
			}
		}
		return $option;
	}

	/**
	 * @param string $ctx
	 * @param array $scriptProperties
	 * @return bool
	 */
	public function initialize($ctx = 'web', $scriptProperties = array())
	{
		$this->config = array_merge($this->config, $scriptProperties);
		if (!$this->pdoTools) {
			$this->loadPdoTools();
		}
		$this->pdoTools->setConfig($this->config);
		$this->config['ctx'] = $ctx;
		if (!empty($this->initialized[$ctx])) {
			return true;
		}
		switch ($ctx) {
			case 'mgr':
				break;
			default:
				if (!defined('MODX_API_MODE') || !MODX_API_MODE) {
					if ($css = trim($this->config['frontend_css'])) {
						if (preg_match('/\.css/i', $css)) {
							$this->modx->regClientCSS(str_replace('[[+assetsUrl]]', $this->config['assetsUrl'], $css));
						}
					}
					$config_js = preg_replace(array('/^\n/', '/\t{5}/'), '', '
					payandsee = {};
					payandseeConfig = {
						cssUrl: "'.$this->config['cssUrl'].'web/"
						,jsUrl: "'.$this->config['jsUrl'].'web/"
						,actionUrl: "'.$this->config['actionUrl'].'"
						,ctx: "'.$this->modx->context->get('key').'"
						,close_all_message: "'.$this->modx->lexicon('pas_message_close_all').'"
						,price_format: '.$this->modx->getOption('payandsee_price_format', null, '[2, ".", " "]').'
						,price_format_no_zeros: '.$this->modx->getOption('payandsee_price_format_no_zeros', null, true).'
					};
					');
					$this->modx->regClientStartupScript("<script type=\"text/javascript\">\n".$config_js."\n</script>", true);
					if ($js = trim($this->config['frontend_js'])) {
						if (!empty($js) && preg_match('/\.js/i', $js)) {
							$this->modx->regClientScript(preg_replace(array('/^\n/', '/\t{7}/'), '', '
							<script type="text/javascript">
								if(typeof jQuery == "undefined") {
									document.write("<script src=\"'.$this->config['jsUrl'].'web/lib/jquery.min.js\" type=\"text/javascript\"><\/script>");
								}
							</script>
							'), true);
							$this->modx->regClientScript(str_replace('[[+assetsUrl]]', $this->config['assetsUrl'], $js));
						}
					}
				}
				$this->initialized[$ctx] = true;
				break;
		}
		return true;
	}

	/**
	 * @param $sp
	 */
	public function OnUserFormPrerender($sp)
	{
		if ($this->isNew($sp)) return;
		$this->modx->controller->addLexiconTopic('userprofile:default');
		$id = $sp['id'];
		$user = $sp['user'];
		$up_extended = array();
		$profile = $user->getOne('Profile')->toArray();
		$profile = array_merge($profile, array(
				'gravatar' => $this->config['gravatarUrl'] . md5(strtolower($profile['email'])) . '?s='.$this->config['gravatarSize'] . '&d=' . $this->config['gravatarIcon'],
			)
		);
		if ($upExtended = $this->modx->getObject('upExtended', array('user_id' => $id))) {
			$up_extended = $upExtended->toArray();
		}
		// если extended пуст
		if (!is_array($profile['extended'])) $profile['extended'] = array();
		$up_extended = array_merge($profile['extended'], $up_extended);
		//
		if (!$extSetting = $this->modx->getObject('upExtendedSetting', array('id' => $up_extended['type_id']))) {
			$extSetting = $this->modx->getObject('upExtendedSetting', array('active' => 1, 'default' => 1));
		}
		$ext_setting = $extSetting->toArray();
		// requires
		$requires = array(1);
		$requires = array_flip(array_merge($requires, explode(',', $ext_setting['requires'])));
//		$this->modx->log(1, print_r($ext_setting, 1));
		$data_js = preg_replace(array('/^\n/', '/\t{6}/'), '', '
			userprofile = {};
			userprofile.config = ' . $this->modx->toJSON(array(
				'connectorUrl' => $this->config['connectorUrl'],
				'extSetting' => $ext_setting,
				'upExtended' => $up_extended,
				'profile' => $profile,
				'tabs' => implode(',', array_keys($this->modx->fromJSON($ext_setting['tabfields']))),
				'disabledTabs' => $this->config['disabledTabs'],
				'requires' => $this->modx->toJSON($requires),
			)) . ';
		');
		$this->modx->regClientStartupScript("<script type=\"text/javascript\">\n" . $data_js . "\n</script>", true);
		$this->modx->regClientCSS($this->getOption('cssUrl') . 'mgr/main.css');
		$this->modx->regClientStartupScript($this->getOption('jsUrl') . 'mgr/misc/up.combo.js');
		$this->modx->regClientStartupScript($this->getOption('jsUrl') . 'mgr/inject/tab.js');
	}

	/**
	 * @param $sp
	 */
	public function OnBeforeUserFormSave($sp)
	{
		if ($this->isNew($sp)) return;
		$this->config['json_response'] = 1;
		$data = $sp['data'];
		$user_id = $data['id'];

		$real = array_merge($data['up']['real'], $data['up']['personal']);
		unset(
		$data['up']['real'],
		$data['up']['personal'],
		$data['up']['activity']
		);
		if (!$upExtended = $this->modx->getObject('upExtended', array('user_id' => $user_id))) {
			$upExtended = $this->modx->newObject('upExtended', array(
				'user_id' => $user_id,
				'registration' => date('Y-m-d H:i:s'),
				'lastactivity' => date('Y-m-d H:i:s'),
			));
		}
		$upExtended->fromArray(
			$real
		);
		if (!$upExtended->save()) {
			echo $this->error('up_save_up_extended_err');
			exit;
		}
		// extended
		$user = $sp['user'];
		$profile = $user->getOne('Profile');
		$profileArr = $profile->toArray();
		$extended = $profileArr['extended'];
		foreach ($data as $dd) {
			if (is_array($dd)) {
				$extended = array_merge($extended, $dd);
			}
		}
		$profile->set('extended', $extended);
		$profile->save();
	}

	/**
	 * @param $sp
	 */
	public function OnUserSave($sp)
	{
		if (!$this->isNew($sp)) return;
		$user = $sp['user'];
		$userArr = $user->toArray();
		$id = $userArr['id'];
		if (!$upExtended = $this->modx->getObject('upExtended', array('user_id' => $id))) {
			$upExtended = $this->modx->newObject('upExtended', array('user_id' => $id));
		}
		$upExtended->fromArray(array(
			'type_id' => $this->defaultTypeId,
			'registration' => date('Y-m-d H:i:s'),
		));
		$upExtended->save();
	}

	/**
	 * @param $sp
	 */
	public function OnLoadWebDocument($sp)
	{
		if ($this->modx->user->isAuthenticated($this->modx->context->get('key'))) {
			$id = $this->modx->user->id;
			if (!$upExtended = $this->modx->getObject('upExtended', array('user_id' => $id))) {
				$upExtended = $this->modx->newObject('upExtended', array('user_id' => $id));
			}
			$upExtended->fromArray(array(
				'type_id' => $this->defaultTypeId,
				'registration' => date('Y-m-d H:i:s'),
				'lastactivity' => date('Y-m-d H:i:s'),
				'ip' => $this->modx->request->getClientIp()['ip'],
			));
			$upExtended->save();
		}
	}

	/**
	 * @param $sp
	 */
	public function OnPageNotFound($sp)
	{
		$alias = $this->modx->context->getOption('request_param_alias', 'q');
		if (!isset($_REQUEST[$alias])) {return false;}
		$rarr = explode('/', $_REQUEST[$alias]);

		//$this->modx->log(1, print_r($_REQUEST, 1));

		$this->modx->log(1, print_r($rarr, 1));

		// для работы
		if ($rarr[0] == $this->modx->getOption('userprofile_main_url', null, 'users') && (count($rarr) > 1)) {
			//$this->modx->log(1, print_r($matches, 1));
/*			if ($matches[1] > 0) {
				$this->modx->sendRedirect($this->modx->makeUrl((int)$matches[1]), array('responseCode' => 'HTTP/1.1 301 Moved Permanently'));
			}*/
		}
	}

	/**
	 * @param string $message
	 * @param array $data
	 * @param array $placeholders
	 * @return array|string
	 */
	public function error($message = '', $data = array(), $placeholders = array())
	{
		$response = array(
			'success' => false,
			'message' => $this->modx->lexicon($message, $placeholders),
			'data' => $data,
		);
		return $this->config['json_response']
			? $this->modx->toJSON($response)
			: $response;
	}

	/**
	 * @param string $message
	 * @param array $data
	 * @param array $placeholders
	 * @return array|string
	 */
	public function success($message = '', $data = array(), $placeholders = array())
	{
		$response = array(
			'success' => true,
			'message' => $this->modx->lexicon($message, $placeholders),
			'data' => $data,
		);
		return $this->config['json_response']
			? $this->modx->toJSON($response)
			: $response;
	}

	/**
	 * @param array $d
	 * @return bool
	 */
	public function isNew($d = array())
	{
		if ($d['mode'] == 'new') {
			return true;
		}
		return false;
	}

	/**
	 * Formats date to "10 minutes ago" or "Yesterday in 22:10"
	 * This algorithm taken from https://github.com/livestreet/livestreet/blob/7a6039b21c326acf03c956772325e1398801c5fe/engine/modules/viewer/plugs/function.date_format.php
	 * @param string $date Timestamp to format
	 * @param string $dateFormat
	 *
	 * @return string
	 */
	public function dateFormat($date, $dateFormat = null) {
		$date = preg_match('/^\d+$/',$date) ?  $date : strtotime($date);
		$dateFormat = !empty($dateFormat) ? $dateFormat : $this->config['dateFormat'];
		$current = time();
		$delta = $current - $date;
		if ($this->config['dateNow']) {
			if ($delta < $this->config['dateNow']) {return $this->modx->lexicon('ticket_date_now');}
		}
		if ($this->config['dateMinutes']) {
			$minutes = round(($delta) / 60);
			if ($minutes < $this->config['dateMinutes']) {
				if ($minutes > 0) {
					return $this->declension($minutes, $this->modx->lexicon('ticket_date_minutes_back',array('minutes' => $minutes)));
				}
				else {
					return $this->modx->lexicon('ticket_date_minutes_back_less');
				}
			}
		}
		if ($this->config['dateHours']) {
			$hours = round(($delta) / 3600);
			if ($hours < $this->config['dateHours']) {
				if ($hours > 0) {
					return $this->declension($hours, $this->modx->lexicon('ticket_date_hours_back',array('hours' => $hours)));
				}
				else {
					return $this->modx->lexicon('ticket_date_hours_back_less');
				}
			}
		}
		if ($this->config['dateDay']) {
			switch(date('Y-m-d', $date)) {
				case date('Y-m-d'):
					$day = $this->modx->lexicon('ticket_date_today');
					break;
				case date('Y-m-d', mktime(0, 0, 0, date('m')  , date('d')-1, date('Y')) ):
					$day = $this->modx->lexicon('ticket_date_yesterday');
					break;
				case date('Y-m-d', mktime(0, 0, 0, date('m')  , date('d')+1, date('Y')) ):
					$day = $this->modx->lexicon('ticket_date_tomorrow');
					break;
				default: $day = null;
			}
			if($day) {
				$format = str_replace("day",preg_replace("#(\w{1})#",'\\\${1}',$day),$this->config['dateDay']);
				return date($format,$date);
			}
		}
		$m = date("n", $date);
		$month_arr = $this->modx->fromJSON($this->modx->lexicon('ticket_date_months'));
		$month = $month_arr[$m - 1];
		$format = preg_replace("~(?<!\\\\)F~U", preg_replace('~(\w{1})~u','\\\${1}', $month), $dateFormat);
		return date($format ,$date);
	}

	/**
	 * Declension of words
	 * This algorithm taken from https://github.com/livestreet/livestreet/blob/eca10c0186c8174b774a2125d8af3760e1c34825/engine/modules/viewer/plugs/modifier.declension.php
	 *
	 * @param int $count
	 * @param string $forms
	 * @param string $lang
	 *
	 * @return string
	 */
	public function declension($count, $forms, $lang = null) {
		if (empty($lang)) {
			$lang = $this->modx->getOption('cultureKey',null,'en');
		}
		$forms = $this->modx->fromJSON($forms);
		if ($lang == 'ru') {
			$mod100 = $count % 100;
			switch ($count%10) {
				case 1:
					if ($mod100 == 11) {$text = $forms[2];}
					else {$text = $forms[0];}
					break;
				case 2:
				case 3:
				case 4:
					if (($mod100 > 10) && ($mod100 < 20)) {$text = $forms[2];}
					else {$text = $forms[1];}
					break;
				case 5:
				case 6:
				case 7:
				case 8:
				case 9:
				case 0:
				default: $text = $forms[2];
			}
		}
		else {
			if ($count == 1) {
				$text = $forms[0];
			}
			else {
				$text = $forms[1];
			}
		}
		return $text;
	}

	/**
	 * from https://github.com/bezumkin/Tickets/blob/9c09152ae4a1cdae04fb31d2bc0fa57be5e0c7ea/core/components/tickets/model/tickets/tickets.class.php#L1120
	 *
	 * Loads an instance of pdoTools
	 * @return boolean
	 */
	public function loadPdoTools()
	{
		if (!is_object($this->pdoTools) || !($this->pdoTools instanceof pdoTools)) {
			/** @var pdoFetch $pdoFetch */
			$fqn = $this->modx->getOption('pdoFetch.class', null, 'pdotools.pdofetch', true);
			if ($pdoClass = $this->modx->loadClass($fqn, '', false, true)) {
				$this->pdoTools = new $pdoClass($this->modx, $this->config);
			} elseif ($pdoClass = $this->modx->loadClass($fqn, MODX_CORE_PATH . 'components/pdotools/model/', false, true)) {
				$this->pdoTools = new $pdoClass($this->modx, $this->config);
			} else {
				$this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not load pdoFetch from "MODX_CORE_PATH/components/pdotools/model/".');
			}
		}
		return !empty($this->pdoTools) && $this->pdoTools instanceof pdoTools;
	}

	/**
	 * from https://github.com/bezumkin/Tickets/blob/9c09152ae4a1cdae04fb31d2bc0fa57be5e0c7ea/core/components/tickets/model/tickets/tickets.class.php#L1147
	 *
	 * Process and return the output from a Chunk by name.
	 * @param string $name The name of the chunk.
	 * @param array $properties An associative array of properties to process the Chunk with, treated as placeholders within the scope of the Element.
	 * @param boolean $fastMode If false, all MODX tags in chunk will be processed.
	 * @return string The processed output of the Chunk.
	 */
	public function getChunk($name, array $properties = array(), $fastMode = false)
	{
		if (!$this->modx->parser) {
			$this->modx->getParser();
		}
		if (!$this->pdoTools) {
			$this->loadPdoTools();
		}
		return $this->pdoTools->getChunk($name, $properties, $fastMode);
	}

}