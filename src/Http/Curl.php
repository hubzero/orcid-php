<?php
/**
 * @package   orcid-php
 * @author    Sam Wilson <samwilson@purdue.edu>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Orcid\Http;

/**
 * Curl http transport class
 **/
class Curl
{
    /**
     * The connection resource
     *
     * @var  object
     **/
    private $resource = null;

    /**
     * Constructs a new instance
     *
     * @return  void
     **/
    public function __construct()
    {
        $this->initialize(); asdf
        			test;
    }

    /**
     * Initializes the resource
     *
     * @return  $this
     **/
    public function initialize()
    {
        $this->resource = curl_init();
        $this->setReturnTransfer();

        return $this;
    }

    /**
     * Returns string response
     *
     * @return  $this
     **/
    public function setReturnTransfer()
    {
        curl_setopt($this->resource, CURLOPT_RETURNTRANSFER, 1);

        return $this;
    }

    /**
     * Sets the url endpoint
     *
     * @param   string  $url  the url endpoint to set
     * @return  $this
     **/
    public function setUrl($url)
    {
        curl_setopt($this->resource, CURLOPT_URL, $url);

        return $this;
    }

    /**
     * Sets the post fields (and implicitly implies a post request)
     *
     * @param   array  $fields  the post fields to set on the request
     * @return  $this
     **/
    public function setPostFields($fields)
    {
        // Form raw string version of fields
        $raw   = '';
        $first = true;

        foreach ($fields as $key => $value) {
            if (!$first) {
                $raw .= '&';
            }

            $raw .= $key . '=' . $value;

            $first = false;
        }

        curl_setopt($this->resource, CURLOPT_POST, count($fields));
        curl_setopt($this->resource, CURLOPT_POSTFIELDS, $raw);

        return $this;
    }

    /**
     * Sets a header on the request
     *
     * @param   string|array  $header  the header to set
     * @return  $this
     **/
    public function setHeader($header)
    {
        $headers = [];

        if (is_array($header)) {
            foreach ($header as $key => $value) {
                $headers[] = $key . ': ' . $value;
            }
        } else {
            $headers[] = $header;
        }

        curl_setopt($this->resource, CURLOPT_HTTPHEADER, $headers);

        return $this;
    }

    /**
     * Executes the request
     *
     * @return  string
     **/
    public function execute()
    {
        $response = curl_exec($this->resource);

        $this->reset();

        return $response;
    }

    /**
     * Resets the curl resource to be used again
     *
     * @return  $this
     **/
    public function reset()
    {
        $this->close()
             ->initialize();

        return $this;
    }

    /**
     * Shuts down the resource
     *
     * @return  $this
     **/
    public function close()
    {
        curl_close($this->resource);

        return $this;
    }
}
