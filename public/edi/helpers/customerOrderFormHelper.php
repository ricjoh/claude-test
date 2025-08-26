<?php

require 'EDIJsonParser.php';

// 940 Form

function getCustomerOrderDataArray($jsonDataArray) 
{
    if (empty($jsonDataArray)) {
        return false;
    }

    $carrier = getCarrierForCustomerOrder($jsonDataArray);

    // TODO: There are some values that if not found, they don't get entered. They should be entered as either false or empty strings.
    // Perhaps the functions would be better off returning the values instead of asking for it with each call?
    $customerOrderDataArray = array(
        "CustomerOrderNum"  => getElementBySegmentNameAndLabel($jsonDataArray, "W05", "W0502")["value"],
        "CustPONum"             => getElementBySegmentNameAndLabel($jsonDataArray, "W05", "W0503")["value"],
        "ShipToID"              => getElementFromSegmentByLabel(getSegmentWithElementValue($jsonDataArray, "N1", "ST"), "N104")["value"],
        "ShipToPONum"           => getElementFromSegmentByLabel(getSegmentWithElementValue($jsonDataArray, "N9", "CO"), "N902")["value"],
        "ShipToMasterBOL"       => getElementFromSegmentByLabel(getSegmentWithElementValue($jsonDataArray, "N9", "MB"), "N902")["value"], // If this value is false, then do NOT enter it into the database
        "ShipToName"            => getElementBySegmentNameAndLabel($jsonDataArray, "N2", "N201")["value"],
        "ShipToAddr1"           => getElementBySegmentNameAndLabel($jsonDataArray, "N3", "N301")["value"],
        "ShipToAddr2"           => getElementBySegmentNameAndLabel($jsonDataArray, "N3", "N302")["value"], // If it exists...
        "ShipToCity"            => getElementBySegmentNameAndLabel($jsonDataArray, "N4", "N401")["value"],
        "ShipToState"           => getElementBySegmentNameAndLabel($jsonDataArray, "N4", "N402")["value"],
        "ShipToZIP"             => getElementBySegmentNameAndLabel($jsonDataArray, "N4", "N403")["value"],
        "ShipToCountry"         => getElementBySegmentNameAndLabel($jsonDataArray, "N4", "N404")["value"], // It it exists...
        "ShipByDate"            => getElementFromSegmentByLabel(getSegmentWithElementValue($jsonDataArray, "G62", "38"), "G6202")["value"],
        "CustomerOrderNotes"    => getElementFromSegmentByLabel(getSegmentWithElementValue($jsonDataArray, "NTE", "INT"), "NTE02")["value"],
        "DeliveryNotes"         => getElementFromSegmentByLabel(getSegmentWithElementValue($jsonDataArray, "NTE", "DEL"), "NTE02")["value"],
        "Carrier"               => $carrier,
        "TotalCustomerOrderQty" => getElementBySegmentNameAndLabel($jsonDataArray, "W76", "W7601")["value"]
    );

    return $customerOrderDataArray;
}

function getCarrierForCustomerOrder($jsonDataArray)
{
    $carrier = getElementBySegmentNameAndLabel($jsonDataArray, "W66", "W6605");

    if ($carrier == false) {
        $carrier = getElementBySegmentNameAndLabel($jsonDataArray, "W66", "W6610");
    }

    if ($carrier != false) {
        $carrier = $carrier["value"];
    }

    return $carrier;
}

function getCustomerOrderDetailsArray($jsonDataArray)
{
    $customerOrderDetailsArray = array();
    $inDetailsCheck = false;

    foreach ($jsonDataArray as $segment) {
        if ($segment["segment"] == "W76") {
            break;
        }

        // If we are in the beginning of a details section
        if ($segment["segment"] == "LX") {
            $inDetailsCheck = true;

            $customerOrderDetailEntry = array(
                "LineNum" => "",
                "Qty" => "",
                "PartNum" => "",
                "ShipToPartNum" => "",
                "POLine" => "",
            );

            $customerOrderDetailEntry["LineNum"] = getElementFromSegmentByLabel($segment, "LX01")["value"];
        }

        // We should only be checking for these if we are in the details section
        if ($inDetailsCheck) {
            if ($segment["segment"] == "W01") {
                $customerOrderDetailEntry["Qty"] = getElementFromSegmentByLabel($segment, "W0101")["value"];
                $customerOrderDetailEntry["PartNum"] = getElementFromSegmentByLabel($segment, "W0105")["value"];
    
                $shipToPartNum = getElementFromSegmentByLabel($segment, "W0107");
    
                if ($shipToPartNum == false) {
                    // If the value is false, then just set it to false in the array
                    $customerOrderDetailEntry["ShipToPartNum"] = $shipToPartNum;
                } else {
                    $customerOrderDetailEntry["ShipToPartNum"] = $shipToPartNum["value"];
                }
            }
    
            // If we are at the end of a details section
            if ($segment["segment"] == "N9") {
                $inDetailsCheck = false;
                
                if (getElementFromSegmentByLabel($segment, "N901")["value"] == "LI") {
                    $customerOrderDetailEntry["POLine"] = getElementFromSegmentByLabel($segment, "N902")["value"];
                }
    
                array_push($customerOrderDetailsArray, $customerOrderDetailEntry);
            }
        }
    }

    return $customerOrderDetailsArray;
}