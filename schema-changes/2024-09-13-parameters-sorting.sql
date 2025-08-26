-- set roles & statuses to sort by Value2
UPDATE ParameterGroup SET Heading2 = 'Sort', PrimarySortField = 2 WHERE Name = 'Role';
Update ParameterGroup SET PrimarySortField = 2 WHERE Name = 'UserStatus';
-- set the sort order for the roles
UPDATE Parameter SET Value2 = 0 WHERE ParameterGroupId = 'F9626EBA-6D4A-408D-BDF6-9A8FFBB00631' AND ParameterId = '53317BBE-0C8F-4C59-8611-2BACBB73A17D';
UPDATE Parameter SET Value2 = 1 WHERE ParameterGroupId = 'F9626EBA-6D4A-408D-BDF6-9A8FFBB00631' AND ParameterId = 'E5928C79-0878-44C8-B715-0A838FA8FC61';
