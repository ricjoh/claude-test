<?php

$dictionary = array(
    "heading" => array(
        "ISA" => array(
            "name" => "Interchange Control Header",
            "elements" => array(
                "Authorization Information Qualifier",
                "Authorization Information",
                "Security Information Qualifier",
                "Security Information",
                "Interchange ID Qualifier",
                "Interchange Sender ID",
                "Interchange ID Qualifier",
                "Interchange Reciever ID",
                "Interchange Date",
                "Interchange Time",
                "Interchange Control Standards Identifier",
                "Interchange Control Version Number",
                "Interchange Control Number",
                "Acknowledgment Requested",
                "Usage Indicator",
                "Component Element Separator"
            )
        ),
        "GS" => array(
            "name" => "Functional Group Header",
            "elements" => array(
                "Functional Identifier Code",
                "Application Sender's Code",
                "Application Receiver's Code",
                "Date",
                "Time",
                "Group Control Number",
                "Responsible Agency Code",
                "Version/Release/Industry Identifier Code"
            )
        ),
        "ST"  => array(
            "name" => "Transaction Set Header",
            "elements" => array(
                "Transaction Set Identifier Code",
                "Transaction Set Control Number"
            )
        )
    ),
    "detail" => array(
        "AK1" => array(
            "name" => "Functional Group Response Header",
            "elements" => array(
                "Functional Identifier Code",
                "Group Control Number"
            )
        ),
        "AK2" => array(
            "name" => "Transactino Set Response Header",
            "elements" => array(
                "Transaction Set Identifier Code",
                "Transaction Set Control Number"
            )
        ),
        "AK9" => array(
            "name" => "Functional Group Response Trailer",
            "elements" => array(
                "Functional Group Acknowledge Code",
                "Number of Transaction Sets Included",
                "Number of Recieved Transaction Sets",
                "Number of Accepted Transaction Sets",
                "Functional Group Syntax Error Code",
                "Functional Group Syntax Error Code",
                "Functional Group Syntax Error Code",
                "Functional Group Syntax Error Code",
                "Functional Group Syntax Error Code"
            )
        )
    ),
    "summary" => array(
        "SE" => array(
            "name" => "Transaction Set Trailer",
            "elements" => array(
                "Number of Included Segments",
                "Transaction Set Control Number"
            )
        ),
        "GE" => array(
            "name" => "Functional Group Trailer",
            "elements" => array(
                "Number of Transaction Sets Included",
                "Group Control Number"
            )
        ),
        "IEA" => array(
            "name" => "Interchange Control Trailer",
            "elements" => array(
                "Number of Included Functional Groups",
                "Interchange Control Number"
            )
        )
    )
);