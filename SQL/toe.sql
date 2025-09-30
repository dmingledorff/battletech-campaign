INSERT INTO toe_templates (name, description, unit_type, faction, era)
VALUES ('Combined Arms Battalion', 'One battalion with mech, vehicle, and infantry companies', 'Battalion', 'Federated Suns', '3025');

INSERT INTO toe_templates (name, description, unit_type, faction, era)
VALUES ('Striker Company', 'Two striker lances and one fire lance', 'Company', 'Federated Suns', '3025');

-- Striker Lance template
INSERT INTO toe_templates (name, description, unit_type, faction, era)
VALUES ('Striker Lance', 'Fast medium mechs with striker focus', 'Lance', 'Federated Suns', '3025');

-- Fire Lance template
INSERT INTO toe_templates (name, description, unit_type, faction, era)
VALUES ('Fire Lance', 'Long-range fire support', 'Lance', 'Federated Suns', '3025');

-- Link Striker Company → 2x Striker Lance + 1x Fire Lance
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES 
    (LAST_INSERT_ID()-2, LAST_INSERT_ID()-1, 2, TRUE), -- Striker lances
    (LAST_INSERT_ID()-2, LAST_INSERT_ID(), 1, TRUE);   -- Fire lance

-- Four mechs, medium striker/scout mix
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class, crew_size)
VALUES 
    ( (SELECT template_id FROM toe_templates WHERE name='Striker Lance'), 'Equipment', 'BattleMech', 'Medium', 1),
    ( (SELECT template_id FROM toe_templates WHERE name='Striker Lance'), 'Equipment', 'BattleMech', 'Medium', 1),
    ( (SELECT template_id FROM toe_templates WHERE name='Striker Lance'), 'Equipment', 'BattleMech', 'Medium', 1),
    ( (SELECT template_id FROM toe_templates WHERE name='Striker Lance'), 'Equipment', 'BattleMech', 'Light', 1);

-- Allowed roles
INSERT INTO toe_slot_roles (slot_id, battlefield_role) VALUES
    (1,'Striker'), (1,'Skirmisher'),
    (2,'Striker'), (2,'Skirmisher'),
    (3,'Striker'), (3,'Skirmisher'),
    (4,'Striker'), (4,'Scout');

INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class, crew_size)
VALUES 
    ( (SELECT template_id FROM toe_templates WHERE name='Fire Lance'), 'Equipment', 'BattleMech', 'Heavy', 1),
    ( (SELECT template_id FROM toe_templates WHERE name='Fire Lance'), 'Equipment', 'BattleMech', 'Heavy', 1),
    ( (SELECT template_id FROM toe_templates WHERE name='Fire Lance'), 'Equipment', 'BattleMech', 'Medium', 1),
    ( (SELECT template_id FROM toe_templates WHERE name='Fire Lance'), 'Equipment', 'BattleMech', 'Medium', 1);

INSERT INTO toe_slot_roles (slot_id, battlefield_role) VALUES
    (5,'Missile Boat'), (5,'Sniper'),
    (6,'Missile Boat'), (6,'Sniper'),
    (7,'Missile Boat'), (7,'Sniper'),
    (8,'Missile Boat'), (8,'Sniper');

-- Reinforced vehicle company

INSERT INTO toe_templates (name, description, unit_type, faction, era)
VALUES ('Reinforced Vehicle Company', 'Four vehicle lances with tank crews', 'Company', 'Federated Suns', '3025');

-- Vehicle Lance template
INSERT INTO toe_templates (name, description, unit_type, faction, era)
VALUES ('Vehicle Lance', 'Four combat vehicles, 3 crew each', 'Lance', 'Federated Suns', '3025');

-- Link Company → 4x Vehicle Lances
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (LAST_INSERT_ID()-1, LAST_INSERT_ID(), 4, TRUE);

-- Vehicle slots
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class, crew_size)
VALUES 
    ((SELECT template_id FROM toe_templates WHERE name='Vehicle Lance'),'Equipment','Vehicle','Heavy',3),
    ((SELECT template_id FROM toe_templates WHERE name='Vehicle Lance'),'Equipment','Vehicle','Heavy',3),
    ((SELECT template_id FROM toe_templates WHERE name='Vehicle Lance'),'Equipment','Vehicle','Heavy',3),
    ((SELECT template_id FROM toe_templates WHERE name='Vehicle Lance'),'Equipment','Vehicle','Medium',3);

