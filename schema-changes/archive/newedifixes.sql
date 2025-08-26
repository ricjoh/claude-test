DROP TABLE IF EXISTS `OrderOfferDetailMap`;
CREATE TABLE `OrderOfferDetailMap` (
        `OODMapID` MEDIUMINT NOT NULL AUTO_INCREMENT,
        `CustomerOrderDetailID` MEDIUMINT NOT NULL,
        `EDIDocID` MEDIUMINT NOT NULL,
        `OfferItemID` VARCHAR(64) DEFAULT NULL, -- once processed to Pending Offer 
        `CreateDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `UpdateDate` DATETIME DEFAULT NULL,
  PRIMARY KEY (`OODMapID`),
  UNIQUE KEY `OfferItemID` (`OfferItemID`),
  INDEX `CustomerOrderDetailID` (`CustomerOrderDetailID`)	
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- FOR CustomerOrderDetail (CustomerOrderDetailID, EDIDocID, OfferItemID)
-- insert into OrderOfferDetailMap (CustomerOrderDetailID, EDIDocID, OfferItemID)

INSERT INTO OrderOfferDetailMap (CustomerOrderDetailID, EDIDocID, OfferItemID) 
SELECT CustomerOrderDetailID, EDIDocID, OfferItemID FROM CustomerOrderDetail;

ALTER TABLE `CustomerOrderDetail` DROP COLUMN OfferItemID;

ALTER TABLE `CustomerShipDetail` ADD COLUMN `OfferItemID` VARCHAR(64) DEFAULT NULL;
ALTER TABLE `CustomerShipDetail` ADD COLUMN `CustomerShipDetailID` MEDIUMINT NOT NULL AUTO_INCREMENT PRIMARY KEY;
ALTER TABLE `CustomerShipDetail` CHANGE COLUMN `CustomerOrderDetailID` `CustomerOrderDetailID` MEDIUMINT NOT NULL;
ALTER TABLE `CustomerShipDetail` DROP PRIMARY KEY, ADD PRIMARY KEY (`CustomerShipDetailID`);


/* 
DROP TABLE IF EXISTS `CustomerShipDetail`;
CREATE TABLE `CustomerShipDetail` (
	`CustomerShipDetailID` MEDIUMINT NOT NULL AUTO_INCREMENT,
	`CustomerOrderDetailID` MEDIUMINT NOT NULL,
	`EDIDocID` MEDIUMINT NOT NULL, 
	`QtyShipped` MEDIUMINT NOT NULL,  
	`OfferItemID` VARCHAR(64) DEFAULT NULL,
	`LicensePlate` VARCHAR(64) DEFAULT NULL, 
	`ShippedDate` DATETIME(6) DEFAULT NULL,
	`CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`CustomerShipDetailID`),
  INDEX `DetailPlusOfferItem` (`CustomerOrderDetailID`, `OfferItemID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
*/

ALTER TABLE DeliveryDetail CHANGE LicensePlate LicensePlate varchar(64) DEFAULT NULL;
ALTER TABLE DeliveryDetail CHANGE CustomerLot CustomerLot varchar(64) DEFAULT NULL;
ALTER TABLE DeliveryDetail CHANGE ExpDate ExpDate datetime(6) DEFAULT NULL;
ALTER TABLE DeliveryDetailReceipt CHANGE LicensePlate LicensePlate varchar(64) DEFAULT NULL;

SHOW INDEX FROM BillOfLading;
SHOW INDEX FROM Contact;
SHOW INDEX FROM CustomerShipDetail;
SHOW INDEX FROM CustomerOrder;
SHOW INDEX FROM Delivery;
SHOW INDEX FROM Lot;
SHOW INDEX FROM Offer;
SHOW INDEX FROM Parameter;

-- Done on live 2/22/2022:

-- CREATE INDEX `OfferCustomerID` ON `Offer` (`CustomerID`); 
-- CREATE INDEX `OfferCustomerPID` ON `Offer` (`CustomerPID`);
-- CREATE INDEX `OfferTermsPID` ON `Offer` (`TermsPID`);
CREATE INDEX `BillOfLadingStatusPID` ON `BillOfLading` (`StatusPID`);
CREATE INDEX `ContactCustomerID` ON `Contact` ( `CustomerID` );
CREATE INDEX `CustomerOrderDetailID` ON `CustomerShipDetail` (`CustomerOrderDetailID`);
CREATE INDEX `CustomerOrderStatus` ON `CustomerOrder` (`Status`);
CREATE INDEX `CustOrderOfferID` ON `CustomerOrder` (`OfferID`);
CREATE INDEX `DeliveryStatus` ON `Delivery` (`StatusPID`);
CREATE INDEX `LotFactID` ON `Lot` ( `FactoryID` );
CREATE INDEX `LotRoomTemp` ON `Lot` ( `RoomTemp` );
CREATE INDEX `LotStatus` ON `Lot` (`StatusPID`);
CREATE INDEX `LotWhouseID` ON `Lot` ( `WarehouseID` );
CREATE INDEX `ParameterDeactivateDate` ON `Parameter` (`DeactivateDate`);
CREATE INDEX `PVal1` ON `Parameter` (`Value1`);
ANALYZE TABLE BillOfLading;
ANALYZE TABLE Contact;
ANALYZE TABLE CustomerShipDetail;
ANALYZE TABLE CustomerOrder;
ANALYZE TABLE Delivery;
ANALYZE TABLE Lot;
ANALYZE TABLE Offer;
ANALYZE TABLE Parameter;


CREATE INDEX `PVal2` ON `Parameter` (`Value2`);
CREATE INDEX `PVal3` ON `Parameter` (`Value3`);
CREATE INDEX `PVal4` ON `Parameter` (`Value4`);

CREATE INDEX `LDescriptionPID` ON `Lot` (`DescriptionPID`);
CREATE INDEX `LRoomPID` ON `Lot` (`RoomPID`);
CREATE INDEX `LRoomPID2` ON `Lot` (`RoomPID2`);
CREATE INDEX `LRoomPID3` ON `Lot` (`RoomPID3`);


CREATE INDEX `LInventoryTypePID` ON `Lot` (`InventoryTypePID`);
CREATE INDEX `VatMakeDate` ON `Vat` (`MakeDate`);


CREATE INDEX `CTGID` ON `ContaminentTest` (`ContaminentTestGroupID`);