-- ================================
-- Rank Variables
-- ================================
SET @marshal   = (SELECT id FROM ranks WHERE full_name = 'Marshal'   LIMIT 1);
SET @major     = (SELECT id FROM ranks WHERE full_name = 'Major'   LIMIT 1);
SET @captain   = (SELECT id FROM ranks WHERE full_name = 'Captain'   LIMIT 1);
SET @lieutenant= (SELECT id FROM ranks WHERE full_name = 'Leftenant' LIMIT 1);
SET @sergeant  = (SELECT id FROM ranks WHERE full_name = 'Sergeant'  LIMIT 1);
SET @corporal  = (SELECT id FROM ranks WHERE full_name = 'Corporal'  LIMIT 1);
SET @private   = (SELECT id FROM ranks WHERE full_name = 'Private'   LIMIT 1);

-- ================================
-- 1st Davion Guards Regiment
-- ================================
INSERT INTO toe_templates (name, description, unit_type)
VALUES ('1st Davion Guards', 'A medium BattleMech regiment with support.', 'Regiment');
SET @regiment_id = LAST_INSERT_ID();

-- Regiment commander (Marshal)
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@regiment_id, 'Personnel', 'Officer', @marshal, @marshal);

-- ================================
-- Combined Arms Battalion
-- ================================
INSERT INTO toe_templates (name, description, unit_type)
VALUES ('Combined Arms Battalion', 'A balanced battalion with mech, vehicle, and infantry companies', 'Battalion');
SET @battalion_id = LAST_INSERT_ID();

INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@regiment_id, @battalion_id, 1, TRUE);

-- Command Lance
INSERT INTO toe_templates (name, description, unit_type, role)
VALUES ('Command Lance', 'Heavy lance to support the battalion commander', 'Lance', 'Command');
SET @command_lance_id = LAST_INSERT_ID();

-- Equipment
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@command_lance_id, 'Equipment', 'BattleMech', 'Heavy'); SET @c_eq1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@command_lance_id, 'Equipment', 'BattleMech', 'Heavy'); SET @c_eq2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@command_lance_id, 'Equipment', 'BattleMech', 'Heavy');  SET @c_eq3 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@command_lance_id, 'Equipment', 'BattleMech', 'Medium');  SET @c_eq4 = LAST_INSERT_ID();

-- Equipment roles
INSERT INTO toe_slot_roles (slot_id, battlefield_role) VALUES
(@c_eq1, 'Brawler'), (@c_eq1, 'Juggernaut'),
(@c_eq2, 'Brawler'), (@c_eq2, 'Juggernaut'),
(@c_eq3, 'Brawler'), (@c_eq3, 'Juggernaut'),
(@c_eq4, 'Brawler'), (@c_eq4, 'Juggernaut');

-- Personnel (Major + 3 Sergeants)
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@command_lance_id, 'Personnel', 'MechWarrior', @lieutenant, @major);      SET @c1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@command_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);     SET @c2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@command_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);     SET @c3 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@command_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);     SET @c4 = LAST_INSERT_ID();

-- Attach lances to battalion
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core, is_command)
VALUES (@battalion_id, @command_lance_id, 1, TRUE, TRUE);

-- ================================
-- Battle Company
-- ================================
INSERT INTO toe_templates (name, description, unit_type, role)
VALUES ('Battle Company', 'Balanced BattleMech company with battle and fire lances', 'Company', 'Battle');
SET @battle_company_id = LAST_INSERT_ID();

INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@battalion_id, @battle_company_id, 2, TRUE);

-- Battle Lance
INSERT INTO toe_templates (name, description, unit_type, role)
VALUES ('Battle Lance', 'Balanced lance with medium and heavy BattleMechs', 'Lance', 'Battle');
SET @battle_lance_id = LAST_INSERT_ID();

-- Equipment
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@battle_lance_id, 'Equipment', 'BattleMech', 'Heavy'); SET @eq1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@battle_lance_id, 'Equipment', 'BattleMech', 'Medium'); SET @eq2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@battle_lance_id, 'Equipment', 'BattleMech', 'Heavy');  SET @eq3 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@battle_lance_id, 'Equipment', 'BattleMech', 'Medium');  SET @eq4 = LAST_INSERT_ID();

-- Equipment roles
INSERT INTO toe_slot_roles (slot_id, battlefield_role) VALUES
(@eq1, 'Brawler'), (@eq1, 'Skirmisher'),
(@eq2, 'Brawler'), (@eq2, 'Skirmisher'),
(@eq3, 'Juggernaut'), (@eq3, 'Brawler'),
(@eq4, 'Juggernaut'), (@eq4, 'Brawler');

-- Personnel (Lieutenant + 3 Sergeants)
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@battle_lance_id, 'Personnel', 'MechWarrior', @lieutenant, @lieutenant); SET @p1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@battle_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);   SET @p2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@battle_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);   SET @p3 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@battle_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);   SET @p4 = LAST_INSERT_ID();

