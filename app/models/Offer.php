<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class Offer extends Model
{

	public $OfferID;
	public $OfferDate;
	public $Attention;
	public $FOB;
	public $OfferExpiration;
	public $TermsPID;
	public $Note;
	public $UserID;
	public $CustomerID;
	public $ContactID;
	public $OfferStatusPID;
	public $SaleDate;
	public $CustomerPhoneNumber;
	public $CustomerFaxNumber;
	public $CustomerEmail;
	public $OCSContactPhoneNumber;
	public $OCSContactFaxNumber;
	public $OCSContactEmail;
	public $Transferred;
	public $CreateDate;
	public $CreateId;
	public $UpdateDate;
	public $UpdateId;

	public const STATUS_SOLD = 'C5F3B7B9-9340-46B4-820A-504AC2A98A00';
	public const STATUS_EXPIRED = '319FB16C-19F5-4364-82E3-93AD7627AF38';
	public const STATUS_OPEN = '9A085965-75B4-4EE2-85A8-02D61924DCC8';
	public const STATUS_CONTRACT = '7001E6DC-0378-4B4A-9FC7-7AD78805884B';
	public const STATUS_EDIPENDING = 'C478D7C5-25FC-439B-A5A4-A155493ABC08';
	public const STATUS_SHIPPED = 'D05C2544-E8BC-45C9-A3C8-3C7E3AD3F831';

    public function initialize()
    {
        // set the db table to be used
        $this->setSource("Offer");
        $this->belongsTo("CustomerID", "Customer", "CustomerID" );
        $this->hasMany("OfferID", "OfferItem", "OfferID");
        $this->hasOne("OfferID", "BillOfLading", "OfferID");
    }

	public static function getDb() {
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('db');
	}
	public static function getLogger() {
		$di = \Phalcon\DI::getDefault();
		return $di->getShared('logger');
	}

	public static function getOffersByStatus( $statusPID )
	{

		$db = self::getDb();
		$db->connect();

		$sql = "SELECT C.Name as CustomerName, O.OfferID, co.CustomerOrderNum,
			co.EDIDocID,
			Status.Value1 as Status, LEFT( O.OfferDate, 10 ) as OfferDate,
			(SELECT SUM(Weight) FROM OfferItem WHERE OfferID = O.OfferID) AS Weight,
			(SELECT SUM(Pieces) FROM OfferItem WHERE OfferID = O.OfferID) AS Pieces
			FROM Offer AS O
			LEFT OUTER JOIN Customer AS C
					ON C.CustomerID = O.CustomerID
			LEFT OUTER JOIN Parameter Status ON O.OfferStatusPID = Status.ParameterID
			LEFT OUTER JOIN CustomerOrder AS co ON co.OfferID = O.OfferID
			WHERE O.OfferStatusPID = ? ORDER BY co.EDIDocID DESC";

		$params = array( $statusPID );
		$results = $db->query($sql, $params);
		$results->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$results = $results->fetchAll($results);

		return $results;

	}

    public static function getOfferInfo( $db, $offerId )
    {
 $logger = self::getLogger();
		$sql = "SELECT DISTINCT
   Offer.OfferID, Offer.OfferDate, Offer.Attention as ContactID, CONCAT(attention_contact.FirstName, ' ', attention_contact.LastName) as Attention,
   Offer.FOB, Offer.OfferExpiration, Offer.TermsPID, Parameter_1.Value1 AS Terms, trim(Offer.Note) as Note, trim(OfferItem.NoteText) as ItemNoteText,
   Customer.Name AS CustomerName, Customer.CustomerID AS CustomerID, Offer.CustomerPhoneNumber, Offer.CustomerFaxNumber, Offer.CustomerEmail, Offer.UserID,
   Offer.OCSContactPhoneNumber, Offer.OCSContactFaxNumber, Offer.OCSContactEmail, OfferItem.OfferItemID, OfferItemVat.OfferItemVatID,
   Parameter.Value1 AS OfferItemDescription, OfferItem.Pieces AS OfferItemPieces, OfferItem.Weight AS OfferItemWeight,
   OfferItem.Cost AS OfferItemCost, Vat.VatID, Vat.VatNumber, Lot.LotNumber, Lot.LotID, Lot.ProductCode, OfferItemVat.Pieces AS PiecesfromVat,
   OfferItemVat.Weight AS WeightfromVat, OfferItemVat.Price AS PriceForItemsInVat, Vat.MakeDate, Vat.Moisture, Vat.FDB, Vat.PH, Vat.Salt, Vat.CustomerLotNumber,
   OfferItem.Pallets AS OfferItemPallets, Lot.Pallets AS LotPallets, OfferItem.DescriptionPID, SUBSTR(DeliveryDetail.LicensePlate, -8) AS LicensePlate
FROM Lot
RIGHT OUTER JOIN Vat ON Lot.LotID = Vat.LotID
RIGHT OUTER JOIN OfferItemVat ON Vat.VatID = OfferItemVat.VatID
RIGHT OUTER JOIN (Parameter
      RIGHT OUTER JOIN OfferItem ON Parameter.ParameterID = OfferItem.DescriptionPID )
   ON OfferItemVat.OfferItemID = OfferItem.OfferItemID
RIGHT OUTER JOIN (Customer
      RIGHT OUTER JOIN (Parameter AS Parameter_1
            RIGHT OUTER JOIN Offer ON Parameter_1.ParameterID = Offer.TermsPID)
        ON Customer.CustomerID = Offer.CustomerID )
   ON OfferItem.OfferID = Offer.OfferID
LEFT OUTER JOIN Contact attention_contact ON attention_contact.ContactID = Offer.Attention
LEFT OUTER JOIN DeliveryDetail ON DeliveryDetail.DeliveryDetailID = Vat.DeliveryDetailID
WHERE (Offer.OfferID = ?)
   AND (OfferItemVat.Pieces > 0)
GROUP BY
   Offer.OfferID, OfferItem.OfferItemID, OfferItemVat.OfferItemVatID, Offer.OfferDate, attention_contact.FirstName, attention_contact.LastName, Offer.FOB, Offer.OfferExpiration, Parameter_1.Value1, Offer.Note, Customer.Name, Offer.CustomerPhoneNumber, Offer.CustomerFaxNumber, Offer.CustomerEmail, Offer.OCSContactPhoneNumber, Offer.OCSContactFaxNumber, Offer.OCSContactEmail, Parameter.Value1, OfferItem.Pieces, OfferItem.Weight, OfferItem.Cost, Vat.VatNumber, OfferItemVat.Pieces, OfferItemVat.Weight, OfferItemVat.Price, Vat.MakeDate, Vat.Moisture, Vat.FDB, Vat.PH, Vat.Salt, Lot.LotNumber
ORDER BY
   Lot.LotNumber, Vat.MakeDate, Vat.VatNumber, Vat.CustomerLotNumber";

    $params = array( $offerId ); // '00075718-F812-4D25-B5DC-22982DCCEA8A'
// $logger->log( $sql );
    // Multilot: 5F688E55-D3E0-4235-A7C4-982EA06AD707
    // Nice Size: 4A057A5F-FB34-4894-B6D9-06CEE581D508

    $db->connect();

    $result_set = $db->query($sql, $params);
    $result_set->setFetchMode(Phalcon\Db::FETCH_ASSOC);
    $result_set = $result_set->fetchAll($result_set);

    return $result_set;

    }

	public static function isSingleSkuOffer( $offerId ) {
		$sql = "SELECT DISTINCT lot.ProductCode
			FROM Offer offer
			JOIN OfferItem item ON item.OfferID = offer.OfferID
			JOIN Lot lot on item.LotID = lot.LotID
			WHERE offer.OfferID = ?";

		$db = self::getDb();
		$db->connect();

		$params = [ $offerId ];
		$results = $db->query($sql, $params);
		$results->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$results = $results->fetchAll($results);
		return count($results) == 1;
	}

	public static function getSoldOfferItemsForVat( $vatID )
	{
		$db = self::getDb();
		$db->connect();
		// $logger = self::getLogger();

		// $sql = "SELECT oi.OfferItemID, bol.StatusPID, oiv.Pieces, oiv.Weight FROM OfferItem oi
		// 	LEFT JOIN OfferItemVat oiv ON oiv.OfferItemID = oi.OfferItemID
		// 	LEFT JOIN Offer o ON o.OfferID = oi.OfferID
		// 	LEFT JOIN BillOfLading bol ON bol.OfferID = o.OfferID
		// 	WHERE oiv.VatID = :vatID AND o.OfferStatusPID = :statusSold
		// 	AND COALESCE(bol.StatusPID, '') <> :statusShipped"; // TODO: Needs to go away one offer status get updated to Shipped.

		$sql = "SELECT oi.OfferItemID, oiv.Pieces, oiv.Weight FROM OfferItem oi
			LEFT JOIN OfferItemVat oiv ON oiv.OfferItemID = oi.OfferItemID
			LEFT JOIN Offer o ON o.OfferID = oi.OfferID
			WHERE oiv.VatID = :vatID AND o.OfferStatusPID = :statusSold";

		// $logger->log( $sql );

		$params = [ 'vatID' => $vatID, 'statusSold' => self::STATUS_SOLD];
		return $db->fetchAll( $sql, Phalcon\Db::FETCH_ASSOC, $params );
	}

	public static function computeTotalSoldItemsForVat( $vatID ) {
		// $logger = self::getLogger();
		$totals = [ 'Pieces' => 0, 'Weight' => 0 ];
		$soldOfferItems = self::getSoldOfferItemsForVat( $vatID );
		foreach ($soldOfferItems as $item) {
// $logger->log( $sql );

			$totals['Pieces'] += $item['Pieces'];
			$totals['Weight'] += $item['Weight'];
		}
		return $totals;
	}

	public static function getOffersForLot( $lotNumber )
	{
		$db = self::getDb();
		$db->connect();
		// $logger = self::getLogger();

		$sql = "SELECT DISTINCT oi.OfferID,  c.Name, LEFT( o.OfferDate, 10) as Date
    			FROM Lot AS l
    			LEFT JOIN Vat AS v ON v.LotID = l.LotID
    			LEFT JOIN OfferItemVat AS oiv ON oiv.VatID = v.VatID
    			LEFT JOIN OfferItem AS oi ON oiv.OfferItemID = oi.OfferItemID
    			LEFT JOIN Offer AS o ON o.OfferID = oi.OfferID
    			LEFT OUTER JOIN Customer AS c ON c.CustomerID = o.CustomerID
    			WHERE LotNumber = :lotnumber";

		// $logger->log( $sql );

		$params = [ 'lotnumber' => $lotNumber ];
		return $db->fetchAll( $sql, Phalcon\Db::FETCH_ASSOC, $params );
	}

}
