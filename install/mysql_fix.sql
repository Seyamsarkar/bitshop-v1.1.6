ALTER TABLE Products MODIFY COLUMN FileVoteSum INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE Products MODIFY COLUMN FileVoteNum INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE Accounts MODIFY COLUMN PassHash VARCHAR(64) NOT NULL DEFAULT '';
ALTER TABLE Accounts MODIFY COLUMN RealName VARCHAR(50) NOT NULL DEFAULT '';
ALTER TABLE Accounts MODIFY COLUMN Email varchar(50) NOT NULL DEFAULT '';
ALTER TABLE Accounts MODIFY COLUMN Phone VARCHAR(20) NOT NULL DEFAULT '';
ALTER TABLE Accounts MODIFY COLUMN Settings TINYTEXT;
ALTER TABLE Accounts MODIFY COLUMN LastIP TINYTEXT;

ALTER TABLE Orders MODIFY COLUMN Amount DECIMAL(18,8) NOT NULL DEFAULT 0;
ALTER TABLE Orders MODIFY COLUMN Currency VARCHAR(5) NOT NULL DEFAULT '';