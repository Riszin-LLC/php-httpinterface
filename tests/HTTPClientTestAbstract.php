<?php
/**
 * Class HTTPClientTestAbstract
 *
 * @filesource   HTTPClientTestAbstract.php
 * @created      21.10.2017
 * @package      chillerlan\HTTPTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\{HTTPClientInterface, HTTPOptionsTrait};
use chillerlan\Traits\{ImmutableSettingsContainer, ImmutableSettingsInterface};
use PHPUnit\Framework\TestCase;

abstract class HTTPClientTestAbstract extends TestCase{

	const CACERT     = __DIR__.'/cacert.pem';
	const USER_AGENT = 'chillerLAN-php-oauth-test';

	/**
	 * @var \chillerlan\HTTP\HTTPClientInterface
	 */
	protected $http;

	/**
	 * @var \chillerlan\Traits\ImmutableSettingsInterface
	 */
	protected $options;

	protected function setUp(){
		$this->options = $this->getOptions([
			'ca_info'    => self::CACERT,
			'user_agent' => self::USER_AGENT,
		]);
	}

	protected function getOptions(array $arr = null):ImmutableSettingsInterface{
		return new class($arr ?? []) implements ImmutableSettingsInterface{
			use ImmutableSettingsContainer, HTTPOptionsTrait;
		};
	}

	public function testInstance(){
		$this->assertInstanceOf(HTTPClientInterface::class, $this->http);
	}

	public function headerDataProvider():array {
		return [
			[['content-Type' => 'application/x-www-form-urlencoded'], ['Content-type' => 'Content-type: application/x-www-form-urlencoded']],
			[['lowercasekey' => 'lowercasevalue'], ['Lowercasekey' => 'Lowercasekey: lowercasevalue']],
			[['UPPERCASEKEY' => 'UPPERCASEVALUE'], ['Uppercasekey' => 'Uppercasekey: UPPERCASEVALUE']],
			[['mIxEdCaSeKey' => 'MiXeDcAsEvAlUe'], ['Mixedcasekey' => 'Mixedcasekey: MiXeDcAsEvAlUe']],
			[['31i71casekey' => '31i71casevalue'], ['31i71casekey' => '31i71casekey: 31i71casevalue']],
			[[1 => 'numericvalue:1'], ['Numericvalue'  => 'Numericvalue: 1']],
			[[2 => 2], []],
			[['what'], []],
		];
	}

	/**
	 * @dataProvider headerDataProvider
	 *
	 * @param $header
	 * @param $normalized
	 */
	public function testNormalizeHeaders(array $header, array $normalized){
		$this->assertSame($normalized, $this->http->normalizeRequestHeaders($header));
	}

	public function requestDataProvider():array {
		return [
			['get',    []],
			['post',   []],
			['put',    []],
			['patch',  []],
			['delete', []],
		];
	}

	/**
	 * @dataProvider requestDataProvider
	 *
	 * @param $method
	 * @param $extra_headers
	 */
	public function testRequest(string $method, array $extra_headers){

		// @todo httpbin times out on a regular basis... a more reliable service, anyone?
		$r = null;

		try{
			$response = $this->http->request(
				'https://httpbin.org/'.$method,
				['foo' => 'bar'],
				$method,
				['huh' => 'wtf'],
				['what' => 'nope'] + $extra_headers
			);

			$r = $response->json;
		}
		catch(\Exception $e){
			$this->markTestSkipped('httpbin.org timeout... '.$e->getMessage());
		}

		if(!$r){
			$this->markTestSkipped('empty response');
		}
		else{
			$this->assertSame('https://httpbin.org/'.$method.'?foo=bar', $r->url);
			$this->assertSame('bar', $r->args->foo);
			$this->assertSame('nope', $r->headers->What);
			$this->assertSame(self::USER_AGENT, $r->headers->{'User-Agent'});
			if(in_array($method, ['patch', 'post', 'put'])){
				$this->assertSame('wtf', $r->form->huh);
			}
		}

	}

	/**
	 * @expectedException \chillerlan\HTTP\HTTPClientException
	 * @expectedExceptionMessage invalid URL
	 */
	public function testInvalidURLException(){
		$this->http->request('');
	}


	public function testCheckParams(){
		$data = ['foo' => 'bar', 'whatever' => null, 'nope' => '', 'true' => true, 'false' => false];

		$this->assertSame(['foo' => 'bar', 'true' => '1', 'false' => '0'], $this->http->checkQueryParams($data));
		$this->assertSame(['foo' => 'bar', 'true' => 'true', 'false' => 'false'], $this->http->checkQueryParams($data, true));
	}


	public function rawurlencodeDataProvider(){
		return [
			['some test string!', 'some%20test%20string%21'],
			[['some other', 'test string', ['oh wait!', 'this', ['is an', 'array!']]], ['some%20other', 'test%20string', ['oh%20wait%21', 'this', ['is%20an', 'array%21']]]],
		];
	}

	/**
	 * @dataProvider rawurlencodeDataProvider
	 */
	public function testRawurlencode($data, $expected){
		$this->assertSame($expected, $this->http->rawurlencode($data));
	}

	public function testBuildHttpQuery(){

		$data = ['foo' => 'bar', 'whatever?' => 'nope!'];

		$this->assertSame('', $this->http->buildQuery([]));
		$this->assertSame('foo=bar&whatever%3F=nope%21', $this->http->buildQuery($data));
		$this->assertSame('foo=bar&whatever?=nope!', $this->http->buildQuery($data, false));
		$this->assertSame('foo=bar, whatever?=nope!', $this->http->buildQuery($data, false, ', '));
		$this->assertSame('foo="bar", whatever?="nope!"', $this->http->buildQuery($data, false, ', ', '"'));

		$data['florps']  = ['nope', 'nope', 'nah'];
		$this->assertSame('florps="nah", florps="nope", florps="nope", foo="bar", whatever?="nope!"', $this->http->buildQuery($data, false, ', ', '"'));
	}

}
