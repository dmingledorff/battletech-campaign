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
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS fortification_assignments;
DROP TABLE IF EXISTS artillery_rules;
DROP TABLE IF EXISTS combat_pool;
DROP TABLE IF EXISTS battle_log;
DROP TABLE IF EXISTS event_log;
DROP TABLE IF EXISTS combat_buildings;
DROP TABLE IF EXISTS buildings;
DROP TABLE IF EXISTS mission_units;
DROP TABLE IF EXISTS mission_log;
DROP TABLE IF EXISTS missions;
DROP TABLE IF EXISTS unit_command_history;
DROP TABLE IF EXISTS personnel_equipment;
DROP TABLE IF EXISTS personnel_assignments;
DROP TABLE IF EXISTS equipment;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS personnel;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS campaigns;
DROP TABLE IF EXISTS planets;
DROP TABLE IF EXISTS star_systems;
DROP TABLE IF EXISTS name_pool;
DROP TABLE IF EXISTS callsign_pool;
DROP TABLE IF EXISTS toe_slot_crews;
DROP TABLE IF EXISTS toe_slot_roles;
DROP TABLE IF EXISTS toe_slots;
DROP TABLE IF EXISTS toe_subunits;
DROP TABLE IF EXISTS toe_templates;
DROP TABLE IF EXISTS ranks;
DROP TABLE IF EXISTS game_state;
DROP TABLE IF EXISTS chassis_crew_requirements;
DROP TABLE IF EXISTS factions;
DROP TABLE IF EXISTS chassis;
SET FOREIGN_KEY_CHECKS=1;

-- Core tables

CREATE TABLE game_state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_name VARCHAR(50) NOT NULL UNIQUE,
    property_value VARCHAR(255) NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE factions (
    faction_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    house VARCHAR(50),
    description TEXT,
    emblem_path VARCHAR(255) NULL, -- optional image path for logo
    color VARCHAR(20) DEFAULT '#FFFFFF' -- optional color theme for UI
);


CREATE TABLE star_systems (
    system_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE planets (
    planet_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    system_id INT NOT NULL,
    position INT NOT NULL,
    time_to_jump_point INT NOT NULL,
    allegiance VARCHAR(100),
    map_background VARCHAR(255) NULL,  -- e.g., /images/maps/galtor3.png
    display_order INT DEFAULT 0,
    FOREIGN KEY (system_id) REFERENCES star_systems(system_id)
);

CREATE TABLE locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('City', 'Spaceport', 'Base', 'Industrial Zone'),                -- City, Spaceport, Base, Industrial Zone, etc.
    terrain ENUM('Urban', 'Dense Urban', 'Rural', 'Plains', 'Mountains', 'Hills', 'Woods', 'Marsh', 'Desert'),
    controlled_by INT NULL,
    planet_id INT NOT NULL,
    coord_x FLOAT NOT NULL DEFAULT 0,
    coord_y FLOAT NOT NULL DEFAULT 0,
    display_order INT DEFAULT 0,
    supply_cache DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (planet_id) REFERENCES planets(planet_id),
    FOREIGN KEY (controlled_by) REFERENCES factions(faction_id) ON DELETE SET NULL
);

CREATE TABLE buildings (
    building_id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('Barracks','Hospital','Repair Bay','Spaceport',
              'Command Center','Warehouse','Factory',
              'Power Plant','Fortification') NOT NULL,
    capacity INT NULL,
    status ENUM('Operational','Damaged','Destroyed') DEFAULT 'Operational',
    max_integrity     INT          NOT NULL DEFAULT 100,
    current_integrity INT          NOT NULL DEFAULT 100,
    as_dmg_s          DECIMAL(4,1) NULL,
    as_dmg_m          DECIMAL(4,1) NULL,
    as_dmg_l          DECIMAL(4,1) NULL,
    as_specials       VARCHAR(255) NULL,
    as_tmm            INT          NOT NULL DEFAULT 0,
    max_armor         INT          NULL,
    current_armor     INT          NULL,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE CASCADE
);

