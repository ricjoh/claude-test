<?php

/*******************************************************************************
Document Name: Advanced Ship Notice/Manifest (ASN)
Documentation: https://www.dandh.com/docs/EDI_Guides%5CCustomer%5CImplementation%20Guide%20856,%204010.pdf
*******************************************************************************/

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
        "BSN"  => array(
            "name" => "Beginning Segment for Ship Notice",
            "elements" => array(
                "Transaction Set Purpose Code",
                "Shipment Identification",
                "Date",
                "Time"
            )
        )
    ),
    "detail" => array(
        "HL" => array(
            "name" => "Hierarchical Level - Shipment Level",
            "elements" => array(
                "Hierarchical ID Number",
                "Hierarchical Parent ID Number",
                "Hierarchical Level Code"
            )
        ),
        "MEA" => array(
            "name" => "Measurements",
            "elements" => array(
                "Measurement Reference ID Code",
                "Measurement Qualifier",
                "Measurement Value",
                "Composite Unit of Measure",
                "Unit or Basis for Measurement Code"
            )
        ),
        "TD1" => array(
            "name" => "Carrier Details (Quantity and Weight)",
            "elements" => array(
                "Packaging Code",
                "Lading Quantity"
            )
        ),
        "TD5" => array(
            "name" => "Carrier Details (Routing Sequence/Transit Time)",
            "elements" => array(
                "Routing Sequence Code",
                "Identification Code Qualifier",
                "Identification Code",
                "Transportation Method/Type Code"
            )
        ),
        "REF" => array(
            "name" => "Reference Identification",
            "elements" => array(
                "Reference Identification Qualifier",
                "Reference Identification"
            )
        ),
        "DTM" => array(
            "name" => "Date/Time Reference",
            "elements" => array(
                "Date/Time Qualifier",
                "Date",
                "Time",
                "Time"
            )
        ),
        "FOB" => array(
            "name" => "F.O.B. Related Instructions",
            "elements" => array(
                "Shipment Method of Payment"
            )
        ),
        "N1" => array(
            "name" => "Name",
            "elements" => array(
                "Entity Identifier Code",
                "Name",
                "Identification Code Qualifier",
                "Identification Code"
            )
        ),
        "LIN" => array(
            "name" => "Item Identification",
            "elements" => array(
                "Assigned Identification",
                "Product/Service ID Qualifier",
                "Product/Service ID"
            )
        ),
        "SN1" => array(
            "name" => "Item Detail (Shipment)",
            "elements" => array(
                "Assigned Identification",
                "Number of Units Shipped",
                "Unit or Basis for Measurement Code",
                "Quantity Shipped to Date"
            )
        ),
        "PRF" => array(
            "name" => "Purchase Order Reference",
            "elements" => array(
                "Purchase Order Number",
                "Release Number"
            )
        ),
        "PID" => array(
            "name" => "Product/Item Description",
            "elements" => array(
                "Item Description Type",
                "Description"
            )
        ),
        "TD1" => array(
            "name" => "Carrier Details (Quantity and Weight)",
            "elements" => array(
                "Packaging Code",
                "Lading Quantity"
            )
        ),
        "REF" => array(
            "name" => "Reference Identification",
            "elements" => array(
                "Reference Identification Qualifier",
                "Reference Identification"
            )
        ),
        "CLD" => array(
            "name" => "Roll Identification",
            "elements" => array(
                "Roll Number Identification",
                "Net Meters",
                "Dyelot Number"
            )
        )
    ),
    "summary" => array(
        "CTT" => array(
            "name" => "Transaction Totals",
            "elements" => array(
                "Number of Line Items",
                "Hash Total"
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