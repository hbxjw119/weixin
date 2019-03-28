<?php
include __DIR__ .'/../conf/config.php';
include __DIR__ .'/../lib/wx.class.php';
include __DIR__ .'/../lib/mysql.php';

//checkSignature();

$obj = new WeChat();
$obj->responseMsg();

class WeChat
{
	private $db_con = null;
	private $category = null;
	private $cate = null;
	
	public function __construct()
	{
		global $dns;
		global $account_category;
		$this->db_con = new Mysql($dns['host'],$dns['user'],$dns['password'],$dns['db']);
		$this->category = $account_category;
		$this->cate = ['1' => '餐饮', '2' => '购物消费', '3' => '交通', '4' => '居家生活', '5' => '其他'];
	}

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
				$arr = preg_split('/([0-9\.]+)/', $content, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
				if (count($arr) == 2) {
					$info = $arr[0];
					$pay = $arr[1];	
					if (is_numeric($pay)) {
						$user_id = $fromUsername;
						$cate_type = $this->_getCategory($content);
						$cate = $this->cate[$cate_type];
						
						if($this->db_con->insert('user_bill', ['user_id' => $user_id, 'info' => $info, 'category' => $cate_type, 'pay' => $pay])) {
								$ret = '记账成功通知';
								echo $wx->sendArticle($fromUsername, $toUsername, $this->handleArticle($user_id, $ret, $info, $cate, $pay));
						} else {
							$ret = '记账失败';
							echo $wx->sendText($fromUsername, $toUsername, $ret);
						}
					} else {
						$ret = '记录失败';
						echo $wx->sendText($fromUsername, $toUsername, $ret);
					}
					
				}
				else if ($content == '新闻') {
                     $article_arr = $this->handleArticle();
                     $send = $wx->sendArticle($fromUsername,$toUsername,$article_arr);
                     echo $send;
				} 
				else if(!strstr($content,'歌曲')) {
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
        include __DIR__ . '/../lib/Pinyin.php';
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
            include __DIR__ . '/../lib/Meting.php';
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

    public function handleArticle($user_id, $title, $info, $category, $pay)
    {
        $news_arr = [];
		$desc = "金额：" . $pay . "\n".
			"备注：" . $info . "\n".
			"类目：" . $category . "\n".
			"时间：" . date("Y-m-d H:i:s") . "\n" ;
		$desc .= "\n点击查看详情";

        $news = [
                'title' => $title,
                'desc' => $desc,
                'url' => BILL_URI . '/account/bill.html?uid=' . $user_id
        ];
        $news_arr[] = $news;

        return $news_arr;
    }

	public function  _getCategory($bill_info)
	{
		$ret = '5';
		foreach($this->category as $idx => $keywords) {
			foreach($keywords as $k => $v) {
				if (strstr($bill_info, $v)) {
					$ret = $idx;
					break;
				}
			}
		}
		return $ret;
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


