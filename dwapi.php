<?php
/**
 * DeviceWISE Public API client class
 *
 * This class serves as an example on interfacing with the DeviceWISE
 * public API. For more information see http://wiki.example.com/foo
 *
 * Copyright 2012 ILS Technologies, LLC under the GNU GPL Version 2
 * CloudLINK & DeviceWISE are (tm) ILS Technology, LLC All Rights Reserved
 */

class DwApi {

  /**
   * The API endpoint for POSTing (e.g. https://www.example.com/api)
   * @var string
   */
  public $endpoint = '';

  /**
   * This applications applicationToken
   * @var string
   */
  public $applicationToken = '';

  /**
   * The organization token you will be using
   * @var string
   */
  public $organizationToken = '';

  /**
   * Session ID given by your portal
   * @var string
   */
  public $sessionId = '';

  /**
   * Array of commands that do not need authentication
   * @var array
   */
  private $noAuthCommands = array();

  /**
   * Last JSON string received from the endpoint. Used when debugging.
   * @var string
   */
  public $lastReceived = '';

  /**
   * Last JSON string sent to the endpoint. Used when debugging.
   * @var string
   */
  public $lastSent = '';

  /**
   * Holds the response data from the api call
   * @var array
   */
  private $response = '';

  /**
   * Holds errors returned by the api
   * @var array
   */
  private $errors = array();


  public function __construct($options = array())
  {
    $this->endpoint          = (!empty($options['endpoint']))          ? $options['endpoint']          : '';
    $this->applicationToken  = (!empty($options['applicationToken']))  ? $options['applicationToken']  : '';
    $this->organizationToken = (!empty($options['organizationToken'])) ? $options['organizationToken'] : '';
    $this->sessionId         = (!empty($options['sessionId']))         ? $options['sessionId']         : '';

    $this->noAuthCommands = array('api.ping', 'api.authenticate');
  }


  /**
   * Uses CURL to do the actual POSTing
   *
   * @param string $command The command to be ran
   * @param array $params The command params to be sent
   * @param return bool If the post function should return the success or the response
   * @return mixed
   */

  private function post($command, $params, $return = true)
  {
    $post_data = json_encode(array('auth' => $this->authArray($command),
                                   'data' => array('command' => $command,
                                                   'params'  => $params)));
    $this->lastSent = $post_data;
    $curl_handler = curl_init($this->endpoint);
    curl_setopt($curl_handler, CURLOPT_HEADER, 0);
    curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $post_data);

    if (!$response = curl_exec($curl_handler)) {
      trigger_error("Failed to POST to {$this->endpoint}");
      return false;
    }

    curl_close($curl_handler);
    $this->lastReceived = $response;
    $response = json_decode($response, true);

