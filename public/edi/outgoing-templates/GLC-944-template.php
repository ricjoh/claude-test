<?php ob_start(); ?>
ISA*00*          *00*          *01*004798716      *01*018219808      *200521*1050*U*00401*<?= $data{ 'ISACtrlNo' } ?>*0*P*>~
GS*RE*004798716*4408342500*20200521*1050*<?= $data{ 'GSCtrlNo' } ?>*X*004010~
ST*944*<?= $data{ 'STCtrlNo' } ?>~
W17*F*<?= $data{ 'ReceiveDate' } ?>*<?= $data{ 'ReceiveNumber' } ?>*<?= $data{ 'VendorOrderNumber' } ?>~
N1*SF**94*<?= $data{ 'ShipFromID' } ?>~
N1*ST**94*<?= $data{ 'ShipToID' } ?>~
N9*F8*<?= $data{ 'OrigRefNumber' } ?>~
<? foreach ( $lines as $line ) : ?>
W07*<?= $line{ 'qtyreceived' } ?>*CA**PN*<?= $line{ 'prodnum' } ?>~
N9*LT*<?= $line{ 'custlotno' } ?>~
N9*LV*<?= $line{ 'licenseplate' } ?>~
N9*LI*<?= $line{ 'line' } ?>~
<?php endforeach ?>
W14*<?= $data{ 'totcases' } ?>~
SE*<?= $data{ 'SECount' } ?>*<?= $data{ 'STCtrlNo' } ?>~
GE*1*<?= $data{ 'GSCtrlNo' } ?>~
IEA*1*<?= $data{ 'ISACtrlNo' } ?>~
<?php
$filecontents = ob_get_contents();
ob_end_clean();
file_put_contents( $filename, $filecontents );
?>