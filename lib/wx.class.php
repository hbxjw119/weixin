<?php

class WeiXin 
{
    public function parseXml($xmlStr)
    {
            if (!$xmlStr) {
                    return '';
            }
            $xmlObj = simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $msg_type = $xmlObj->MsgType;
            $Msg = [];
            $Msg['from'] = $xmlObj->FromUserName; 
            $Msg['to'] = $xmlObj->ToUserName;
            $Msg['type'] = $msg_type;
            if ($msg_type == 'text') {
                    $Msg['content'] = trim($xmlObj->Content);
            }
            if ($msg_type == 'image') {
                    $Msg['imageId'] = $xmlObj->MediaId;
            }
            return $Msg;
    }

    public function send($content) 
    {
            $xmlObj = $this->parseXml($xmlStr);
            $fromUsername = $xmlObj->FromUserName;
            $toUsername = $xmlObj->ToUserName;
            if($xmlObj->Content) {
                return  $this->sendText($fromUsername, $toUsername,$content);
            }
            if($xmlObj->MsgType == 'image') {
                    return $this->sendImage($fromUsername,$toUsername,$content);
            }
    }

    public function sendText($from,$to,$content)
    {
            $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>0<FuncFlag>
                    </xml>";
            return sprintf($textTpl,$from,$to,time(),$content);
    }

    public function sendImage($from,$to,$mediaId) 
    {
            $imageTpl = "<xml>
                         <ToUserName><![CDATA[%s]]></ToUserName>
                         <FromUserName><![CDATA[%s]]></FromUserName>
                         <CreateTime>%s</CreateTime>
                         <MsgType><![CDATA[image]]></MsgType>
                         <Image>
                           <MediaId><![CDATA[%s]]></MediaId>
                         </Image>
                         </xml>";
            return sprintf($imageTpl,$from,$to,time(),$mediaId);
    }

    public function sendMusic($from,$to,$title,$desc,$url,$hqurl)
    {
            $musicTpl = "<xml>
                     <ToUserName><![CDATA[%s]]></ToUserName>
                     <FromUserName><![CDATA[%s]]></FromUserName>
                     <CreateTime>%s</CreateTime>
                     <MsgType><![CDATA[music]]></MsgType>
                     <Music>
                       <Title><![CDATA[%s]]></Title>
                       <Description><![CDATA[%s]]></Description>
                       <MusicUrl><![CDATA[%s]]></MusicUrl>
                       <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                       <ThumbMediaId><![CDATA[LoPN58TZfWPdussKu2BhLwiTmcESbv312tUKURvFjQBbdCm1i_w6u8zr6QXWTWbm]]></ThumbMediaId>
                      </Music>
                      </xml>";
        return sprintf($musicTpl,$from,$to,time(),$title,$desc,$url,$hqurl);
    }

    public function sendArticle($from,$to,$newsArray)
    {
            $articleTpl = "
                <xml>
                  <ToUserName><![CDATA[%s]]></ToUserName>
                  <FromUserName><![CDATA[%s]]></FromUserName>
                  <CreateTime>%s</CreateTime>
                  <MsgType><![CDATA[news]]></MsgType>
                  <ArticleCount>%s</ArticleCount>
                  <Articles>";
            $itemTpl = "
                  <item>
                  <Title><![CDATA[%s]]></Title> 
                  <Description><![CDATA[%s]]></Description>
                  <PicUrl><![CDATA[%s]]></PicUrl>
                  <Url><![CDATA[%s]]></Url>
                  </item>";
            $item_str = '';
            foreach($newsArray as $item) {
                $item_str .= sprintf($itemTpl,$item['title'],$item['desc'],$item['picurl'],$item['url']);
            }
            $articleTpl .= $item_str;
            $articleTpl .= "
                  </Articles>
                  </xml>";
        return sprintf($articleTpl,$from,$to,time(),count($newsArray));
    }
}
