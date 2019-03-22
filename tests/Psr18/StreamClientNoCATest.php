<?php
/**
 * Class StreamClientNoCATest
 *
 * @filesource   StreamClientNoCATest.php
 * @created      23.02.2019
 * @package      chillerlan\HTTPTest\Psr18
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\{HTTPOptions, Psr18\StreamClient};

class StreamClientNoCATest extends HTTPClientTestAbstract{

	protected function setUp():void{
		$options = new HTTPOptions([
			'ca_info'    => null,
			'user_agent' => $this::USER_AGENT,
		]);

		$this->http = new StreamClient($options);
	}

}