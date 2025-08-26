-- MySQL dump 10.17  Distrib 10.3.22-MariaDB, for Linux (i686)
--
-- Host: localhost    Database: tracker_oshkoshcheese_com
-- ------------------------------------------------------
-- Server version	10.3.22-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ApplicationSettings`
--

DROP TABLE IF EXISTS `ApplicationSettings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApplicationSettings` (
  `SettingID` varchar(64) NOT NULL,
  `StoragePrefix` char(25) DEFAULT NULL,
  `StorageStartingNumber` int(11) DEFAULT NULL,
  `FinancePrefix` char(25) DEFAULT NULL,
  `FinanceStartingNumber` int(11) DEFAULT NULL,
  UNIQUE KEY `SettingID` (`SettingID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Authority`
--

DROP TABLE IF EXISTS `Authority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Authority` (
  `AuthorityID` varchar(64) NOT NULL,
  `RolePID` varchar(64) NOT NULL,
  `SecurityPID` varchar(64) NOT NULL,
  `RightsPID` varchar(64) NOT NULL,
  `CreateId` varchar(64) NOT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`AuthorityID`),
  UNIQUE KEY `AuthorityID` (`AuthorityID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BillOfLading`
--

DROP TABLE IF EXISTS `BillOfLading`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `BillOfLading` (
  `BOLID` varchar(64) NOT NULL,
  `OfferID` varchar(64) NOT NULL,
  `CarrierName` varchar(50) DEFAULT NULL,
  `CarrierNumber` varchar(25) DEFAULT NULL,
  `ConsignedTo` varchar(50) NOT NULL,
  `ShippedToName` varchar(50) NOT NULL,
  `ShippedToAddress` varchar(500) NOT NULL,
  `ShipperNumber` varchar(50) NOT NULL,
  `CustomerPO` varchar(50) DEFAULT NULL,
  `BOLDate` datetime(6) NOT NULL,
  `SealNumber` varchar(15) DEFAULT NULL,
  `TotalTareWeight` decimal(18,2) DEFAULT NULL,
  `InstructionOnePID` varchar(64) DEFAULT NULL,
  `InstructionTwoPID` varchar(64) DEFAULT NULL,
  `InstructionThreePID` varchar(64) DEFAULT NULL,
  `InstructionFourPID` varchar(64) DEFAULT NULL,
  `InstructionFive` varchar(255) DEFAULT NULL,
  `InstructionSix` varchar(255) DEFAULT NULL,
  `CustomerItemNumber` varchar(50) DEFAULT NULL,
  `PickupDate` datetime(6) DEFAULT NULL,
  `PrintManifest` tinyint(1) DEFAULT 0,
  `AttachManifest` tinyint(1) DEFAULT 0,
  `PrintTestResults` tinyint(1) DEFAULT 0,
  `UserID` varchar(64) NOT NULL,
  `ManifestDetailTemplatePID` varchar(64) DEFAULT NULL,
  `BOLTemplatePID` varchar(64) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
  `CreateID` varchar(64) NOT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `UpdateID` varchar(64) DEFAULT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `StatusPID` varchar(64) DEFAULT '1E732327-AD3E-42C1-9602-F505B3A75E7E',
  PRIMARY KEY (`BOLID`),
  UNIQUE KEY `BOLID` (`BOLID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Contact`
--

DROP TABLE IF EXISTS `Contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Contact` (
  `ContactID` varchar(64) NOT NULL,
  `CustomerID` varchar(64) NOT NULL,
  `Prefix` varchar(10) DEFAULT NULL,
  `FirstName` varchar(25) NOT NULL,
  `MiddleInitial` varchar(1) DEFAULT NULL,
  `LastName` varchar(35) NOT NULL,
  `Suffix` varchar(10) DEFAULT NULL,
  `Title` varchar(25) DEFAULT NULL,
  `BusPhone` varchar(25) NOT NULL,
  `MobilePhone` varchar(25) DEFAULT NULL,
  `HomePhone` varchar(25) DEFAULT NULL,
  `OtherPhone` varchar(25) DEFAULT NULL,
  `OtherPhoneType` varchar(15) DEFAULT NULL,
  `Fax` varchar(25) DEFAULT NULL,
  `BusEmail` varchar(100) DEFAULT NULL,
  `AltEmail` varchar(100) DEFAULT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `CreateDate` datetime(6) NOT NULL,
  `CreateId` varchar(64) NOT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`ContactID`),
  UNIQUE KEY `ContactID` (`ContactID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ContaminentTest`
--

DROP TABLE IF EXISTS `ContaminentTest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ContaminentTest` (
  `ContaminentTestID` varchar(64) NOT NULL,
  `ContaminentTestGroupID` varchar(64) NOT NULL,
  `TestPerformedPID` varchar(64) NOT NULL,
  `TestResultsPID` varchar(64) NOT NULL,
  `NoteID` varchar(64) DEFAULT NULL,
  `NoteText` varchar(150) DEFAULT NULL,
  `CreateId` varchar(64) NOT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`ContaminentTestID`),
  UNIQUE KEY `ContaminentTestID` (`ContaminentTestID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ContaminentTestGroup`
--

DROP TABLE IF EXISTS `ContaminentTestGroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ContaminentTestGroup` (
  `ContaminentTestGroupID` varchar(64) NOT NULL,
  `LotID` varchar(64) NOT NULL,
  `TestDate` datetime(6) DEFAULT NULL,
  `NoteText` varchar(1000) DEFAULT NULL,
  `NoteID` varchar(64) DEFAULT NULL,
  `CreateId` varchar(64) NOT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`ContaminentTestGroupID`),
  UNIQUE KEY `ContaminentTestGroupID` (`ContaminentTestGroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Customer`
--

DROP TABLE IF EXISTS `Customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Customer` (
  `CustomerID` varchar(64) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `Phone` varchar(25) DEFAULT NULL,
  `Fax` varchar(25) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `HandlingCharge` decimal(18,4) DEFAULT 0.0000,
  `StorageCharge` decimal(18,4) DEFAULT 0.0000,
  `TermsPID` varchar(64) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `InventoryReportName` varchar(255) DEFAULT NULL,
  `SalesReportName` varchar(255) DEFAULT NULL,
  `CreateDate` datetime(6) NOT NULL DEFAULT '2000-01-01 12:00:00.000000',
  `CreateId` varchar(64) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `LoginID` varchar(50) DEFAULT NULL,
  `Password` varchar(60) DEFAULT NULL,
  `EDIFlag` tinyint(1) NOT NULL DEFAULT 0,
  `EDIISAID` varchar(100) DEFAULT NULL,
  `EDIGSID` varchar(100) DEFAULT NULL,
  `EDIKey` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`CustomerID`),
  UNIQUE KEY `CustomerID` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CustomerOrder`
