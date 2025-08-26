CREATE TABLE `WarehouseAdjustment` (
    `WarehouseAdjustmentID` MEDIUMINT NOT NULL AUTO_INCREMENT,
    `WarehouseAdjustmentGroup` MEDIUMINT DEFAULT 0,
    `VatID` VARCHAR(64) NOT NULL,
    `LotID` VARCHAR(64) NOT NULL,
    `EDIDocumentID` mediumint(9),
    `PreviousValue` VARCHAR(64) NOT NULL,
    `NewValue` VARCHAR(64) NOT NULL,
    `ValueTypeChanged` VARCHAR(64) NOT NULL,
    `ValueTypeChangedPlainText` VARCHAR(255),
    `AdjustmentReason` VARCHAR(2) NOT NULL,
    `NetWeight` DECIMAL(18, 2) NOT NULL,
    `CreditDebitQuantity` MEDIUMINT NOT NULL,
    `UnitsCreated` decimal(18, 2),
    `MeasurementCode` VARCHAR(2) NOT NULL,
    `ProductID` VARCHAR(64) NOT NULL,
    `InventoryTransactionTypeCode` VARCHAR(2) NOT NULL,
    `ServiceID` VARCHAR(4) NOT NULL,
    `CreateDate` datetime(6) DEFAULT CURRENT_TIMESTAMP,
    `UpdateDate` datetime(6) DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(`WarehouseAdjustmentID`)
) DEFAULT CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE INDEX warehouse_adjustment_id ON WarehouseAdjustment (WarehouseAdjustmentID);
CREATE INDEX warehouse_adjustment_group ON WarehouseAdjustment (WarehouseAdjustmentGroup);
CREATE INDEX vat_id ON WarehouseAdjustment (VatID);
CREATE INDEX lot_id ON WarehouseAdjustment (LotID);

ALTER TABLE WarehouseAdjustment
ADD COLUMN ServiceID VARCHAR(4) AFTER InventoryTransactionTypeCode;

ALTER TABLE WarehouseAdjustment
ADD COLUMN CreditDebitQuantity MEDIUMINT NOT NULL AFTER AdjustmentReason;

ALTER TABLE WarehouseAdjustment
ADD COLUMN NetWeight DECIMAL(18, 2) NOT NULL AFTER AdjustmentReason;
ADD COLUMN ServiceID VARCHAR(4) AFTER InventoryTransactionTypeCode;

-- Not for this table, but theese changes should not be lost.
ALTER TABLE CustomerOrder 
ADD COLUMN LoadSequenceStopNumber VARCHAR(30) DEFAULT NULL AFTER TotalCustomerOrderQty;
