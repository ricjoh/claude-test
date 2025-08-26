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
        ),
        "W05" => array(
            "name" => "Shipping Order Identification",
            "elements" => array(
                "Order Status Code",
                "Depositor Order Number",
                "Purchase Order Number"
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
        "N2" => array(
            "name" => "Name",
            "elements" => array(
                "Additional Name Information"
            )
        ),
        "N3" => array(
            "name" => "Address Information",
            "elements" => array(
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
        "G61" => array(
            "name" => "Contact",
            "elements" => array(
                "Contact Function Code",
                "Name",
                "Communcation Number Qualifier",
                "Communication Number"
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
        "W66" => array(
            "name" => "Warehouse Carrier Information",
            "elements" => array(
                "Shipment Method of Payment",
                "Transportation Method/Type Code",
                "Routing",
                "Standard Carrier Alpha Code"
            )
        ),
        "W6" => array(
            "name" => "Special Handling Information",
            "elements" => array(
                "Special Handling Code",
                "Special Handling Code",
                "Special Handling Code",
                "Special Handling Code"
            )
        ) 
    ),
    "detail" => array(
        "LX" => array(
            "name" => "Assigned Number",
            "elements" => array(
                "Assigned Number"
            )
        ),
        "W01" => array(
            "name" => "Line Item Detail - Warehouse",
            "elements" => array(
                "Quantity Ordered",
                "Unit or Basis for Measurement Code",
                "U.P.C. Case Code",
                "Product/Service ID Qualifier",
                "Product/Service ID",
                "Product/Service ID Qualifier",
                "Product/Service ID",
                "Product/Service Condition Code",
                "Product/Service ID Qualifier",
                "Product/Service ID"
            )
        ),
        "G69" => array(
            "name" => "Line Item Detail - Description",
            "elements" => array(
                "Free-form Description"
            )
        ),
        "N9" => array(
            "name" => "Reference Identification - Line Number",
            "elements" => array(
                "Reference Identification Qualifier",
                "Reference Identification"
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
        "W76" => array(
            "name" => "Total Shipping Order",
            "elements" => array(
                "Quantity Ordered",
                "Weight",
                "Unit or Basis for Measurement Code",
                "Volume",
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