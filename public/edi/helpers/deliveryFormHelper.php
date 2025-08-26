<?php

require 'EDIJsonParser.php';

// 943 Form

function getDeliveryTableDataArray($jsonDataArray)
{
    if (empty($jsonDataArray)) {
        return $jsonDataArray;
    }

    $shipmentSegment = getSegmentWithElementValue($jsonDataArray, "N9", "SI");
    $shipmentID = getElementFromSegmentByLabel($shipmentSegment, "N902")["value"];

    $carrierElements = getElementsBySegmentName($jsonDataArray, "W27");
    $carrierIsOptional = elementsContainValue($carrierElements, "O");
    $carrier = getElementBySegmentNameAndLabel($jsonDataArray, "W27", "W2702");

    if ($carrierIsOptional == false && $carrier == false) {
        // Error - We need a carrier
    }

    if ($carrier == false) {
        $carrier = '';
    }

    // Column_name => data
    $deliveryTableData = array(
        "OrderNum"              => getElementBySegmentNameAndLabel($jsonDataArray, "W06", "W0602")["value"],
        "ShipDate"              => getElementBySegmentNameAndLabel($jsonDataArray, "W06", "W0603")["value"], 
        "Sender"                => getElementFromSegmentByLabel(getSegmentWithElementValue($jsonDataArray, "N1", "SF"), "N102")["value"],
        "Warehouse"             => getElementFromSegmentByLabel(getSegmentWithElementValue($jsonDataArray, "N1", "ST"), "N104")["value"],
        "Reference"             => getElementFromSegmentByLabel(getSegmentWithElementValue($jsonDataArray, "N1", "F8"), "N104")["value"],
        "ShipmentID"            => $shipmentID, 
        "Carrier"               => $carrier,
        "TotalShippedQty"       => getElementBySegmentNameAndLabel($jsonDataArray, "W03", "W0302")["value"],
        "TotalShippedWeight"    => getElementBySegmentNameAndLabel($jsonDataArray, "W03", "W0302")["value"]
    );

    return $deliveryTableData;
}

function getDeliveryDetailsArray($jsonDataArray) 
{
    $deliveryDetailsArray = array();

    // Start with this value being true because nothing happens until hitting a "W04" segment anyways
    $inDeliveryDetail = false;

    $n9HelperArray = array(
        "LI" => "LineNum",
        "LT" => "CustomerLot",
        "LV" => "LicensePlate"
    );

    foreach ($jsonDataArray as $segment) {
        // If we have reached the segment after all of the details then we are done.
        if ($segment["segment"] == "W03") {
            break;
        }

        if ($segment["segment"] == "W04") {
            $inDeliveryDetail = true;
            // We are in a a new detail section, so we make an array to hold the data
            $deliveryDetailEntryArray = array(
                "LineNum"       => "",
                "Qty"           => "",
                "QtyUOM"        => "",
                "UPC"           => "",
                "CustomerLot"   => "",
                "LicensePlate"  => "",
                "ExpDate"       => "",
                "NetWeight"     => "",
                "WeightUOM"     => "",
            );

            $deliveryDetailEntryArray["Qty"] = getElementFromSegmentByLabel($segment, "W0401")["value"];
            $deliveryDetailEntryArray["QtyUOM"] = getElementFromSegmentByLabel($segment, "W0402")["value"];
            $deliveryDetailEntryArray["UPC"] = getElementFromSegmentByLabel($segment, "W0403")["value"];
        }

        if ($inDeliveryDetail) {
            if ($segment["segment"] == "N9") {
                foreach ($n9HelperArray as $key => $value) {
                    if (elementsContainValue($segment["elements"], $key)) {
                        $deliveryDetailEntryArray[$value] = getElementFromSegmentByLabel($segment, "N902")["value"];
                    }
                }
            }
    
            // There is only one of these per detail so I should be able to pull the data inside this block
            if ($segment["segment"] == "W20") {
                $inDeliveryDetail = false;
    
                $deliveryDetailEntryArray["NetWeight"] = getElementFromSegmentByLabel($segment, "W2004")["value"];
    
                // We add to the array in the W20 section because that marks the end of a detail
                array_push($deliveryDetailsArray, $deliveryDetailEntryArray);
            }
        }
    }

    return $deliveryDetailsArray;
}

/*
Error if we don't have the data listed below
weight
w0401 quantity
w2004 netweight
w0301 total cases shipped should be total of quantity number
w0302 total weight - should be greater than the netweight x number of lines

documents
    if there's an 'O' they may not send it to us

*/