CREATE TABLE artillery_rules (
    artillery_id    INT AUTO_INCREMENT PRIMARY KEY,
    special_code    VARCHAR(20)  NOT NULL UNIQUE,
    name            VARCHAR(100) NOT NULL,
    primary_damage  INT          NOT NULL DEFAULT 0,
    splash_damage   INT          NULL,
    aoe_template    INT          NOT NULL DEFAULT 2,
    min_roll        INT          NULL,
    notes           VARCHAR(255) NULL
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
    faction_id INT,
    location_id INT NULL,
    status ENUM('Active','KIA','Retired', 'Injured', 'MIA') DEFAULT 'Active',
    gender ENUM('Male','Female','Other'),
    callsign VARCHAR(50),
    mos ENUM('MechWarrior','Tanker','Infantry','Officer','Medic','Tech') NULL,
    experience ENUM('Green','Regular','Veteran','Elite') DEFAULT 'Green',
    date_of_birth DATE NULL,
    morale DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    missions INT DEFAULT 0,
    FOREIGN KEY (rank_id) REFERENCES ranks(id),
    FOREIGN KEY (faction_id) REFERENCES factions(faction_id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE SET NULL
);

CREATE TABLE toe_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    unit_type ENUM('Regiment','Battalion','Company','Lance','Platoon','Squad') NOT NULL,
    role ENUM(
        'Command','Battle','Striker','Pursuit',
        'Fire','Security','Support','Assault', 'Recon', 'Urban Combat', 'Infantry'
    ) NULL,
    mobility ENUM('Foot','Mechanized','Motorized','Airborne','Jump','Hover') NULL,
    faction VARCHAR(50), -- optional filter
    era VARCHAR(50) -- optional filter
);

SET FOREIGN_KEY_CHECKS = 0;
CREATE TABLE units (
    unit_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    faction_id INT,
    nickname VARCHAR(100),
    current_supply DECIMAL(10,2) DEFAULT 0,
    unit_type ENUM('Regiment','Battalion','Company','Lance','Platoon','Squad') NOT NULL,
    role ENUM(
        'Command','Battle','Striker','Pursuit',
        'Fire','Security','Support','Assault', 'Recon', 'Urban Combat'
    ) NULL,
    status ENUM('Garrisoned','In Transit','Combat','Deactivated','Dispersed') DEFAULT 'Garrisoned',
    mission_id INT NULL,
    parent_unit_id INT,
    commander_id INT,
    location_id INT,
    template_id INT NULL,
    FOREIGN KEY (parent_unit_id) REFERENCES units(unit_id),
    FOREIGN KEY (commander_id) REFERENCES personnel(personnel_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id),
    FOREIGN KEY (template_id) REFERENCES toe_templates(template_id),
    FOREIGN KEY (faction_id) REFERENCES factions(faction_id) ON DELETE SET NULL,
    FOREIGN KEY (mission_id) REFERENCES missions(mission_id) ON DELETE SET NULL
);
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE missions (
    mission_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    mission_type ENUM('Transfer','Resupply','Assault','Recon','Harass','Withdrawal') NOT NULL,
    status ENUM('Planning','In Transit','Arrived','Complete','Aborted','Combat') DEFAULT 'Planning',
    origin_location_id INT NOT NULL,
    destination_location_id INT NOT NULL,
    launched_date DATE NULL,
    eta_date DATE NULL,
    arrived_date DATE NULL,
    distance DECIMAL(8,4) NOT NULL DEFAULT 0,
    transit_days INT NOT NULL DEFAULT 0,
    transit_hours INT NOT NULL DEFAULT 0,
    days_elapsed INT NOT NULL DEFAULT 0,
    hours_elapsed INT NOT NULL DEFAULT 0,
    slowest_speed DECIMAL(8,2) NOT NULL DEFAULT 0,
    current_coord_x DECIMAL(8,4) NULL,
    current_coord_y DECIMAL(8,4) NULL,
    faction_id INT NOT NULL,
    notes TEXT NULL,
	combat_phase    ENUM('Skirmish','Melee','Pursuit') NULL,
    combat_round    INT DEFAULT 0,
    FOREIGN KEY (origin_location_id) REFERENCES locations(location_id),
    FOREIGN KEY (destination_location_id) REFERENCES locations(location_id),
    FOREIGN KEY (faction_id) REFERENCES factions(faction_id)
);

