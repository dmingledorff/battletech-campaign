ALTER TABLE users
    ADD COLUMN faction_id INT NULL,
    ADD FOREIGN KEY (faction_id) REFERENCES factions(faction_id);