INSERT INTO EDIDocument
(EDIKey, Transaction, ControlNumber, Incoming, DocISAID, DocGSID, Status, X12FilePath)
VALUES (
	'GLC',
	'945',
	'64655',
	0,
	'018219808',
	'4408342500',
	'Pending',
	'/web/html/edi-oshkosh/samples/945_sample.x12'
);

INSERT INTO EDIDocument
(EDIKey, Transaction, ControlNumber, Incoming, DocISAID, DocGSID, Status, X12FilePath)
VALUES (
	'GLC',
	'997',
	'45565',
	0,
	'018219808',
	'4408342500',
	'Sent',
	'/web/html/edi-oshkosh/samples/997_sample.x12'
);

INSERT INTO EDIDocument
(EDIKey, Transaction, ControlNumber, Incoming, DocISAID, DocGSID, Status, X12FilePath)
VALUES (
	'GLC',
	'947',
	'0165645',
	0,
	'018219808',
	'4408342500',
	'Sent',
	'/web/html/edi-oshkosh/samples/947_sample.x12'
);

select ControlNumber, DocID, EDIKey, Transaction ,Incoming, DocISAID ,Status , X12FilePath from EDIDocument;
