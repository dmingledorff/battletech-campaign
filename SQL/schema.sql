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
DROP TABLE IF EXISTS name_pool;
DROP TABLE IF EXISTS callsign_pool;
DROP TABLE IF EXISTS lance_template_slots;
DROP TABLE IF EXISTS lance_templates;
DROP TABLE IF EXISTS toe_slot_roles;
DROP TABLE IF EXISTS toe_slot_crews;
DROP TABLE IF EXISTS toe_slots;
DROP TABLE IF EXISTS toe_subunits;
DROP TABLE IF EXISTS toe_templates;
DROP TABLE IF EXISTS ranks;

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

CREATE TABLE ranks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faction VARCHAR(50) NOT NULL,          -- e.g. 'Davion', 'Kurita', 'Generic'
    full_name VARCHAR(50) NOT NULL,        -- e.g. 'Sergeant'
    abbreviation VARCHAR(10) NOT NULL,     -- e.g. 'Sgt'
    grade INT NOT NULL                     -- order of precedence, lower = junior
);

CREATE TABLE personnel (
    personnel_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    rank_id INT,
    status ENUM('Active','KIA','Retired') DEFAULT 'Active',
    gender ENUM('Male','Female','Other'),
    callsign VARCHAR(50),
    mos VARCHAR(50),  -- Military Occupational Specialty
    experience ENUM('Green','Regular','Veteran','Elite') DEFAULT 'Green',
    morale DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    missions INT DEFAULT 0,
    FOREIGN KEY (rank_id) REFERENCES ranks(id)
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
    role ENUM(
        'Command','Battle','Striker','Pursuit',
        'Fire','Security','Support','Assault', 'Recon', 'Urban Combat'
    ) NULL,
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
    variant VARCHAR(50),
    type ENUM('BattleMech','Vehicle','APC') NOT NULL,
    weight_class ENUM('Light','Medium','Heavy','Assault','Infantry') NOT NULL,
    battlefield_role ENUM(
        'Ambusher',
        'Brawler',
        'Missile Boat',
        'Juggernaut',
        'Scout','Sniper'
        ,'Skirmisher'
        ,'Striker'
    ) DEFAULT 'Brawler',
    hard_attack INT,
    soft_attack INT,
    armor_value INT,
    ammo_reliance DECIMAL(4,2),
    supply_consumption DECIMAL(6,2),
    tonnage INT,
    speed DECIMAL(5,2)
);

CREATE TABLE equipment (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    chassis_id INT NOT NULL,
    serial_number VARCHAR(50) UNIQUE NOT NULL,
    assigned_unit_id INT,
    damage_percentage DECIMAL(5,2) DEFAULT 0.0,
    equipment_status ENUM(
        'Active',
        'Destroyed',
        'Salvaged',
        'Repair',
        'Mothballed'
    ) NOT NULL DEFAULT 'Active',
    FOREIGN KEY (chassis_id) REFERENCES chassis(chassis_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_unit_id) REFERENCES units(unit_id) ON DELETE SET NULL
);

CREATE TABLE personnel_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    unit_id INT NOT NULL,
    date_assigned DATE,
    date_released DATE,
    FOREIGN KEY (personnel_id) REFERENCES personnel(personnel_id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(unit_id) ON DELETE CASCADE
);

CREATE TABLE personnel_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    equipment_id INT NOT NULL,
    role VARCHAR(50),
    date_assigned DATE,
    date_released DATE,
    FOREIGN KEY (personnel_id) REFERENCES personnel(personnel_id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE
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

CREATE TABLE name_pool (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faction VARCHAR(50) NOT NULL, -- e.g. 'Davion', 'Kurita', 'Liao', 'Generic'
    name_type ENUM('first_male','first_female','last') NOT NULL,
    value VARCHAR(50) NOT NULL
);

CREATE TABLE callsign_pool (
    id INT AUTO_INCREMENT PRIMARY KEY,
    value VARCHAR(50) NOT NULL,
    used BOOLEAN DEFAULT FALSE
);

CREATE TABLE toe_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    unit_type ENUM('Regiment','Battalion','Company','Lance','Platoon','Squad') NOT NULL,
    role ENUM(
        'Command','Battle','Striker','Pursuit',
        'Fire','Security','Support','Assault', 'Recon', 'Urban Combat'
    ) NULL,
    faction VARCHAR(50), -- optional filter
    era VARCHAR(50) -- optional filter
);

CREATE TABLE toe_slots (
    slot_id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    slot_type ENUM('Personnel','Equipment','SubUnit') NOT NULL,
    -- Personnel slots
    mos VARCHAR(50), -- e.g. MechWarrior, Tanker, Infantry
    min_rank_id INT,
    max_rank_id INT,
    -- Equipment slots
    equipment_type ENUM('BattleMech','Vehicle','APC','Aerospace','Infantry') NULL,
    weight_class SET('Light','Medium','Heavy','Assault') NULL,
    crew_size INT DEFAULT 1, -- 1 = Mechs, >1 = Vehicles
    -- Subunits
    subunit_template_id INT NULL,
    is_core BOOLEAN DEFAULT TRUE, -- differentiate core vs detachment
    FOREIGN KEY (template_id) REFERENCES toe_templates(template_id) ON DELETE CASCADE,
    FOREIGN KEY (min_rank_id) REFERENCES ranks(id) ON DELETE SET NULL,
    FOREIGN KEY (max_rank_id) REFERENCES ranks(id) ON DELETE SET NULL
);

CREATE TABLE toe_subunits (
    subunit_id INT AUTO_INCREMENT PRIMARY KEY,
    parent_template_id INT NOT NULL,
    child_template_id INT NOT NULL,
    quantity INT DEFAULT 1,
    is_core BOOLEAN DEFAULT TRUE,  -- Core vs Detachment
    is_command BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (parent_template_id) REFERENCES toe_templates(template_id) ON DELETE CASCADE,
    FOREIGN KEY (child_template_id) REFERENCES toe_templates(template_id) ON DELETE CASCADE
);

CREATE TABLE toe_slot_roles (
    slot_role_id INT AUTO_INCREMENT PRIMARY KEY,
    slot_id INT NOT NULL,
    battlefield_role ENUM(
        'Ambusher','Brawler','Missile Boat','Juggernaut',
        'Scout','Sniper','Skirmisher','Striker'
    ) NOT NULL,
    FOREIGN KEY (slot_id) REFERENCES toe_slots(slot_id) ON DELETE CASCADE
);

CREATE TABLE toe_slot_crews (
    crew_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_slot_id INT NOT NULL,
    personnel_slot_id INT NOT NULL,
    crew_role ENUM('Commander','Driver','Gunner','Loader','Tech', 'Pilot', 'Dismount') NOT NULL,
    FOREIGN KEY (equipment_slot_id) REFERENCES toe_slots(slot_id) ON DELETE CASCADE,
    FOREIGN KEY (personnel_slot_id) REFERENCES toe_slots(slot_id) ON DELETE CASCADE
);


