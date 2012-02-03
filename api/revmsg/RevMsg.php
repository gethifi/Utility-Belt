<?php
/**
 * Revolution Messaging API
 *
 * @author      Josh Lockhart <josh@newmediacampaigns.com>
 * @copyright   2011 New Media Campaigns
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
 */

/**
 * RevMsg
 *
 * This class provides a simple interface to subscribe or unsubscribe a user to
 * a Revolution Messaging subscriber list.
 *
 * @author  Josh Lockhart
 * @since   1.0.0
 *
 * AVAILABLE ATTRIBUTES:
 *
 * phone (required)
 * email (required)
 * zip (required)
 * lname
 * fname
 * name
 * tags (comma-delimited list)
 * custom-tag-names (any additional attributes are considered custom fields)
 *
 * Use one of `name` or (`fname` and `lname`). Do not use both.
 *
 * USAGE (SUBSCRIBE)
 *
 * $msg = new RevMsg('your-uuid');
 * $result = $msg->subscribe(array(
 *     'phone' => '0001112222',
 *     'email' => 'john.smith@gmail.com',
 *     'zip' => '12345',
 *     'name' => 'John Smith'
 * ));
 * if ( $result ) {
 *     echo "Success!";
 * } else {
 *     $apiResponse = $msg->getResponse(); //<-- JSON string
 *     $errors = $msg->getErrors();
 *     $errorDetails = $msg->getErrorDetails();
 * }
 *
 * USAGE (UNSUBSCRIBE)
 *
 * $msg = new RevMsg('your-uuid');
 * $result = $msg->unsubscribe('0001112222');
 * if ( $result ) {
 *     echo "Success!";
 * } else {
 *     $apiResponse = $msg->getResponse(); //<-- JSON string
 *     $errors = $msg->getErrors();
 *     $errorDetails = $msg->getErrorDetails();
 * }
 */
class RevMsg {
    protected static $api = 'http://api.revmsg.net/json/v1/';
    protected static $requiredFields = array('phone', 'email', 'zip');
    protected $uuid;
    protected $errors;
    protected $errorDetails;
    protected $response;

    /**
     * Constructor
     *
     * Create a new Revolution Messaging API instance. Set the UUID to be used in API calls
     * and preset the error properties to empty arrays.
     *
     * @param string $uuid The Revolution Messaging API subscriber list UUID
     * @return void
     */
    public function __construct( $uuid ) {
        $this->uuid = $uuid;
        $this->errors = array();
        $this->errorDetails = array();
    }

    /**
     * Subscribe user
     *
     * Send user information to the Revolution Messaging API and subscribe
     * the user to the subscriber list identified by the UUID. The user information
     * must be an associative array and include all required fields. If this
     * method returns false, inspect for errors with `getErrors` and `getErrorDetails`.
     *
     * @param array $userInfo Associative array containing all required fields
     * @return bool
     */
    public function subscribe( $userInfo ) {
        if ( $this->isValid($userInfo) ) {
            return $this->sendPostRequest($this->getSubscribeUrl(), $userInfo);
        }
        return false;
    }

    /**
     * Unsubscribe user
     *
     * Remove the user identified by the given phone number from the Revolution Messaging
     * subscriber list identified by the UUID. Only the phone number is required. If this
     * method returns false, inspect for errors with `getErrors` and `getErrorDetails`.
     *
     * @param string $phone The phone number (only digits)
     * @return bool
     */
    public function unsubscribe( $phone ) {
        return $this->sendPostRequest($this->getUnsubscribeUrl(), array('phone' => $phone));
    }

    /**
     * Has errors?
     * @return bool
     */
    public function hasErrors() {
        return !empty($this->errors);
    }

    /**
     * Get errors
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get error details
     * @return array
     */
    public function getErrorDetails() {
        return $this->errorDetails;
    }

    /**
     * Get API response
     * @return string|null
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Is user information valid?
     *
     * This method makes sure the user information contains
     * all required fields. Errors will be added to the `$errors` array.
     *
     * @param array $userInfo Associative array of user information
     * @return bool
     */
    protected function isValid( $userInfo ) {
        foreach ( self::$requiredFields as $name ) {
            if ( !isset($userInfo[$name]) || empty($userInfo[$name]) ) {
                $this->errors[] = "$name is required";
            } 
        }
        return empty($this->errors);
    }

    /**
     * Send POST request to API
     *
     * This method sends an HTTP POST request to the Revolution Messaging API
     * and logs any curl or API errors to the `$errors` and `$errorDetails` arrays.
     *
     * @param string $url The API endpoint
     * @param array $params Associative array of POST parameter names and values
     * @return bool
     */
    protected function sendPostRequest( $url, $params ) {
        //Reset errors
        $this->errors = array();
        $this->errorDetails = array();

        //Build POST request body
        $postBody = '';
        foreach ( $params as $key => $value ) {
            $postBody .= sprintf('&%s=%s', urlencode($key), urlencode($value));
        }
        $postBody = ltrim($postBody, '&');

        //Send POST request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
        $this->response = curl_exec($ch);

        //Handle response
        if ( $this->response ) {
            $result = json_decode($this->response, true);
            if ( $result['error'] ) {
                $this->errors[] = $result['message'];
                $this->errorDetails = $result;
            }
        } else {
            $this->errors[] = 'Unable to contact API';
            $this->errorDetails = curl_getinfo($ch);
        }
        curl_close($ch);

        //Return bool
        return empty($this->errors);
    }

    /**
     * Generate API subscribe URL
     * @return string
     */
    protected function getSubscribeUrl() {
        return self::$api . $this->uuid . '/';
    }

    /**
     * Generate API unsubscribe URL
     * @return string
     */
    protected function getUnsubscribeUrl() {
        return self::$api . $this->uuid . '/true';
    }
}