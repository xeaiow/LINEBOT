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

	$data = json_decode($body, true);

	foreach ( $data['events'] as $event ) {

		switch ( $event['type'] ) {

			case 'message':

				switch ( $event['message']['type'] ) {

					// 如果訊息是文字
					case 'text':

						if (strpos($event['message']['text'], "愛你") !== false ) {

							$result = $bot->replyText( $event['replyToken'], "我也是唷～" );
						}
						else{

							$result = $bot->replyText( $event['replyToken'], $event['message']['text'] );
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
