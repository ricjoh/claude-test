DELIMITER $$
DROP PROCEDURE IF EXISTS inv;
CREATE PROCEDURE inv( IN vat VARCHAR(64) )
BEGIN
    SELECT CASE WHEN InventoryStatusPID LIKE 'C6%' THEN 'Offered'
             WHEN InventoryStatusPID LIKE 'D9%' THEN 'Available'
             WHEN InventoryStatusPID LIKE '23%' THEN 'Unavailable'
             WHEN InventoryStatusPID LIKE 'D6%' THEN 'SoldUnshipped'
        END AS Status, Pieces, Weight, UpdateDate, VatID
        FROM InventoryStatus WHERE VatID = vat ORDER BY Status;
END$$
DELIMITER ;

CALL inv('43373CA0-51B5-4356-9C72-129163210486');

DELIMITER $$
DROP PROCEDURE IF EXISTS offers;
CREATE PROCEDURE offers( IN lot VARCHAR(64) )
BEGIN
    SELECT DISTINCT oi.OfferID,  c.Name, LEFT( o.OfferDate, 10) as Date, l.LotNumber
    FROM Lot AS l
    LEFT JOIN Vat AS v ON v.LotID = l.LotID
    LEFT JOIN OfferItemVat AS oiv ON oiv.VatID = v.VatID
    LEFT JOIN OfferItem AS oi ON oiv.OfferItemID = oi.OfferItemID
    LEFT JOIN Offer AS o ON o.OfferID = oi.OfferID
    LEFT OUTER JOIN Customer AS c ON c.CustomerID = o.CustomerID
    WHERE LotNumber = lot ORDER BY Date DESC;

END$$
DELIMITER ;

call offers('301624');

DROP TABLE IF EXISTS `V_CustomerInventory`;

DELIMITER $$
DROP PROCEDURE IF EXISTS vats;
CREATE PROCEDURE vats( IN lot VARCHAR(64) )
BEGIN
    SELECT DISTINCT v.VatID, v.VatNumber, v.CustomerLotNumber, LEFT( v.MakeDate, 10) as MakeDate, l.LotNumber, l.LotID
    FROM Lot AS l
    LEFT JOIN Vat AS v ON v.LotID = l.LotID
    WHERE l.LotNumber = lot OR l.LotID = lot ORDER BY VatNumber, MakeDate DESC;
END$$
DELIMITER ;

call vats('E69F909F-D53E-4FE8-A056-3B2A774000C7');



INSERT INTO Parameter VALUES ('D6BB15FC-BA12-46A2-A5EE-9CCCB5BCAC5E', '8E321110-EA4D-4FF1-A70A-5E7AC496DDA8', 'Sold/Not Shipped', NULL, NULL, NULL, 'Unavailable for sale, but available for inventory control.', '2025-12-31 00:00:00.000000', 0, '2024-06-27 14:54:08.000000', 'E87EA5C0-0152-419C-9D91-5438C7EC5C37', NULL, NULL);



