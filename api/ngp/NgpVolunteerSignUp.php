<?php
/**
 * NGP Volunteer Sign Up
 *
 * @author      Josh Lockhart <josh@newmediacampaigns.com>
 * @copyright   2012 New Media Campaigns
 * @link        http://www.newmediacampaigns.com
 * @version     1.0.0
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * USAGE:
 *
 * $v = new NgpVolunteerSignUp(
 *     'credentials',
 *     array(
 *          'code' => 'Note about the code'
 *     ),
 *     array(
 *          'FirstName' => 'John',
 *          'LastName' => 'Doe',
 *          'Email' => 'john.doe@fakegmail.com',
 *          'Address1' => '100 Elm Street',
 *          'Zip' => '12345'
 *     )
 * );
 * if ( $v->save() ( {
 *     //Success!
 * } else if ( $v->hasErrors() ) {
 *     $errors = $v->getErrors();
 *     //Show errors to user
 * } else if ( $v->hasFault() ) {
 *     $fault = $v->getFault();
 *     //Log fault for review
 * } else {
 *     $result = $v->getResult();
 *     //Log result for review
 * }
 */
class NgpVolunteerSignUp {
    /**
     * @var string
     */
    protected $api = 'http://services.myngp.com/ngponlineservices/VolunteerSignUpService.asmx?wsdl';

    /**
     * @var string Provided by NGP
     */
    protected $credentials;

    /**
     * @var array[String] Case sensitive!
     */
    protected $fields;

    /**
     * @var array[String] Case sensitive!
     */
    protected $requiredFields;

    /**
     * @var array[Code => Note] Volunteer info
     */
    protected $volunteerInfo;

    /**
     * @var array[String]
     */
    protected $errors;

    /**
     * @var SoapClient
     */
    protected $client;

    /**
     * @var SoapFault
     */
    protected $fault;

    /**
     * @var array
     */
    protected $response;

    /**
     * Constructor
     * @param  string   $credentials    Your NGP credentials string
     * @param  array    $info           Associative array of volunteer codes and notes; array( Code => Note, Code => Note, ... )
     * @param  array    $data           Associative array of user data fields
     * @return void
     */
    public function __construct( $credentials, $info, $data ) {
        $this->credentials = $credentials;
        $this->client = new SoapClient($this->api);
        $this->fields = array_merge(array(
            'LastName' => '',
            'FirstName' => '',
            'MiddleName' => '',
            'Prefix' => '',
            'Suffix' => '',
            'Address1' => '',
            'Address2' => '',
            'Address3' => '',
            'City' => '',
            'State' => '',
            'Zip' => '',
            'Salutation' => '',
            'Email' => '',
            'HomePhone' => '',
            'WorkPhone' => '',
            'WorkExtension' => '',
            'FaxPhone' => '',
            'Employer' => '',
            'Occupation' => '',
            'OptIn' => false //bool
        ), $data);
        $this->requiredFields = array('FirstName', 'LastName', 'Email', 'Address1', 'Zip');
        $this->volunteerInfo = $info;
    }

    /**
     * Set required fields
     * @param array[String] Case sensitive numeric array of field names
     * @return void
     */
    public function setRequiredFields( $fields ) {
        $this->requiredFields = $fields;
    }

    /**
     * Add volunteer info (code and note)
     * @param int $code
     * @param string $note
     * @return void
     */
    public function addVolunteerInfo( $code, $note ) {
        $this->volunteerInfo[$code] = $note;
    }

    /**
     * Save signup
     *
     * If this returns FALSE, you should inspect for errors or a fault.
     *
     * @return bool
     */
    public function save() {
        if ( $this->isValid() === false ) {
            return false;
        }
        $args = array(
            'credentials' => $this->credentials,
            'data' => $this->generateXml()
        );
        try {
            $responseXml = $this->client->VolunteerSignUp($args);
            $this->response = new SimpleXMLElement($responseXml->VolunteerSignUpResult);
            return (string)$this->response->successMsg === '0';
        } catch ( SoapFault $e ) {
            $this->fault = $e;
            return false;
        }
    }

    /**
     * Generate XML payload for SOAP API request
     * @return string
     */
    protected function generateXml() {
        $xml = '<VolunteerSignUp>';
        $xml .= '<ContactInfo>';
        foreach ( $this->fields as $key => $value ) {
            if ( is_bool($value) ) {
                $value = $value ? 'true' : 'false';
            }
            $xml .= sprintf('<%s>%s</%s>', $key, $value, $key);
        }
        $xml .= '</ContactInfo>';
        foreach ( $this->volunteerInfo as $code => $note ) {
            $xml .= sprintf('<VolunteerInfo><Code>%s</Code><Note>%s</Note></VolunteerInfo>', $code, $note);
        }
        $xml .= '</VolunteerSignUp>';
        return $xml;
    }

    /**
     * Is signup valid?
     * @return bool
     */
    public function isValid() {
        foreach( $this->requiredFields as $field ) {
            if ( !isset($this->fields[$field]) || empty($this->fields[$field]) ) {
                $this->errors[] = "$field is required";
            }
        }
        return empty($this->errors);
    }

    /**
     * Get result
     * return array
     */
    public function getResult() {
        return $this->response;
    }

    /**
     * Get errors
     * return array[String]|null
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Has errors?
     * @return bool
     */
    public function hasErrors() {
        return !empty($this->errors);
    }

    /**
     * Get last fault
     * @return SoapFault|null
     */
    public function getFault() {
        return $this->fault;
    }

    /**
     * Has fault?
     * @return bool
     */
    public function hasFault() {
        return !empty($this->fault);
    }
}
?>
