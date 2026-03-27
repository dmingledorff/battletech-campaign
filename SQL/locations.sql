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
INSERT INTO locations (name, type, terrain, planet_id, coord_x, coord_y, controlled_by) VALUES
('New Derry', 'City', 'Dense Urban', @galtor3_id, 62, 46, 1),
('New Wuhan City', 'City', 'Dense Urban', @galtor3_id, 11, 45, NULL),
('Changlee', 'City', 'Dense Urban', @galtor3_id, 45, 15, NULL),
('Maeglin', 'City', 'Urban', @galtor3_id, 59, 53, NULL),
('St. Colm', 'City', 'Urban', @galtor3_id, 71, 17, NULL),
('Glencar', 'City', 'Urban', @galtor3_id, 65, 65, NULL),
('Webster', 'City', 'Urban', @galtor3_id, 45, 50, NULL),
('Lifford', 'City', 'Rural', @galtor3_id, 38, 65, NULL),
('Cead Cathair', 'City', 'Urban', @galtor3_id, 49, 70, NULL),
('Buncrana', 'City', 'Urban', @galtor3_id, 70, 85, 2),
('Rathmullan', 'City', 'Urban', @galtor3_id, 49, 88, 2),
('Cloc Ceann Faola', 'City', 'Urban', @galtor3_id, 8, 82, 2);

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
INSERT INTO locations (name, type, terrain, planet_id, coord_x, coord_y, controlled_by) VALUES
('New Pontiac', 'City', 'Dense Urban', @marduk4_id, 55.0, 50.0, NULL),
('Fort Ea', 'Base', 'Urban', @marduk4_id, 60.0, 52.0, NULL),
('Stanton', 'City', 'Urban', @marduk4_id, 45.0, 40.0, NULL),
('Victory Industries Complex', 'Industrial Zone', 'Urban', @marduk4_id, 50.0, 48.0, NULL),
('Lomen', 'City', 'Urban', @marduk4_id, 42.0, 37.0, NULL),
('Wurmensburg', 'City', 'Urban', @marduk4_id, 65.0, 53.0, NULL),
('Deshler', 'City', 'Urban', @marduk4_id, 46.0, 42.0, NULL);


-- Buildings
INSERT INTO buildings (location_id, name, type, capacity, status) VALUES
(1, 'Fort New Derry', 'Barracks', NULL, 'Operational'),
(1, 'New Derry Spaceport', 'Spaceport', NULL, 'Operational'),
(1, 'Field Hospital Alpha', 'Hospital', 50, 'Operational'),
(1, 'Repair Bay 1', 'Repair Bay', 8, 'Operational');
