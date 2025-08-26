ALTER TABLE EDIDocument ADD COLUMN `SecondaryStatus` varchar(64) DEFAULT NULL;
ALTER TABLE EDIDocument ADD INDEX `edidoc_status` (`Status`,`SecondaryStatus`);
ALTER TABLE Customer ADD COLUMN `EDIDocCodes` varchar(255) DEFAULT NULL;
ALTER TABLE CustomerOrder ADD COLUMN `LoadSequenceStopNumber` varchar(30) DEFAULT NULL;
ALTER TABLE DeliveryDetail ADD COLUMN  `PalletCount` int(11) DEFAULT NULL;
ALTER TABLE DeliveryDetail ADD INDEX  `CustomerLot` (`CustomerLot`);
ALTER TABLE DeliveryDetailReceipt MODIFY LicensePlate varchar(64) DEFAULT NULL;
ALTER TABLE DeliveryDetailReceipt DROP INDEX `LicensePlate`; -- drop unique index
ALTER TABLE DeliveryDetailReceipt ADD INDEX `LicensePlate` (`LicensePlate`); -- add regular index
ALTER TABLE Lot ADD INDEX `LotNumber` (`LotNumber`);
ALTER TABLE Vat ADD COLUMN `DeliveryDetailID` mediumint(9) DEFAULT NULL;
ALTER TABLE Vat ADD INDEX `CustomerLotNumber` (`CustomerLotNumber`);
ALTER TABLE Vat ADD INDEX `DeliveryDetailID` (`DeliveryDetailID`);
UPDATE EDIDocument SET SecondaryStatus = 'Archived' WHERE DocID < 27000;
INSERT INTO Parameter VALUES ('D6BB15FC-BA12-46A2-A5EE-9CCCB5BCAC5E', '8E321110-EA4D-4FF1-A70A-5E7AC496DDA8', 'Sold/Not Shipped', NULL, NULL, NULL, 'Unavailable for sale, but available for inventory control.', '2025-12-31 00:00:00.000000', 0, '2024-06-27 14:54:08.000000', 'E87EA5C0-0152-419C-9D91-5438C7EC5C37', NULL, NULL);
