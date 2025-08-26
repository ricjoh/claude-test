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
                "Interchange Receiver ID",
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
        ),
        "W06" => array(
            "name" => "Warehouse Shipment Identification",
            "elements" => array(
                "Reporting Code",
                "Depositor Order Number",
                "Shipment Date"
            )
        ),
        "N1" => array(
            "name" => "Name - Depositor",
            "elements" => array(
                "Entity Identifier Code",
                "Name",
                "Identification Code Qualifier",
                "Identification Code"
            )
        ), 
        "N3" => array(
            "name" => "Address Information",
            "elements" => array(
                "Address Information",
                "Address Information"
            )
        ),
        "N4" => array(
            "name" => "Geographic Location",
            "elements" => array(
                "City Name",
                "State or Province Code",
                "Postal Code"
            )
        ),
        "N9" => array(
            "name" => "Reference Identification",
            "elements" => array(
                "Reference Identification Qualifier",
                "Reference Identification"
            )
        ),
        "G62" => array(
            "name" => "Date/Time",
            "elements" => array(
                "Date Qualifier",
                "Date"
            )
        ),
        "NTE" => array(
            "name" => "Note/Special Instruction",
            "elements" => array(
                "Note Reference Code",
                "Description"
            )
        ),
        "W27" => array(
            "name" => "Carrier Detail",
            "elements" => array(
                "Transportation Method/Type Code",
                "Standard Carrier Alpha Code",
                "Equipment Initial",
                "Equipment Number"
            )
        )
    ),
    "detail" => array(
        "W04" => array(
            "name" => "Item Detail Total",
            "elements" => array(
                "Number of Units Shipped",
                "Unit or Basis for Measurement Code",
                "U.P.C. Case Code",
                "Product/Service ID Qualifier",
                "Product/Service ID",
                "Product/Service ID Qualifier",
                "Product/Service ID",
                "Inbound Condition Hold Code",
                "Product/Service ID Qualifier",
                "Product/Service ID"
            )
        ),
        "W20" => array(
            "name" => "Line-Item Detail - Miscellaneous",
            "elements" => array(
                "Weight",
                "Weight Qualifier",
                "Weight Unit Code"
            )
        )
    ),
    "summary" => array(
        "W03" => array(
            "name" => "Total Shipment Information",
            "elements" => array(
                "Number of Units Shipped",
                "Weight",
                "Unit or Basis for Measurement Code"
            )
        ),
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