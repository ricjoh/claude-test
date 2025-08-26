alter table CustomerOrder add column MetaData longtext not null default '{}';
alter table CustomerOrderDetail add column MetaData longtext not null default '{}';