    if (!empty($response['data']['errorMessage'])) {
      $this->errors = $response['data']['errorMessage'];
      return false;
    }
    $this->response = $response['data']['params'];
    if ($return) {
      return $this->response;
    }
    return true;
  }


  /**
   * Generates the auth section of the API request
   *
   * @param string $command The command to be executed
   * @return mixed
   * @see $this->post()
   */

  private function authArray($command)
  {
    if (in_array($command, $this->noAuthCommands)) {
      return null;
    }

    if (empty($this->applicationToken)) {
      trigger_error('No Application Token has been defined.');
      return false;
    }

    if (empty($this->sessionId)) {
      trigger_error('No session id has been defined.');
      return false;
    }

    return array('applicationToken' => $this->applicationToken,
                 'sessionId'        => $this->sessionId);
  }


  /**
   * Does an api-based ping
   *
   * @return bool
   */

  public function ping()
  {
    return $this->post('api.ping', array(), false);
  }


  /**
   * Authenticates the app/org and returns the session ID
   *
   * @param string $applicationToken Either sent or set as $this->applicationToken
   * @param string $organizationToken Either sent or set as $this->organizationToken
   * @return mixed
   */

  public function auth($applicationToken = null, $organizationToken = null)
  {
    $applicationToken  = ($applicationToken)  ? $applicationToken  : $this->applicationToken;
    if (is_array($organizationToken)) {
      $username = $organizationToken['username'];
      $password = $organizationToken['password'];
    }
    elseif (empty($organizationToken)) {
      $organizationToken = $this->organizationToken;
    }

    if (empty($applicationToken)) {
      trigger_error('No applicationToken has been given.');
      return false;
    }

    if (empty($organizationToken) and empty($username) and empty($password)) {
      trigger_error('No organizationToken, username or password have been given.');
      return false;
    }

    $params = array('applicationToken' => $applicationToken);
    if (isset($username) and isset($password)) {
      $params['username'] = $username;
      $params['password'] = $password;
    }
    else {
      $params['organizationToken'] = $organizationToken;
    }

    if ($this->post('api.authenticate', $params, false)) {
      return $this->response['sessionId'];
    }
    return false;
  }


  /**
   * Returns a list of all available orgs for the user
   *
   * @return mixed
   */

  public function orgList()
  {
    return $this->post('user.org.list', array());
  }


  /**
   * Sets the currently active org for the user
   *
   * @param string $orgId
   * @see DwApi::orgList()
   */

  public function orgSet($orgId)
  {
    return $this->post('user.org.set', array('id' => $orgId));
  }


  /**
   * Returns a list of all gateways
   *
   * @return mixed
   */

  public function gatewayList()
  {
    return $this->post('gateway.list', array());
  }


  /**
   * Returns details for a given gateway
   *
   * @param string $cloudlinkId The CloudLINK ID if the gateway you want details about
   * @return mixed
   */

  public function gatewayDetails($cloudlinkId)
  {
    $params = array('cloudlinkId' => $cloudlinkId);
    return $this->post('gateway.details', $params);
  }


  /**
   * Returns attributes of type system, user or both for a given gateway
   *
   * @params string $cloudlinkId The CloudLINK ID of the gateway you are listing attributes for
   * @params string $type user, system or both - Attribute types
   * @return mixed
   */

  public function attributeList($cloudlinkId, $type = 'both')
  {
    $params = array('cloudlinkId' => $cloudlinkId, 'type' => $type);
    return $this->post('gateway.attribute.list', $params);
  }


  /**
   * Returns a list of all user operations on a given gateway
   *
   * @return mixed
   */

  public function useropList($cloudlinkId)
  {
    $params = array('cloudlinkId' => $cloudlinkId);
    return $this->post('gateway.userop.list', $params);
  }


  /**
   * Executes a specified user operation
   *
   * @param string $cloudlinkId The CloudLINK ID you want to execute the operation on
   * @param string $userop The operation you want to execute
   * @param array $inputs The operation inputs (arguments) as an associative array
   * @return mixed
   */

  public function useropExec($cloudlinkId, $userop, $inputs = array())
  {
    $_inputs = array();
    if (!empty($inputs)) {
      foreach ($inputs as $k => $v) {
        $_inputs[] = array('key'   => $k,
                           'value' => $v);
      }
    }

    $params = array('cloudlinkId' => $cloudlinkId,
                    'operation'   => $userop,
                    'inputs'      => $_inputs);
    return $this->post('gateway.userop.exec', $params, false);
  }


  /**
   * Lists all remote triggers on a given gateway
   *
   * @param string $cloudlinkId The CloudLINK ID you want to list the remote triggers on
   * @return mixed
   */

  public function remtriggerList($cloudlinkId)
  {
    $params = array('cloudlinkId' => $cloudlinkId);
    return $this->post('gateway.remtrigger.list', $params);
  }


  /**
   * Executes a specified remote trigger
   *
   * @param string $cloudlinkId The CloudLINK ID you want to execute the remote trigger on
   * @param string $remTrigger The identifier of the remote trigger
   * @param array $notificationVariables The remote trigger notification variables as an associative array
   * @return mixed
   */

  public function remtriggerExec($cloudlinkId, $remTrigger, $notificationVariables = array())
  {
    $_notificationVariables = array();
    foreach ($notificationVariables as $k => $v) {
      $_notificationVariables[] = array('key'   => $k,
                                        'value' => $v);
    }

    $params = array('cloudlinkId'       => $cloudlinkId,
                    'identifier'        => $remTrigger,
                    'notificationItems' => $_notificationVariables);
    return $this->post('gateway.remtrigger.exec', $params, true);
  }

}