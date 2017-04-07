<?php
/**
 * @package   orcid-php
 * @author    Sam Wilson <samwilson@purdue.edu>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Orcid;

use Orcid\Http\Curl as Curl;

/**
 * ORCID profile API class
 **/
class Profile
{
    /**
     * The oauth object
     *
     * @var  object
     **/
    private $oauth = null;

    /**
     * The raw orcid profile
     *
     * @var  object
     **/
    private $raw = null;

    /**
     * Constructs object instance
     *
     * @param   object  $oauth  the oauth object used for making calls to orcid
     * @return  void
     **/
    public function __construct($oauth = null)
    {
        $this->oauth = $oauth;
    }

    /**
     * Grabs the ORCID iD
     *
     * @return  string
     **/
    public function id()
    {
        return $this->oauth->getOrcid();
    }

    /**
     * Grabs the orcid profile (oauth client must have requested this level or access)
     *
     * @return  object
     **/
    public function raw()
    {
        if (!isset($this->raw)) {
            $this->raw = $this->oauth->getProfile()->{'record'};
        }

        return $this->raw;
    }

    /**
     * Grabs the ORCID bio
     *
     * @return  object
     **/
    public function person()
    {
        $this->raw();

        return $this->raw->{'person'};
    }

    /**
     * Grabs the users email if it's set and available
     *
     * @return  string|null
     **/
    public function email()
    {
        $this->raw();

        $email = null;
        $bio   = $this->person();

        if (isset($person->{'emails'})) {
            if (isset($person->{'emails'}->email)) {
                if (is_array($person->{'emails'}->email) && isset($person->{'emails'}->email[0])) {
                    $email = $person->{'emails'}->email[0]->email;
                }
            }
        }

        return $email;
    }

    /**
     * Grabs the raw name elements to create fullname
     *
     * @return  string
     **/
    public function fullName()
    {
        $this->raw();
        $details = $this->person()->{'name'};

        return $details->{'given-names'}->value . ' ' . $details->{'family-name'}->value;
    }

    /**
     * Saves the orcid-message xml provided to the correct scope
     *
     * @return  mixed - either false on failure or result content if successful
     **/
    public function save($scope, $xml)
    {
        $endpoint = $this->oauth->getApiEndpoint($scope, self::API_VERSION, $this->id());
        $headers = [
                'Content-Type'  => 'application/vnd.orcid+xml',
                'Authorization' => 'Bearer ' . $this->id()->getAccessToken()
            ];

        $orcid_msg = stripslashes($xml);

        /* We need to set up a tmp file in order
         * to do the HTTP PUT request properly
         */
        $tmp_file = tmpfile();
        fwrite($tmp_file,$orcid_msg);
        fseek($tmp_file,0);

        $c = new Curl;

        $c->setUrl($endpoint);
        $c->setOpt(CURLOPT_PUT, true);
        $c->setOpt(CURLOPT_BINARYTRANSFER, true);
        $c->setOpt(CURLOPT_RETURNTRANSFER, true);
        $c->setOpt(CURLOPT_INFILE, $tmp_file);
        $c->setOpt(CURLOPT_INFILESIZE, strlen($orcid_msg));
        $c->setOpt(CURLOPT_VERBOSE, true);
        $c->setHeader($headers);
        $result = $c->execute();
        unlink($tmp_file);
        return $result;
    }
}
