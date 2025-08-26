<?php

function outbound($json){
    $data = json_decode($json);

    $filename = "outboundFiles/997-for-" . $data->heading->ST->ST01->value . "-" . time() . ".x12";
    // $myfile = fopen($filename, "w") or die("Unable to open file!");

    foreach($data as $sectionName => $section){
        foreach($section as $key => $code){
            print($key);

            $index = 0;
            foreach($code as $subKey => $subCode){
                if ($subKey == "name"){
                    continue;
                }

                if (is_array($subCode)){
                    if ($index > 0){
                        print($key);
                    }

                    foreach($subCode as $x){
                        print("*");
                        print(current((array)$x)->value);
                    }
                } else {
                    print("*");
                    print($subCode->value);
                }

                $index++;
            }
        }
    }

    // $txt = "John Doe\n";
    // fwrite($myfile, $txt);
    // $txt = "Jane Doe\n";
    // fwrite($myfile, $txt);

    // fclose($myfile);
}