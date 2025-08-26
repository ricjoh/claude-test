CREATE TABLE `InventoryInquiry` (
    `InventoryInquiryID` mediumint(9) NOT NULL AUTO_INCREMENT,
    `CreateDate` datetime(6) DEFAULT CURRENT_TIMESTAMP,
    `UpdateDate` datetime(6) DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(`InventoryInquiryID`)
) DEFAULT CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE INDEX inv_inquiry_id ON InventoryInquiry (InventoryInquiryID);