CREATE TABLE mission_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mission_id INT NOT NULL,
    unit_id INT NOT NULL,
    FOREIGN KEY (mission_id) REFERENCES missions(mission_id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(unit_id) ON DELETE CASCADE
);

CREATE TABLE chassis (
    chassis_id          INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(100) NOT NULL,
    variant             VARCHAR(50),
    type                ENUM('BattleMech','Vehicle','APC') NOT NULL,
    weight_class        ENUM('Light','Medium','Heavy','Assault','Infantry') NOT NULL,
    battlefield_role    ENUM(
                            'Ambusher','Brawler','Missile Boat','Juggernaut',
                            'Scout','Sniper','Skirmisher','Striker',
                            'Command','MASH','Repair','Supply','Transport', 'Artillery'
                        ) DEFAULT 'Brawler',
    supply_consumption  DECIMAL(6,2),
    tonnage             INT,
    speed               DECIMAL(5,2)    COMMENT 'kph — used for travel/transit',
    -- Alpha Strike stats
    as_pv               INT NULL,
    as_type             VARCHAR(10) NULL,
    as_size             INT NULL,
    as_tmm              INT NULL,
    as_mv               INT NULL        COMMENT 'Movement in AS inches — used for combat',
    as_mv_type          VARCHAR(5) NULL COMMENT 'g/j/v/f/w/t/h',
    as_dmg_s            DECIMAL(4,1) NULL,
    as_dmg_m            DECIMAL(4,1) NULL,
    as_dmg_l            DECIMAL(4,1) NULL,
    as_dmg_e            DECIMAL(4,1) NULL,
    as_ov               INT NULL,
    as_armor            INT NULL,
    as_structure        INT NULL,
    as_threshold        INT NULL,
    as_specials         VARCHAR(255) NULL
);

CREATE TABLE chassis_crew_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chassis_id INT NOT NULL,
    crew_role ENUM('Commander','Driver','Gunner','Loader','Pilot','Dismount', 'Crew') NOT NULL,
    is_required BOOLEAN DEFAULT TRUE,
    required_mos VARCHAR(50),
    FOREIGN KEY (chassis_id) REFERENCES chassis(chassis_id) ON DELETE CASCADE
);

