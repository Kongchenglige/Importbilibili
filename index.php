<?php

//定义

define('APPID','');

define('APPSECRET','');

$callback_url = 'https://api.iasoc.club/BangumiTransfer/';

//开始

session_start();

//取得Bilibili uid,以get方式传入

$_SESSION['bilibili_uid'] = $_GET['uid'];

//如果从bangumi返回了code

if(isset($_GET['code'])){

    $fields = [

        'grant_type' => 'authorization_code',

        'client_id' => APPID,

        'client_secret' => APPSECRET,

        'code' => $_GET['code'],

        'redirect_uri' => $callback_url

    ];

    $ch = curl_init();

    curl_setopt($ch,CURLOPT_URL, 'https://bgm.tv/oauth/access_token');

    curl_setopt($ch,CURLOPT_POST, true);

    curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($fields, null, '&'));

    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    //POST并置换accesstoken

    $result = json_decode($result,true);

    curl_close($ch);

    //储存

    $_SESSION['access_token'] = $result['access_token'];

    $_SESSION['bangumi_user_id'] = $result['user_id'];

}

//若未设置accesstoken

if(!isset($_SESSION['access_token'])){

    //跳转到oauth

    $_SESSION['rand'] = mt_rand();

    header('Location: https://bgm.tv/oauth/authorize?client_id='.APPID.'&response_type=code&redirect_uri='.$callback_url.'&state='.$_SESSION['rand']);

}else{

    //存在accesstoken

    if(!isset($_SESSION['bilibili_uid'])){

        //bilibili uid未设定

        exit('bilibili uid unset');

    }else{

        //设定UID了



        //主逻辑开始

        echo '正在使用bilibiliapi,请稍后'.PHP_EOL;

        flush();

        //清理变量

        unset($ch,$result);

        //抓取追番列表

        $totalbangumi = json_decode(file_get_contents('https://api.bilibili.com/x/space/bangumi/follow/list?type=1&follow_status=0&pn=1&ps=1&vmid='.$_SESSION['bilibili_uid'].'&ts=0'),true);

        //取得总数

        $totalbangumi = $totalbangumi['data']['total'];

        echo '共计 '.$totalbangumi.' 部番剧'.PHP_EOL;

        flush();

        //计算次数

        $needcount = ceil($totalbangumi / 50);

        //开始循环取列表

        for ($x = 1; $x <= $needcount; $x++) {

            $catch_url = 'https://api.bilibili.com/x/space/bangumi/follow/list?type=1&follow_status=0&pn='.$x.'&ps=50&vmid='.$_SESSION['bilibili_uid'].'&ts=0';

            $bangumi_result = json_decode(file_get_contents($catch_url),true);



            //取值

            for ($i = 0; $i <= 50; $x++) {

                $bangumi_result = $bangumi_result['data']['list'][$i];

                $bangumi_name = $bangumi_result['data']['list'][$i]['title'];

                echo '正在添加: '.$bangumi_name;

                //收视状态

                if (!empty($bangumi_result['data']['list'][$i]['progress'])) {

                    $bangumi_status = 'watched';

                } else {

                    $bangumi_status = 'queue';

                }

                echo ' 状态:'.$bangumi_status;

                //自bangumi检索

                $bangumi_search = json_decode(file_get_contents('http://api.bgm.tv/search/subject/' . $bangumi_name . '?type=2&max_results=10&responseGroup=small'), true);

                //遍历搜索结果

                foreach ($bangumi_search['list'] as $p) {

                    //如果相同

                    if ($p['name_cn'] == $bangumi_name) {

                        echo ' 检索成功';

                        //POST更新

                        $fields = [

                            'status' => $bangumi_status

                        ];

                        $ch = curl_init();

                        curl_setopt($ch, CURLOPT_URL, 'http://api.bgm.tv/collection/'.$p['id'].'/update?access_token='.$_SESSION['access_token']);

                        curl_setopt($ch, CURLOPT_POST, true);

                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields, null, '&'));

                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                        $result = curl_exec($ch);

                        //POST并置换accesstoken

                        $result = json_decode($result);

                        if(!empty($result['user'])){

                            echo ' 添加成功';

                        }else{

                            echo ' 添加失败';

                        }

                        curl_close($ch);

                    }else{

                        echo ' 检索失败,请手动添加';

                    }

                }

                echo PHP_EOL;

                flush();

                unset($p);

            }

        }

        unset($x);

    }

}
