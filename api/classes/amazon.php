<?php

namespace Blindworm;

class Blindworm{

  //Enter your IDs
  const Access_Key_ID = "[Your Access Key ID]";
  const Associate_tag = "[Your Associate Tag ID]";
  const Request_Signature = "[Request Signature]";

  //Set up the operation in the request
  public function itemSearch($SearchIndex, $Keywords){

    //Set the values for some of the parameters
    $Operation = "ItemSearch";
    $Version = "2015-07-20";
    $ResponseGroup = "ItemAttributes,Offers";
    //User interface provides values
    //for $SearchIndex and $Keywords

    //Define the request
    $request=
         "http://webservices.amazon.com/onca/xml"
       . "?Service=AWSECommerceService"
       . "&AssociateTag=" . Associate_tag
       . "&AWSAccessKeyId=" . Access_Key_ID
       . "&Operation=" . $Operation
       . "&Version=" . $Version
       . "&SearchIndex=" . $SearchIndex
       . "&Keywords=" . $Keywords
       . "&Signature=" . Request_Signature
       . "&ResponseGroup=" . $ResponseGroup;

    //Catch the response in the $response object
    $response = file_get_contents($request);
    $parsed_xml = simplexml_load_string($response);
    printSearchResults($parsed_xml, $SearchIndex);
  }

  public function createCart(){}
  public function destroyCart(){}
  public function addItemToCart(){}

}
