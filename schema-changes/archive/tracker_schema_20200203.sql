DROP TABLE IF EXISTS `ApplicationSettings`;
CREATE TABLE `ApplicationSettings` (
  `SettingID` VARCHAR(64) NOT NULL,
  `StoragePrefix` CHAR(25) DEFAULT NULL,
  `StorageStartingNumber` INT(11) DEFAULT NULL,
  `FinancePrefix` CHAR(25) DEFAULT NULL,
  `FinanceStartingNumber` INT(11) DEFAULT NULL,
  UNIQUE KEY `SettingID` (`SettingID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Authority`;
CREATE TABLE `Authority` (
  `AuthorityID` VARCHAR(64) NOT NULL,
  `RolePID` VARCHAR(64) NOT NULL,
  `SecurityPID` VARCHAR(64) NOT NULL,
  `RightsPID` VARCHAR(64) NOT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`AuthorityID`),
  UNIQUE KEY `AuthorityID` (`AuthorityID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `BillOfLading`;
CREATE TABLE `BillOfLading` (
  `BOLID` VARCHAR(64) NOT NULL,
  `OfferID` VARCHAR(64) NOT NULL,
  `CarrierName` VARCHAR(50) DEFAULT NULL,
  `CarrierNumber` VARCHAR(25) DEFAULT NULL,
  `ConsignedTo` VARCHAR(50) NOT NULL,
  `ShippedToName` VARCHAR(50) NOT NULL,
  `ShippedToAddress` VARCHAR(500) NOT NULL,
  `ShipperNumber` VARCHAR(50) NOT NULL,
  `CustomerPO` VARCHAR(50) DEFAULT NULL,
  `BOLDate` DATETIME(6) NOT NULL,
  `SealNumber` VARCHAR(15) DEFAULT NULL,
  `TotalTareWeight` DECIMAL(18,2) DEFAULT NULL,
  `InstructionOnePID` VARCHAR(64) DEFAULT NULL,
  `InstructionTwoPID` VARCHAR(64) DEFAULT NULL,
  `InstructionThreePID` VARCHAR(64) DEFAULT NULL,
  `InstructionFourPID` VARCHAR(64) DEFAULT NULL,
  `InstructionFive` VARCHAR(255) DEFAULT NULL,
  `InstructionSix` VARCHAR(255) DEFAULT NULL,
  `CustomerItemNumber` VARCHAR(50) DEFAULT NULL,
  `PickupDate` DATETIME(6) DEFAULT NULL,
  `PrintManifest` tinyint(1) DEFAULT 0,
  `AttachManifest` tinyint(1) DEFAULT 0,
  `PrintTestResults` tinyint(1) DEFAULT 0,
  `UserID` VARCHAR(64) NOT NULL,
  `ManifestDetailTemplatePID` VARCHAR(64) DEFAULT NULL,
  `BOLTemplatePID` VARCHAR(64) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
  `CreateID` VARCHAR(64) NOT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `UpdateID` VARCHAR(64) DEFAULT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`BOLID`),
  UNIQUE KEY `BOLID` (`BOLID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Contact`;
CREATE TABLE `Contact` (
  `ContactID` VARCHAR(64) NOT NULL,
  `CustomerID` VARCHAR(64) NOT NULL,
  `Prefix` VARCHAR(10) DEFAULT NULL,
  `FirstName` VARCHAR(25) NOT NULL,
  `MiddleInitial` VARCHAR(1) DEFAULT NULL,
  `LastName` VARCHAR(35) NOT NULL,
  `Suffix` VARCHAR(10) DEFAULT NULL,
  `Title` VARCHAR(25) DEFAULT NULL,
  `BusPhone` VARCHAR(25) NOT NULL,
  `MobilePhone` VARCHAR(25) DEFAULT NULL,
  `HomePhone` VARCHAR(25) DEFAULT NULL,
  `OtherPhone` VARCHAR(25) DEFAULT NULL,
  `OtherPhoneType` VARCHAR(15) DEFAULT NULL,
  `Fax` VARCHAR(25) DEFAULT NULL,
  `BusEmail` VARCHAR(100) DEFAULT NULL,
  `AltEmail` VARCHAR(100) DEFAULT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `CreateDate` DATETIME(6) NOT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`ContactID`),
  UNIQUE KEY `ContactID` (`ContactID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ContaminentTest`;
CREATE TABLE `ContaminentTest` (
  `ContaminentTestID` VARCHAR(64) NOT NULL,
  `ContaminentTestGroupID` VARCHAR(64) NOT NULL,
  `TestPerformedPID` VARCHAR(64) NOT NULL,
  `TestResultsPID` VARCHAR(64) NOT NULL,
  `NoteID` VARCHAR(64) DEFAULT NULL,
  `NoteText` VARCHAR(150) DEFAULT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`ContaminentTestID`),
  UNIQUE KEY `ContaminentTestID` (`ContaminentTestID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ContaminentTestGroup`;
CREATE TABLE `ContaminentTestGroup` (
  `ContaminentTestGroupID` VARCHAR(64) NOT NULL,
  `LotID` VARCHAR(64) NOT NULL,
  `TestDate` DATETIME(6) DEFAULT NULL,
  `NoteText` VARCHAR(1000) DEFAULT NULL,
  `NoteID` VARCHAR(64) DEFAULT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`ContaminentTestGroupID`),
  UNIQUE KEY `ContaminentTestGroupID` (`ContaminentTestGroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Customer`;
CREATE TABLE `Customer` (
  `CustomerID` VARCHAR(64) NOT NULL,
  `Name` VARCHAR(50) NOT NULL,
  `Phone` VARCHAR(25) DEFAULT NULL,
  `Fax` VARCHAR(25) DEFAULT NULL,
  `Email` VARCHAR(100) DEFAULT NULL,
  `HandlingCharge` DECIMAL(18,4) DEFAULT 0.0000,
  `StorageCharge` DECIMAL(18,4) DEFAULT 0.0000,
  `TermsPID` VARCHAR(64) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `InventoryReportName` VARCHAR(255) DEFAULT NULL,
  `SalesReportName` VARCHAR(255) DEFAULT NULL,
  `CreateDate` DATETIME(6) NOT NULL DEFAULT '2000-01-01 12:00:00.000000',
  `CreateId` VARCHAR(64) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  `LoginID` VARCHAR(50) DEFAULT NULL,
  `Password` VARCHAR(60) DEFAULT NULL,
  PRIMARY KEY (`CustomerID`),
  UNIQUE KEY `CustomerID` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Factory`;
CREATE TABLE `Factory` (
  `FactoryID` VARCHAR(64) NOT NULL,
  `VendorID` VARCHAR(64) DEFAULT NULL,
  `Name` VARCHAR(50) NOT NULL,
  `Number` VARCHAR(50) NOT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `CreateDate` DATETIME(6) NOT NULL DEFAULT '2000-01-01 12:00:00.000000',
  `CreateId` VARCHAR(64) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  `FacilityLocation` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`FactoryID`),
  UNIQUE KEY `FactoryID` (`FactoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `FlavorTest`;
CREATE TABLE `FlavorTest` (
  `FlavorTestID` VARCHAR(64) NOT NULL,
  `FlavorTestGroupID` VARCHAR(64) NOT NULL,
  `Flavor` VARCHAR(100) NOT NULL,
  `NoteID` VARCHAR(64) DEFAULT NULL,
  `NoteText` VARCHAR(150) DEFAULT NULL,
  `TestDate` DATETIME(6) NOT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`FlavorTestID`),
  UNIQUE KEY `FlavorTestID` (`FlavorTestID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `FlavorTestGroup`;
CREATE TABLE `FlavorTestGroup` (
  `FlavorTestGroupID` VARCHAR(64) NOT NULL,
  `VatID` VARCHAR(64) NOT NULL,
  `TestDate` DATETIME(6) DEFAULT NULL,
  `NoteText` VARCHAR(1000) DEFAULT NULL,
  `NoteID` VARCHAR(64) DEFAULT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`FlavorTestGroupID`),
  UNIQUE KEY `FlavorTestGroupID` (`FlavorTestGroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Grading`;
CREATE TABLE `Grading` (
  `GradingID` VARCHAR(64) NOT NULL,
  `VatID` VARCHAR(64) DEFAULT NULL,
  `GradingDate` DATETIME(6) DEFAULT NULL,
  `ExteriorColor` VARCHAR(100) DEFAULT NULL,
  `InteriorColor` VARCHAR(100) DEFAULT NULL,
  `Knit` VARCHAR(100) DEFAULT NULL,
  `Application` VARCHAR(100) DEFAULT NULL,
  `Flavor` VARCHAR(65) DEFAULT NULL,
  `NetNumGraded` FLOAT(4,2) DEFAULT NULL,
  `WheelDestination` VARCHAR(64) DEFAULT NULL,
  `Comments` text DEFAULT NULL,
  `LotID` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`GradingID`),
  KEY `Grading_LotID_GradingDate` (`LotID`,`GradingDate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Inspection`;
CREATE TABLE `Inspection` (
  `InspectionID` VARCHAR(64) NOT NULL,
  `VatID` VARCHAR(64) DEFAULT NULL,
  `InspectionDate` DATETIME(6) DEFAULT NULL,
  `Pallet` VARCHAR(32) DEFAULT NULL,
  `MoldCount` INT(2) DEFAULT NULL,
  `Comments` text DEFAULT NULL,
  `LotID` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`InspectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `InventoryStatus`;
CREATE TABLE `InventoryStatus` (
  `InventoryStatusID` VARCHAR(64) NOT NULL,
  `VatID` VARCHAR(64) NOT NULL,
  `Pieces` INT(11) NOT NULL,
  `Weight` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `InventoryStatusPID` VARCHAR(64) NOT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `CreateID` VARCHAR(64) NOT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `UpdateID` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`InventoryStatusID`),
  UNIQUE KEY `InventoryStatusID` (`InventoryStatusID`),
  KEY `VatID_Index` (`VatID`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Log`;
CREATE TABLE `Log` (
  `LogID` VARCHAR(64) NOT NULL,
  `ForeignID` VARCHAR(64) DEFAULT NULL,
  `Code` VARCHAR(20) NOT NULL,
  `Entry` VARCHAR(2000) NOT NULL,
  `CreateDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `CreateID` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`LogID`),
  UNIQUE KEY `LogID` (`LogID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Lot`;
CREATE TABLE `Lot` (
  `LotID` VARCHAR(64) NOT NULL,
  `LotNumber` VARCHAR(64) DEFAULT NULL,
  `DateIn` DATETIME(6) NOT NULL,
  `DescriptionPID` VARCHAR(64) NOT NULL,
  `VendorID` VARCHAR(64) DEFAULT NULL,
  `FactoryID` VARCHAR(64) DEFAULT NULL,
  `InventoryTypePID` VARCHAR(64) NOT NULL,
  `CustomerID` VARCHAR(64) DEFAULT NULL,
  `Cost` DECIMAL(19,4) DEFAULT NULL,
  `FirstMonthRate` DECIMAL(19,4) DEFAULT NULL,
  `AdditionalMonthRate` DECIMAL(19,4) DEFAULT NULL,
  `RoomPID` VARCHAR(64) DEFAULT NULL,
  `Handling` DECIMAL(18,4) DEFAULT NULL,
  `HandlingUnit` VARCHAR(10) DEFAULT 'lb',
  `Storage` DECIMAL(18,4) DEFAULT NULL,
  `StorageUnit` VARCHAR(10) DEFAULT 'lb',
  `Pallets` INT(10) unsigned DEFAULT NULL,
  `CustomerPONumber` VARCHAR(50) DEFAULT NULL,
  `ProductCode` VARCHAR(255) DEFAULT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  `Site` VARCHAR(50) DEFAULT NULL,
  `RoomPID2` VARCHAR(64) DEFAULT NULL,
  `RoomPID3` VARCHAR(64) DEFAULT NULL,
  `Site2` VARCHAR(50) DEFAULT NULL,
  `Site3` VARCHAR(50) DEFAULT NULL,
  `NoteText` longtext DEFAULT NULL,
  `WarehouseID` INT(11) DEFAULT NULL,
  `OwnedBy` INT(11) DEFAULT NULL,
  `TempChangeDate` DATETIME(6) DEFAULT NULL,
  `RoomTemp` VARCHAR(16) DEFAULT NULL,
  `RoomTemp2` VARCHAR(16) DEFAULT NULL,
  `RoomTemp3` VARCHAR(16) DEFAULT NULL,
  PRIMARY KEY (`LotID`),
  UNIQUE KEY `LotID` (`LotID`),
  KEY `Searching` (`LotNumber`,`DateIn`,`DescriptionPID`,`VendorID`,`InventoryTypePID`,`CustomerID`),
  KEY `LotCustomer_Index` (`CustomerID`(8))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Vat`;
CREATE TABLE `Vat` (
  `VatID` VARCHAR(64) NOT NULL,
  `LotID` VARCHAR(64) NOT NULL,
  `VatNumber` VARCHAR(64) DEFAULT NULL,
  `MakeDate` DATETIME(6) NOT NULL,
  `Pieces` bigint(20) NOT NULL,
  `Moisture` DECIMAL(18,2) DEFAULT NULL,
  `FDB` DECIMAL(18,2) DEFAULT NULL,
  `PH` DECIMAL(18,2) DEFAULT NULL,
  `Salt` DECIMAL(18,2) DEFAULT NULL,
  `Weight` DECIMAL(18,2) DEFAULT NULL,
  `NoteText` longtext DEFAULT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  `CustomerLotNumber` VARCHAR(25) DEFAULT NULL,
  PRIMARY KEY (`VatID`),
  UNIQUE KEY `VatID` (`VatID`),
  KEY `Searching` (`VatNumber`,`MakeDate`),
  KEY `LotID_Index` (`LotID`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Offer`;
CREATE TABLE `Offer` (
  `OfferID` VARCHAR(64) NOT NULL,
  `OfferDate` DATETIME(6) NOT NULL,
  `Attention` VARCHAR(64) DEFAULT '00000000-0000-0000-0000-000000000000',
  `FOB` VARCHAR(100) DEFAULT NULL,
  `OfferExpiration` DATETIME(6) NOT NULL,
  `TermsPID` VARCHAR(64) DEFAULT NULL,
  `Note` longtext DEFAULT NULL,
  `UserID` VARCHAR(64) NOT NULL,
  `CustomerID` VARCHAR(64) NOT NULL,
  `ContactID` VARCHAR(64) NOT NULL,
  `OfferStatusPID` VARCHAR(64) NOT NULL,
  `SaleDate` DATETIME(6) DEFAULT NULL,
  `CustomerPhoneNumber` VARCHAR(25) DEFAULT NULL,
  `CustomerFaxNumber` VARCHAR(25) DEFAULT NULL,
  `CustomerEmail` VARCHAR(100) DEFAULT NULL,
  `OCSContactPhoneNumber` VARCHAR(25) DEFAULT NULL,
  `OCSContactFaxNumber` VARCHAR(25) DEFAULT NULL,
  `OCSContactEmail` VARCHAR(100) DEFAULT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`OfferID`),
  UNIQUE KEY `OfferID` (`OfferID`),
  KEY `OfferTermsPID_Index` (`TermsPID`) USING BTREE,
  KEY `OfferCustomerID_Index` (`CustomerID`) USING BTREE,
  KEY `OfferStatusPID_Index` (`OfferStatusPID`(4))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `OfferItem`;
CREATE TABLE `OfferItem` (
  `OfferItemID` VARCHAR(64) NOT NULL,
  `OfferID` VARCHAR(64) NOT NULL,
  `DescriptionPID` VARCHAR(64) NOT NULL,
  `Weight` DECIMAL(18,4) NOT NULL,
  `MakeDate` DATETIME(6) NOT NULL,
  `Pieces` INT(11) NOT NULL,
  `Pallets` INT(11) DEFAULT NULL,
  `Cost` DECIMAL(18,4) NOT NULL,
  `LotID` VARCHAR(64) NOT NULL,
  `NoteID` VARCHAR(64) DEFAULT NULL,
  `NoteText` longtext DEFAULT NULL,
  `Credit` tinyint(1) NOT NULL DEFAULT 0,
  `CreateId` VARCHAR(64) NOT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`OfferItemID`),
  UNIQUE KEY `OfferItemID` (`OfferItemID`),
  KEY `OiDescriptionPID_Index` (`DescriptionPID`) USING BTREE,
  KEY `OiOfferID_Index` (`OfferID`) USING BTREE,
  KEY `OiLotID_Index` (`LotID`(8))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `OfferItemVat`;
CREATE TABLE `OfferItemVat` (
  `OfferItemVatID` VARCHAR(64) NOT NULL,
  `OfferItemID` VARCHAR(64) NOT NULL,
  `VatID` VARCHAR(64) NOT NULL,
  `Pieces` INT(11) NOT NULL,
  `Weight` DECIMAL(18,2) NOT NULL,
  `EstPallets` INT(11) DEFAULT NULL,
  `Price` DECIMAL(18,2) NOT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `Sort` INT(11) DEFAULT NULL,
  PRIMARY KEY (`OfferItemVatID`),
  UNIQUE KEY `OfferItemVatID` (`OfferItemVatID`),
  KEY `OivVatID_Index` (`VatID`) USING BTREE,
  KEY `OivOfferItemID_Index` (`OfferItemID`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Parameter`;
CREATE TABLE `Parameter` (
  `ParameterId` VARCHAR(64) NOT NULL,
  `ParameterGroupId` VARCHAR(64) NOT NULL,
  `Value1` VARCHAR(50) DEFAULT NULL,
  `Value2` VARCHAR(50) DEFAULT NULL,
  `Value3` VARCHAR(50) DEFAULT NULL,
  `Value4` VARCHAR(50) DEFAULT NULL,
  `Description` VARCHAR(1000) DEFAULT NULL,
  `DeactivateDate` DATETIME(6) NOT NULL,
  `ReadOnly` tinyint(1) NOT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`ParameterId`),
  UNIQUE KEY `ParameterId` (`ParameterId`),
  KEY `ParameterID_Index` (`ParameterId`,`ParameterGroupId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ParameterGroup`;
CREATE TABLE `ParameterGroup` (
  `ParameterGroupID` VARCHAR(64) NOT NULL,
  `Name` VARCHAR(25) NOT NULL,
  `Heading1` VARCHAR(50) NOT NULL,
  `Heading2` VARCHAR(50) DEFAULT NULL,
  `Heading3` VARCHAR(50) DEFAULT NULL,
  `Heading4` VARCHAR(50) DEFAULT NULL,
  `PrimarySortField` SMALLINT(6) NOT NULL,
  `ReadOnly` tinyint(1) NOT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`ParameterGroupID`),
  UNIQUE KEY `ParameterGroupID` (`ParameterGroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `User`;
CREATE TABLE `User` (
  `UserID` VARCHAR(64) NOT NULL,
  `LoginID` VARCHAR(50) DEFAULT NULL,
  `FirstName` VARCHAR(25) DEFAULT NULL,
  `MiddleInitial` VARCHAR(1) DEFAULT NULL,
  `LastName` VARCHAR(35) DEFAULT NULL,
  `FullName` VARCHAR(63) DEFAULT NULL,
  `Phone` VARCHAR(25) DEFAULT NULL,
  `Fax` VARCHAR(25) DEFAULT NULL,
  `StatusPID` VARCHAR(64) NOT NULL,
  `RequirePasswordChange` tinyint(1) NOT NULL,
  `Password` VARCHAR(60) DEFAULT NULL,
  `RolePID` VARCHAR(64) DEFAULT 'E5928C79-0878-44C8-B715-0A838FA8FC61',
  `OpenTime` DATETIME DEFAULT NULL,
  `CloseTime` DATETIME DEFAULT NULL,
  `DayOfWeekAccessPID` VARCHAR(64) DEFAULT NULL,
  `Email` VARCHAR(100) DEFAULT NULL,
  `Note` VARCHAR(512) DEFAULT NULL,
  `LastPasswordChange` DATETIME(6) DEFAULT NULL,
  `LastLogin` DATETIME(6) DEFAULT NULL,
  `LastLogout` DATETIME(6) DEFAULT NULL,
  `CreateDate` DATETIME(6) NOT NULL,
  `CreateID` VARCHAR(64) NOT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `UpdateID` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `UserSession`;
CREATE TABLE `UserSession` (
  `Token` VARCHAR(64) NOT NULL,
  `UserID` VARCHAR(64) NOT NULL,
  `CreateDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`Token`),
  UNIQUE KEY `Token` (`Token`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `V_CustomerInventory`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
  `LotID` tinyint NOT NULL,
  `LotNumber` tinyint NOT NULL,
  `CustomerPONumber` tinyint NOT NULL,
  `Cost` tinyint NOT NULL,
  `DateIn` tinyint NOT NULL,
  `LotDescription` tinyint NOT NULL,
  `VatNumber` tinyint NOT NULL,
  `MakeDate` tinyint NOT NULL,
  `Pieces` tinyint NOT NULL,
  `Weight` tinyint NOT NULL,
  `FactoryName` tinyint NOT NULL,
  `FactoryNumber` tinyint NOT NULL,
  `CustomerID` tinyint NOT NULL,
  `Name` tinyint NOT NULL,
  `RoomName` tinyint NOT NULL,
  `InventoryType` tinyint NOT NULL,
  `ProductCode` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

DROP TABLE IF EXISTS `Vendor`;
CREATE TABLE `Vendor` (
  `VendorID` VARCHAR(64) NOT NULL,
  `Name` VARCHAR(50) NOT NULL,
  `NoFactory` tinyint(1) NOT NULL DEFAULT 0,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `CreateDate` DATETIME(6) NOT NULL DEFAULT '2000-01-01 12:00:00.000000',
  `CreateId` VARCHAR(64) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`VendorID`),
  UNIQUE KEY `VendorID` (`VendorID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Warehouse`;
CREATE TABLE `Warehouse` (
  `WarehouseID` INT(11) NOT NULL,
  `Name` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`WarehouseID`),
  UNIQUE KEY `WarehouseID` (`WarehouseID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `WarehouseReceipt`;
CREATE TABLE `WarehouseReceipt` (
  `ReceiptID` VARCHAR(64) NOT NULL,
  `ReceiptNumber` VARCHAR(64) NOT NULL,
  `ReceiptDate` DATETIME(6) NOT NULL,
  `CustomerID` VARCHAR(64) DEFAULT NULL,
  `CreateDate` DATETIME(6) DEFAULT NULL,
  `CreateId` VARCHAR(64) NOT NULL,
  `UpdateDate` DATETIME(6) DEFAULT NULL,
  `UpdateId` VARCHAR(64) DEFAULT NULL,
  `Handling` DECIMAL(18,4) DEFAULT NULL,
  `HandlingUnit` VARCHAR(10) DEFAULT NULL,
  `Storage` DECIMAL(18,4) DEFAULT NULL,
  `StorageUnit` VARCHAR(10) DEFAULT NULL,
  PRIMARY KEY (`ReceiptID`),
  UNIQUE KEY `ReceiptNumber` (`ReceiptNumber`),
  KEY `WarehouseReceiptDate_Index` (`ReceiptDate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `WarehouseReceiptItem`;
CREATE TABLE `WarehouseReceiptItem` (
  `ReceiptItemID` VARCHAR(64) NOT NULL,
  `ReceiptID` VARCHAR(64) NOT NULL,
  `LotID` VARCHAR(64) NOT NULL,
  `Sort` tinyint(4) DEFAULT 0,
  `Pieces` INT(11) DEFAULT NULL,
  `Weight` DECIMAL(18,2) DEFAULT NULL,
  PRIMARY KEY (`ReceiptItemID`),
  KEY `LotID` (`LotID`,`ReceiptID`),
  KEY `WRItemReceiptID_Index` (`ReceiptID`(8))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `sysdiagrams`;
CREATE TABLE `sysdiagrams` (
  `name` VARCHAR(160) NOT NULL,
  `principal_id` INT(11) NOT NULL,
  `diagram_id` INT(11) NOT NULL AUTO_INCREMENT,
  `version` INT(11) DEFAULT NULL,
  `definition` longblob DEFAULT NULL,
  PRIMARY KEY (`diagram_id`),
  UNIQUE KEY `UK_principal_name` (`principal_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
