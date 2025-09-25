-- ==============================
-- Schema for 1st Davion Guards Regiment Database
-- Includes AUTO_INCREMENT on IDs, chassis tonnage & speed,
-- personnel attributes, and locations with planet linkage
-- ==============================
use battletech;
-- Drop views first
DROP VIEW IF EXISTS unit_summary;
DROP VIEW IF EXISTS unit_personnel_equipment;
DROP VIEW IF EXISTS unit_hierarchy_chain;

-- Drop dependent tables (reverse FK order)
DROP TABLE IF EXISTS unit_command_history;
DROP TABLE IF EXISTS personnel_equipment;
DROP TABLE IF EXISTS personnel_assignments;
DROP TABLE IF EXISTS equipment;
DROP TABLE IF EXISTS chassis;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS personnel;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS campaigns;
DROP TABLE IF EXISTS planets;

-- Core tables

CREATE TABLE planets (
    planet_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    allegiance VARCHAR(100)
);

CREATE TABLE campaigns (
    campaign_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_date DATE,
    end_date DATE,
    theater VARCHAR(100)
);

CREATE TABLE personnel (
    personnel_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    grade VARCHAR(30),
    status ENUM('Active','KIA','Retired') DEFAULT 'Active',
    gender ENUM('Male','Female','Other'),
    callsign VARCHAR(50),
    mos VARCHAR(50),  -- Military Occupational Specialty
    experience ENUM('Green','Regular','Veteran','Elite') DEFAULT 'Green',
    missions_completed INT DEFAULT 0
);

CREATE TABLE locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50),                -- City, Spaceport, Base, Industrial Zone, etc.
    planet_id INT NOT NULL,
    FOREIGN KEY (planet_id) REFERENCES planets(planet_id)
);

CREATE TABLE units (
    unit_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    nickname VARCHAR(100),
    current_supply DECIMAL(10,2) DEFAULT 0,
    unit_type ENUM('Regiment','Battalion','Company','Lance','InfantryPlatoon','Squad') NOT NULL,
    allegiance VARCHAR(100),
    parent_unit_id INT,
    commander_id INT,
    location_id INT,
    FOREIGN KEY (parent_unit_id) REFERENCES units(unit_id),
    FOREIGN KEY (commander_id) REFERENCES personnel(personnel_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

CREATE TABLE chassis (
    chassis_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('BattleMech','Vehicle','InfantryWeapon') NOT NULL,
    weight_class ENUM('Light','Medium','Heavy','Assault','Infantry') NOT NULL,
    tonnage INT,                       -- Always multiples of 5
    speed DECIMAL(6,2),                -- in kph
    hard_attack INT,
    soft_attack INT,
    armor_value INT,
    ammo_reliance DECIMAL(4,2),
    supply_consumption DECIMAL(6,2)
);

CREATE TABLE equipment (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    chassis_id INT NOT NULL,
    serial_number VARCHAR(50) UNIQUE NOT NULL,
    assigned_unit_id INT,
    damage_percentage DECIMAL(5,2) DEFAULT 0.0,
    equipment_status VARCHAR(50) NOT NULL DEFAULT 'Active',
    FOREIGN KEY (chassis_id) REFERENCES chassis(chassis_id),
    FOREIGN KEY (assigned_unit_id) REFERENCES units(unit_id)
);

CREATE TABLE personnel_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    unit_id INT NOT NULL,
    date_assigned DATE,
    date_released DATE,
    FOREIGN KEY (personnel_id) REFERENCES personnel(personnel_id),
    FOREIGN KEY (unit_id) REFERENCES units(unit_id)
);

CREATE TABLE personnel_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    equipment_id INT NOT NULL,
    role VARCHAR(50),
    date_assigned DATE,
    date_released DATE,
    FOREIGN KEY (personnel_id) REFERENCES personnel(personnel_id),
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id)
);

CREATE TABLE unit_command_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    commander_id INT NOT NULL,
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (unit_id) REFERENCES units(unit_id),
    FOREIGN KEY (commander_id) REFERENCES personnel(personnel_id)
);

-- Views

CREATE VIEW unit_summary AS
SELECT u.unit_id, u.name, u.nickname, u.unit_type,
       COUNT(DISTINCT pa.personnel_id) AS personnel_count,
       COUNT(DISTINCT e.equipment_id) AS equipment_count
FROM units u
LEFT JOIN personnel_assignments pa ON u.unit_id = pa.unit_id
LEFT JOIN equipment e ON u.unit_id = e.assigned_unit_id
GROUP BY u.unit_id;

CREATE VIEW unit_personnel_equipment AS
SELECT u.unit_id, u.name AS unit_name,
       p.personnel_id, CONCAT(p.first_name,' ',p.last_name) AS personnel_name, p.grade,
       e.equipment_id, c.name AS chassis_name, pe.role
FROM units u
JOIN personnel_assignments pa ON u.unit_id = pa.unit_id
JOIN personnel p ON pa.personnel_id = p.personnel_id
LEFT JOIN personnel_equipment pe ON p.personnel_id = pe.personnel_id
LEFT JOIN equipment e ON pe.equipment_id = e.equipment_id
LEFT JOIN chassis c ON e.chassis_id = c.chassis_id;

CREATE VIEW unit_hierarchy_chain AS
SELECT u.unit_id, u.name, u.unit_type, u.parent_unit_id, pu.name AS parent_name
FROM units u
LEFT JOIN units pu ON u.parent_unit_id = pu.unit_id;
