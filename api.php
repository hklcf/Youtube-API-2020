<?php
$videoid = isset($_GET['videoid']) ? $_GET['videoid'] : null;
$authorization = 'SAPISIDHASH 0000000000_a0a000a0000a0a00a0aaa00000aa0aaaaaaaaaaa'; // authorization header
$user_agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.122 Safari/537.36'; // user_agent
$cookie = 'SID=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.; __Secure-3PSID=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.; HSID=aaaaaaaaaaaaaaaaa; SSID=aaaaaaaaaaaaaaaaa; APISID=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa; SAPISID=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa; __Secure-HSID=aaaaaaaaaaaaaaaaa; __Secure-SSID=aaaaaaaaaaaaaaaaa; __Secure-APISID=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa; __Secure-3PAPISID=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa; CONSENT=YES+HK.zh-HK+202002; YSC=aaaaaaaaaaa; LOGIN_INFO=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa; VISITOR_INFO1_LIVE=aaaaaaaaaaa; SIDCC=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'; // cookie
$on_behalf_of_user = '000000000000000000000';

function getVideoInfo($video_id) {
  global $authorization, $user_agent, $cookie, $on_behalf_of_user;

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://studio.youtube.com/youtubei/v1/creator/get_creator_videos?alt=json&key=AIzaSyBUPetSUmoZL-OhlxA7wSac5XinrygCqMo');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"context\":{\"client\":{\"clientName\":62,\"clientVersion\":\"1.20200226.0.3\"},\"user\":{\"onBehalfOfUser\":\"$on_behalf_of_user\"}},\"failOnError\":true,\"videoIds\":[\"$video_id\"],\"mask\":{\"title\":true,\"titleFormattedString\":{\"all\":true},\"videoStreamUrl\":true,\"downloadUrl\":true}}");
  curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
  $headers = array();
  $headers[] = "Authorization: $authorization";
  $headers[] = 'Content-Type: application/json';
  $headers[] = "User-Agent: $user_agent";
  $headers[] = 'Origin: https://studio.youtube.com';
  $headers[] = "Cookie: $cookie";
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $result = curl_exec($ch);
  if(curl_errno($ch)) {
    echo 'Error:'.curl_error($ch);
  }
  curl_close($ch);

  $json = json_decode($result);
  if(isset($json->error->status)) {
    echo '[]';
  } else {
    foreach($json->videos as $key => $videos) {
      if(isset($videos->status) && $videos->status == 'VIDEO_STATUS_DELETED') {
        echo '[]';
      } else {
        if(empty($videos->videoStreamUrl)) {
          echo '[]';
        } else {
          $output = ['file' => cache($videos->downloadUrl, $video_id), 'type' => 'mp4'];
          $title = ', "title": "'.$videos->title.'"';
          $output = json_encode($output).$title;
          header('Content-Type: application/json');
          echo $output;
        }
      }
    }
  }
}

function cache($videoDownloadUrl, $video_id) {
  $cache_dir = 'cache';
  $cache_file = "$cache_dir/$video_id.tmp";
  $cache_time = 4.8 * 60 * 60; // 4.8 hours
  if(!file_exists($cache_dir)) {
    mkdir($cache_dir, 0755);
  }
  if(file_exists($cache_file)) {
    $last_modified = filemtime($cache_file);
    if(time() - $last_modified > $cache_time) {
      $videoStreamUrl = getVideoStreamUrl($videoDownloadUrl);
      file_put_contents($cache_file, $videoStreamUrl);
      $cache_videoStreamUrl = $videoStreamUrl;
    } else {
      $cache_videoStreamUrl = file_get_contents($cache_file);
    }
  } else {
    $videoStreamUrl = getVideoStreamUrl($videoDownloadUrl);
    file_put_contents($cache_file, $videoStreamUrl);
    $cache_videoStreamUrl = $videoStreamUrl;
  }
  return $cache_videoStreamUrl;
}

function getVideoStreamUrl($videoDownloadUrl) {
  global $user_agent, $cookie;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://www.youtube.com'.$videoDownloadUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
  curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
  $headers = array();
  $headers[] = "User-Agent: $user_agent";
  $headers[] = "Cookie: $cookie";
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_exec($ch);
  $result = curl_getinfo($ch);
  if(curl_errno($ch)) {
    echo 'Error:'.curl_error($ch);
  }
  curl_close($ch);
  return $result['redirect_url'];
}

if(empty($videoid)) {
  echo '[]';
} else {
  getVideoInfo($videoid);
}
?>
