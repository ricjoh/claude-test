DROP TABLE IF EXISTS `EDIDocument`;
CREATE TABLE `EDIDocument` (
  `DocID` MEDIUMINT NOT NULL AUTO_INCREMENT,
  `EDIKey` VARCHAR(50) NOT NULL,
  `Transaction` VARCHAR(5) NOT NULL,
  `ControlNumber` VARCHAR(25) NOT NULL,
  `Incoming` TINYINT(1) NOT NULL,
  `DocISAID` VARCHAR(100) NOT NULL,
  `DocGSID` VARCHAR(100) NOT NULL,
  `Status` VARCHAR(64) DEFAULT NULL,
  `SecondaryStatus` VARCHAR(64) DEFAULT NULL,
  `X12FilePath` VARCHAR(255) DEFAULT NULL,
  `JsonObject` MEDIUMTEXT DEFAULT NULL,
  `CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`DocID`),
  UNIQUE KEY `DocID` (`DocID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Delivery`;
CREATE TABLE `Delivery` (
	`DeliveryID` MEDIUMINT NOT NULL AUTO_INCREMENT,
	`EDIDocID` MEDIUMINT NOT NULL, -- key from EDIDocument
	`StatusPID` VARCHAR(64) NOT NULL DEFAULT '51398435-A4A6-4C41-ACC8-45F6D569057B', -- Pending
	`OrderNum` VARCHAR(64) DEFAULT NULL, -- W0602
	`ShipDate` DATETIME(6) NOT NULL, -- W0603
	`Sender` VARCHAR(64) DEFAULT NULL, -- N102	Name	Great Lakes Cheese - Plymouth
	`ShipFromID` VARCHAR(64) DEFAULT NULL, -- N104 SF ShipFromID
	`Warehouse` VARCHAR(64) DEFAULT NULL, -- N104 ST Identification Code	OSHKOSHWAREHOUSECODE Oshkosh/Plymouth?
	`Reference` VARCHAR(64) DEFAULT NULL, -- N9 F8 Originator reference
	`ShipmentID` VARCHAR(64) DEFAULT NULL, -- N9 SI Shipment ID #
	`Carrier` VARCHAR(16) DEFAULT NULL, -- N9 T N902 SCAC code
	`TotalShippedQty` MEDIUMINT NOT NULL, -- W0301
	`TotalShippedWeight` DECIMAL(18,2) NOT NULL DEFAULT 0.00, -- W0302
	`CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`UpdateDate` DATETIME(6) DEFAULT NULL,
	`ReceivedDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`DeliveryID`),
  UNIQUE KEY `DeliveryID` (`DeliveryID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `DeliveryDetail`;
CREATE TABLE `DeliveryDetail` (
	`DeliveryDetailID` MEDIUMINT NOT NULL AUTO_INCREMENT,
	`DeliveryID` MEDIUMINT NOT NULL, -- key from Delivery
	`EDIDocID` MEDIUMINT NOT NULL, -- key from EDIDocument
	`StatusPID` VARCHAR(64) NOT NULL DEFAULT '51398435-A4A6-4C41-ACC8-45F6D569057B', -- Pending
	`LineNum` SMALLINT NOT NULL, -- N9 LI
	`Qty` MEDIUMINT NOT NULL, -- W0401
	`QtyUOM` VARCHAR(16) DEFAULT 'CA', -- (W0402)
	`PartNum` VARCHAR(16) DEFAULT NULL, -- W0405
	`CustomerLot` VARCHAR(64) NOT NULL, -- N9 LT
	`LicensePlate`  VARCHAR(64) NOT NULL, -- N9 LV
	`ExpDate` DATETIME(6) DEFAULT NULL,
	`NetWeight` DECIMAL(18,2) NOT NULL DEFAULT 0.00, -- W2004
	`WeightUOM` VARCHAR(16) DEFAULT 'LB',
	`CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`DeliveryDetailID`),
  UNIQUE KEY `DeliveryID` (`DeliveryDetailID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `DeliveryDetailReceipt`;
CREATE TABLE `DeliveryDetailReceipt` (
	`ReceiptID` MEDIUMINT NOT NULL AUTO_INCREMENT,
	`LicensePlate`  VARCHAR(64) NOT NULL,
	`DeliveryDetailID` MEDIUMINT NOT NULL, -- key from DeliveryDetail
	`ReceivedQty` MEDIUMINT NOT NULL,
	`LotID` VARCHAR(64) DEFAULT NULL, -- what lot did this get put into?
	`EDISent` MEDIUMINT, -- BOOLEAN? ID of another table? Timestamp?
	`ReceivedDate` DATETIME(6) DEFAULT NULL,
	`CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`ReceiptID`),
  UNIQUE KEY `ReceiptID` (`ReceiptID`),
  UNIQUE KEY `LicensePlate` (`LicensePlate`),
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `CustomerOrder`;
CREATE TABLE `CustomerOrder` (
	`CustomerOrderID` MEDIUMINT NOT NULL AUTO_INCREMENT,
	`EDIDocID` MEDIUMINT NOT NULL, -- key from EDIDocument
	`Status` VARCHAR(64) DEFAULT NULL, -- Different from EDI
	`CustomerOrderDate` DATETIME(6) NOT NULL, -- GS04
	`CustomerOrderNum` VARCHAR(64) NOT NULL, --  W502
	`CustPONum` VARCHAR(64) NOT NULL, -- W501 = N  W503 = value
	`ShipToID` VARCHAR(64) NOT NULL, -- N101 = ST N104 = value
	`ShipToPONum` VARCHAR(64) DEFAULT NULL, -- N901 = CO, N902  value
	`ShipToMasterBOL` VARCHAR(64) DEFAULT NULL, -- N901 = MB, N902  value, if exists
	`ShipToName` VARCHAR(64) DEFAULT NULL, -- N201
	`ShipToAddr1` VARCHAR(64) DEFAULT NULL, -- N301
	`ShipToAddr2` VARCHAR(64) DEFAULT NULL, -- N302 if exists
	`ShipToCity` VARCHAR(64) DEFAULT NULL, -- N401
	`ShipToState` VARCHAR(64) DEFAULT NULL, -- N402
	`ShipToZIP` VARCHAR(32) DEFAULT NULL, -- N403
	`ShipToCountry` VARCHAR(8) DEFAULT NULL, -- N404 if set
	`ShipByDate` DATETIME(6) DEFAULT NULL, -- G62 = 38, G602 = value
	`CustomerOrderNotes` TEXT DEFAULT NULL, -- NTE01 = INT, NTE02 = value
	`DeliveryNotes` TEXT DEFAULT NULL, -- NTE01 = DEL, NTE02 = value
	`Carrier` VARCHAR(16) DEFAULT NULL, -- W6605 or W6610 if either is set.
	`TotalCustomerOrderQty` MEDIUMINT NOT NULL, -- W7601
	`OfferID` VARCHAR(64) DEFAULT NULL, -- once processed to Offer Pending
	`CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`CustomerOrderID`),
  UNIQUE KEY `CustomerOrderID` (`CustomerOrderID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `CustomerOrderDetail`;
CREATE TABLE `CustomerOrderDetail` (
	`CustomerOrderDetailID` MEDIUMINT NOT NULL AUTO_INCREMENT,
	`CustomerOrderID` MEDIUMINT NOT NULL, -- key from CustomerOrder
	`EDIDocID` MEDIUMINT NOT NULL, -- key from EDIDocument
	`LineNum` SMALLINT NOT NULL, -- LX01
	`Qty` MEDIUMINT NOT NULL,  -- W0101
	`QtyUOM` VARCHAR(16) DEFAULT 'CA', -- W0102
	`PartNum` VARCHAR(16) NOT NULL, -- W0105
	`ShipToPartNum` VARCHAR(64) DEFAULT NULL, -- W0107 if set
	`POLine`  VARCHAR(64) NOT NULL, -- N901 = LI N902 = value
	`EDIOfferItemID` VARCHAR(64) DEFAULT NULL, -- once processed to Offer Pending
	`CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`CustomerOrderDetailID`),
  UNIQUE KEY `CustomerOrderDetailID` (`CustomerOrderDetailID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- Ric: Don't know if we need all these fields
DROP TABLE IF EXISTS `CustomerShipDetail`;
CREATE TABLE `CustomerShipDetail` (
	`CustomerShipDetailID` MEDIUMINT NOT NULL AUTO_INCREMENT,
	`CustomerOrderDetailID` MEDIUMINT,
	`EDIDocID` MEDIUMINT NOT NULL,
	`QtyShipped` MEDIUMINT NOT NULL,
	`LicensePlate` VARCHAR(64) DEFAULT NULL,
	`ShippedDate` DATETIME(6) DEFAULT NULL,
	`CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`CustomerOrderDetailID`),
  UNIQUE KEY `CustomerOrderDetailID` (`CustomerOrderDetailID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `OrderOfferDetailMap`;
CREATE TABLE `OrderOfferDetailMap` (
	`CustomerOrderDetailID` MEDIUMINT NOT NULL AUTO_INCREMENT,
	`EDIDocID` MEDIUMINT NOT NULL,
	`EDIOfferItemID` VARCHAR(64) DEFAULT NULL, -- once processed to Offer Pending
	`CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`CustomerOrderDetailID`),
  UNIQUE KEY `CustomerOrderDetailID` (`CustomerOrderDetailID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



ALTER TABLE `Customer` ADD COLUMN EDIFlag  TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `Customer` ADD COLUMN EDIISAID VARCHAR(100);
ALTER TABLE `Customer` ADD COLUMN EDIGSID  VARCHAR(100);
ALTER TABLE `Customer` ADD COLUMN EDIKey   VARCHAR(50);

ALTER TABLE `Vendor` ADD COLUMN EDIFlag  TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `Vendor` ADD COLUMN EDIISAID VARCHAR(100);
ALTER TABLE `Vendor` ADD COLUMN EDIGSID  VARCHAR(100);
ALTER TABLE `Vendor` ADD COLUMN EDIKey   VARCHAR(50);

UPDATE `Vendor`
SET EDIFlag = 1, EDIISAID = '018219808', EDIGSID = '4408342500', EDIKey = 'GLC'
WHERE VendorID = 'D9BB928A-1934-4712-A47A-987C32581A1C';

ALTER TABLE `Factory` ADD COLUMN EDIFlag  TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `Factory` ADD COLUMN EDIISAID VARCHAR(100) DEFAULT NULL;
ALTER TABLE `Factory` ADD COLUMN EDIGSID  VARCHAR(100) DEFAULT NULL;
ALTER TABLE `Factory` ADD COLUMN EDIKey   VARCHAR(50) DEFAULT NULL;

UPDATE `Factory`
SET EDIFlag = 1, EDIISAID = '018219808', EDIGSID = '4408342500', EDIKey = 'GLC'
WHERE FactoryID = '8A99F27C-27DA-4F2A-AEBE-21FCB2DB973D';

UPDATE `Customer`
SET EDIFlag = 1, EDIISAID = '018219808', EDIGSID = '4408342500', EDIKey = 'GLC'
WHERE CustomerID = '62B545A4-9C0D-430C-A88C-5CB37CC8EBEA';

-- FIX: views/billoflading/edit.phtml for barewords
-- FIX: views/offer/printmanifest.phtml for barewords


/*
-----------------------------------------------
*/
-- Do we need both of these? Maybe Just Vat  Status: Offer Pending, Offer Created, Shipped
ALTER TABLE `CustomerOrderDetail` ADD COLUMN EDISuggestedLotID VARCHAR(64) DEFAULT NULL;
ALTER TABLE `CustomerOrderDetail` ADD COLUMN EDISuggestedVatID VARCHAR(64) DEFAULT NULL;
ALTER TABLE `CustomerOrder` ADD COLUMN OfferID VARCHAR(64) DEFAULT NULL;
ALTER TABLE `Vat` ADD COLUMN `ExpDate` DATETIME(6) DEFAULT NULL, -- ask
ALTER TABLE `Lot` ADD COLUMN `StatusPID` VARCHAR(64) DEFAULT '103A03A1-9B62-4FD1-BFDD-32C2C0026415';
ALTER TABLE `Lot` ADD COLUMN `DeliveryDetailID` MEDIUMINT DEFAULT NULL;

INSERT INTO ParameterGroup
	(ParameterGroupID, Name, Heading1, Heading2, PrimarySortField, ReadOnly,
	CreateDate, CreateID)
VALUES
	('9173DEE9-5907-4211-85CC-7CE51FF170F7','CustomerOrderStatus',
	 'Customer Order Status', 'Status', 1, 1, '2020-05-31 00:00:00',
	 '00000000-0000-0000-0000-000000000000');

ALTER TABLE `BillOfLading` ADD COLUMN StatusPID VARCHAR(64) DEFAULT '1E732327-AD3E-42C1-9602-F505B3A75E7E'; -- 'Created'
UPDATE `BillOfLading` SET StatusPID = '124B3F46-9F63-4256-AC3A-B8CB00656CC1' WHERE OfferID <> 'C478D7C5-25FC-439B-A5A4-A155493ABC08';

INSERT INTO ParameterGroup
	(ParameterGroupID, Name, Heading1, Heading2, PrimarySortField, ReadOnly,
	CreateDate, CreateID)
VALUES
	('EFFCB53D-3929-4E0D-BD40-5E0ECC45D251','BOLStatus',
	 'Bill of Lading Status', 'Status', 1, 1, '2020-05-31 00:00:00',
	 '00000000-0000-0000-0000-000000000000');

INSERT INTO ParameterGroup
	(ParameterGroupID, Name, Heading1, Heading2, PrimarySortField, ReadOnly,
	CreateDate, CreateID)
VALUES
	('5F7730A1-AF76-4E3D-9B62-35872908944E','LotStatus',
	 'Lot Status', 'Status', 1, 1, '2020-06-02 00:00:00',
	 '00000000-0000-0000-0000-000000000000');

INSERT INTO ParameterGroup
	(ParameterGroupID, Name, Heading1, Heading2, PrimarySortField, ReadOnly,
	CreateDate, CreateID)
VALUES
	('E7D27442-F7B9-4ADD-84FC-B56D32AADBE6','DeliveryStatus',
	 'Delivery Status', 'Status', 1, 1, '2020-06-02 00:00:00',
	 '00000000-0000-0000-0000-000000000000');


ADD   `SecondaryStatus` VARCHAR(64) DEFAULT NULL, to EDIDocument

/*
-- DAN

There's a VERY small chance someone at some time will
send more than one ST per GS, but we'll cross that bridge if it comes
I've never seen it, but it's legal.

Create a connection to MySQL tracker_oshkoshcheese_com
host     = pluto.xymmetrix.com
username = oshcheese
password = copy2cluj
dbname   = tracker_oshkoshcheese_com
Use MySQLi. I created a script here that works:
\\pluto.xymmetrix.com\web\html\edi-oshkosh\db_connect.php

Update the database when you process a document.

Example:
When and 943 comes in:

DocID = auto-increments
EDIKey = 'GLC' (technically because ISA sender ID matches EDIISAID from Customer, but hard code it for now)
Transaction = '943' (from ST01)
ControlNumber = '0594'  (from ST02)
Incoming = 1 (0 = outgoing)
DocISAID = '006253835DFP' (from ISA06)
DocGSID = '006253835DFP' (from GS02)
Status = 'New'
X12FilePath = '/web/html/edi-oshkosh/...' (absolute file path)
JsonObect = json_encode( <translated object data> )


When you create a 997:

DocID = auto-increments
EDIKey = 'GLC'
Transaction = '997' (from ST01)
ControlNumber = '0594'  (from ST02)
Incoming = 0 (0 = outgoing)
DocISAID = '006253835DFP' (from ISA07) (Incoming changes the meaning of this. We never need to record our own IDs.)
DocGSID = '006253835DFP' (from GS03) (Incoming changes the meaning of this. We never need to record our own IDs.)
Status = 'New'
X12FilePath = '/web/html/edi-oshkosh/...' (absolute file path)
JsonObect = json_encode( <translated object data> )

Future: When you go to create and outgoing document, the data to translate will be in
the JsonObject and you can json_decode and build it from there.

Once all the above is done, let's talk about buiding deictionaries for outbound 944 and 945


-- ROBERT

Make the EDIKey for Greate Lakes Cheese = 'GLC'
*/

/* INSERT INTO EDIDocument
(EDIKey, Transaction, ControlNumber, Incoming, DocISAID, DocGSID, Status, X12FilePath)
VALUES ('GLC', '943', '0001', 1, '006253835DFP', '006253835DFP', 'New','/web/html/edi-oshkosh/samples/943_GL.x12' ); */