<?php
/**
 * Class StreamFactory
 *
 * @filesource   StreamFactory.php
 * @created      27.08.2018
 * @package      chillerlan\HTTP\Psr17
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7\Stream;
use InvalidArgumentException;
use Psr\Http\Message\{StreamFactoryInterface, StreamInterface};

final class StreamFactory implements StreamFactoryInterface{

	/**
	 * Create a new stream from a string.
	 *
	 * The stream SHOULD be created with a temporary resource.
	 *
	 * @param string $content String content with which to populate the stream.
	 *
	 * @return StreamInterface
	 */
	public function createStream(string $content = ''):StreamInterface{
		$stream = fopen('php://temp', 'r+');

		if($content !== ''){
			fwrite($stream, $content);
			fseek($stream, 0);
		}

		return new Stream($stream);
	}

	/**
	 * Create a stream from an existing file.
	 *
	 * The file MUST be opened using the given mode, which may be any mode
	 * supported by the `fopen` function.
	 *
	 * The `$filename` MAY be any string supported by `fopen()`.
	 *
	 * @param string $filename Filename or stream URI to use as basis of stream.
	 * @param string $mode     Mode with which to open the underlying filename/stream.
	 *
	 * @return StreamInterface
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'):StreamInterface{
		return new Stream(fopen($filename, $mode));
	}

	/**
	 * Create a new stream from an existing resource.
	 *
	 * The stream MUST be readable and may be writable.
	 *
	 * @param resource $resource PHP resource to use as basis of stream.
	 *
	 * @return StreamInterface
	 */
	public function createStreamFromResource($resource):StreamInterface{
		return new Stream($resource);
	}

	/**
	 * @param mixed $in
	 *
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public function createStreamFromInputGuess($in = null):StreamInterface{
		$in = $in ?? '';

		if(is_string($in) && is_file($in) && is_readable($in)){
			return new Stream(fopen($in, 'r'));
		}

		if(is_scalar($in)){
			return $this->createStream((string)$in);
		}

		$type = gettype($in);

		if($type === 'resource'){
			return new Stream($in);
		}
		elseif($type === 'object'){

			if($in instanceof StreamInterface){
				return $in;
			}
			elseif(method_exists($in, '__toString')){
				return $this->createStream((string)$in);
			}

		}

		throw new InvalidArgumentException('Invalid resource type: '.$type);
	}

}
