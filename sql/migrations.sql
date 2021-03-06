-- Doctrine Migration File Generated on 2012-10-14 14:10:10
-- Migrating from 0 to 20121013121601

-- Version 20120820212620
CREATE TABLE pastes (id INTEGER PRIMARY KEY NOT NULL, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, paste TEXT NOT NULL, token VARCHAR(50), filename VARCHAR(100));

-- Version 20120820212710
ALTER TABLE pastes ADD COLUMN ip BLOB(16);

-- Version 20120929184739
CREATE TABLE paste_content (id INTEGER PRIMARY KEY NOT NULL, content TEXT NOT NULL, digest CHAR(32));
ALTER TABLE pastes ADD COLUMN content_id INTEGER;
INSERT INTO paste_content (content) SELECT paste FROM pastes GROUP BY paste;
CREATE TABLE pastes__tmp (id INTEGER PRIMARY KEY NOT NULL, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, token VARCHAR(50), filename VARCHAR(100), ip BLOB(16), content_id INTEGER, FOREIGN KEY(content_id) REFERENCES paste_content(id));
INSERT INTO pastes__tmp SELECT p.id, p.timestamp, p.token, p.filename, p.ip, c.id FROM pastes p, paste_content c WHERE c.content = p.paste;
DROP TABLE pastes;
ALTER TABLE pastes__tmp RENAME TO pastes;

-- Version 20121013121601
ALTER TABLE pastes ADD COLUMN highlight BOOLEAN DEFAULT 1;
UPDATE pastes SET highlight = 1;
