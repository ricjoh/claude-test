-- MariaDB dump 10.19  Distrib 10.6.17-MariaDB, for Linux (i686)
--
-- Host: localhost    Database: tracker_oshkoshcheese_com_uuid_ric
-- ------------------------------------------------------
-- Server version	10.6.17-MariaDB-log

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
-- Current Database: `tracker_oshkoshcheese_com_uuid_ric`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `tracker_oshkoshcheese_com_uuid_ric` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `tracker_oshkoshcheese_com_uuid_ric`;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
  UNIQUE KEY `BOLID` (`BOLID`),
  KEY `BillOfLadingStatusPID` (`StatusPID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
  UNIQUE KEY `ContactID` (`ContactID`),
  KEY `ContactCustomerID` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
  UNIQUE KEY `ContaminentTestID` (`ContaminentTestID`),
  KEY `CTGID` (`ContaminentTestGroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
  `EDIDocCodes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`CustomerID`),
  UNIQUE KEY `CustomerID` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
  `LoadSequenceStopNumber` varchar(30) DEFAULT NULL,
  `DistributionCenter` varchar(64) DEFAULT NULL,
  `ProductType` varchar(64) DEFAULT NULL,
  `ProductDept` varchar(64) DEFAULT NULL,
  `Walmart` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`CustomerOrderID`),
  UNIQUE KEY `CustomerOrderID` (`CustomerOrderID`),
  KEY `CustomerOrderStatus` (`Status`),
  KEY `CustOrderOfferID` (`OfferID`)
) ENGINE=InnoDB AUTO_INCREMENT=3327 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
  PRIMARY KEY (`CustomerOrderDetailID`),
  UNIQUE KEY `CustomerOrderDetailID` (`CustomerOrderDetailID`)
) ENGINE=InnoDB AUTO_INCREMENT=31660 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CustomerShipDetail`
--

DROP TABLE IF EXISTS `CustomerShipDetail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CustomerShipDetail` (
  `CustomerOrderDetailID` mediumint(9) NOT NULL,
  `EDIDocID` mediumint(9) NOT NULL,
  `QtyShipped` mediumint(9) NOT NULL,
  `LicensePlate` varchar(64) DEFAULT NULL,
  `ShippedDate` datetime(6) DEFAULT NULL,
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT NULL,
  `OfferItemID` varchar(64) DEFAULT NULL,
  `CustomerShipDetailID` mediumint(9) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`CustomerShipDetailID`),
  KEY `DetailPlusOfferItem` (`CustomerOrderDetailID`,`OfferItemID`),
  KEY `CustomerOrderDetailID` (`CustomerOrderDetailID`)
) ENGINE=InnoDB AUTO_INCREMENT=33385 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
  UNIQUE KEY `DeliveryID` (`DeliveryID`),
  KEY `DeliveryStatus` (`StatusPID`)
) ENGINE=InnoDB AUTO_INCREMENT=4227 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
  `CustomerLot` varchar(64) DEFAULT NULL,
  `LicensePlate` varchar(64) DEFAULT NULL,
  `ExpDate` datetime(6) DEFAULT NULL,
  `NetWeight` decimal(18,2) NOT NULL DEFAULT 0.00,
  `WeightUOM` varchar(16) DEFAULT 'LB',
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT NULL,
  `PalletCount` int(11) DEFAULT NULL,
  PRIMARY KEY (`DeliveryDetailID`),
  UNIQUE KEY `DeliveryID` (`DeliveryDetailID`)
) ENGINE=InnoDB AUTO_INCREMENT=89771 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DeliveryDetailReceipt`
--

DROP TABLE IF EXISTS `DeliveryDetailReceipt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DeliveryDetailReceipt` (
  `ReceiptID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `LicensePlate` varchar(64) DEFAULT NULL,
  `DeliveryDetailID` mediumint(9) NOT NULL,
  `ReceivedQty` mediumint(9) NOT NULL,
  `LotID` varchar(64) DEFAULT NULL,
  `EDISent` mediumint(9) DEFAULT NULL,
  `ReceivedDate` datetime(6) DEFAULT NULL,
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`ReceiptID`),
  UNIQUE KEY `ReceiptID` (`ReceiptID`)
) ENGINE=InnoDB AUTO_INCREMENT=78487 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
  `SecondaryStatus` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`DocID`),
  UNIQUE KEY `DocID` (`DocID`),
  KEY `edidoc_status` (`Status`,`SecondaryStatus`)
) ENGINE=InnoDB AUTO_INCREMENT=20384 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `InventoryInquiry`
--

