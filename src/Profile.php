<?php
/**
 * @package   orcid-php
 * @author    Sam Wilson <samwilson@purdue.edu>
 * @author    Darren Stephens <darren.stephesn@durham.ac.uk>
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
     * The API version
     *
     * @var String
     **/
    private $api_version = "2.0";

    /**
     * Constructs object instance
     *
     * @param   object  $oauth  the oauth object used for making calls to orcid
     * @return  void
     **/
    public function __construct($oauth = null, $version = "2.0")
    {
        $this->oauth = $oauth;
        $this->api_version = $version;
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
            if ($this->api_version === '2.0') {
                $this->raw = $this->oauth
                                ->getProfile($this->id(), $this->api_version);
            } else {
                $this->raw = $this->oauth
                                ->getProfile($this->id(), $this->api_version)
                                ->{'orcid-profile'};
            }
        }

        return $this->raw;
    }

    /**
     * Grabs the ORCID bio for v1.2
     *
     * @return  object
     **/
    public function bio()
    {
        if ($this->api_version === '1.2') {
            $this->raw();
            return $this->raw->{'orcid-bio'};
        }
        return null;
    }

    /**
     * Grabs the ORCID person bio for API 2.0
     *
     * @return  object
     **/
    public function person()
    {
        if ($this->api_version === '2.0') {
            $this->raw();
            return $this->raw->{'person'};
        }
        return null;
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

        if ($this->api_version === '2.0') {
            $person = $this->person();
            if (isset($person->{'emails'})) {
                if (isset($person->{'emails'}->email)) {
                    if (is_array($person->{'emails'}->email) && isset($person->{'emails'}->email[0])) {
                        $email = $person->{'emails'}->email[0]->email;
                    }
                }
            }
        } else {
            $bio = $this->bio();
            if (isset($bio->{'contact-details'})) {
                if (isset($bio->{'contact-details'}->email)) {
                    if (is_array($bio->{'contact-details'}->email) && isset($bio->{'contact-details'}->email[0])) {
                        $email = $bio->{'contact-details'}->email[0]->value;
                    }
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

        if ($this->api_version === '2.0') {
            $details = $this->person()->{'name'};
        } else {
            $details = $this->bio()->{'personal-details'};
        }
        return $details->{'given-names'}->value . ' ' . $details->{'family-name'}->value;
    }

    /**
     * Saves the orcid-message xml provided to the correct scope
     *
     * @return  mixed - either false on failure or result content if successful
     **/
    public function save($scope, $xml)
    {
        $endpoint = $this->oauth->getApiEndpoint($scope, $this->api_version, $this->id());
        $headers = [
                'Content-Type'  => 'application/vnd.orcid+xml',
                'Authorization' => 'Bearer ' . $this->id()->getAccessToken()
            ];

        $orcid_msg = stripslashes($xml);

        /* We need to set up a tmp file in order
         * to do the HTTP PUT request properly
         */
        $tmp_file = tmpfile();
        fwrite($tmp_file, $orcid_msg);
        fseek($tmp_file, 0);

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
