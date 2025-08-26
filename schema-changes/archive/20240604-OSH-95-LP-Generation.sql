/**************************
Branch: OSH-95-LP-Generation
Date: 2024-06-04
Author: Ric
Description:
    Create LogisticsUnit Table for storing License Plate (LP) for Logistics Units (LU) in the warehouse by VatID.
    Added a trigger to generate a GS1-128 compliant License Plate (LP) for Logistics Units (LU) in the LogisticsUnit table.

Notes:
-- Oshkosh Cold Storage, LLC. GS1 Prefix:
-- 08600042725

-- Legal Entity Global Location Number (GLN): 0860004272502

-- https://www.gs1.org/services/how-calculate-check-digit-manually
-- https://www.gs1.org/services/check-digit-calculator
**/


DELIMITER $$
DROP PROCEDURE IF EXISTS GS1128CheckDigit;
DROP FUNCTION IF EXISTS GS1128CheckDigit;
CREATE FUNCTION GS1128CheckDigit( barcode VARCHAR(255) ) RETURNS INT
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE sum INT DEFAULT 0;
    DECLARE char_val INT;
    DECLARE char_length INT;
    DECLARE check_digit_code INT;
    DECLARE multiplier INT DEFAULT 3;

    SET char_length = CHAR_LENGTH(barcode);
    SET sum = 0; -- Include the start character's value in the sum

    WHILE i <= char_length DO
        SET char_val = CAST(SUBSTRING(barcode, i, 1) AS INT); -- Convert character to its equivalent numeric value
        SET sum = sum + (char_val * multiplier); -- Weighted sum: value times its position
        IF (multiplier = 1) THEN
            SET multiplier = 3;
        ELSE
            SET multiplier = 1;
        END IF;
        SET i = i + 1;
    END WHILE;

    SET check_digit_code = (10 - (sum MOD 10)) MOD 10; -- Calculate Modulo 10 to find the check digit
    RETURN check_digit_code;
END$$

DELIMITER ;

DROP TABLE IF EXISTS `LogisticsUnit`;
CREATE TABLE `LogisticsUnit` (
    `LogisticsUnitID` MEDIUMINT NOT NULL AUTO_INCREMENT,
    `LicensePlate` VARCHAR(32) NOT NULL DEFAULT '',
    `VatID` VARCHAR(64) DEFAULT NULL,
    `CreateDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `UpdateDate` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`LogisticsUnitID`),
  UNIQUE KEY `LicensePlate` (`LicensePlate`),
  KEY `VatID` (`VatID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DELIMITER $$
DROP TRIGGER IF EXISTS `CreateLicensePlate`;
CREATE TRIGGER `CreateLicensePlate` BEFORE INSERT ON LogisticsUnit
FOR EACH ROW
BEGIN
    DECLARE LP CHAR(32);
    DECLARE check_digit INT;
    DECLARE LID INT;

    if (NEW.LogisticsUnitID=0) then
        set @A = (SELECT AUTO_INCREMENT
        FROM information_schema.TABLES
        WHERE TABLE_NAME = "LogisticsUnit" AND TABLE_SCHEMA = DATABASE());
    end if;
    SET LID = @A;

    SET LP = CONCAT('08600042725', LPAD(@A, 6, '0'));
    SET check_digit = GS1128CheckDigit(LP);
    SET NEW.LicensePlate = CONCAT(LP, check_digit);
END$$
DELIMITER ;