DROP TABLE IF EXISTS `InventoryInquiry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `InventoryInquiry` (
  `InventoryInquiryID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `CreateDate` datetime(6) DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`InventoryInquiryID`),
  KEY `inv_inquiry_id` (`InventoryInquiryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LogisticsUnit`
--

DROP TABLE IF EXISTS `LogisticsUnit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LogisticsUnit` (
  `LogisticsUnitID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `LicensePlate` varchar(32) NOT NULL DEFAULT '',
  `VatID` varchar(64) DEFAULT NULL,
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`LogisticsUnitID`),
  UNIQUE KEY `LicensePlate` (`LicensePlate`),
  KEY `VatID` (`VatID`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`oshcheese_ric`@`arrokoth.xymmetrix.com`*/ /*!50003 TRIGGER `CreateLicensePlate` BEFORE INSERT ON LogisticsUnit
FOR EACH ROW
BEGIN
	DECLARE LP CHAR(32);
	DECLARE check_digit INT;
	DECLARE LID INT;

	if (NEW.LogisticsUnitID=0) then
		set @A = (SELECT AUTO_INCREMENT
		FROM information_schema.TABLES
		WHERE TABLE_NAME = "LogisticsUnit");
	end if;
	SET LID = @A;

	SET LP = CONCAT('08600042725', LPAD(@A, 6, '0'));
	SET check_digit = GS1128CheckDigit(LP);
    SET NEW.LicensePlate = CONCAT(LP, check_digit);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `LogisticsUnitConfig`
--

DROP TABLE IF EXISTS `LogisticsUnitConfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LogisticsUnitConfig` (
  `LogisticsUnitConfigID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `cname` varchar(32) NOT NULL,
  `cvalue` varchar(32) NOT NULL DEFAULT '',
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`LogisticsUnitConfigID`),
  UNIQUE KEY `cname` (`cname`),
  KEY `cvalue` (`cvalue`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
  `BillingFrequency` enum('1 MONTH','2 WEEKS') NOT NULL DEFAULT '1 MONTH',
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
  `Archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`LotID`),
  UNIQUE KEY `LotID` (`LotID`),
  KEY `Searching` (`LotNumber`,`DateIn`,`DescriptionPID`,`VendorID`,`InventoryTypePID`,`CustomerID`),
  KEY `LotCustomer_Index` (`CustomerID`(8)),
  KEY `lot_archived_index` (`Archived`),
  KEY `lot_date_in_index` (`DateIn`),
  KEY `LotFactID` (`FactoryID`),
  KEY `LotRoomTemp` (`RoomTemp`),
  KEY `LotStatus` (`StatusPID`),
  KEY `LotWhouseID` (`WarehouseID`),
  KEY `LDescriptionPID` (`DescriptionPID`),
  KEY `LRoomPID` (`RoomPID`),
  KEY `LRoomPID2` (`RoomPID2`),
  KEY `LRoomPID3` (`RoomPID3`),
  KEY `LInventoryTypePID` (`InventoryTypePID`),
  KEY `LotNumber` (`LotNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
  KEY `OfferStatusPID_Index` (`OfferStatusPID`(4)),
  KEY `offer_offerdate` (`OfferDate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `OrderOfferDetailMap`
--

DROP TABLE IF EXISTS `OrderOfferDetailMap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `OrderOfferDetailMap` (
  `OODMapID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `CustomerOrderDetailID` mediumint(9) NOT NULL,
  `EDIDocID` mediumint(9) NOT NULL,
  `OfferItemID` varchar(64) DEFAULT NULL,
  `CreateDate` datetime NOT NULL DEFAULT current_timestamp(),
  `UpdateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`OODMapID`),
  UNIQUE KEY `OfferItemID` (`OfferItemID`),
  KEY `CustomerOrderDetailID` (`CustomerOrderDetailID`)
) ENGINE=InnoDB AUTO_INCREMENT=33541 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
  KEY `ParameterID_Index` (`ParameterId`,`ParameterGroupId`),
  KEY `ParameterDeactivateDate` (`DeactivateDate`),
  KEY `PVal1` (`Value1`),
  KEY `PVal2` (`Value2`),
  KEY `PVal3` (`Value3`),
  KEY `PVal4` (`Value4`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Parameter_Backup`
--

DROP TABLE IF EXISTS `Parameter_Backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Parameter_Backup` (
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Parameter_Backup_Two`
--

DROP TABLE IF EXISTS `Parameter_Backup_Two`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Parameter_Backup_Two` (
  `ParameterId` varchar(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `ParameterGroupId` varchar(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `Value1` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `Value2` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `Value3` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `Value4` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `Description` varchar(1000) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `DeactivateDate` datetime(6) NOT NULL,
  `ReadOnly` tinyint(1) NOT NULL,
  `CreateDate` datetime(6) NOT NULL,
  `CreateId` varchar(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateId` varchar(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ShipPallet`
--

DROP TABLE IF EXISTS `ShipPallet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ShipPallet` (
  `ShipPalletID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `CustomerOrderID` mediumint(9) NOT NULL,
  `CustomerOrderDetailID` mediumint(9) NOT NULL,
  `OfferID` varchar(64) DEFAULT NULL,
  `OfferItemID` varchar(64) DEFAULT NULL,
  `EDIDocIDIn` mediumint(9) NOT NULL,
  `EDIDocIDOut` mediumint(9) NOT NULL,
  `LicensePlate` varchar(32) NOT NULL DEFAULT '',
  `ChepPallet` tinyint(1) NOT NULL DEFAULT 0,
  `PalletSKU` varchar(16) DEFAULT NULL COMMENT 'For SAPUTO pallets',
  `CreateDate` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`ShipPalletID`),
  KEY `CustomerOrderDetailID` (`CustomerOrderDetailID`),
  KEY `LicensePlate` (`LicensePlate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ShipToAddress`
--

DROP TABLE IF EXISTS `ShipToAddress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ShipToAddress` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CustomerID` varchar(64) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Address` varchar(255) NOT NULL,
  `Address2` varchar(255) DEFAULT NULL,
  `City` varchar(255) NOT NULL,
  `State` varchar(255) NOT NULL,
  `Zip` varchar(255) DEFAULT NULL,
  `ConsignedName` varchar(255) NOT NULL,
  `Nickname` varchar(255) DEFAULT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT 1,
  `CreateDate` datetime(6) NOT NULL,
  `CreateID` varchar(64) NOT NULL,
  `UpdateDate` datetime(6) DEFAULT NULL,
  `UpdateID` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID` (`ID`),
  KEY `sta_CustomerID` (`CustomerID`)
) ENGINE=InnoDB AUTO_INCREMENT=255 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `DeliveryDetailID` mediumint(9) DEFAULT NULL,
  PRIMARY KEY (`VatID`),
  UNIQUE KEY `VatID` (`VatID`),
  KEY `Searching` (`VatNumber`,`MakeDate`),
  KEY `LotID_Index` (`LotID`) USING BTREE,
  KEY `VatMakeDate` (`MakeDate`),
  KEY `DeliveryDetailID` (`DeliveryDetailID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WarehouseAdjustment`
--

DROP TABLE IF EXISTS `WarehouseAdjustment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WarehouseAdjustment` (
  `WarehouseAdjustmentID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `WarehouseAdjustmentGroup` mediumint(9) DEFAULT 0,
  `VatID` varchar(64) NOT NULL,
  `LotID` varchar(64) NOT NULL,
  `EDIDocumentID` mediumint(9) DEFAULT NULL,
  `PreviousValue` varchar(64) NOT NULL,
  `NewValue` varchar(64) NOT NULL,
  `ValueTypeChanged` varchar(64) NOT NULL,
  `ValueTypeChangedPlainText` varchar(255) DEFAULT NULL,
  `AdjustmentReason` varchar(2) NOT NULL,
  `NetWeight` decimal(18,2) NOT NULL,
  `CreditDebitQuantity` mediumint(9) NOT NULL,
  `UnitsCreated` decimal(18,2) DEFAULT NULL,
  `MeasurementCode` varchar(2) NOT NULL,
  `ProductID` varchar(64) NOT NULL,
  `InventoryTransactionTypeCode` varchar(2) NOT NULL,
  `ServiceID` varchar(4) NOT NULL,
  `CreateDate` datetime(6) DEFAULT current_timestamp(6),
  `UpdateDate` datetime(6) DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`WarehouseAdjustmentID`),
  KEY `warehouse_adjustment_id` (`WarehouseAdjustmentID`),
  KEY `warehouse_adjustment_group` (`WarehouseAdjustmentGroup`),
  KEY `vat_id` (`VatID`),
  KEY `lot_id` (`LotID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-06-17 17:39:28
