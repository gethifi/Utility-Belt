<?php
/**
 * NGP Email Sign Up
 * @author Josh Lockhart
 * @version 1.0
 *
 * USAGE:
 *
 * NgpSignUp::$api = 'https://services.myngp.com/ngponlineservices/onlinecontribservice.asmx?wsdl';
 * NgpSignUp::$credentials = 'GET THIS FROM NGP';
 *
 * $signup = new NgpSignUp(array(
 *    'lastName' => 'Doe',
 *    'firstName' => 'John',
 *    'email' => 'john.doe@gmail.com',
 *    'zip' => '12345'
 * ));
 * if ( $signup->isValid() ) {
 *    $result = $signup->save(); //bool
 *    if ( $result ) {
 *        //Done
 *    } else {
 *        $fault = $result->getFault(); //SoapFault
 *    }
 * } else {
 *    $errors = $signup->getErrors();
 * }
 *
 */
class NgpSignUp {
    /**
     * @var string
     */
    public static $api;

    /**
     * @var string Provided by NGP
     */
    public static $credentials;

    /**
     * @var array[String] Case sensitive!
     */
    protected $fields;

    /**
     * @var array[String] Case sensitive!
     */
    protected $requiredFields;

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
     * Constructor
     * @param array[(String)Name => (String)Value] Key-value array of field names and values
     * @throws RuntimeException If API or credentials not set
     */
    public function __construct( $data = array() ) {
        if ( !self::$api || !self::$credentials ) {
            throw new RuntimeException('NgpSignUp API and credentials must be set first!');
        }
        $this->client = new SoapClient(self::$api);
        $this->fields = array_merge(array(
            'lastName' => '',
            'firstName' => '',
            'email' => '',
            'zip' => ''
        ), $data);
        $this->requiredFields = array();
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
     * Save email signup
     *
     * If this returns FALSE, you should inspect for errors or a fault.
     *
     * @return bool
     */
    public function save() {
        if ( $this->isValid() === false ) {
            return false;
        }
        $args = array_merge($this->fields, array('credentials' => self::$credentials));
        try {
            $response = $this->client->EmailSignUp($args);
            return $response->EmailSignUpResult; //true or false (BOOL)
        } catch ( SoapFault $e ) {
            $this->fault = $e;
            return false;
        }
    }

    /**
     * Is email signup valid?
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