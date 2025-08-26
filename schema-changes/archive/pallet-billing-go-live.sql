ALTER TABLE Lot ADD BillingFrequency enum('1 MONTH','2 WEEKS') NOT NULL DEFAULT '1 MONTH' AFTER Pallets;
ALTER TABLE Lot CHANGE DeliveryDetailID DeliveryID mediumint(9) DEFAULT NULL;