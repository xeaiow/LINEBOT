<?php

require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// load config
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// initiate app
$configs =  [
	'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

/* ROUTES */
$app->get('/', function ($request, $response) {
	return "run!";
});

$app->post('/', function ($request, $response)
{
	// get request body and line signature header
	$body 	   = file_get_contents('php://input');
	$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];

	// log body and signature
	file_put_contents('php://stderr', 'Body: '.$body);

	// is LINE_SIGNATURE exists in request header?
	if (empty($signature)){
		return $response->withStatus(400, '沒設定好 Channel');
	}

	// is this request comes from LINE?
	if ( $_ENV['PASS_SIGNATURE'] == false && ! SignatureValidator::validateSignature($body, $_ENV['CHANNEL_SECRET'], $signature) ){
		return $response->withStatus(400, '無效的 Channel');
	}

	// init bot
	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

	// match array string
	function strpos_array($haystack, $needle) {
		if ( is_array($needle) ) {
			foreach ( $needle as $need ) {
				if ( strpos( $haystack, $need ) !== false ) {
					return true;
				}
			}
		}else {
			if (strpos($haystack, $need) !== false) {
				return true;
			}
		}

		return false;
	}

	// db connect
	$db = new PDO( 'mysql:host=localhost;dbname=xeeecopl_linebot;charset=utf8', 'xeeecopl_xeaiow', 'a9b9c8d1' );

	$data = json_decode($body, true);

	foreach ( $data['events'] as $event ) {

		switch ( $event['type'] ) {

			case 'message':

				switch ( $event['message']['type'] ) {

					// 如果訊息是文字
					case 'text':

						$ubike = array("ubike", "單車", "youbike", "微笑單車", "腳踏車", "bicycle");
						$area  = array("中壢", "桃園", "平鎮", "龍潭", "楊梅", "新屋", "觀音", "龜山", "八德", "大溪", "大園", "蘆竹", "復興");
						$request_text = $event['message']['text']; // Client 輸入的字串

						if ( strpos_array($request_text, $ubike) ) {

							$result = $bot->replyText( $event['replyToken'], "找Ubike嗎？ ex. 中壢區");
						}
						else if ( strpos_array($request_text, $area) ) {

							$ubike_data = json_decode(file_get_contents("http://data.tycg.gov.tw/api/v1/rest/datastore/a1b4714b-3b75-4ff8-a8f2-cc377e4eaa0f?format=json"), true);

								foreach ($ubike_data['result']['records'] as $data) {

									if ($request_text == $data['sarea']) {

										$str .= $data['sna']."\t".", 剩 ".$data['sbi']."台"."\n\n";
									}

							    }
								$result = $bot->replyText( $event['replyToken'], $str);

						}
						else {

							$result = $bot->replyText( $event['replyToken'], "我不懂意思耶。");
							// $query 		  = $db->prepare("SELECT address FROM shop WHERE name = ?");
							// $query->execute(array($request_text));
							// $re 	  	  = $query->fetch(PDO::FETCH_OBJ);
							//
							// $result = $bot->replyText( $event['replyToken'], $re->address );
						}
						break;

					// 如果訊息是地理位置
					case 'location':

						$result = $bot->replyText( $event['replyToken'], "傳地址給我幹嘛啦><" );
						break;

					default:

						break;
				}
				break;

			default:

				break;
		}

		return $result->getHTTPStatus().' '.$result->getRawBody();
	}
});

$app->run();
