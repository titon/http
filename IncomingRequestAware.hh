<?hh // partial
// Because of PSR HTTP Message
/**
 * @copyright   2010-2015, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Http;

use Psr\Http\Message\IncomingRequestInterface;

/**
 * Permits a class to interact with an incoming request object.
 *
 * @package Titon\Http
 */
trait IncomingRequestAware {

    /**
     * Request object.
     *
     * @var \Psr\Http\Message\IncomingRequestInterface
     */
    protected ?IncomingRequestInterface $request;

    /**
     * Return the request object.
     *
     * @return \Psr\Http\Message\IncomingRequestInterface
     */
    public function getRequest(): ?IncomingRequestInterface {
        return $this->request;
    }

    /**
     * Set the request object.
     *
     * @param \Psr\Http\Message\IncomingRequestInterface $request
     * @return $this
     */
    public function setRequest(IncomingRequestInterface $request): this {
        $this->request = $request;

        return $this;
    }

}
