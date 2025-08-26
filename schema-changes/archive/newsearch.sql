SELECT l.LotNumber, l.LotID, l.Cost, l.Pallets, v.Name AS VendorName, f.Name AS FactoryName, p.Value1 AS Description,
c.Name AS CustomerName, SUM(vt.Weight) AS VatWeight, SUM(vt.Pieces) AS VatPieces,
(SELECT MIN(MakeDate) FROM Vat WHERE LotID = l.LotID) AS MakeDate,
SUM(iavail.Pieces) - SUM(COALESCE( isold.Pieces, 0 )) AS AvailablePieces,
SUM(iavail.Weight) - SUM(COALESCE( isold.Weight, 0 )) AS AvailableWeight,
l.CustomerPONumber, l.ProductCode,
proom.Value1 AS RoomNumber,   IFNULL(l.RoomTemp, proom.Value2)  AS RoomTemp,  l.Site,
proom2.Value1 AS RoomNumber2, IFNULL(l.RoomTemp2, proom.Value2) AS RoomTemp2, l.Site2,
proom3.Value1 AS RoomNumber3, IFNULL(l.RoomTemp3, proom.Value2) AS RoomTemp3, l.Site3,
inventype.Value1 AS InventoryType, l.DateIn
FROM Lot l
LEFT JOIN Customer c ON l.CustomerID = c.CustomerID
LEFT JOIN Vendor v ON l.VendorID = v.VendorID
LEFT JOIN Factory f ON l.FactoryID = f.FactoryID
LEFT JOIN Parameter p ON l.DescriptionPID = p.ParameterId
LEFT JOIN Parameter proom ON l.RoomPID = proom.ParameterId
LEFT JOIN Parameter proom2 ON l.RoomPID2 = proom2.ParameterId
LEFT JOIN Parameter proom3 ON l.RoomPID3 = proom3.ParameterId
LEFT JOIN Parameter inventype ON l.InventoryTypePID = inventype.ParameterId
INNER JOIN Vat vt ON l.LotID = vt.LotID AND vt.pieces > 0
LEFT JOIN InventoryStatus iavail ON vt.VatID = iavail.VatID AND iavail.InventoryStatusPID = 'D99FC80E-52BC-4AD0-9B10-3E5A5F07EAE0'
LEFT OUTER JOIN InventoryStatus isold ON vt.VatID = isold.VatID AND isold.InventoryStatusPID = 'xD6BB15FC-BA12-46A2-A5EE-9CCCB5BCAC5E'
WHERE 1 = 1  AND l.CustomerID = '62B545A4-9C0D-430C-A88C-5CB37CC8EBEA'
AND l.ProductCode = '176033' AND l.WarehouseID IN (2)  AND l.archived = 0
GROUP BY l.LotNumber, l.LotID
HAVING SUM(iavail.Pieces)> 0 ORDER BY MakeDate DESC, l.LotNumber\G
