
TRUNCATE InventoryInquiry;
TRUNCATE WarehouseAdjustment;

DELETE from CustomerOrder WHERE EDIDocId in ( select DocId from EDIDocument where EDIKey = 'LOL' );
DELETE from CustomerOrderDetail WHERE EDIDocId in ( select DocId from EDIDocument where EDIKey = 'LOL' );
DELETE from CustomerShipDetail WHERE EDIDocId in ( select DocId from EDIDocument where EDIKey = 'LOL' );
DELETE from OrderOfferDetailMap WHERE EDIDocId in ( select DocId from EDIDocument where EDIKey = 'LOL' );

DELETE from DeliveryDetailReceipt WHERE DeliveryDetailID in (SELECT DeliveryDetailID from DeliveryDetail WHERE EDIDocId in ( SELECT DocId FROM EDIDocument WHERE EDIKey = 'LOL' ));
-- DELETE from DeliveryDetailReceipt WHERE CreateDate > '2021-12-07 12:00:00.000000'
DELETE from DeliveryDetail WHERE EDIDocId in ( select DocId from EDIDocument where EDIKey = 'LOL' ); 
DELETE from Delivery WHERE CreateDate > '2021-12-07 12:00:00.000000';
 
DELETE from OfferItemVat WHERE CreateDate > '2021-12-07 12:00:00.000000';
DELETE from OfferItem WHERE CreateDate > '2021-12-07 12:00:00.000000';
DELETE from Offer WHERE CreateDate > '2021-12-07 12:00:00.000000';

DELETE from BillOfLading WHERE CreateDate > '2021-12-07 12:00:00.000000';
update Lot set StatusPID = '103A03A1-9B62-4FD1-BFDD-32C2C0026415' WHERE StatusPID = 'BABA841F-7D26-4A4D-8342-897F4F500B64';
update Offer set OfferStatusPID = '319FB16C-19F5-4364-82E3-93AD7627AF38' WHERE OfferStatusPID = 'C478D7C5-25FC-439B-A5A4-A155493ABC08';

DELETE from EDIDocument WHERE EDIKey = 'LOL';

-- SELECT * from Vat WHERE CreateDate > '2021-12-07 12:00:00.000000';

