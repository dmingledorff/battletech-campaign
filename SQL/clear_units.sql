USE battletech;
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE units;
TRUNCATE TABLE personnel_equipment;
TRUNCATE TABLE personnel_assignments;
TRUNCATE TABLE equipment;
TRUNCATE TABLE missions;
TRUNCATE TABLE mission_units;
TRUNCATE TABLE combat_pool;
TRUNCATE TABLE combat_buildings;
TRUNCATE TABLE fortification_assignments;
TRUNCATE TABLE personnel;
TRUNCATE TABLE event_log;
TRUNCATE TABLE battle_log;
SET FOREIGN_KEY_CHECKS=1;

UPDATE buildings
SET current_integrity = 20,
    max_integrity = 20,
    status = 'Operational'
WHERE building_id IN (5,6);
