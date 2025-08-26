SELECT
   Offer.OfferID
 , Offer.OfferDate
 , attention_contact.FirstName + ' ' + attention_contact.LastName as Attention
 , Offer.FOB
 , Offer.OfferExpiration
 , Parameter_1.Value1 AS Terms
 , Offer.Note
 , Customer.Name AS CustomerName
 , Offer.CustomerPhoneNumber
 , Offer.CustomerFaxNumber
 , Offer.CustomerEmail
 , Offer.OCSContactPhoneNumber
 , Offer.OCSContactFaxNumber
 , Offer.OCSContactEmail
 , OfferItem.OfferItemID
 , OfferItemVat.OfferItemVatID
 , Parameter.Value1 AS OfferItemDescription
 , OfferItem.Pieces AS OfferItemPieces
 , OfferItem.Weight AS OfferItemWeight
 , OfferItem.Cost AS OfferItemCost
 , Vat.VatNumber
 , Lot.LotNumber
 , OfferItemVat.Pieces AS PiecesfromVat
 , OfferItemVat.Weight AS WeightfromVat
 , OfferItemVat.EstPallets
 , OfferItemVat.Price AS PriceForItemsInVat
 , Vat.MakeDate
 , Vat.Moisture
 , Vat.FDB
 , Vat.PH
 , Vat.Salt
FROM Lot 
RIGHT OUTER JOIN Vat ON Lot.LotID = Vat.LotID 
RIGHT OUTER JOIN OfferItemVat ON Vat.VatID = OfferItemVat.VatID 
RIGHT OUTER JOIN Parameter 
      RIGHT OUTER JOIN OfferItem ON Parameter.ParameterId = OfferItem.DescriptionPID 
   ON OfferItemVat.OfferItemID = OfferItem.OfferItemID 
RIGHT OUTER JOIN Customer 
      RIGHT OUTER JOIN Parameter AS Parameter_1 
            RIGHT OUTER JOIN Offer ON Parameter_1.ParameterId = Offer.TermsPID 
        ON Customer.CustomerID = Offer.CustomerID 
   ON OfferItem.OfferID = Offer.OfferID
RIGHT OUTER JOIN Contact attention_contact ON attention_contact.ContactID = Offer.Attention
WHERE (Offer.OfferID = '5A71B5AD-E063-4E27-B06F-994AF3F91BCB') 
   AND (OfferItemVat.Pieces > 0)
GROUP BY 
   Offer.OfferID
 , OfferItem.OfferItemID
 , OfferItemVat.OfferItemVatID
 , Offer.OfferDate
 , attention_contact.FirstName
 , attention_contact.LastName
 , Offer.FOB
 , Offer.OfferExpiration
 , Parameter_1.Value1
 , Offer.Note
 , Customer.Name
 , Offer.CustomerPhoneNumber
 , Offer.CustomerFaxNumber
 , Offer.CustomerEmail
 , Offer.OCSContactPhoneNumber
 , Offer.OCSContactFaxNumber
 , Offer.OCSContactEmail
 , Parameter.Value1
 , OfferItem.Pieces
 , OfferItem.Weight
 , OfferItem.Cost
 , Vat.VatNumber
 , OfferItemVat.Pieces
 , OfferItemVat.Weight
 , OfferItemVat.EstPallets
 , OfferItemVat.Price
 , Vat.MakeDate
 , Vat.Moisture
 , Vat.FDB
 , Vat.PH
 , Vat.Salt
 , Lot.LotNumber
ORDER BY 
   Lot.LotNumber
 , Vat.MakeDate
 , Vat.VatNumber