<?php

function getElementsBySegmentName($segmentArray, $segmentName)
{
    // If the segmentArray is not empty
    if (!empty($segmentArray)) {
        foreach ($segmentArray as $segment) {
            if ($segment["segment"] == $segmentName) {
                return $segment["elements"];
            }
        }
    }

    return false;
}

function getSegmentBySegmentName($segmentArray, $segmentName)
{
    // If the segmentArray is not empty
    if (!empty($segmentArray)) {
        foreach ($segmentArray as $segment) {
            if ($segment["segment"] == $segmentName) {
                return $segment;
            }
        }
    }

    return false;
}

function getElementBySegmentNameAndLabel($segmentArray, $segmentName, $labelName)
{
    $segment = getSegmentBySegmentName($segmentArray, $segmentName);

    foreach ($segment["elements"] as $element) {
        if ($element["label"] == $labelName) {
            return $element;
        }
    }

    return false;
}

// Gets a specific segment that contains a specific element value
function getSegmentWithElementValue($segmentArray, $segmentName, $elementValue)
{
    foreach ($segmentArray as $segment) {
        if ($segment["segment"] == $segmentName) {
            if (elementsContainValue($segment["elements"], $elementValue)) {
                return $segment;
            }
        }
    }

    return false;
}

function getElementFromSegmentByLabel($segment, $labelName) {
    foreach ($segment["elements"] as $element) {
        if ($element["label"] == $labelName) {
            return $element;
        }
    }

    return false;
}

// Checks an array of elements to see if they contain a certain value
function elementsContainValue($elements, $value)
{
    foreach ($elements as $element) {
        if ($element["value"] == $value) {
            return true;
        }
    }

    return false;
}

// Returns the form type. ie - 940, 943, etc...
function getFormType($segmentArray)
{
    return getElementBySegmentNameAndLabel($segmentArray, "ST", "ST01")["value"];
}