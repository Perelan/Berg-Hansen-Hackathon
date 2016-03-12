<?php

//if session is not started
if(!session_id()){
    session_start();
}

//get code for connecting and fetching data from API
require_once('bridge.php');

function sort_array($data){
  usort($data, function($a, $b) {
    return $a['Fare'] < $b['Fare'] ? -1 : 1;
  });

  return $data;
}

function add_car($data){

    $buffer = '{
                "OTA_VehAvailRateRQ": {
                  "VehAvailRQCore": {
                    "QueryType": "Shop",
                    "VehRentalCore": {
                      "PickUpDateTime": "' . $data['DepartureDateTime'] . '",
                      "ReturnDateTime": "' . $data['ReturnDateTime'] . '",
                      "PickUpLocation": {
                        "LocationCode": "' . $data['DestinationLocation'] . '"
                      }
                    }
                  }
                }
              }';


    $session = sendRequest('v2.4.0/shop/cars', $buffer);

    $session = $session['OTA_VehAvailRateRS']['VehAvailRSCore'];

    $car = $session['VehVendorAvails']['VehVendorAvail'][0]['VehAvailCore']['VehicleCharges']['VehicleCharge']['TotalCharge']['Amount'];

  return $car;
}

function create_client_data($data, $car){
  $buffer = array();
  $is_in = false;

  $current_time =   $current_time = date("Y-m-d H:i:s", strtotime('+5 hours'));

  foreach($data as $dest){
    //if there is more than 5 hours to the flight
    if(date("Y-m-d H:i:s", strtotime($dest['DepartureDateTime'])) > $current_time){
      foreach ($buffer as $dest_inner){
        if($dest_inner['DestinationLocation'] == $dest['DestinationLocation']){
          $is_in = true;
          break;
        }
      }

      if(!$is_in){
        $temp = array(
          "DestinationLocation" => $dest['DestinationLocation'],
          "DepartureDateTime" => $dest['DepartureDateTime'],
          "ReturnDateTime" => $dest['ReturnDateTime'],
          "Fare" => $dest['LowestFare']['Fare'],
          "AirlineCodes" => $dest['LowestFare']['AirlineCodes'],
          "Car" => null);
          if($car == "true"){
            $temp['Car'] = add_car($temp);
          }

        array_push($buffer, $temp);
      }
    }
  }

  return $buffer;
}

function overprise($data, $limit){
  $buffer = array();

  foreach ($data as $dest){
    if(($dest['Fare'] + $dest['Car']) < $limit && $dest['Car'] != null){
      array_push($buffer, $dest);
    }
  }

  return $buffer;
}

function handle_client_flight($data){
  $payload = 'origin=NYC';

  if($data['departuredate'] != "null"){
    $payload = $payload . "&departuredate=" . $data['departuredate'];
  }
  if($data['returndate'] != "null"){
    $payload = $payload . "&returndate=" . $data['returndate'];
  }
  if($data['lengthofstay'] != "null"){
    $payload = $payload . "&lengthofstay=" . $data['lengthofstay'];
  }
  if($data['maxfare'] != "null"){
    $payload = $payload . "&minfare=0&maxfare=" . $data['maxfare'];
  }else{
    exit;
  }

  $payload = $payload . "&topdestinations=50";

  $session = sendRequest('v2/shop/flights/fares?' . $payload, null);

  $session = create_client_data($session['FareInfo'], $data['car']);

  if($data['car'] == "true"){
    $session = overprise($session, $data['maxfare']);
  }

  $session = sort_array($session);

  echo json_encode($session, 128);

}

//get data from client
$data_client = json_decode($_GET['data'], true);
handle_client_flight($data_client);

//echo json_encode(sort_array(create_client_data(handle_client_flight($data_client)['FareInfo'])), JSON_PRETTY_PRINT);

//$data = sendRequest('v2/shop/flights/fares?origin=NYC&departuredate=&returndate=&lengthofstay=2&minfare=0&maxfare=1000&topdestinations=50');

//$data = $data['FareInfo'];
