<?php
include __DIR__ .'/conf/config.php';
include __DIR__ .'/lib/wx.class.php';
//checkSignature();

$obj = new WeChat();
$obj->responseMsg();

class WeChat
{
    public function responseMsg()
    {
        $postStr = file_get_contents("php://input");
        if (!empty($postStr)){
            $wx = new WeiXin();
            $recevie = $wx->parseXml($postStr);

            $fromUsername = $recevie['from'];
            $toUsername = $recevie['to'];
            $type = $recevie['type'];
            if ($type == 'text') {
                $content = $recevie['content'];
                if ($content == '新闻') {
                     $article_arr = $this->handleArticle();
                     $send = $wx->sendArticle($fromUsername,$toUsername,$article_arr);
                     echo $send;
                } else if(!strstr($content,'歌曲')) {
                    $weather = $this->handleWeather($content);
                    $send = $wx->sendText($fromUsername,$toUsername,$weather);
                    echo $send;
                } else {
                     $music_name = explode(" ",$content)[1];
                     $music = $this->musicSearch($music_name);
                     $title = $music['title'];
                     $url = $hqurl = $music['url'];
                     $desc = $music_name;
                     $send = $wx->sendMusic($fromUsername,$toUsername,$title,$desc,$url,$hqurl);
                     echo $send;
                }
            } elseif ($type == 'image') {
                    $imageId = $recevie['imageId'];
                   $send = $wx->sendImage($fromUsername,$toUsername,$imageId); 
                    echo $send;
            }
        } else {
            echo '';
            exit;
        }
    }

    public function translateCity($city) 
    {
        include __DIR__ . '/lib/Pinyin.php';
        $pinyin = new Pinyin();
        return $pinyin->transformWithoutTone($city,'');
    }
    
    public function handleWeather($city) 
    {
        $cityPinyin = $this->translateCity($city);
        if(!$cityPinyin) {
            return '无法获得您需要的天气，请输入正确的城市名';
        }

	    $weatherApi = 'http://api.seniverse.com/v3/weather/daily.json?key='.WKEY.'&location=' . $cityPinyin . '&language=zh-Hans&unit=c';
	    $weather = $this->_getapi($weatherApi);
	    $w_json = json_decode($weather,true);
        if($w_json['results']) {
	        $w = $w_json['results'][0]['daily'];
	        $last_3_day = $city . "最近3天天气\n";
	        foreach($w as $d) {
                $_date = $d['date'];
                $_text_day = $d['text_day'];
                $_text_night = $d['text_night'];
                $low = $d['low'];
                $high = $d['high'];
                $last_3_day .= ($_date ."\n". "白天：" . $_text_day . "\n"."夜晚：".$_text_night. "\n"."温度：".$low."~".$high."\n");
            }
		        return $last_3_day;                                                                      
	    } else {
		    return '无法获得您需要的天气，请输入正确的城市名';
	    }
    }
    
    public function _getapi($url) 
    {
	    return file_get_contents($url);
    }

    public function handleMusic($music)
    {
        $search_result = $this->musicSearch($music);
        $title = $music['title'];
        $url = $music['url'];
    }

    public function musicSearch($music)
    {
            $music_arr = [];
            include __DIR__ . '/lib/Meting.php';
            $api = new Meting('netease');
            $api = $api->format(true);
            $data = $api->search($music);
            $json_data = json_decode($data,true);
            if($json_data) {
                    $m = $json_data[0];
            }
            $music_id = $m['id'];
            $music_pic_id = $m['pic_id'];
            $url = json_decode($api->url($music_id),true);
            $pic = json_decode($api->pic($m['pic_id']),true);
            if($url) {
                $url = $url['url'];
            }
            if($pic) {
                $pic = $pic['url'];
            }
            $music_arr['title'] = $m['name'];
            $music_arr['url'] = $url;
            $music_arr['pic'] = $pic;
            return $music_arr;
    }

    public function handleArticle()
    {
        $news_arr = [];

        $news = [
                'title' => '全面屏iPhone X简直有毒！乔布斯的苹果终于回来了',
                'desc' => 'iPhone X在发布前就已经被曝到体无完肤，万万没想到，发布会还真的是连打脸的机会都不给我！亏果粉们都熬夜看了一场重播',
                'picurl' => 'https://mmbiz.qpic.cn/mmbiz_jpg/PicLGiaOegHVd9Ce8yFuGQkkIjBP1xMY772oMkG9LLFfQicYOrgPuPoEvnM7tNSRCWJXlsJ45eYoibByPX9xKm2wTw/640?wx_fmt=jpeg&tp=webp&wxfrom=5&wx_lazy=1',
                'url' => 'https://mp.weixin.qq.com/s?__biz=MjM5NzAwNzMyMA==&mid=2659800881&idx=1&sn=210be5516bed1a183aba6e7abc0c7eeb&chksm=bd9d6d868aeae490248d7c7ad80c2245bae28e7a3473526cc17a9c53d3cd61c733b0ac58d470#rd'
        ];
        $news_arr[] = $news;

        return $news_arr;
    }
}

/*
function checkSignature() {
        $signature = $_GET['signature'];
        $nonce = $_GET['nonce'];
        $timestamp = $_GET['timestamp'];

        $arr = [$timestamp, $nonce, TOKEN];

        sort($arr);

        $arr = implode($arr);

        $m_arr = sha1($arr);
        if($m_arr == $signature) {
                return true;
        } 
        return false;
}

if (checkSignature()) {
        $echostr = $_GET['echostr'];
        if($echostr) {
                echo $echostr;
        }
}
 */