--

DROP TABLE IF EXISTS `CustomerOrder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CustomerOrder` (
  `CustomerOrderID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `EDIDocID` mediumint(9) NOT NULL,
  `Status` varchar(64) DEFAULT NULL,
  `CustomerOrderDate` datetime(6) NOT NULL,
  `CustomerOrderNum` varchar(64) NOT NULL,
  `CustPONum` varchar(64) NOT NULL,
  `ShipToID` varchar(64) NOT NULL,
  `ShipToPONum` varchar(64) DEFAULT NULL,
  `ShipToMasterBOL` varchar(64) DEFAULT NULL,
  `ShipToName` varchar(64) DEFAULT NULL,
  `ShipToAddr1` varchar(64) DEFAULT NULL,
  `ShipToAddr2` varchar(64) DEFAULT NULL,
  `ShipToCity` varchar(64) DEFAULT NULL,
  `ShipToState` varchar(64) DEFAULT NULL,
  `ShipToZIP` varchar(32) DEFAULT NULL,
  `ShipToCountry` varchar(8) DEFAULT NULL,
  `ShipByDate` datetime(6) DEFAULT NULL,
  `CustomerOrderNotes` text DEFAULT NULL,
  `DeliveryNotes` text DEFAULT NULL,
  `Carrier` varchar(16) DEFAULT NULL,
  `TotalCustomerOrderQty` mediumint(9) NOT NULL,
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT current_timestamp(6),
  `OfferID` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`CustomerOrderID`),
  UNIQUE KEY `CustomerOrderID` (`CustomerOrderID`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CustomerOrderDetail`
--

DROP TABLE IF EXISTS `CustomerOrderDetail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CustomerOrderDetail` (
  `CustomerOrderDetailID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `CustomerOrderID` mediumint(9) NOT NULL,
  `EDIDocID` mediumint(9) NOT NULL,
  `LineNum` smallint(6) NOT NULL,
  `Qty` mediumint(9) NOT NULL,
  `QtyUOM` varchar(16) DEFAULT 'CA',
  `PartNum` varchar(16) NOT NULL,
  `ShipToPartNum` varchar(64) DEFAULT NULL,
  `POLine` varchar(64) NOT NULL,
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT current_timestamp(6),
  `OfferItemID` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`CustomerOrderDetailID`),
  UNIQUE KEY `CustomerOrderDetailID` (`CustomerOrderDetailID`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CustomerShipDetail`
--

DROP TABLE IF EXISTS `CustomerShipDetail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CustomerShipDetail` (
  `CustomerOrderDetailID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `EDIDocID` mediumint(9) NOT NULL,
  `QtyShipped` mediumint(9) NOT NULL,
  `LicensePlate` varchar(64) DEFAULT NULL,
  `ShippedDate` datetime(6) DEFAULT NULL,
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`CustomerOrderDetailID`),
  UNIQUE KEY `CustomerOrderDetailID` (`CustomerOrderDetailID`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Delivery`
--

DROP TABLE IF EXISTS `Delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Delivery` (
  `DeliveryID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `EDIDocID` mediumint(9) NOT NULL,
  `StatusPID` varchar(64) NOT NULL DEFAULT '51398435-A4A6-4C41-ACC8-45F6D569057B',
  `OrderNum` varchar(64) DEFAULT NULL,
  `ShipDate` datetime(6) NOT NULL,
  `Sender` varchar(64) DEFAULT NULL,
  `ShipFromID` varchar(64) DEFAULT NULL,
  `Warehouse` varchar(64) DEFAULT NULL,
  `Reference` varchar(64) DEFAULT NULL,
  `ShipmentID` varchar(64) DEFAULT NULL,
  `Carrier` varchar(16) DEFAULT NULL,
  `TotalShippedQty` mediumint(9) NOT NULL,
  `TotalShippedWeight` decimal(18,2) NOT NULL DEFAULT 0.00,
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT NULL,
  `ReceivedDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`DeliveryID`),
  UNIQUE KEY `DeliveryID` (`DeliveryID`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DeliveryDetail`
--

DROP TABLE IF EXISTS `DeliveryDetail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DeliveryDetail` (
  `DeliveryDetailID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `DeliveryID` mediumint(9) NOT NULL,
  `EDIDocID` mediumint(9) NOT NULL,
  `StatusPID` varchar(64) NOT NULL DEFAULT '51398435-A4A6-4C41-ACC8-45F6D569057B',
  `LineNum` smallint(6) NOT NULL,
  `Qty` mediumint(9) NOT NULL,
  `QtyUOM` varchar(16) DEFAULT 'CA',
  `PartNum` varchar(16) DEFAULT NULL,
  `CustomerLot` varchar(64) NOT NULL,
  `LicensePlate` varchar(64) NOT NULL,
  `ExpDate` datetime(6) DEFAULT NULL,
  `NetWeight` decimal(18,2) NOT NULL DEFAULT 0.00,
  `WeightUOM` varchar(16) DEFAULT 'LB',
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`DeliveryDetailID`),
  UNIQUE KEY `DeliveryID` (`DeliveryDetailID`),
  UNIQUE KEY `LicensePlate` (`LicensePlate`)
) ENGINE=InnoDB AUTO_INCREMENT=218 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DeliveryDetailReceipt`
--

DROP TABLE IF EXISTS `DeliveryDetailReceipt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DeliveryDetailReceipt` (
  `ReceiptID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `LicensePlate` varchar(64) NOT NULL,
  `DeliveryDetailID` mediumint(9) NOT NULL,
  `ReceivedQty` mediumint(9) NOT NULL,
  `LotID` varchar(64) DEFAULT NULL,
  `EDISent` mediumint(9) DEFAULT NULL,
  `ReceivedDate` datetime(6) DEFAULT NULL,
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`ReceiptID`),
  UNIQUE KEY `ReceiptID` (`ReceiptID`),
  UNIQUE KEY `LicensePlate` (`LicensePlate`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `EDIDocument`
--

DROP TABLE IF EXISTS `EDIDocument`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EDIDocument` (
  `DocID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `EDIKey` varchar(50) NOT NULL,
  `Transaction` varchar(5) NOT NULL,
  `ControlNumber` varchar(25) NOT NULL,
  `Incoming` tinyint(1) NOT NULL,
  `DocISAID` varchar(100) NOT NULL,
  `DocGSID` varchar(100) NOT NULL,
  `Status` varchar(64) DEFAULT NULL,
  `X12FilePath` varchar(255) DEFAULT NULL,
  `JsonObject` mediumtext DEFAULT NULL,
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT current_timestamp(6),
  PRIMARY KEY (`DocID`),
  UNIQUE KEY `DocID` (`DocID`)
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Factory`
--

DROP TABLE IF EXISTS `Factory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Factory` (
  `FactoryID` varchar(64) NOT NULL,
  `VendorID` varchar(64) DEFAULT NULL,
  `Name` varchar(50) NOT NULL,
  `Number` varchar(50) NOT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `CreateDate` datetime(6) NOT NULL DEFAULT '2000-01-01 12:00:00.000000',
  `CreateId` varchar(64) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `FacilityLocation` varchar(255) DEFAULT NULL,
  `EDIFlag` tinyint(1) NOT NULL DEFAULT 0,
  `EDIISAID` varchar(100) DEFAULT NULL,
  `EDIGSID` varchar(100) DEFAULT NULL,
  `EDIKey` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`FactoryID`),
  UNIQUE KEY `FactoryID` (`FactoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FlavorTest`
--

DROP TABLE IF EXISTS `FlavorTest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FlavorTest` (
  `FlavorTestID` varchar(64) NOT NULL,
  `FlavorTestGroupID` varchar(64) NOT NULL,
  `Flavor` varchar(100) NOT NULL,
  `NoteID` varchar(64) DEFAULT NULL,
  `NoteText` varchar(150) DEFAULT NULL,
  `TestDate` datetime(6) NOT NULL,
  `CreateId` varchar(64) NOT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`FlavorTestID`),
  UNIQUE KEY `FlavorTestID` (`FlavorTestID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FlavorTestGroup`
--

DROP TABLE IF EXISTS `FlavorTestGroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FlavorTestGroup` (
  `FlavorTestGroupID` varchar(64) NOT NULL,
  `VatID` varchar(64) NOT NULL,
  `TestDate` datetime(6) DEFAULT NULL,
  `NoteText` varchar(1000) DEFAULT NULL,
  `NoteID` varchar(64) DEFAULT NULL,
  `CreateId` varchar(64) NOT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`FlavorTestGroupID`),
  UNIQUE KEY `FlavorTestGroupID` (`FlavorTestGroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Grading`
--

DROP TABLE IF EXISTS `Grading`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Grading` (
  `GradingID` varchar(64) NOT NULL,
  `VatID` varchar(64) DEFAULT NULL,
  `GradingDate` datetime(6) DEFAULT NULL,
  `ExteriorColor` varchar(100) DEFAULT NULL,
  `InteriorColor` varchar(100) DEFAULT NULL,
  `Knit` varchar(100) DEFAULT NULL,
  `Application` varchar(100) DEFAULT NULL,
  `Flavor` varchar(65) DEFAULT NULL,
  `NetNumGraded` float(4,2) DEFAULT NULL,
  `WheelDestination` varchar(64) DEFAULT NULL,
  `Comments` text DEFAULT NULL,
  `LotID` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`GradingID`),
  KEY `Grading_LotID_GradingDate` (`LotID`,`GradingDate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Inspection`
--

DROP TABLE IF EXISTS `Inspection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Inspection` (
  `InspectionID` varchar(64) NOT NULL,
  `VatID` varchar(64) DEFAULT NULL,
  `InspectionDate` datetime(6) DEFAULT NULL,
  `Pallet` varchar(32) DEFAULT NULL,
  `MoldCount` int(2) DEFAULT NULL,
  `Comments` text DEFAULT NULL,
  `LotID` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`InspectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `InventoryStatus`
--

DROP TABLE IF EXISTS `InventoryStatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `InventoryStatus` (
  `InventoryStatusID` varchar(64) NOT NULL,
  `VatID` varchar(64) NOT NULL,
  `Pieces` int(11) NOT NULL,
  `Weight` decimal(18,2) NOT NULL DEFAULT 0.00,
  `InventoryStatusPID` varchar(64) NOT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `CreateID` varchar(64) NOT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateID` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`InventoryStatusID`),
  UNIQUE KEY `InventoryStatusID` (`InventoryStatusID`),
  KEY `VatID_Index` (`VatID`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Log`
--

DROP TABLE IF EXISTS `Log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Log` (
  `LogID` varchar(64) NOT NULL,
  `ForeignID` varchar(64) DEFAULT NULL,
  `Code` varchar(20) NOT NULL,
  `Entry` varchar(2000) NOT NULL,
  `CreateDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `CreateID` varchar(64) NOT NULL,
  PRIMARY KEY (`LogID`),
  UNIQUE KEY `LogID` (`LogID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Lot`
--

DROP TABLE IF EXISTS `Lot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Lot` (
  `LotID` varchar(64) NOT NULL,
  `LotNumber` varchar(64) DEFAULT NULL,
  `DateIn` datetime(6) NOT NULL,
  `DescriptionPID` varchar(64) NOT NULL,
  `VendorID` varchar(64) DEFAULT NULL,
  `FactoryID` varchar(64) DEFAULT NULL,
  `InventoryTypePID` varchar(64) NOT NULL,
  `CustomerID` varchar(64) DEFAULT NULL,
  `Cost` decimal(19,4) DEFAULT NULL,
  `FirstMonthRate` decimal(19,4) DEFAULT NULL,
  `AdditionalMonthRate` decimal(19,4) DEFAULT NULL,
  `RoomPID` varchar(64) DEFAULT NULL,
  `Handling` decimal(18,4) DEFAULT NULL,
  `HandlingUnit` varchar(10) DEFAULT 'lb',
  `Storage` decimal(18,4) DEFAULT NULL,
  `StorageUnit` varchar(10) DEFAULT 'lb',
  `Pallets` int(10) unsigned DEFAULT NULL,
  `CustomerPONumber` varchar(50) DEFAULT NULL,
  `ProductCode` varchar(255) DEFAULT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `CreateId` varchar(64) NOT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `Site` varchar(50) DEFAULT NULL,
  `RoomPID2` varchar(64) DEFAULT NULL,
  `RoomPID3` varchar(64) DEFAULT NULL,
  `Site2` varchar(50) DEFAULT NULL,
  `Site3` varchar(50) DEFAULT NULL,
  `NoteText` longtext DEFAULT NULL,
  `WarehouseID` int(11) DEFAULT NULL,
  `OwnedBy` int(11) DEFAULT NULL,
  `TempChangeDate` datetime(6) DEFAULT NULL,
  `RoomTemp` varchar(16) DEFAULT NULL,
  `RoomTemp2` varchar(16) DEFAULT NULL,
  `RoomTemp3` varchar(16) DEFAULT NULL,
  `StatusPID` varchar(64) DEFAULT '103A03A1-9B62-4FD1-BFDD-32C2C0026415',
  `DeliveryID` mediumint(9) DEFAULT NULL,
  PRIMARY KEY (`LotID`),
  UNIQUE KEY `LotID` (`LotID`),
  KEY `Searching` (`LotNumber`,`DateIn`,`DescriptionPID`,`VendorID`,`InventoryTypePID`,`CustomerID`),
  KEY `LotCustomer_Index` (`CustomerID`(8))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Offer`
--

DROP TABLE IF EXISTS `Offer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Offer` (
  `OfferID` varchar(64) NOT NULL,
  `OfferDate` datetime(6) NOT NULL,
  `Attention` varchar(64) DEFAULT '00000000-0000-0000-0000-000000000000',
  `FOB` varchar(100) DEFAULT NULL,
  `OfferExpiration` datetime(6) NOT NULL,
  `TermsPID` varchar(64) DEFAULT NULL,
  `Note` longtext DEFAULT NULL,
  `UserID` varchar(64) NOT NULL,
  `CustomerID` varchar(64) NOT NULL,
  `ContactID` varchar(64) NOT NULL,
  `OfferStatusPID` varchar(64) NOT NULL,
  `SaleDate` datetime(6) DEFAULT NULL,
  `CustomerPhoneNumber` varchar(25) DEFAULT NULL,
  `CustomerFaxNumber` varchar(25) DEFAULT NULL,
  `CustomerEmail` varchar(100) DEFAULT NULL,
  `OCSContactPhoneNumber` varchar(25) DEFAULT NULL,
  `OCSContactFaxNumber` varchar(25) DEFAULT NULL,
  `OCSContactEmail` varchar(100) DEFAULT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `CreateId` varchar(64) NOT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`OfferID`),
  UNIQUE KEY `OfferID` (`OfferID`),
  KEY `OfferTermsPID_Index` (`TermsPID`) USING BTREE,
  KEY `OfferCustomerID_Index` (`CustomerID`) USING BTREE,
  KEY `OfferStatusPID_Index` (`OfferStatusPID`(4))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `OfferItem`
--

DROP TABLE IF EXISTS `OfferItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `OfferItem` (
  `OfferItemID` varchar(64) NOT NULL,
  `OfferID` varchar(64) NOT NULL,
  `DescriptionPID` varchar(64) NOT NULL,
  `Weight` decimal(18,4) NOT NULL,
  `MakeDate` datetime(6) NOT NULL,
  `Pieces` int(11) NOT NULL,
  `Pallets` int(11) DEFAULT NULL,
  `Cost` decimal(18,4) NOT NULL,
  `LotID` varchar(64) NOT NULL,
  `NoteID` varchar(64) DEFAULT NULL,
  `NoteText` longtext DEFAULT NULL,
  `Credit` tinyint(1) NOT NULL DEFAULT 0,
  `CreateId` varchar(64) NOT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`OfferItemID`),
  UNIQUE KEY `OfferItemID` (`OfferItemID`),
  KEY `OiDescriptionPID_Index` (`DescriptionPID`) USING BTREE,
  KEY `OiOfferID_Index` (`OfferID`) USING BTREE,
  KEY `OiLotID_Index` (`LotID`(8))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `OfferItemVat`
--

DROP TABLE IF EXISTS `OfferItemVat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `OfferItemVat` (
  `OfferItemVatID` varchar(64) NOT NULL,
  `OfferItemID` varchar(64) NOT NULL,
  `VatID` varchar(64) NOT NULL,
  `Pieces` int(11) NOT NULL,
  `Weight` decimal(18,2) NOT NULL,
  `EstPallets` int(11) DEFAULT NULL,
  `Price` decimal(18,2) NOT NULL,
  `CreateId` varchar(64) NOT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `Sort` int(11) DEFAULT NULL,
  PRIMARY KEY (`OfferItemVatID`),
  UNIQUE KEY `OfferItemVatID` (`OfferItemVatID`),
  KEY `OivVatID_Index` (`VatID`) USING BTREE,
  KEY `OivOfferItemID_Index` (`OfferItemID`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Parameter`
--

DROP TABLE IF EXISTS `Parameter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Parameter` (
  `ParameterId` varchar(64) NOT NULL,
  `ParameterGroupId` varchar(64) NOT NULL,
  `Value1` varchar(50) DEFAULT NULL,
  `Value2` varchar(50) DEFAULT NULL,
  `Value3` varchar(50) DEFAULT NULL,
  `Value4` varchar(50) DEFAULT NULL,
  `Description` varchar(1000) DEFAULT NULL,
  `DeactivateDate` datetime(6) NOT NULL,
  `ReadOnly` tinyint(1) NOT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `CreateId` varchar(64) NOT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`ParameterId`),
  UNIQUE KEY `ParameterId` (`ParameterId`),
  KEY `ParameterID_Index` (`ParameterId`,`ParameterGroupId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ParameterGroup`
--

DROP TABLE IF EXISTS `ParameterGroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ParameterGroup` (
  `ParameterGroupID` varchar(64) NOT NULL,
  `Name` varchar(25) NOT NULL,
  `Heading1` varchar(50) NOT NULL,
  `Heading2` varchar(50) DEFAULT NULL,
  `Heading3` varchar(50) DEFAULT NULL,
  `Heading4` varchar(50) DEFAULT NULL,
  `PrimarySortField` smallint(6) NOT NULL,
  `ReadOnly` tinyint(1) NOT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `CreateId` varchar(64) NOT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`ParameterGroupID`),
  UNIQUE KEY `ParameterGroupID` (`ParameterGroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `User`
--

DROP TABLE IF EXISTS `User`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `User` (
  `UserID` varchar(64) NOT NULL,
  `LoginID` varchar(50) DEFAULT NULL,
  `FirstName` varchar(25) DEFAULT NULL,
  `MiddleInitial` varchar(1) DEFAULT NULL,
  `LastName` varchar(35) DEFAULT NULL,
  `FullName` varchar(63) DEFAULT NULL,
  `Phone` varchar(25) DEFAULT NULL,
  `Fax` varchar(25) DEFAULT NULL,
  `StatusPID` varchar(64) NOT NULL,
  `RequirePasswordChange` tinyint(1) NOT NULL,
  `Password` varchar(60) DEFAULT NULL,
  `RolePID` varchar(64) DEFAULT 'E5928C79-0878-44C8-B715-0A838FA8FC61',
  `OpenTime` datetime DEFAULT NULL,
  `CloseTime` datetime DEFAULT NULL,
  `DayOfWeekAccessPID` varchar(64) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Note` varchar(512) DEFAULT NULL,
  `LastPasswordChange` datetime(6) DEFAULT NULL,
  `LastLogin` datetime(6) DEFAULT NULL,
  `LastLogout` datetime(6) DEFAULT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `CreateID` varchar(64) NOT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateID` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `UserID` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `UserSession`
--

DROP TABLE IF EXISTS `UserSession`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserSession` (
  `Token` varchar(64) NOT NULL,
  `UserID` varchar(64) NOT NULL,
  `CreateDate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Token`),
  UNIQUE KEY `Token` (`Token`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `V_CustomerInventory`
--

DROP TABLE IF EXISTS `V_CustomerInventory`;
/*!50001 DROP VIEW IF EXISTS `V_CustomerInventory`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `V_CustomerInventory` (
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

--
-- Table structure for table `Vat`
--

DROP TABLE IF EXISTS `Vat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Vat` (
  `VatID` varchar(64) NOT NULL,
  `LotID` varchar(64) NOT NULL,
  `VatNumber` varchar(64) DEFAULT NULL,
  `MakeDate` datetime(6) NOT NULL,
  `Pieces` bigint(20) NOT NULL,
  `Moisture` decimal(18,2) DEFAULT NULL,
  `FDB` decimal(18,2) DEFAULT NULL,
  `PH` decimal(18,2) DEFAULT NULL,
  `Salt` decimal(18,2) DEFAULT NULL,
  `Weight` decimal(18,2) DEFAULT NULL,
  `NoteText` longtext DEFAULT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `CreateId` varchar(64) NOT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `CustomerLotNumber` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`VatID`),
  UNIQUE KEY `VatID` (`VatID`),
  KEY `Searching` (`VatNumber`,`MakeDate`),
  KEY `LotID_Index` (`LotID`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Vendor`
--

DROP TABLE IF EXISTS `Vendor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Vendor` (
  `VendorID` varchar(64) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `NoFactory` tinyint(1) NOT NULL DEFAULT 0,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `CreateDate` datetime(6) NOT NULL DEFAULT '2000-01-01 12:00:00.000000',
  `CreateId` varchar(64) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `EDIFlag` tinyint(1) NOT NULL DEFAULT 0,
  `EDIISAID` varchar(100) DEFAULT NULL,
  `EDIGSID` varchar(100) DEFAULT NULL,
  `EDIKey` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`VendorID`),
  UNIQUE KEY `VendorID` (`VendorID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Warehouse`
--

DROP TABLE IF EXISTS `Warehouse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Warehouse` (
  `WarehouseID` int(11) NOT NULL,
  `Name` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`WarehouseID`),
  UNIQUE KEY `WarehouseID` (`WarehouseID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WarehouseReceipt`
--

DROP TABLE IF EXISTS `WarehouseReceipt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WarehouseReceipt` (
  `ReceiptID` varchar(64) NOT NULL,
  `ReceiptNumber` varchar(64) NOT NULL,
  `ReceiptDate` datetime(6) NOT NULL,
  `CustomerID` varchar(64) DEFAULT NULL,
  `CreateDate` datetime(6) DEFAULT NULL,
  `CreateId` varchar(64) NOT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateId` varchar(64) DEFAULT NULL,
  `Handling` decimal(18,4) DEFAULT NULL,
  `HandlingUnit` varchar(10) DEFAULT NULL,
  `Storage` decimal(18,4) DEFAULT NULL,
  `StorageUnit` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`ReceiptID`),
  UNIQUE KEY `ReceiptNumber` (`ReceiptNumber`),
  KEY `WarehouseReceiptDate_Index` (`ReceiptDate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WarehouseReceiptItem`
--

DROP TABLE IF EXISTS `WarehouseReceiptItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WarehouseReceiptItem` (
  `ReceiptItemID` varchar(64) NOT NULL,
  `ReceiptID` varchar(64) NOT NULL,
  `LotID` varchar(64) NOT NULL,
  `Sort` tinyint(4) DEFAULT 0,
  `Pieces` int(11) DEFAULT NULL,
  `Weight` decimal(18,2) DEFAULT NULL,
  PRIMARY KEY (`ReceiptItemID`),
  KEY `LotID` (`LotID`,`ReceiptID`),
  KEY `WRItemReceiptID_Index` (`ReceiptID`(8))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sysdiagrams`
--

DROP TABLE IF EXISTS `sysdiagrams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sysdiagrams` (
  `name` varchar(160) NOT NULL,
  `principal_id` int(11) NOT NULL,
  `diagram_id` int(11) NOT NULL AUTO_INCREMENT,
  `version` int(11) DEFAULT NULL,
  `definition` longblob DEFAULT NULL,
  PRIMARY KEY (`diagram_id`),
  UNIQUE KEY `UK_principal_name` (`principal_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `V_CustomerInventory`
--

/*!50001 DROP TABLE IF EXISTS `V_CustomerInventory`*/;
/*!50001 DROP VIEW IF EXISTS `V_CustomerInventory`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `V_CustomerInventory` AS select distinct `L`.`LotID` AS `LotID`,`L`.`LotNumber` AS `LotNumber`,`L`.`CustomerPONumber` AS `CustomerPONumber`,`L`.`Cost` AS `Cost`,`L`.`DateIn` AS `DateIn`,`P`.`Value1` AS `LotDescription`,`V`.`VatNumber` AS `VatNumber`,`V`.`MakeDate` AS `MakeDate`,`I`.`Pieces` AS `Pieces`,`I`.`Weight` AS `Weight`,`VF`.`Name` AS `FactoryName`,`VF`.`Number` AS `FactoryNumber`,`C`.`CustomerID` AS `CustomerID`,`C`.`Name` AS `Name`,`RP`.`Value1` AS `RoomName`,`LT`.`Value1` AS `InventoryType`,`L`.`ProductCode` AS `ProductCode` from (((((((`Lot` `L` left join `Vat` `V` on(`L`.`LotID` = `V`.`LotID`)) left join `InventoryStatus` `I` on(`I`.`VatID` = `V`.`VatID` and `I`.`InventoryStatusPID` = 'd99fc80e-52bc-4ad0-9b10-3e5a5f07eae0')) left join `Customer` `C` on(`C`.`CustomerID` = `L`.`CustomerID`)) left join `Parameter` `P` on(`P`.`ParameterId` = `L`.`DescriptionPID`)) left join `Parameter` `RP` on(`RP`.`ParameterId` = `L`.`RoomPID`)) left join `Parameter` `LT` on(`LT`.`ParameterId` = `L`.`InventoryTypePID`)) left join `Factory` `VF` on(`VF`.`FactoryID` = `L`.`FactoryID`)) where `I`.`Pieces` > 0 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-07-19 18:31:40