-- Fire Lance (reuse from Striker example, but with battle role)
INSERT INTO toe_templates (name, description, unit_type, role)
VALUES ('Fire Lance', 'Missile boat/sniper lance for long-range firepower', 'Lance', 'Fire');
SET @fire_lance_id = LAST_INSERT_ID();

-- Equipment
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@fire_lance_id, 'Equipment', 'BattleMech', 'Medium'); SET @eq5 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@fire_lance_id, 'Equipment', 'BattleMech', 'Heavy'); SET @eq6 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@fire_lance_id, 'Equipment', 'BattleMech', 'Heavy');  SET @eq7 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@fire_lance_id, 'Equipment', 'BattleMech', 'Heavy');  SET @eq8 = LAST_INSERT_ID();

-- Equipment roles
INSERT INTO toe_slot_roles (slot_id, battlefield_role) VALUES
(@eq5, 'Sniper'), (@eq5, 'Missile Boat'),
(@eq6, 'Sniper'), (@eq6, 'Missile Boat'),
(@eq7, 'Sniper'), (@eq7, 'Missile Boat'),
(@eq8, 'Sniper'), (@eq8, 'Missile Boat');

-- Personnel (Lieutenant + 3 Sergeants)
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@fire_lance_id, 'Personnel', 'MechWarrior', @lieutenant, @lieutenant); SET @p5 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@fire_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);     SET @p6 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@fire_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);     SET @p7 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@fire_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);     SET @p8 = LAST_INSERT_ID();

-- Attach lances to Battle Company
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@battle_company_id, @battle_lance_id, 2, TRUE),
       (@battle_company_id, @fire_lance_id, 1, TRUE);

-- ================================
-- Striker Company
-- ================================
INSERT INTO toe_templates (name, description, unit_type, role)
VALUES ('Striker Company', 'Fast BattleMech company with striker and fire lances', 'Company', 'Striker');
SET @striker_company_id = LAST_INSERT_ID();

INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@battalion_id, @striker_company_id, 1, TRUE);

-- Striker Lance
INSERT INTO toe_templates (name, description, unit_type, role)
VALUES ('Striker Lance', 'Fast medium BattleMech lance', 'Lance', 'Striker');
SET @striker_lance_id = LAST_INSERT_ID();

-- Equipment
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@striker_lance_id, 'Equipment', 'BattleMech', 'Medium'); SET @eq1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@striker_lance_id, 'Equipment', 'BattleMech', 'Medium'); SET @eq2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@striker_lance_id, 'Equipment', 'BattleMech', 'Medium'); SET @eq3 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@striker_lance_id, 'Equipment', 'BattleMech', 'Light');  SET @eq4 = LAST_INSERT_ID();

-- Equipment roles
INSERT INTO toe_slot_roles (slot_id, battlefield_role) VALUES
(@eq1, 'Skirmisher'), (@eq1, 'Striker'),
(@eq2, 'Skirmisher'), (@eq2, 'Striker'),
(@eq3, 'Skirmisher'), (@eq3, 'Striker'),
(@eq4, 'Skirmisher'), (@eq4, 'Striker');

-- Personnel (Lieutenant + 3 Sergeants)
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@striker_lance_id, 'Personnel', 'MechWarrior', @lieutenant, @lieutenant); SET @p1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@striker_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);   SET @p2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@striker_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);   SET @p3 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@striker_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);   SET @p4 = LAST_INSERT_ID();

-- Fire Lance
INSERT INTO toe_templates (name, description, unit_type, role)
VALUES ('Fire Lance', 'Missile boat/sniper lance for long-range firepower', 'Lance', 'Fire');
SET @fire_lance_id = LAST_INSERT_ID();

-- Equipment
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@fire_lance_id, 'Equipment', 'BattleMech', 'Medium'); SET @eq5 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@fire_lance_id, 'Equipment', 'BattleMech', 'Medium'); SET @eq6 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@fire_lance_id, 'Equipment', 'BattleMech', 'Heavy');  SET @eq7 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@fire_lance_id, 'Equipment', 'BattleMech', 'Heavy');  SET @eq8 = LAST_INSERT_ID();

-- Equipment roles
INSERT INTO toe_slot_roles (slot_id, battlefield_role) VALUES
(@eq5, 'Sniper'), (@eq5, 'Missile Boat'),
(@eq6, 'Sniper'), (@eq6, 'Missile Boat'),
(@eq7, 'Sniper'), (@eq7, 'Missile Boat'),
(@eq8, 'Sniper'), (@eq8, 'Missile Boat');

-- Personnel (Lieutenant + 3 Sergeants)
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@fire_lance_id, 'Personnel', 'MechWarrior', @lieutenant, @lieutenant); SET @p5 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@fire_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);     SET @p6 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@fire_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);     SET @p7 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@fire_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);     SET @p8 = LAST_INSERT_ID();

