<?php
//ST = start - tells what (ie 940)
//SE = end - lines from ST to SE inclusive
//IS and GS - like from, to of email

require 'utilities.php';

$fileName = "samples/OSHKOSH SAMPLE 943 - 1.x12";

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



    // print_r($mainArray);

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

    // print_r($mainArray);
    $finalJson = json_encode($mainArray, JSON_PRETTY_PRINT);
    // print($finalJson);
}

// x12ToArray($fileName);

// require 'outbound.php';
// outbound($finalJson);

require 'generate997.php';
$filepath = generate997($finalJson);

require 'db_insert.php';
db_insert($finalJson, $filepath);