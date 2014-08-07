<?hh // strict
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Http\Stream;

use Psr\Http\Message\StreamInterface;

/**
 * Defines shared functionality for an HTTP resource stream.
 *
 * @package Titon\Http\Stream
 */
class AbstractStream implements StreamInterface {

    /**
     * Cached meta data.
     *
     * @type Map<string, mixed>
     */
    protected Map<string, mixed> $_cache = Map {};

    /**
     * The stream resource.
     *
     * @type resource
     */
    protected ?resource $_stream;

    /**
     * Close the resource on destruction.
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): ?string {
        return $this->getContents();
    }

    /**
     * Build a meta cache for the current stream.
     *
     * @return $this
     */
    public function buildCache(): this {
        $cache = stream_get_meta_data($this->getStream());
        $cache['local'] = stream_is_local($this->getStream());

        $mode = str_replace('b', '', $cache['mode']);

        $cache['readable'] = !in_array($mode, ['w', 'a', 'x', 'c']);
        $cache['writable'] = ($mode !== 'r');

        $this->_cache = new Map($cache);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool {
        if (is_resource($this->getStream()) && fclose($this->getStream())) {
            $this->_cache['readable'] = false;
            $this->_cache['writable'] = false;

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function detach(): bool {
        $this->_stream = null;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool {
        return feof($this->getStream());
    }

    /**
     * Return the cached meta data.
     *
     * @return Map<string, mixed>
     */
    public function getCache(): Map<string, mixed> {
        return $this->_cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents($maxLength = -1): ?string {
        if (!$this->isReadable() || (!$this->isSeekable() && $this->eof())) {
            return '';
        }

        // Save cursor position before reading
        $tell = $this->tell();

        $buffer = stream_get_contents($this->getStream(), $maxLength, 0);

        // Reset cursor position
        $this->seek($tell);

        return ($buffer === false) ? null : $buffer;
    }

    /**
     * Return the read/write mode used on the stream.
     *
     * @return string
     */
    public function getMode(): string {
        return $this->_cache['mode'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): int {
        if ($this->isLocal()) {
            clearstatcache(true, $this->_cache['uri']);
        }

        if ($this->_cache['wrapper_type'] !== 'PHP') {
            $stat = fstat($this->getStream());

            if (isset($stat['size'])) {
                return $stat['size'];
            }
        }

        return mb_strlen($this->getContents());
    }

    /**
     * Return the raw resource stream.
     *
     * @return resource
     */
    public function getStream(): ?resource {
        return $this->_stream;
    }

    /**
     * Alias for eof().
     *
     * @return bool
     */
    public function isConsumed(): bool {
        return $this->eof();
    }

    /**
     * Return true if the stream is a local file.
     *
     * @return bool
     */
    public function isLocal(): bool {
        return $this->_cache['local'];
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool {
        return $this->_cache['readable'];
    }

    /**
     * Return true if the stream can be re-read once the EOF has been reached.
     *
     * @return bool
     */
    public function isRepeatable(): bool {
        return $this->isReadable() && $this->isSeekable();
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool {
        return $this->_cache['seekable'];
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool {
        return $this->_cache['writable'];
    }

    /**
     * {@inheritdoc}
     */
    public function read($length): ?string {
        if (!$this->isReadable()) {
            return null;
        }

        $buffer = fread($this->getStream(), $length);

        return ($buffer === false) ? null : $buffer;
    }

    /**
     * Rewind the pointer to the beginning.
     *
     * @return bool
     */
    public function rewind() {
        return $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET): bool {
        return $this->isSeekable() ? (fseek($this->getStream(), $offset, $whence) === 0) : false;
    }

    /**
     * Set a resource as a stream and generate a new meta data cache.
     *
     * @param resource $stream
     * @return $this
     */
    public function setStream(resource $stream): this {
        $this->_stream = $stream;
        $this->buildCache();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): ?int {
        $tell = ftell($this->getStream());

        return ($tell === false) ? null : $tell;
    }

    /**
     * {@inheritdoc}
     */
    public function write($string): ?int {
        if (!$this->isWritable()) {
            return null;
        }

        $write = fwrite($this->getStream(), $string);

        return ($write === false) ? null : $write;
    }

}