-- Attach lances to Striker Company
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@striker_company_id, @striker_lance_id, 2, TRUE),
       (@striker_company_id, @fire_lance_id, 1, TRUE);

-- ================================
-- Reinforced Vehicle Company
-- ================================
INSERT INTO toe_templates (name, description, unit_type, role)
VALUES ('Reinforced Vehicle Company', 'Company with 4 armored lances', 'Company', 'Battle');
SET @vehicle_company_id = LAST_INSERT_ID();

INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@battalion_id, @vehicle_company_id, 1, TRUE);

-- Vehicle Lance
INSERT INTO toe_templates (name, description, unit_type, role)
VALUES ('Vehicle Lance', '4 combat vehicles with full crews', 'Lance', 'Battle');
SET @vehicle_lance_id = LAST_INSERT_ID();

-- ================================
-- Vehicle 1
-- ================================
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@vehicle_lance_id, 'Equipment', 'Vehicle', 'Heavy'); 
SET @v1_eq = LAST_INSERT_ID();

INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @lieutenant, @lieutenant); SET @v1_p1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @corporal, @corporal); SET @v1_p2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @private, @private);  SET @v1_p3 = LAST_INSERT_ID();

-- ================================
-- Vehicle 2
-- ================================
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@vehicle_lance_id, 'Equipment', 'Vehicle', 'Medium'); 
SET @v2_eq = LAST_INSERT_ID();

INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @sergeant, @sergeant); SET @v2_p1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @corporal, @corporal); SET @v2_p2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @private, @private);  SET @v2_p3 = LAST_INSERT_ID();

-- ================================
-- Vehicle 3
-- ================================
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@vehicle_lance_id, 'Equipment', 'Vehicle', 'Medium'); 
SET @v3_eq = LAST_INSERT_ID();

INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @sergeant, @sergeant); SET @v3_p1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @corporal, @corporal); SET @v3_p2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @private, @private);  SET @v3_p3 = LAST_INSERT_ID();

-- ================================
-- Vehicle 4
-- ================================
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@vehicle_lance_id, 'Equipment', 'Vehicle', 'Light'); 
SET @v4_eq = LAST_INSERT_ID();

INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @sergeant, @sergeant); SET @v4_p1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @corporal, @corporal); SET @v4_p2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @private, @private);  SET @v4_p3 = LAST_INSERT_ID();

-- Attach 4 lances
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@vehicle_company_id, @vehicle_lance_id, 4, TRUE);

-- ================================
-- Infantry Company - Mechanized
-- ================================
INSERT INTO toe_templates (name, description, unit_type, role)
VALUES ('Infantry Company', 'Standard foot infantry with platoons and squads', 'Company', 'Infantry');
SET @infantry_company_id = LAST_INSERT_ID();

-- Company commander (Captain)
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@infantry_company_id, 'Personnel', 'Infantry', @captain, @captain);

INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@battalion_id, @infantry_company_id, 1, TRUE);

-- Infantry Platoon
INSERT INTO toe_templates (name, description, unit_type, role, mobility)
VALUES ('Infantry Platoon', 'Mechanized infantry platoon riding in APCs', 'Platoon', 'Infantry', 'Mechanized');
SET @infantry_platoon_id = LAST_INSERT_ID();

-- Platoon commander (Lieutenant)
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@infantry_platoon_id, 'Personnel', 'Infantry', @lieutenant, @lieutenant);

-- Infantry Squad
INSERT INTO toe_templates (name, description, unit_type)
VALUES ('Infantry Squad', '8 infantry soldiers with Sergeant leader + 1 APC', 'Squad');
SET @infantry_squad_id = LAST_INSERT_ID();

-- Personnel slots for squad: 1 Sergeant, 2 Corporals, 4 Privates
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@infantry_squad_id, 'Personnel', 'Infantry', @sergeant, @sergeant);  SET @squad_sergeant = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@infantry_squad_id, 'Personnel', 'Infantry', @corporal, @corporal); SET @squad_cpl1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@infantry_squad_id, 'Personnel', 'Infantry', @corporal, @corporal); SET @squad_cpl2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@infantry_squad_id, 'Personnel', 'Infantry', @private, @private);  SET @squad_pvt1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@infantry_squad_id, 'Personnel', 'Infantry', @private, @private);  SET @squad_pvt2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@infantry_squad_id, 'Personnel', 'Infantry', @private, @private);  SET @squad_pvt3 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@infantry_squad_id, 'Personnel', 'Infantry', @private, @private);  SET @squad_pvt4 = LAST_INSERT_ID();

-- ================================
-- APC for the Squad
-- ================================
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@infantry_squad_id, 'Equipment', 'APC', 'Light'); 
SET @apc_eq = LAST_INSERT_ID();

-- Attach 4 squads per platoon
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@infantry_platoon_id, @infantry_squad_id, 4, TRUE);

-- Attach 3 platoons per infantry company
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@infantry_company_id, @infantry_platoon_id, 3, TRUE);
