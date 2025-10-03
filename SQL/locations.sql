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
('New Derry', 'City', 'Dense Urban', @galtor3_id, 30.5, 42.0),
('New Wuhan City', 'City', 'Dense Urban', @galtor3_id, 72.0, 55.5),
('Changlee', 'City', 'Dense Urban', @galtor3_id, 68.0, 48.0),
('Maeglin', 'City', 'Urban', @galtor3_id, 40.0, 25.0),
('St. Colm', 'City', 'Urban', @galtor3_id, 28.0, 38.5),
('Glencar', 'City', 'Urban', @galtor3_id, 26.5, 41.0),
('Webster', 'City', 'Urban', @galtor3_id, 36.0, 28.0),
('Lifford', 'City', 'Rural', @galtor3_id, 29.0, 36.5),
('Cead Cathair', 'City', 'Urban', @galtor3_id, 33.0, 39.0),
('Buncrana', 'City', 'Urban', @galtor3_id, 25.0, 43.0),
('Rathmullan', 'City', 'Urban', @galtor3_id, 27.0, 44.5),
('Cloc Ceann Faola', 'City', 'Urban', @galtor3_id, 24.0, 46.0);

-- ===========================
-- Marduk System Inserts
-- ===========================

INSERT INTO star_systems (name)
VALUES ('Marduk');
SET @marduk_system_id = LAST_INSERT_ID();

-- Insert the planet Marduk IV
INSERT INTO planets (name, system_id, position, time_to_jump_point, allegiance)
VALUES ('Marduk IV', @marduk_system_id, 4, 6, 'Davion');
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
