-- ================================
-- Rank Variables
-- ================================
SET @captain   = (SELECT id FROM ranks WHERE full_name = 'Captain'   LIMIT 1);
SET @lieutenant= (SELECT id FROM ranks WHERE full_name = 'Leftenant' LIMIT 1);
SET @sergeant  = (SELECT id FROM ranks WHERE full_name = 'Sergeant'  LIMIT 1);
SET @corporal  = (SELECT id FROM ranks WHERE full_name = 'Corporal'  LIMIT 1);
SET @private   = (SELECT id FROM ranks WHERE full_name = 'Private'   LIMIT 1);

-- ================================
-- Combined Arms Battalion
-- ================================
INSERT INTO toe_templates (name, description, unit_type)
VALUES ('Combined Arms Battalion', 'A balanced battalion with mech, vehicle, and infantry companies', 'Battalion');
SET @battalion_id = LAST_INSERT_ID();

-- ================================
-- Striker Company
-- ================================
INSERT INTO toe_templates (name, description, unit_type)
VALUES ('Striker Company', 'Fast BattleMech company with striker and fire lances', 'Company');
SET @striker_company_id = LAST_INSERT_ID();

INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@battalion_id, @striker_company_id, 1, TRUE);

-- Striker Lance
INSERT INTO toe_templates (name, description, unit_type)
VALUES ('Striker Lance', 'Fast medium BattleMech lance', 'Lance');
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

-- Personnel (Lieutenant + 3 Sergeants)
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@striker_lance_id, 'Personnel', 'MechWarrior', @lieutenant, @lieutenant); SET @p1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@striker_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);   SET @p2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@striker_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);   SET @p3 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@striker_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);   SET @p4 = LAST_INSERT_ID();

-- Crew
INSERT INTO toe_slot_crews (equipment_slot_id, personnel_slot_id, crew_role) VALUES
(@eq1, @p1, 'Pilot'),
(@eq2, @p2, 'Pilot'),
(@eq3, @p3, 'Pilot'),
(@eq4, @p4, 'Pilot');

-- Fire Lance
INSERT INTO toe_templates (name, description, unit_type)
VALUES ('Fire Lance', 'Missile boat/sniper lance for long-range firepower', 'Lance');
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

-- Personnel (Lieutenant + 3 Sergeants)
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@fire_lance_id, 'Personnel', 'MechWarrior', @lieutenant, @lieutenant); SET @p5 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@fire_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);     SET @p6 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@fire_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);     SET @p7 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@fire_lance_id, 'Personnel', 'MechWarrior', @sergeant, @sergeant);     SET @p8 = LAST_INSERT_ID();

-- Crew
INSERT INTO toe_slot_crews (equipment_slot_id, personnel_slot_id, crew_role) VALUES
(@eq5, @p5, 'Pilot'),
(@eq6, @p6, 'Pilot'),
(@eq7, @p7, 'Pilot'),
(@eq8, @p8, 'Pilot');

-- Attach lances to Striker Company
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@striker_company_id, @striker_lance_id, 2, TRUE),
       (@striker_company_id, @fire_lance_id, 1, TRUE);

-- ================================
-- Reinforced Vehicle Company
-- ================================
INSERT INTO toe_templates (name, description, unit_type)
VALUES ('Reinforced Vehicle Company', 'Company with 4 armored lances', 'Company');
SET @vehicle_company_id = LAST_INSERT_ID();

INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@battalion_id, @vehicle_company_id, 1, TRUE);

-- Vehicle Lance
INSERT INTO toe_templates (name, description, unit_type)
VALUES ('Vehicle Lance', '4 combat vehicles with full crews', 'Lance');
SET @vehicle_lance_id = LAST_INSERT_ID();

-- ================================
-- Vehicle 1
-- ================================
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@vehicle_lance_id, 'Equipment', 'Vehicle', 'Heavy'); 
SET @v1_eq = LAST_INSERT_ID();

INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @sergeant, @sergeant); SET @v1_p1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @corporal, @corporal); SET @v1_p2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @private, @private);  SET @v1_p3 = LAST_INSERT_ID();

INSERT INTO toe_slot_crews (equipment_slot_id, personnel_slot_id, crew_role) VALUES
(@v1_eq, @v1_p1, 'Commander'),
(@v1_eq, @v1_p2, 'Driver'),
(@v1_eq, @v1_p3, 'Gunner');

-- ================================
-- Vehicle 2
-- ================================
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@vehicle_lance_id, 'Equipment', 'Vehicle', 'Heavy'); 
SET @v2_eq = LAST_INSERT_ID();

INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @sergeant, @sergeant); SET @v2_p1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @corporal, @corporal); SET @v2_p2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @private, @private);  SET @v2_p3 = LAST_INSERT_ID();

INSERT INTO toe_slot_crews (equipment_slot_id, personnel_slot_id, crew_role) VALUES
(@v2_eq, @v2_p1, 'Commander'),
(@v2_eq, @v2_p2, 'Driver'),
(@v2_eq, @v2_p3, 'Gunner');

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

INSERT INTO toe_slot_crews (equipment_slot_id, personnel_slot_id, crew_role) VALUES
(@v3_eq, @v3_p1, 'Commander'),
(@v3_eq, @v3_p2, 'Driver'),
(@v3_eq, @v3_p3, 'Gunner');

-- ================================
-- Vehicle 4
-- ================================
INSERT INTO toe_slots (template_id, slot_type, equipment_type, weight_class)
VALUES (@vehicle_lance_id, 'Equipment', 'Vehicle', 'Medium'); 
SET @v4_eq = LAST_INSERT_ID();

INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @sergeant, @sergeant); SET @v4_p1 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @corporal, @corporal); SET @v4_p2 = LAST_INSERT_ID();
INSERT INTO toe_slots (template_id, slot_type, mos, min_rank_id, max_rank_id)
VALUES (@vehicle_lance_id, 'Personnel', 'Tanker', @private, @private);  SET @v4_p3 = LAST_INSERT_ID();

INSERT INTO toe_slot_crews (equipment_slot_id, personnel_slot_id, crew_role) VALUES
(@v4_eq, @v4_p1, 'Commander'),
(@v4_eq, @v4_p2, 'Driver'),
(@v4_eq, @v4_p3, 'Gunner');

-- Attach 4 lances
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@vehicle_company_id, @vehicle_lance_id, 4, TRUE);

-- ================================
-- Infantry Company
-- ================================
INSERT INTO toe_templates (name, description, unit_type)
VALUES ('Infantry Company', 'Standard foot infantry with platoons and squads', 'Company');
SET @infantry_company_id = LAST_INSERT_ID();

INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@battalion_id, @infantry_company_id, 1, TRUE);

-- Infantry Platoon
INSERT INTO toe_templates (name, description, unit_type)
VALUES ('Infantry Platoon', '4 squads led by a Lieutenant', 'Platoon');
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

-- Assign 2 privates as vehicle crew
INSERT INTO toe_slot_crews (equipment_slot_id, personnel_slot_id, crew_role) VALUES
(@apc_eq, @squad_pvt1, 'Driver'),
(@apc_eq, @squad_pvt2, 'Gunner');

-- Remaining squad members ride as dismounts
INSERT INTO toe_slot_crews (equipment_slot_id, personnel_slot_id, crew_role) VALUES
(@apc_eq, @squad_sergeant, 'Dismount'),
(@apc_eq, @squad_cpl1, 'Dismount'),
(@apc_eq, @squad_cpl2, 'Dismount'),
(@apc_eq, @squad_pvt3, 'Dismount'),
(@apc_eq, @squad_pvt4, 'Dismount');


-- Attach 4 squads per platoon
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@infantry_platoon_id, @infantry_squad_id, 4, TRUE);

-- Attach 3 platoons per infantry company
INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core)
VALUES (@infantry_company_id, @infantry_platoon_id, 3, TRUE);
