-- ===========================
-- Galtor System Inserts
-- ===========================

-- Insert the star system
INSERT INTO star_systems (name)
VALUES ('Galtor');
SET @galtor_system_id = LAST_INSERT_ID();

-- Insert the planet Galtor III
INSERT INTO planets (name, system_id, position, time_to_jump_point, allegiance, map_background)
VALUES ('Galtor III', @galtor_system_id, 3, 12, 'Davion', '/images/maps/galtor3.jpg');
SET @galtor3_id = LAST_INSERT_ID();

-- Insert major locations (cities with coordinates)
INSERT INTO locations (name, type, terrain, planet_id, coord_x, coord_y) VALUES
('New Derry', 'City', 'Dense Urban', @galtor3_id, 62, 46),
('New Wuhan City', 'City', 'Dense Urban', @galtor3_id, 11, 45),
('Changlee', 'City', 'Dense Urban', @galtor3_id, 45, 15),
('Maeglin', 'City', 'Urban', @galtor3_id, 59, 53),
('St. Colm', 'City', 'Urban', @galtor3_id, 71, 17),
('Glencar', 'City', 'Urban', @galtor3_id, 65, 65),
('Webster', 'City', 'Urban', @galtor3_id, 45, 50),
('Lifford', 'City', 'Rural', @galtor3_id, 38, 65),
('Cead Cathair', 'City', 'Urban', @galtor3_id, 49, 70),
('Buncrana', 'City', 'Urban', @galtor3_id, 70, 85),
('Rathmullan', 'City', 'Urban', @galtor3_id, 49, 88),
('Cloc Ceann Faola', 'City', 'Urban', @galtor3_id, 8, 82);

-- ===========================
-- Marduk System Inserts
-- ===========================

INSERT INTO star_systems (name)
VALUES ('Marduk');
SET @marduk_system_id = LAST_INSERT_ID();

-- Insert the planet Marduk IV
INSERT INTO planets (name, system_id, position, time_to_jump_point, allegiance, map_background)
VALUES ('Marduk IV', @marduk_system_id, 4, 6, 'Davion', '/images/maps/marduk4.jpg');
SET @marduk4_id = LAST_INSERT_ID();

-- Insert major locations (cities, rough placeholder coords until mapped)
INSERT INTO locations (name, type, terrain, planet_id, coord_x, coord_y) VALUES
('New Pontiac', 'City', 'Dense Urban', @marduk4_id, 55.0, 50.0),
('Fort Ea', 'Base', 'Urban', @marduk4_id, 60.0, 52.0),
('Stanton', 'City', 'Urban', @marduk4_id, 45.0, 40.0),
('Victory Industries Complex', 'Industrial Zone', 'Urban', @marduk4_id, 50.0, 48.0),
('Lomen', 'City', 'Urban', @marduk4_id, 42.0, 37.0),
('Wurmensburg', 'City', 'Urban', @marduk4_id, 65.0, 53.0),
('Deshler', 'City', 'Urban', @marduk4_id, 46.0, 42.0);
