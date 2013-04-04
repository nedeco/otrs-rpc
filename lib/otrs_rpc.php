<?php
/**
 * PHP interface to OTRS' RPC interface.
 * OTRS API documentation: http://dev.otrs.org
 *
 * @package    otrs_rpc
 * @copyright  Copyright (c) 2013, nedeco GmbH
 * @license    http://opensource.org/licenses/MIT MIT License
 */

/**
 * The OTRSRPC class is used to interact with OTRS via its SOAP API.
 *
 * @package  otrs_rpc
 * @author   Daniel Kempkens <d.kempkens@nedeco.de>
 */
class OTRSRPC {
  /**
   * Default parameters for ticket creation (passed to OTRS)
   *
   * @access  public
   */
  public $defaults_ticket_create = array(
    "QueueID" => OTRS_DEFAULT_QUEUE_ID,
    "TypeID" => OTRS_DEFAULT_TYPE_ID,
    "PriorityID" => OTRS_DEFAULT_PRIORITY_ID,
    "State" => "new"
  );

  /**
   * Default parameters for article creation (passed to OTRS)
   *
   * @access  public
   */
  public $defaults_article_create = array(
    "MimeType" => "text/plain",
    "Charset" => "utf8"
  );

  /**
   * Base URL to the OTRS installation
   *
   * @access  private
   */
  private $base_url;

  /**
   * OTRS SOAP username
   *
   * @access  private
   */
  private $username;

  /**
   * OTRS SOAP password
   *
   * @access  private
   */
  private $password;

  /**
   * OTRS SOAP webservice
   *
   * @access  private
   */
  private $webservice;

  /**
   * OTRS SOAP namespace
   *
   * @access  private
   */
  private $namespace;

  /**
   * OTRS login type
   *
   * @access  private
   */
  private $login_type;

  /**
   * Creates a new instance of the OTRSRPC class.
   *
   * @access  public
   *
   * @param   string  $base_url    Base URL of the OTRS installation
   * @param   string  $username    OTRS SOAP username
   * @param   string  $password    OTRS SOAP password
   * @param   string  $webservice  OTRS webservice name
   * @param   string  $namespace   OTRS namespace
   * @param   string  $login_type  The type of login to use (either UserLogin or CustomerUserLogin)
   */
  public function __construct($base_url, $username, $password, $webservice, $namespace, $login_type = 'UserLogin') {
    $this->base_url = $base_url;
    $this->username = $username;
    $this->password = $password;
    $this->webservice = $webservice;
    $this->namespace = $namespace;
    $this->login_type = $login_type;
  }

  /**
   * Returns a list of all tickets (accessible by UserID)
   *
   * @access  public
   *
   * @param   array  $params  A hash of additional parameters (passed to OTRS)
   *
   * @return  array
   */
  public function ticket_list($params = array()) {
    $ids = $this->ticket_list_ids($params);
    if (is_array($ids)) {
      $ids_count = count($ids);
      $result = array();

      for($i = 0; $i < $ids_count; ++$i) {
        $id = $ids[$i];
        $result[] = $this->ticket_get($id);
      }

      return $result;
    } else {
      return null;
    }
  }

  /**
   * Returns a list of all ticket IDs (accessible by UserID)
   *
   * @access  public
   *
   * @param   array  $params  A hash of additional parameters (passed to OTRS)
   *
   * @return  array
   */
  public function ticket_list_ids($params = array()) {
    return $this->ticket_search($params);
  }

  /**
   * Performs a ticket search operation with a given set of parameters and returns a list of IDs.
   *
   * @access  public
   *
   * @param   array  $params  A hash of parameters (passed to OTRS)
   *
   * @return  array
   */
  public function ticket_search($params) {
    $result = $this->dispatch_call("TicketSearch", $params);
    if (is_array($result)) {
      return $result['TicketID'];
    } else {
      return null;
    }
  }

  /**
   * Returns a ticket based on an ID.
   *
   * @access  public
   *
   * @param   int    $id      A ticket ID
   * @param   array  $params  A hash of additional parameters (passed to OTRS)
   *
   * @return  array
   */
  public function ticket_get($id, $params = array()) {
    if (is_null($id)) { return null; }
    $params['TicketID'] = $id;

    $result = $this->dispatch_call("TicketGet", $params);
    if (is_object($result)) {
      return self::object_to_hash($result);
    } else {
      return null;
    }
  }

  /**
   * Creates a new ticket with an article.
   *
   * @access  public
   *
   * @param   array  $params  A hash of parameters (passed to OTRS)
   *
   * @return  array
   */
  public function ticket_article_create($params) {
    $params['Ticket'] = array_merge($this->defaults_ticket_create, $params['Ticket']);
    $params['Article'] = array_merge($this->defaults_article_create, $params['Article']);
    return $this->dispatch_call("TicketCreate", $params);
  }

  /**
   * Add a new article to an existing ticket.
   *
   * @access  public
   *
   * @param   int    $id      A ticket ID
   * @param   array  $params  A hash of parameters (passed to OTRS)
   *
   * @return  integer
   */
  public function ticket_article_add($id, $params) {
    $soap_params = array();
    $soap_params['TicketID'] = $id;
    $soap_params['Article'] = array_merge($this->defaults_article_create, $params);

    $result = $this->dispatch_call("TicketUpdate", $soap_params);
    if (is_array($result)) {
      return $result['ArticleID'];
    } else {
      return null;
    }
  }

  /**
   * Edits a ticket based on an ID.
   *
   * @access  public
   *
   * @param   int    $id      A ticket ID
   * @param   array  $params  A hash of parameters (passed to OTRS)
   *
   * @return  array
   */
  public function ticket_update($id, $params) {
    $soap_params = array();
    $soap_params['TicketID'] = $id;
    $soap_params['Ticket'] = $params;

    return $this->dispatch_call("TicketUpdate", $soap_params);
  }

  /**
   * Makes a call to the SOAP Server.
   *
   * @access  private
   *
   * @param   string  $endpoint  Name of the generic OTRS endpoint
   * @param   array   $params    A hash of parameters (passed to OTRS)
   *
   * @return  mixed
   */
  private function dispatch_call($endpoint, $params) {
    $soap_params = array();
    $soap_params[] = new SoapParam($this->username, $this->login_type);
    if (!is_null($this->password)) {
      $soap_params[] = new SoapParam($this->password, "Password");
    }
    foreach($params as $key => $value) {
      $soap_params[] = new SoapParam($value, $key);
    }

    try {
      $client = new SoapClient(null, array(
        'location' => $this->base_url.'/nph-genericinterface.pl/Webservice/'.$this->webservice,
        'uri' => $this->namespace,
        'trace' => 1,
        'style' => SOAP_RPC,
        'use' => SOAP_ENCODED
      ));

      $result = $client->__soapCall($endpoint, $soap_params);
      unset($client);
      return $result;
    } catch (SoapFault $fault) {
      unset($client);
      return null;
    }
  }

  /**
   * Converts an object to a hash
   *
   * @access  private
   * @static
   *
   * @param   object  $obj  Object to convert
   *
   * @return  array
   */
  private static function object_to_hash($obj) {
    $arr = array();
    $arr_obj = is_object($obj) ? get_object_vars($obj) : $obj;
    foreach ($arr_obj as $key => $value) {
      $value = (is_array($value) || is_object($value)) ? self::object_to_hash($value) : $value;
      $arr[$key] = $value;
    }
    return $arr;
  }
}
