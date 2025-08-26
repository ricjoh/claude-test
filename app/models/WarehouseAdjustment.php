<?php

use Phalcon\Mvc\Model;

class WarehouseAdjustment extends Model
{
    public $WarehouseAdjustmentID;
    public $WarehouseAdjustmentGroup;
    public $VatID;
    public $LotID;
    public $EDIDocumentID;
    public $PreviousValue;
    public $NewValue;
    public $ValueTypeChanged;
    public $ValueTypeChangedPlainText;
    public $AdjustmentReason;
    public $NetWeight;
    public $CreditDebitQuantity;
    public $UnitsCreated;
    public $MeasurementCode;
    public $ProductID;
    public $InventoryTransactionTypeCode;
    public $ServiceID;
    public $CreateDate;
    public $UpdateDate;

    public const INVENTORY_TRANSACTION_INVENTORY_ADJUSTMENT_DECREASE = 'AD';
    public const INVENTORY_TRANSACTION_COMMITMENT = 'CM';
    public const INVENTORY_TRANSACTION_HOLD_QUARANTINED_INVENTORY = 'QU';
    public const INVENTORY_TRANSACTION_SALEABLE_INVENTORY = 'SA';

    public const MD_PRODUCT_ID_GOOD = '0';
    public const MD_PRODUCT_HOLD_OR_RESERVED_STOCK = 'HOLD';

    public const CHANGED_WEIGHT = 'Weight';
    public const CHANGED_PIECES = 'Pieces';
    public const CHANGED_PRODUCT_CODE = 'Product Code';

    public const PRODUCT_SERVICE_ID_ARRAY = [
        self::MD_PRODUCT_ID_GOOD => 'Good',
        self::MD_PRODUCT_HOLD_OR_RESERVED_STOCK => 'Hold or reserve stock'
    ];

    public const TYPE_WEIGHT_CHANGE = 'weight';

	public function initialize()
	{
		// set the db table to be used
		$this->setSource("WarehouseAdjustment");
	}

    public static function getWarehouseAdjustmentsByGroup($warehouseAdjustmentGroup)
    {
        return WarehouseAdjustment::find(
            [
                'conditions' => 'WarehouseAdjustmentGroup = :warehouseAdjustmentGroup:',
                'bind' => ['warehouseAdjustmentGroup' => $warehouseAdjustmentGroup]
            ]
        );
    }
}

/* =====================================================================================================================
Table description
+------------------------------+---------------+------+-----+----------------------+--------------------------------+
| Field                        | Type          | Null | Key | Default              | Extra                          |
+------------------------------+---------------+------+-----+----------------------+--------------------------------+
| WarehouseAdjustmentID        | mediumint(9)  | NO   | PRI | NULL                 | auto_increment                 |
| WarehouseAdjustmentGroup     | mediumint(9)  | YES  | MUL | 0                    |                                |
| VatID                        | varchar(64)   | NO   | MUL | NULL                 |                                |
| LotID                        | varchar(64)   | NO   | MUL | NULL                 |                                |
| EDIDocumentID                | mediumint(9)  | YES  |     | NULL                 |                                |
| PreviousValue                | varchar(64)   | NO   |     | NULL                 |                                |
| NewValue                     | varchar(64)   | NO   |     | NULL                 |                                |
| ValueTypeChanged             | varchar(64)   | NO   |     | NULL                 |                                |
| ValueTypeChangedPlainText    | varchar(255)  | YES  |     | NULL                 |                                |
| AdjustmentReason             | varchar(2)    | NO   |     | NULL                 |                                |
| NetWeight                    | decimal(18,2) | NO   |     | NULL                 |                                |
| CreditDebitQuantity          | mediumint(9)  | NO   |     | NULL                 |                                |
| UnitsCreated                 | decimal(18,2) | YES  |     | NULL                 |                                |
| MeasurementCode              | varchar(2)    | NO   |     | NULL                 |                                |
| ProductID                    | varchar(64)   | NO   |     | NULL                 |                                |
| InventoryTransactionTypeCode | varchar(2)    | NO   |     | NULL                 |                                |
| ServiceID                    | varchar(4)    | NO   |     | NULL                 |                                |
| CreateDate                   | datetime(6)   | YES  |     | current_timestamp(6) |                                |
| UpdateDate                   | datetime(6)   | YES  |     | current_timestamp(6) | on update current_timestamp(6) |
+------------------------------+---------------+------+-----+----------------------+--------------------------------+
===================================================================================================================== */
