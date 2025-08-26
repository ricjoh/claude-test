

DROP TABLE IF EXISTS `LogisticsUnitConfig`;
CREATE TABLE `LogisticsUnitConfig` (
	`LogisticsUnitConfigID` MEDIUMINT NOT NULL AUTO_INCREMENT,
	`cname` VARCHAR(32) NOT NULL,
	`cvalue` VARCHAR(32) NOT NULL DEFAULT '',
	`CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`LogisticsUnitConfigID`),
  UNIQUE KEY `cname` (`cname`),
  KEY `cvalue` (`cvalue`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


INSERT INTO `LogisticsUnitConfig` (`cname`, `cvalue`) VALUES ('Zebra_Server_IP_Address', '98.100.210.50');
INSERT INTO `LogisticsUnitConfig` (`cname`, `cvalue`) VALUES ('Zebra_Server_Port', '2345');
INSERT INTO `LogisticsUnitConfig` (`cname`, `cvalue`) VALUES ('Print_Speed', '3	');

DROP TABLE IF EXISTS `CustomerShipPallet`;
DROP TABLE IF EXISTS `ShipPallet`;
CREATE TABLE `ShipPallet` (
	`ShipPalletID` MEDIUMINT NOT NULL AUTO_INCREMENT,
	`CustomerOrderID` MEDIUMINT(9) NOT NULL,
	`CustomerOrderDetailID` MEDIUMINT(9) NOT NULL,
	`OfferID` VARCHAR(64),
	`OfferItemID` VARCHAR(64),
	`OfferItemVatID` VARCHAR(64),
	`EDIDocIDIn` MEDIUMINT(9),
	`EDIDocIDOut` MEDIUMINT(9),
	`LicensePlate` VARCHAR(32) NOT NULL DEFAULT '',
	`ChepPallet` TINYINT(1) NOT NULL DEFAULT 0,
	`PalletSKU` VARCHAR(16) COMMENT "For SAPUTO pallets",
	`CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`ShipPalletID`),
  KEY `CustomerOrderDetailID` (`CustomerOrderDetailID`),
  KEY `LicensePlate` (`LicensePlate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/* CREATE INDEX DeliveryDetailID ON Vat (`DeliveryDetailID`); */
