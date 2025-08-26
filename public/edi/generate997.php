<?php

function generate997($json, $EDIKey){
    $data = json_decode($json);

    $filename = "/web/data/tracker.oshkoshcheese.com/x12-data/{$EDIKey}/997-for-" . getValueByElement($data, 'ST', 1) . "-" . time() . ".x12";
    $myfile = fopen($filename, "w") or die("Unable to open file!");

    $keys997 = Array("ISA", "GS", "GE", "IEA");

    foreach($data as $line){
        if (in_array($line->segment, $keys997)){
            if ($line->segment == "ISA"){ //swap receiver/sender
                $segmentId = array_search('ISA', array_column($data, 'segment'));
                $tempISA05 = getValueByElement($data, 'ISA', 5);
                $tempISA06 = getValueByElement($data, 'ISA', 6);
                $tempISA07 = getValueByElement($data, 'ISA', 7);
                $tempISA08 = getValueByElement($data, 'ISA', 8);
                $data[$segmentId]->elements[4]->value = $tempISA07;
                $data[$segmentId]->elements[5]->value = $tempISA08;
                $data[$segmentId]->elements[6]->value = $tempISA05;
                $data[$segmentId]->elements[7]->value = $tempISA06;
            }

            if ($line->segment == "GS"){ //swap receiver/sender
                $segmentId = array_search('GS', array_column($data, 'segment'));
                $tempGS02 = getValueByElement($data, 'GS', 2);
                $tempGS03 = getValueByElement($data, 'GS', 3);
                $data[$segmentId]->elements[1]->value = $tempGS03;
                $data[$segmentId]->elements[2]->value = $tempGS02;
            }

            if ($line->segment == "GE"){
                fwrite($myfile, "ST*997*" . getValueByElement($data, 'ST', 2));
                fwrite($myfile, "~\n");

                fwrite($myfile, "AK1*" . getValueByElement($data, 'GS', 1) ."*" . getValueByElement($data, 'GS', 6));
                fwrite($myfile, "~\n");
                fwrite($myfile, "AK2*" . getValueByElement($data, 'ST', 1) . "*" . getValueByElement($data, 'ST', 2));
                fwrite($myfile, "~\n");
                fwrite($myfile, "AK5*A");
                fwrite($myfile, "~\n");
                fwrite($myfile, "AK9*A*1*1*1");
                fwrite($myfile, "~\n");

				fwrite($myfile, "SE*6*" . getValueByElement($data, 'ST', 2));
            	fwrite($myfile, "~\n");
            }

            // print_r($line);
            fwrite($myfile, $line->segment);
			$written = false;
            foreach($line->elements as $element){
                fwrite($myfile, "*");
                fwrite($myfile, $element->value);
				$written = true;
            }
            if ( $written ) fwrite($myfile, "~\n");
        }
    }

    fclose($myfile);
    return $filename;
}
