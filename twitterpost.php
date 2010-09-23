<?php

// no direct access

defined('_JEXEC') or die('Restricted access');
require_once (JPATH_SITE . DS . 'components' . DS . 'com_content' . DS . 'helpers' . DS . 'route.php');

// Import library dependencies

jimport('joomla.event.plugin');
class plgContentTwitterpost extends JPlugin

	{
	var $Username;
	var $Password;
	var $Tinyurl;
	var $Maxlen;
	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for
	 * plugins because func_get_args ( void ) returns a copy of all passed arguments
	 * NOT references.  This causes problems with cross-referencing necessary for the
	 * observer design pattern.
	 */
	function plgContentTwitterpost(&$subject)
		{
		parent::__construct($subject);

		// load plugin parameters

		$this->_plugin = JPluginHelper::getPlugin('content', 'twitterpost');
		$this->params = new JParameter($this->_plugin->params);
		$this->Username = $this->params->get('username');
		$this->Password = $this->params->get('password');
		$this->Tinyurl = (int) $this->params->get('tinyurl');
		$this->Maxlen = (int) $this->params->get('maxlen');
		}

	/**
	 * Plugin method with the same name as the event will be called automatically.
	 */
	function onAfterContentSave($article, $isNew)
		{
		global $mainframe;
		//$isNew = true;
		$categories = trim($this->params->get('categories'));
		$sections = trim($this->params->get('sections'));
		if ($categories)
			{
			$ids_cat = explode(',', $categories);
			JArrayHelper::toInteger($ids_cat);
			}else{
			$ids_cat=array();
			}


		if ($sections)
			{
			$ids_sec = explode(',', $sections);
			JArrayHelper::toInteger($ids_sec);
			}else{
			$ids_sec=array();
			}
		
		if ((!$sections || ($sections && !$categories) || in_array($article->catid, $ids_cat) || in_array($article->sectionid, $ids_sec)) && $isNew)
			{
			$link = JRoute::_(ContentHelperRoute::getArticleRoute($article->id, $article->catid, $article->sectionid));
			$link = str_replace('//', '/', JURI::base() . $link);
			$link = str_replace('http:/', 'http://', $link);
			$link = str_replace('/administrator', '', $link);
			if($this->params->get('tinyurl'))
				{
				$link = $this->make_tiny($link);
				}
			
			if($this->params->get('maxlen'))
				{
				$article->title =substr($article->title,0,$this->params->get('maxlen'));
				}
			
			$msg=$article->title . " - URL: " . $link;

			$this->postToTwitter($this->Username,$this->Password,$msg);

			}
		return true;
		}

	function make_tiny($url)
		{
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, 'http://tinyurl.com/api-create.php?url=' . $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
		}

	function postToTwitter($username, $password, $message)
		{
		if (!get_magic_quotes_gpc()) 
			{			
			$message=stripslashes($message);
			}
		$host = "http://twitter.com/statuses/update.xml?status=" . urlencode($message);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $host);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_POST, 1);
		$result = curl_exec($ch);

		// Look at the returned header

		$resultArray = curl_getinfo($ch);
		curl_close($ch);
		if ($resultArray['http_code'] == "200")
			{
			$twitter_status = 'Your message has been sent! <a href="http://twitter.com/' . $username . '">See your profile</a>';
			}
		  else
			{
			$twitter_status = "Error posting to Twitter. Retry";
			}

		return $twitter_status;
		}
	}

?>
