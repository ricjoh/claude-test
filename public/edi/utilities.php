<?php

function getValueByElement($data, $segment, $element){ //doesn't work for duplicated segments
    $element = $element - 1;
    $segmentId = array_search($segment, array_column($data, 'segment'));
    return $data[$segmentId]->elements[$element]->value;
}

// - Robert
// Should work for duplicate segments.
// TODO: Refactor - This function is not currently working, DO NOT USE!!!
function getValueArrayByElement($jsonData, $segment, $element)
{
    $element -= 1;

    $segmentsArray = array();
    $valueArray = array();
    $segmentDataArray = array_column($jsonData, 'segment');
    
    if (!empty($segmentDataArray)) {
        foreach ($segmentDataArray as $segmentData) {
            if ($segment == $segmentData) {
                array_push($segmentsArray, $segmentData);
            }
        }

        if (!empty($segmentsArray)) {
            foreach ($segmentsArray as $segmentPart) {
                array_push($valueArray, $jsonData[$segmentPart]->elements[$element]->value);
            }
        }
    }

    return $valueArray;
}

function x12ToArray($filepath){
    $file = fopen($filepath,"r");
    $mainArray = array();

    $counter = 1;
    if (count(file($file)) > 1){
        while(!feof($file))
        {
            $thisLine = explode("*", fgets($file));
            $thisKey = $thisLine[0];
            $thisLine = array_slice($thisLine, 1);

            $arrayGroup = array();
            $arrayGroup['elements'] = $thisLine;
            $arrayGroup['segment'] = $thisKey;
            $arrayGroup['line'] = $counter;

            array_push($mainArray, $arrayGroup);

            $counter++;
        }
    } else {
        while(!feof($file))
        {
            $wholeThing = explode("~", fgets($file));

            foreach($wholeThing as $piece){
                $thisLine = $piece;
                //bunch of duplicated code that can be cleaned up
                $thisKey = $thisLine[0];
                $thisLine = array_slice($thisLine, 1);

                $arrayGroup = array();
                $arrayGroup['elements'] = $thisLine;
                $arrayGroup['segment'] = $thisKey;
                $arrayGroup['line'] = $counter;

                array_push($mainArray, $arrayGroup);

                $counter++;
            }
        }
    }

    $identifierCodeId = array_search('ST', array_column($mainArray, 'segment'));
    $identifierCode = $mainArray[$identifierCodeId]['elements'][0];
    require 'dictionaries/' . $identifierCode . '.php';

    foreach($mainArray as $index => $elementArray){
        foreach(array_keys($dictionary) as $dictionarySection){
            if (array_key_exists($elementArray['segment'], $dictionary[$dictionarySection])){
                $mainArray[$index]['level'] = $dictionarySection;
                $mainArray[$index]['name'] = $dictionary[$dictionarySection][$elementArray['segment']]['name'];

                foreach($elementArray['elements'] as $elementIndex => $element){
                    $mainArray[$index]['elements'][$elementIndex] = array(
                        'label' => $elementArray['segment'] . sprintf('%02d', $elementIndex + 1),
                        'value' => $element,
                        'name' => $dictionary[$dictionarySection][$elementArray['segment']]['elements'][$elementIndex]
                    );
                }
            }
        }
    }

    fclose($file);

    return $mainArray;
}