-- Crew slots linked with toe_slot_crews (commander, driver, gunner)
-- Example for first vehicle slot:
INSERT INTO toe_slots (template_id, slot_type, mos)
VALUES 
    ((SELECT template_id FROM toe_templates WHERE name='Vehicle Lance'),'Personnel','Tanker'),
    ((SELECT template_id FROM toe_templates WHERE name='Vehicle Lance'),'Personnel','Tanker'),
    ((SELECT template_id FROM toe_templates WHERE name='Vehicle Lance'),'Personnel','Tanker');

INSERT INTO toe_slot_crews (equipment_slot_id, personnel_slot_id, crew_role)
VALUES (9, 10, 'Commander'), (9, 11, 'Driver'), (9, 12, 'Gunner');

-- Infantry company

INSERT INTO toe_templates (name, description, unit_type, faction, era)
VALUES ('Infantry Company', 'Three mechanized platoons with APCs and squads', 'Company', 'Federated Suns', '3025');

INSERT INTO toe_templates (name, description, unit_type, faction, era)
VALUES ('Infantry Platoon (Mechanized)', 'Four squads, each with APC transport and infantry', 'Platoon', 'Federated Suns', '3025');

-- Link Company → 3 Platoons
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (
    (SELECT template_id FROM toe_templates WHERE name='Infantry Company'),
    (SELECT template_id FROM toe_templates WHERE name='Infantry Platoon (Mechanized)'),
    3, TRUE
);

INSERT INTO toe_templates (name, description, unit_type, faction, era)
VALUES ('Infantry Squad', 'One APC and 8 infantry soldiers', 'Squad', 'Federated Suns', '3025');

-- Link Platoon → 4 Squads
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (
    (SELECT template_id FROM toe_templates WHERE name='Infantry Platoon (Mechanized)'),
    (SELECT template_id FROM toe_templates WHERE name='Infantry Squad'),
    4, TRUE
);

-- APC slot
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class, crew_size)
VALUES (
    (SELECT template_id FROM toe_templates WHERE name='Infantry Squad'),
    'Equipment', 'APC', 'Light', 2
);

-- APC crew slots
INSERT INTO toe_slots (template_id, slot_type, mos)
VALUES
    ((SELECT template_id FROM toe_templates WHERE name='Infantry Squad'), 'Personnel', 'Infantry'),
    ((SELECT template_id FROM toe_templates WHERE name='Infantry Squad'), 'Personnel', 'Infantry');

-- Link crew to APC
INSERT INTO toe_slot_crews (equipment_slot_id, personnel_slot_id, crew_role)
VALUES
    (LAST_INSERT_ID()-2, LAST_INSERT_ID()-1, 'Driver'),
    (LAST_INSERT_ID()-2, LAST_INSERT_ID(), 'Gunner');

INSERT INTO toe_slots (template_id, slot_type, mos)
VALUES
    ((SELECT template_id FROM toe_templates WHERE name='Infantry Squad'), 'Personnel', 'Infantry'),
    ((SELECT template_id FROM toe_templates WHERE name='Infantry Squad'), 'Personnel', 'Infantry'),
    ((SELECT template_id FROM toe_templates WHERE name='Infantry Squad'), 'Personnel', 'Infantry'),
    ((SELECT template_id FROM toe_templates WHERE name='Infantry Squad'), 'Personnel', 'Infantry'),
    ((SELECT template_id FROM toe_templates WHERE name='Infantry Squad'), 'Personnel', 'Infantry'),
    ((SELECT template_id FROM toe_templates WHERE name='Infantry Squad'), 'Personnel', 'Infantry');

-- Battalion composition

INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES
    ((SELECT template_id FROM toe_templates WHERE name='Combined Arms Battalion'),
     (SELECT template_id FROM toe_templates WHERE name='Striker Company'), 1, TRUE),
    ((SELECT template_id FROM toe_templates WHERE name='Combined Arms Battalion'),
     (SELECT template_id FROM toe_templates WHERE name='Reinforced Vehicle Company'), 1, TRUE),
    ((SELECT template_id FROM toe_templates WHERE name='Combined Arms Battalion'),
     (SELECT template_id FROM toe_templates WHERE name='Infantry Company'), 1, TRUE);
