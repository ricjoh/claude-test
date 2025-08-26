-- Add Reference Number to EDIDocument
ALTER TABLE EDIDocument ADD COLUMN ReferenceNumber VARCHAR(100) NULL;
CREATE INDEX ReferenceNumber ON EDIDocument (ReferenceNumber);