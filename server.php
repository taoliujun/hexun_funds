<?php
set_time_limit(0);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');


class app {

	public static function getParam($name,$defaultValue=null)
	{
		return isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : $defaultValue);
	}

}

class CCurlHelper
{
	private $_curl;
	private $_url = null;
	public function __construct( $url )
	{
		$this->_url = $url;
		$this->_open();
	}

	public function __destruct()
	{
		curl_close($this->_curl);
	}

	public function getCurl()
	{
		return $this->_curl;
	}

	public function getError()
	{
		return curl_error($this->_curl);
	}

	public function getInfo($option)
	{
		return curl_getinfo( $this->_curl , $option );
	}

	public function curlPost($data)
	{
		curl_setopt( $this->_curl , CURLOPT_POST , true);
		curl_setopt( $this->_curl , CURLOPT_POSTFIELDS , $data);
		$ret = curl_exec( $this->_curl );
		return $ret;
	}

	public function curlGet()
	{
		curl_setopt( $this->_curl , CURLOPT_POST , false);
		$ret = curl_exec( $this->_curl );
		return $ret;
	}

	private function _open()
	{
		$curlHandle = curl_init();
		curl_setopt( $curlHandle , CURLOPT_URL , $this->_url ); //指定url
		curl_setopt( $curlHandle , CURLOPT_RETURNTRANSFER , true ); //返回源码
		curl_setopt( $curlHandle , CURLOPT_SSL_VERIFYPEER, false); //关闭ssl
		curl_setopt( $curlHandle , CURLOPT_USERAGENT , $_SERVER['HTTP_USER_AGENT']);//模拟一个header
		curl_setopt( $curlHandle , CURLOPT_TIMEOUT, 30 ); //读取的最大时间秒
		curl_setopt( $curlHandle , CURLOPT_FRESH_CONNECT ,true); //关闭内容缓存
		$this->_curl = $curlHandle;
	}

}

$do = app::getParam('do');

if( $do === 'getAll' )
{
	$time = time();
	$id = app::getParam('id');
	$starttime = app::getParam('startdate' , date('Y-m-d' , strtotime('-30days')));
	$endtime = app::getParam('endtime' , date('Y-m-d'));

	$curl = new CCurlHelper("http://jingzhi.funds.hexun.com/DataBase/jzzs.aspx?fundcode={$id}&startdate={$starttime}&enddate={$endtime}");
	$html = $curl->curlGet();
	$ret = null;
	preg_match('/<table class="n_table m_table".+<tbody>([\s\S]+)<\/tbody>/is' , $html , $matchs);
	if($matchs)
	{
		$ret = $matchs[1];
	}
	preg_match_all("/<tr>.+?<td[^>]+>(\d+\-\d+\-\d+)<\/td>.+?<td[^>]+>([0-9\.]+)<\/td>.+?<td[^>]+>([0-9\.]+)<\/td>.+?<td[^>]+>(-{0,1}[0-9\.]+%)<\/td>.*?<\/tr>/is" , $ret , $matchs);
	if( $matchs )
	{
		$ret = array();
		krsort($matchs[1]);
		$matchs[1] = array_values($matchs[1]);
		krsort($matchs[2]);
		$matchs[2] = array_values($matchs[2]);
		krsort($matchs[3]);
		$matchs[3] = array_values($matchs[3]);
		krsort($matchs[4]);
		$matchs[4] = array_values($matchs[4]);
		foreach($matchs[1] as $k=>$v)
		{
			$ret[$k] = array(
				'date' => $matchs[1][$k],
				'permoney' => $matchs[2][$k],
				'totalmoney' => $matchs[3][$k],
				'bill' => str_replace('%','',$matchs[4][$k]),
			);
		}
	}
	else
	{
		$ret = array();
	}
	$title = '';
	preg_match('/<title>([\s\S]+)?\([^<]+?<\/title>/is' , $html , $match);
	if( $match )
	{
		$title = mb_convert_encoding($match[1] , 'utf-8' , 'gb2312');
	}

	exit(json_encode(array(
		'title' => $title,
		'datas' => $ret,
	),1));
}