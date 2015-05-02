<?php
/*
  challonge-php
  A PHP API wrapper class for Challonge! (http://challonge.com)
  (c) 2014 Tony Drake
  (c) 2015 Alexey Solodkiy
  Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
*/

namespace Challonge;


class ChallongeApi
{
  private $api_key;

  public $verify_ssl = true;
  public $result = false;
  
  public function __construct($api_key='') {
    $this->api_key = $api_key;
  }
  
  public function makeCall($path='', $params=array(), $method='get') {
   
    // Clear the public vars
    $status_code = 0;
    $this->result = false;
    
    // Append the api_key to params so it'll get passed in with the call
    $params['api_key'] = $this->api_key;
    
    // Build the URL that'll be hit. If the request is GET, params will be appended later
    $call_url = "https://api.challonge.com/v1/".$path.'.json';
    
    $curl_handle=curl_init();
    // Common settings
    curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,5);
    curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
    
    if (!$this->verify_ssl) {
      // WARNING: this would prevent curl from detecting a 'man in the middle' attack
      curl_setopt ($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt ($curl_handle, CURLOPT_SSL_VERIFYPEER, 0); 
    }
    
    $curlheaders = array(); //array('Content-Type: text/xml','Accept: text/xml');
    
    // Determine REST verb and set up params
    switch( strtolower($method) ) {
      case "post":
        $fields = http_build_query($params, '', '&');
        $curlheaders[] = 'Content-Length: ' . strlen($fields);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields);
        break;
        
      case 'put':
        $fields = http_build_query($params, '', '&');
        $curlheaders[] = 'Content-Length: ' . strlen($fields);
        curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields);
        break;
        
      case 'delete':
        $params["_method"] = "delete";
        $fields = http_build_query($params, '', '&');
        $curlheaders[] = 'Content-Length: ' . strlen($fields);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields);
        // curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "DELETE");
        break;
        
      case "get":
      default:
        $call_url .= "?".http_build_query($params, "", "&");
    }
    
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $curlheaders); 
    curl_setopt($curl_handle,CURLOPT_URL, $call_url);
    
    $curl_result = curl_exec($curl_handle);   
    $info = curl_getinfo($curl_handle);
    $status_code = (int) $info['http_code'];
      try {
          if ($curl_result === false) {
              // CURL Failed
              throw new ChallongeException(curl_error($curl_handle));
          } else {
              switch ($status_code) {

                  case 401: // Bad API Key
                  case 422: // Validation errors
                  case 404: // Not found/Not in scope of account
                      $this->result = json_decode($curl_result, true);
                      throw new ChallongeException(implode(', ', $this->result['errors']), $status_code);
                      break;

                  case 500:
                      $this->result = false;
                      throw new ChallongeException("Server returned HTTP 500", $status_code);
                      break;

                  case 200:
                      $return = $this->result = json_decode($curl_result, true);
                      // Check if the result set is nil/empty
                      if (sizeof($return) == 0) {
                          throw new ChallongeException("Result set empty");
                      }

                      curl_close($curl_handle);
                      return $return;
                      break;

                  default:
                      throw new ChallongeException("Server returned unexpected HTTP Code ($status_code)");
              }
          }
      } catch (ChallongeException $e) {
          curl_close($curl_handle);
          throw $e;
      }
  }
  
  public function getTournaments($params=array()) {
    return $this->makeCall('tournaments', $params, 'get');
  }
  
  public function getTournament($tournament_id, $params=array()) {
    return $this->makeCall("tournaments/$tournament_id", $params, "get");
  }
  
  public function createTournament($params=array()) {
    if (sizeof($params) == 0) {
        throw new ChallongeException('empty params');
    }
    return $this->makeCall("tournaments", $params, "post");
  }
  
  public function updateTournament($tournament_id, $params=array()) {
    return $this->makeCall("tournaments/$tournament_id", $params, "put");
  }
  
  public function deleteTournament($tournament_id) {
    return $this->makeCall("tournaments/$tournament_id", array(), "delete");
  }
  
  public function publishTournament($tournament_id, $params=array()) {
    return $this->makeCall("tournaments/publish/$tournament_id", $params, "post");
  }
  
  public function startTournament($tournament_id, $params=array()) {
    return $this->makeCall("tournaments/start/$tournament_id", $params, "post");
  }
  
  public function resetTournament($tournament_id, $params=array()) {
    return $this->makeCall("tournaments/reset/$tournament_id", $params, "post");
  }
  
  
  public function getParticipants($tournament_id) {
    return $this->makeCall("tournaments/$tournament_id/participants");
  }
  
  public function getParticipant($tournament_id, $participant_id, $params=array()) {
    return $this->makeCall("tournaments/$tournament_id/participants/$participant_id", $params);
  }
  
  public function createParticipant($tournament_id, $params=array()) {
    if (sizeof($params) == 0) {
        throw new ChallongeException('empty params');
    }
    return $this->makeCall("tournaments/$tournament_id/participants", $params, "post");
  }
  
  public function updateParticipant($tournament_id, $participant_id, $params=array()) {
    return $this->makeCall("tournaments/$tournament_id/participants/$participant_id", $params, "put");
  }
  
  public function deleteParticipant($tournament_id, $participant_id) {
    return $this->makeCall("tournaments/$tournament_id/participants/$participant_id", array(), "delete");
  }
  
  public function randomizeParticipants($tournament_id) {
    return $this->makeCall("tournaments/$tournament_id/participants/randomize", array(), "post");
  }
  
  
  public function getMatches($tournament_id, $params=array()) {
    return $this->makeCall("tournaments/$tournament_id/matches", $params);
  }
  
  public function getMatch($tournament_id, $match_id) {
    return $this->makeCall("tournaments/$tournament_id/matches/$match_id");
  }
  
  public function updateMatch($tournament_id, $match_id, $params=array()) {
    if (sizeof($params) == 0) {
        throw new ChallongeException('empty params');
    }
    return $this->makeCall("tournaments/$tournament_id/matches/$match_id", $params, "put");
  }
}
