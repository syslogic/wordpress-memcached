<?php
/*
	WordPress Latest with FlexiCache
	cURL Installer for Pagoda Box v1.06
	Copyright 2012 by Martin Zeitler
	http://codefx.biz/contact
*/

/* the environment */
$fn1='latest.zip';
$fn2='flexicache.1.2.4.3.zip';
$src1='http://wordpress.org/'.$fn1;
$src2='http://downloads.wordpress.org/plugin/'.$fn2;
$base_dir = str_replace('/pagoda','', dirname(__FILE__));
$hostname=$_SERVER['APP_NAME'].'.pagodabox.com';
$version_info=dirname(__FILE__).'/wordpress/wp-includes/version.php';
$plugins=dirname(__FILE__).'/wordpress/wp-content/plugins';
$dst1=$base_dir.'/pagoda/'.$fn1;
$dst2=$base_dir.'/pagoda/'.$fn2;

/* fetch the packages */
wget($src1, $dst1);
wget($src2, $dst2);

/* extract the main package */
$zip = new ZipArchive;
if($zip->open($dst1) === TRUE) {
	$zip->extractTo(dirname(__FILE__));
	$zip->close();
}

/* extract the plug-in package */
$zip = new ZipArchive;
if($zip->open($dst2) === TRUE) {
	$zip->extractTo($plugins);
	$zip->close();
}

/* fixing the directory structure (required to mount the shared directory) */
if(file_exists($plugins.'/flexicache/_data/.htaccess')){unlink($plugins.'/flexicache/_data/.htaccess');}
if(is_dir($plugins.'/flexicache/_data/_storage')){rmdir($plugins.'/flexicache/_data/_storage');}

/* removing some useless files */
unlink(dirname(__FILE__).'/wordpress/wp-config-sample.php');
unlink($plugins.'/hello.php');

/* [TODO] unique salts would need to be added wp-config.php */
copy(dirname(__FILE__).'/wp-config.php', dirname(__FILE__).'/wordpress/wp-config.php');

/* [TODO] patching FlexiCache's config.ser with the actual hostname */
// $config = file_get_contents(dirname(__FILE__).'/config.ser');
// preg_replace('/HTTP_HOST/i', $hostname, $config);
// file_put_contents(dirname(__FILE__).'/config.ser', $config);

/* retrieve version number */
if(file_exists($version_info)){
	require_once($version_info);
	echo 'WordPress v'.$wp_version.' with FlexiCache v1.2.4.3 will now be deployed.';
}

function wget($src, $dst){
	$fp = fopen($dst, 'w');
	$curl = curl_init();
	$opt = array(CURLOPT_URL => $src, CURLOPT_HEADER => false, CURLOPT_FILE => $fp);
	curl_setopt_array($curl, $opt);
	$rsp = curl_exec($curl);
	if($rsp===false){
		die("[cURL] errno:".curl_errno($curl)."\n[cURL] error:".curl_error($curl)."\n");
	}
	$info = curl_getinfo($curl);
	curl_close($curl);
	fclose($fp);
	
	/* cURL stats */
	$time = $info['total_time']-$info['namelookup_time']-$info['connect_time']-$info['pretransfer_time']-$info['starttransfer_time']-$info['redirect_time'];
	echo "Fetched '$src' @ ".abs(round(($info['size_download']*8/$time/1024/1024),2))."MBit/s.\n";
}

function format_size($size=0) {
	if($size < 1024){
		return $size.'b';
	}
	elseif($size < 1048576){
		return round($size/1024,2).'kb';
	}
	else {
		return round($size/1048576,2).'mb';
	}
}
?>