CREATE TABLE equipment (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    chassis_id INT NOT NULL,
    serial_number VARCHAR(50) UNIQUE NOT NULL,
    assigned_unit_id INT,
    location_id INT,
    faction_id INT,
    damage_percentage DECIMAL(5,2) DEFAULT 0.0,
    equipment_status ENUM(
        'Active',
        'Destroyed',
        'Salvaged',
        'Repair',
        'Mothballed'
    ) NOT NULL DEFAULT 'Active',
    max_armor          INT NULL,
    max_structure      INT NULL,
    current_armor      INT NULL,
    current_structure  INT NULL,
    heat_buildup       INT DEFAULT 0,
    combat_status      ENUM('Operational','Crippled','Destroyed') DEFAULT 'Operational',
    salvage_status    ENUM('None','Available','Claimed','Scrap') DEFAULT 'None',
    FOREIGN KEY (chassis_id) REFERENCES chassis(chassis_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_unit_id) REFERENCES units(unit_id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE SET NULL,
    FOREIGN KEY (faction_id) REFERENCES factions(faction_id) ON DELETE SET NULL
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
    slot_id INT NULL,
    role VARCHAR(50),
    date_assigned DATE,
    date_released DATE,
    is_active TINYINT AS (IF(date_released IS NULL, 1, NULL)) STORED,
    FOREIGN KEY (personnel_id) REFERENCES personnel(personnel_id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES chassis_crew_requirements(id) ON DELETE SET NULL,
    UNIQUE KEY unique_slot_assignment (equipment_id, slot_id, is_active)
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

CREATE TABLE toe_slots (
    slot_id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    slot_type ENUM('Personnel','Equipment','SubUnit') NOT NULL,
    -- Personnel slots
    mos ENUM('MechWarrior','Tanker','Infantry','Officer','Medic','Tech') NULL,
    min_rank_id INT,
    max_rank_id INT,
    min_grade INT NULL,
    max_grade INT NULL,
    -- Equipment slots
    equipment_type ENUM('BattleMech','Vehicle','APC','Aerospace','Infantry') NULL,
    weight_class ENUM('Light','Medium','Heavy','Assault') NULL,
    chassis_id INT NULL,
    crew_size INT DEFAULT 1, -- 1 = Mechs, >1 = Vehicles
    -- Subunits
    subunit_template_id INT NULL,
    is_core BOOLEAN DEFAULT TRUE, -- differentiate core vs detachment
    FOREIGN KEY (template_id) REFERENCES toe_templates(template_id) ON DELETE CASCADE,
    FOREIGN KEY (min_rank_id) REFERENCES ranks(id) ON DELETE SET NULL,
    FOREIGN KEY (max_rank_id) REFERENCES ranks(id) ON DELETE SET NULL,
    FOREIGN KEY (chassis_id) REFERENCES chassis(chassis_id) ON DELETE SET NULL
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
        'Scout','Sniper','Skirmisher','Striker','MASH',
        'Repair', 'Supply', 'Transport', 'Artillery'
    ) NOT NULL,
    FOREIGN KEY (slot_id) REFERENCES toe_slots(slot_id) ON DELETE CASCADE
);

CREATE TABLE toe_slot_crews (
    crew_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_slot_id INT NOT NULL,
    personnel_slot_id INT NOT NULL,
    crew_role ENUM('Commander','Driver','Gunner','Loader','Tech','Pilot','Dismount','Crew') NOT NULL,
    FOREIGN KEY (equipment_slot_id) REFERENCES toe_slots(slot_id) ON DELETE CASCADE,
    FOREIGN KEY (personnel_slot_id) REFERENCES toe_slots(slot_id) ON DELETE CASCADE
);

CREATE TABLE event_log (
    log_id       INT AUTO_INCREMENT PRIMARY KEY,
    faction_id   INT NULL,
    game_date    DATE NOT NULL,
    log_type     ENUM(
                     'System',
                     'Mission',
                     'Combat',
                     'Supply',
                     'Maintenance',
                     'Personnel',
                     'World',
                     'Intel'
                 ) NOT NULL DEFAULT 'System',
    severity     ENUM('Info','Warning','Critical') NOT NULL DEFAULT 'Info',
    title        VARCHAR(150) NOT NULL,
    description  TEXT NULL,
    -- Optional FK references for filtering/linking
    unit_id      INT NULL,
    mission_id   INT NULL,
    location_id  INT NULL,
    personnel_id INT NULL,
    FOREIGN KEY (faction_id)   REFERENCES factions(faction_id)   ON DELETE CASCADE,
    FOREIGN KEY (unit_id)      REFERENCES units(unit_id)         ON DELETE SET NULL,
    FOREIGN KEY (mission_id)   REFERENCES missions(mission_id)   ON DELETE SET NULL,
    FOREIGN KEY (location_id)  REFERENCES locations(location_id) ON DELETE SET NULL,
    FOREIGN KEY (personnel_id) REFERENCES personnel(personnel_id) ON DELETE SET NULL,
    INDEX idx_faction_date (faction_id, game_date),
    INDEX idx_log_type    (log_type)
);

CREATE TABLE battle_log (
    log_id       INT AUTO_INCREMENT PRIMARY KEY,
    mission_id   INT NOT NULL,
    game_date    DATE NOT NULL,
    game_hour    TINYINT NOT NULL DEFAULT 0,
    combat_phase ENUM('Skirmish','Melee','Pursuit') NOT NULL,
    combat_round INT NOT NULL,
    log_type     ENUM(
                     'RoundSummary','Attack','Damage','Crippled',
                     'Destroyed','Ejection','Retreat',
                     'PhaseChange','BattleStart','BattleEnd'
                 ) NOT NULL,
    attacker_id  INT NULL,
    target_id    INT NULL,
    damage_dealt DECIMAL(5,2) NULL,
    description  TEXT NOT NULL,
    FOREIGN KEY (mission_id)  REFERENCES missions(mission_id) ON DELETE CASCADE,
    FOREIGN KEY (attacker_id) REFERENCES equipment(equipment_id) ON DELETE SET NULL,
    INDEX idx_mission_round (mission_id, combat_round),
    INDEX idx_mission_phase (mission_id, combat_phase)
);

CREATE TABLE combat_buildings (
    combat_building_id  INT AUTO_INCREMENT PRIMARY KEY,
    mission_id          INT          NOT NULL,
    building_id         INT          NOT NULL,
    name                VARCHAR(100) NOT NULL,
    type                VARCHAR(50)  NOT NULL,
    capacity 			INT 		 NULL,
    current_integrity   INT          NOT NULL,
    max_integrity       INT          NOT NULL,
    current_armor       INT          NULL,
    max_armor           INT          NULL,
    as_dmg_s            DECIMAL(4,1) NULL,
    as_dmg_m            DECIMAL(4,1) NULL,
    as_dmg_l            DECIMAL(4,1) NULL,
    as_specials         VARCHAR(255) NULL,
    as_tmm              INT          NOT NULL DEFAULT 0,
    status              ENUM('Operational','Damaged','Destroyed') DEFAULT 'Operational',
    FOREIGN KEY (mission_id)  REFERENCES missions(mission_id)  ON DELETE CASCADE,
    FOREIGN KEY (building_id) REFERENCES buildings(building_id)
);

CREATE TABLE fortification_assignments (
    assignment_id       INT AUTO_INCREMENT PRIMARY KEY,
    combat_building_id  INT NOT NULL,
    unit_id             INT NOT NULL,
    mission_id          INT NOT NULL,
    FOREIGN KEY (combat_building_id) REFERENCES combat_buildings(combat_building_id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id)            REFERENCES units(unit_id),
    FOREIGN KEY (mission_id)         REFERENCES missions(mission_id) ON DELETE CASCADE
);

CREATE TABLE combat_pool (
    pool_id            INT AUTO_INCREMENT PRIMARY KEY,
    mission_id         INT NOT NULL,
    side               ENUM('attacker','defender') NOT NULL,
    participant_type   ENUM('equipment','infantry') NOT NULL,
    unit_id            INT NOT NULL,
    equipment_id       INT NULL,
    personnel_id       INT NULL,
    status             ENUM('Active','Crippled','Retreated','Destroyed','Routed') DEFAULT 'Active',
    joined_at          DATE NOT NULL,
    resolved           TINYINT DEFAULT 0,
    pilot_first_name   VARCHAR(100) NULL,
    pilot_last_name    VARCHAR(100) NULL,
    pilot_rank_abbr    VARCHAR(20)  NULL,
    pilot_experience   VARCHAR(20)  NULL,
    pilot_morale       DECIMAL(5,2) NULL,
    pilot_final_status VARCHAR(20)  NULL DEFAULT 'Active',
    current_armor      INT NULL,
    current_structure  INT NULL,
    max_armor          INT NULL,
    max_structure      INT NULL,
    structure_at_death INT NULL,
    heat_buildup  INT         NOT NULL DEFAULT 0,
    is_shutdown   TINYINT  NOT NULL DEFAULT 0,
    used_ov       TINYINT  NOT NULL DEFAULT 0,
    building_id   INT         NULL,
    FOREIGN KEY (mission_id)   REFERENCES missions(mission_id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id)      REFERENCES units(unit_id),
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id),
    FOREIGN KEY (personnel_id) REFERENCES personnel(personnel_id),
    FOREIGN KEY (building_id) REFERENCES combat_buildings(combat_building_id),
    INDEX idx_mission_side     (mission_id, side),
    INDEX idx_mission_status   (mission_id, status),
    INDEX idx_mission_resolved (mission_id, resolved)
);