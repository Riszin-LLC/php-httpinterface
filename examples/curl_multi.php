<?php
/**
 * cURL multi example, fetch data from the GUildWars2 items API
 * @link         https://wiki.guildwars2.com/wiki/API:2/items
 *
 * @filesource   curl_multi.php
 * @created      08.11.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

use chillerlan\HTTP\CurlUtils\{CurlMultiClient, MultiResponseHandlerInterface};
use chillerlan\HTTP\HTTPOptions;
use chillerlan\HTTP\Psr18\CurlClient;
use chillerlan\HTTP\Psr7\Request;
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use function chillerlan\HTTP\Psr7\{build_http_query, get_json};

require_once __DIR__.'/../vendor/autoload.php';


// invoke the http clients
$options = new HTTPOptions([
	'ca_info'    => __DIR__.'/cacert.pem',
	'sleep'      => 60 / 300 * 1000000, // GW2 API limit: 300 requests/minute
#	'user_agent' => 'my fancy http client',
]);

$client      = new CurlClient($options);
$multiClient = new CurlMultiClient($options);


// request the list of item ids
$endpoint     = 'https://api.guildwars2.com/v2/items';
$itemResponse = $client->sendRequest(new Request('GET', $endpoint));

if($itemResponse->getStatusCode() !== 200){
	exit('/v2/items fetch error');
}


// chunk the response into arrays of 200 ids each (API limit) and create Request objects for each desired language
$languages = ['de', 'en', 'es'];//, 'fr', 'zh'
$requests  = [];

foreach(array_chunk(get_json($itemResponse), 200) as $chunk){
	foreach($languages as $lang){
		$requests[] = new Request('GET', $endpoint.'?'.build_http_query(['lang' => $lang, 'ids' => implode(',', $chunk)]));
	}
}


// create directories for each language to dump the item responses into
foreach($languages as $lang){
	$dir = __DIR__.'/'.$lang;

	if(!file_exists($dir)){
		mkdir($dir);
	}
}


// the multi request handler
$handler = new class() implements MultiResponseHandlerInterface{

	public function handleResponse(ResponseInterface $response, RequestInterface $request, int $id, array $curl_info):?RequestInterface{

		// the API returns either 200 or 206 on OK responses
		// https://gitter.im/arenanet/api-cdi?at=5738e2c0ae26c1967f9eb4a0
		if(in_array($response->getStatusCode(), [200, 206], true)){
			$lang = $response->getHeaderLine('content-language');

			// create a file for each item in the response (ofc you'd rather put this in a DB)
			foreach(get_json($response) as $item){
				$file = $lang.'/'.$item->id;
				file_put_contents(__DIR__.'/'.$file.'.json', json_encode($item, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

				echo $file.PHP_EOL;
			}

			// response ok, nothing to return
			return null;
		}

		// return the failed request back to the stack
		return $request;
	}

};


// run the whole thing
$multiClient
	->setMultiResponseHandler($handler)
	->addRequests($requests)
	->process()
;

