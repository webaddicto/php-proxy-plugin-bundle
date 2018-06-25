<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

use Proxy\Html;

class DailyMotionPlugin extends AbstractPlugin {

	protected $url_pattern = 'dailymotion.com';
	
	public function onBeforeRequest(ProxyEvent $event){
		// googlebot
		$event['request']->headers->set('user-agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
	}
	
	public function onCompleted(ProxyEvent $event){
		
		$response = $event['response'];
		$content = $response->getContent();
		$request = $event['request'];
		$url = $request->getUri();
		if (preg_match('%^.+dailymotion.com/(video|hub)/([^_]+)[^#]*(#video=([^_&]+))?%m', $url, $video_info)) {
			if (!empty($video_info[4])) {
				$video_id = $video_info[4];
			} else {
				$video_id = $video_info[2];
			}
			$gvi = file_get_contents('https://www.dailymotion.com/player/metadata/video/'.$video_id);
		}

		// http://www.dailymotion.com/json/video/{$id}?fields=stream_h264_sd_url,stream_h264_hq_url,stream_h264_url,stream_h264_hd_url
		if(preg_match('/"url":"([^"]+mp4[^"]*)"/m', $content, $matches) ||
			(!empty($gvi) && preg_match('/"url":"([^"]+mp4[^"]*)"/m', $gvi, $matches))){

			$video = stripslashes($matches[1]);
			
			// generate our own player
			$player = vid_player($video, 1240, 478);
			
			//$content = Html::replace_inner("#player", $player, $content);
			$content = Html::replace_outer("#dmp_Video", $player, $content);
			$content = str_replace(" dmp_is-hidden", "", $content);
		}
		
		// too many useless scripts on this site
		$content = Html::remove_scripts($content);
		
		$response->setContent($content);
	}

}


?>