<?php
/**
 * PHP interface to OTRS' RPC interface.
 * OTRS API documentation: http://dev.otrs.org
 *
 * @package    otrs_rpc
 * @author     Daniel Kempkens <d.kempkens@nedeco.de>
 * @copyright  Copyright (c) 2013, nedeco GmbH
 * @license    http://opensource.org/licenses/MIT MIT License
 */

class OTRSRPC {
  /**
   * Default parameters for the ticket search (passed to OTRS)
   *
   * @access  public
   */
  public $defaults_ticket_search = array(
    "UserID" => OTRS_DEFAULT_USER_ID
  );

  /**
   * Default parameters for ticket creation (passed to OTRS)
   *
   * @access  public
   */
  public $defaults_ticket_create = array(
    "QueueID" => OTRS_DEFAULT_QUEUE_ID,
    "LockID" => 1,
    "PriorityID" => OTRS_DEFAULT_PRIORITY_ID,
    "State" => "new"
  );

  /**
   * Default parameters for article creation (passed to OTRS)
   *
   * @access  public
   */
  public $defaults_article_create = array(
    "ContentType" => "text/plain; charset=UTF-8"
  );

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
   * SOAP client
   *
   * @access  private
   */
  private $client;

  /**
   * Returns a new instance of the OTRSRPC class.
   *
   * @access  public
   *
   * @param   string  $base_url  Base URL of the OTRS installation
   * @param   string  $username  OTRS SOAP username
   * @param   string  $password  OTRS SOAP password
   */
  public function __construct($base_url, $username, $password) {
    $this->username = $username;
    $this->password = $password;
    $this->client = new SoapClient(null, array(
      'location' => $base_url.'/rpc.pl',
      'uri' => 'Core',
      'login' => $this->username,
      'password' => $this->password,
      'style' => SOAP_RPC,
      'use' => SOAP_ENCODED
    ));
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
    $default_params = array("TicketObject", "TicketSearch");
    $params = array_merge($this->defaults_ticket_search, $params);
    $params = array_merge($default_params, self::flatten_hash($params));
    $result = $this->dispatch_call($params);
    if (is_array($result)) {
      return array_keys($result);
    } else {
      return null;
    }
  }

  /**
   * Returns a ticket based on an ID.
   *
   * @access  public
   *
   * @param   integer  $id            A ticket ID
   * @param   array    $extra_params  A hash of additional parameters (passed to OTRS)
   *
   * @return  array
   */
  public function ticket_get($id, $extra_params = array()) {
    $params = array("TicketObject", "TicketGet", "TicketID", $id);
    $params = array_merge($params, self::flatten_hash($extra_params));
    return $this->dispatch_call($params);
  }

  /**
   * Returns a (formatted) ticket number, based on a ticket ID.
   *
   * @access  public
   *
   * @param   integer  $id  A ticket ID
   *
   * @return  string
   */
  public function ticket_number_lookup($id) {
    $params = array("TicketObject", "TicketNumberLookup", "TicketID", $id);
    $number = $this->dispatch_call($params);
    return (is_null($number)) ? null : number_format($number, 0, '.', '');
  }

  /**
   * Creates a new ticket.
   *
   * @access  public
   *
   * @param   array  $params  A hash of parameters (passed to OTRS)
   *
   * @return  integer
   */
  public function ticket_create($params) {
    $default_params = array("TicketObject", "TicketCreate");
    $params = array_merge($this->defaults_ticket_create, $params);
    $params = array_merge($default_params, self::flatten_hash($params));
    return $this->dispatch_call($params);
  }

  /**
   * Creates a new article.
   *
   * @access  public
   *
   * @param   integer  $id      A ticket ID
   * @param   array    $params  A hash of parameters (passed to OTRS)
   *
   * @return  integer
   */
  public function article_create($id, $params) {
    $default_params = array("TicketObject", "ArticleCreate", "TicketID", $id);
    $params = array_merge($this->defaults_article_create, $params);
    $params = array_merge($default_params, self::flatten_hash($params));
    return $this->dispatch_call($params);
  }

  /**
   * Creates a ticket and a related article.
   *
   * @access  public
   *
   * @param   array  $param_ticket   A hash of ticket parameters (passed to OTRS)
   * @param   array  $param_article  A hash of article parameters (passed to OTRS)
   *
   * @return  array
   */
  public function ticket_create_with_article($param_ticket, $param_article) {
    $ticket_id = $this->ticket_create($param_ticket);
    if (is_int($ticket_id)) {
      $article_id = $this->article_create($ticket_id, $param_article);
      return array("TicketID" => $ticket_id, "ArticleID" => $article_id);
    } else {
      return null;
    }
  }

  /**
   * Makes a call to the SOAP Server.
   *
   * @access  private
   *
   * @param   array  $params  A list (flattened hash) of parameters (passed to OTRS)
   */
  private function dispatch_call($params) {
    $default_params = array($this->username, $this->password);
    $soap_result = $this->client->__soapCall('Dispatch', array_merge($default_params, $params));
    if (is_array($soap_result)) {
      return self::inflate_array($soap_result);
    } else {
      return $soap_result;
    }
  }

  /**
   * Makes a flat list from out of a hash.
   *
   * @access  public
   * @static
   *
   * @param   array  $arr  The hash that should be flattened
   *
   * @return  array
   */
  public static function flatten_hash($arr) {
    $keys = array_keys($arr);
    $values = array_values($arr);
    $arr_count = count($arr);
    $result = array();

    for($i = 0; $i < $arr_count; ++$i) {
      $result[] = $keys[$i];
      $result[] = $values[$i];
    }

    return $result;
  }

  /**
   * Makes a hash out of a flat list.
   *
   * @access  public
   * @static
   *
   * @param   array  $arr  The list that should be inflated
   *
   * @return  array
   */
  public static function inflate_array($arr) {
    $arr_values = array_values($arr);
    $arr_count = count($arr_values);
    $result_keys = array();
    $result_values = array();

    for($i = 0; $i < $arr_count; ++$i) {
      if ($i % 2 == 0) { # Key
        $result_keys[] = $arr_values[$i];
      } else { # Value
        $value = $arr_values[$i];
        if (is_array($value)) {
          $result_values[] = self::inflate_array($value);
        } else {
          $result_values[] = $value;
        }
      }
    }

    return array_combine($result_keys, $result_values);
  }
}
