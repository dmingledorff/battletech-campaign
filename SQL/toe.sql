-- ===========================
-- TOE Data
-- ===========================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE toe_slot_crews;
TRUNCATE TABLE toe_slot_roles;
TRUNCATE TABLE toe_slots;
TRUNCATE TABLE toe_subunits;
TRUNCATE TABLE toe_templates;

SET FOREIGN_KEY_CHECKS = 1;

-- ===========================
-- Templates
-- ===========================

INSERT INTO toe_templates (template_id, name, description, unit_type, role, mobility, faction, era) VALUES
-- Davion
(1,  '1st Davion Guards',        'A medium BattleMech regiment with support.',                                    'Regiment',  NULL,      NULL,          'Davion',  '3025'),

-- Generic shared templates
(2,  'Combined Arms Battalion',  'A balanced battalion with mech, vehicle, and infantry companies',               'Battalion', NULL,      NULL,          NULL,      NULL),
(3,  'Command Lance',            'Heavy lance to support the battalion commander',                                 'Lance',     'Command', NULL,          NULL,      NULL),
(4,  'Battle Company',           'Balanced BattleMech company with battle and fire lances',                       'Company',   'Battle',  NULL,          NULL,      NULL),
(5,  'Battle Lance',             'Balanced lance with medium and heavy BattleMechs',                              'Lance',     'Battle',  NULL,          NULL,      NULL),
(6,  'Fire Lance',               'Missile boat/sniper lance for long-range firepower',                            'Lance',     'Fire',    NULL,          NULL,      NULL),
(7,  'Striker Company',          'Fast BattleMech company with striker and fire lances',                          'Company',   'Striker', NULL,          NULL,      NULL),
(8,  'Striker Lance',            'Fast medium BattleMech lance',                                                  'Lance',     'Striker', NULL,          NULL,      NULL),
(9,  'Fire Lance',               'Missile boat/sniper lance for long-range firepower',                            'Lance',     'Fire',    NULL,          NULL,      NULL),
(10, 'Reinforced Vehicle Company','Company with 4 armored lances',                                               'Company',   'Battle',  NULL,          NULL,      NULL),
(11, 'Vehicle Lance',            '4 combat vehicles with full crews',                                             'Lance',     'Battle',  NULL,          NULL,      NULL),
(12, 'Infantry Company',         'Standard foot infantry with platoons and squads',                               'Company',   'Infantry',NULL,          NULL,      NULL),
(13, 'Infantry Platoon',         'Mechanized infantry platoon riding in APCs',                                    'Platoon',   'Infantry','Mechanized',  NULL,      NULL),
(14, 'Infantry Squad',           '7 infantry soldiers with Sergeant leader + 1 APC',                             'Squad',     'Infantry',NULL,          NULL,      NULL),
(17, 'Light Striker Company',    'Company that focuses on speed and firepower.',                                   'Company',   'Striker', NULL,          NULL,      NULL),
(18, 'Light Striker Lance',      'Light BattleMechs with speed and firepower',                                    'Lance',     'Striker', NULL,          NULL,      NULL),
(19, 'Light Recon Lance',        'Light BattleMech lance focusing on speed.',                                     'Lance',     NULL,      NULL,          NULL,      NULL),

-- Kurita / ALAG
(15, '1st ALAG',                 'A light Battlemech regiment that focuses on outmaneuvering their enemy regardless of terrain or situation.', 'Regiment', NULL, NULL, 'Kurita', '3025'),
(16, 'ALAG Battalion',           'Light Battlemech battalion',                                                    'Battalion', NULL,      NULL,          'Kurita',  '3025'),
(21, 'ALAG Command Lance',       'Light Command Lance for an ALAG Battalion',                                     'Lance',     'Command', NULL,          'Kurita',  NULL),
(22, 'ALAG Light Striker',       'Light striker lance for the ALAG',                                              'Lance',     'Striker', NULL,          'Kurita',  NULL),
(23, 'ALAG Striker Company',     'Light BattleMech striker company for ALAG',                                     'Company',   'Striker', NULL,          'Kurita',  NULL),
(24, 'ALAG Recon Lance',         'Light BattleMech recon lance for ALAG.',                                        'Lance',     'Recon',   NULL,          'Kurita',  NULL);

-- ===========================
-- Slots
-- ===========================

INSERT INTO toe_slots (slot_id, template_id, slot_type, mos, equipment_type, weight_class, crew_size, is_core, min_grade, max_grade) VALUES

-- Template 1: 1st Davion Guards (Regiment)
(1,  1,  'Personnel', 'Officer',      NULL,         NULL,     1, 1, 12, 12),

-- Template 3: Command Lance
(2,  3,  'Equipment', NULL,           'BattleMech', 'Heavy',  1, 1, NULL, NULL),
(3,  3,  'Equipment', NULL,           'BattleMech', 'Heavy',  1, 1, NULL, NULL),
(4,  3,  'Equipment', NULL,           'BattleMech', 'Heavy',  1, 1, NULL, NULL),
(5,  3,  'Equipment', NULL,           'BattleMech', 'Medium', 1, 1, NULL, NULL),
(6,  3,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 4,    6),
(7,  3,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),
(8,  3,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),
(9,  3,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),

-- Template 5: Battle Lance
(10, 5,  'Equipment', NULL,           'BattleMech', 'Heavy',  1, 1, NULL, NULL),
(11, 5,  'Equipment', NULL,           'BattleMech', 'Medium', 1, 1, NULL, NULL),
(12, 5,  'Equipment', NULL,           'BattleMech', 'Heavy',  1, 1, NULL, NULL),
(13, 5,  'Equipment', NULL,           'BattleMech', 'Medium', 1, 1, NULL, NULL),
(14, 5,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 4,    4),
(15, 5,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),
(16, 5,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),
(17, 5,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),

-- Template 6: Fire Lance
(18, 6,  'Equipment', NULL,           'BattleMech', 'Medium', 1, 1, NULL, NULL),
(19, 6,  'Equipment', NULL,           'BattleMech', 'Heavy',  1, 1, NULL, NULL),
(20, 6,  'Equipment', NULL,           'BattleMech', 'Heavy',  1, 1, NULL, NULL),
(21, 6,  'Equipment', NULL,           'BattleMech', 'Heavy',  1, 1, NULL, NULL),
(22, 6,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 4,    4),
(23, 6,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),
(24, 6,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),
(25, 6,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),

-- Template 8: Striker Lance
(26, 8,  'Equipment', NULL,           'BattleMech', 'Medium', 1, 1, NULL, NULL),
(27, 8,  'Equipment', NULL,           'BattleMech', 'Medium', 1, 1, NULL, NULL),
(28, 8,  'Equipment', NULL,           'BattleMech', 'Medium', 1, 1, NULL, NULL),
(29, 8,  'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),
(30, 8,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 4,    4),
(31, 8,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),
(32, 8,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),
(33, 8,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),

-- Template 9: Fire Lance (duplicate for Striker Company)
(34, 9,  'Equipment', NULL,           'BattleMech', 'Medium', 1, 1, NULL, NULL),
(35, 9,  'Equipment', NULL,           'BattleMech', 'Medium', 1, 1, NULL, NULL),
(36, 9,  'Equipment', NULL,           'BattleMech', 'Heavy',  1, 1, NULL, NULL),
(37, 9,  'Equipment', NULL,           'BattleMech', 'Heavy',  1, 1, NULL, NULL),
(38, 9,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 4,    4),
(39, 9,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),
(40, 9,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),
(41, 9,  'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    3),

-- Template 11: Vehicle Lance
(42, 11, 'Equipment', NULL,           'Vehicle',    'Heavy',  1, 1, NULL, NULL),
(43, 11, 'Personnel', 'Tanker',       NULL,         NULL,     1, 1, 4,    4),
(44, 11, 'Personnel', 'Tanker',       NULL,         NULL,     1, 1, 2,    2),
(45, 11, 'Personnel', 'Tanker',       NULL,         NULL,     1, 1, 1,    1),
(46, 11, 'Equipment', NULL,           'Vehicle',    'Medium', 1, 1, NULL, NULL),
(47, 11, 'Personnel', 'Tanker',       NULL,         NULL,     1, 1, 3,    3),
(48, 11, 'Personnel', 'Tanker',       NULL,         NULL,     1, 1, 2,    2),
(49, 11, 'Personnel', 'Tanker',       NULL,         NULL,     1, 1, 1,    1),
(50, 11, 'Equipment', NULL,           'Vehicle',    'Medium', 1, 1, NULL, NULL),
(51, 11, 'Personnel', 'Tanker',       NULL,         NULL,     1, 1, 3,    3),
(52, 11, 'Personnel', 'Tanker',       NULL,         NULL,     1, 1, 2,    2),
(53, 11, 'Personnel', 'Tanker',       NULL,         NULL,     1, 1, 1,    1),
(54, 11, 'Equipment', NULL,           'Vehicle',    'Light',  1, 1, NULL, NULL),
(55, 11, 'Personnel', 'Tanker',       NULL,         NULL,     1, 1, 3,    3),
(56, 11, 'Personnel', 'Tanker',       NULL,         NULL,     1, 1, 2,    2),
(57, 11, 'Personnel', 'Tanker',       NULL,         NULL,     1, 1, 1,    1),

-- Template 12: Infantry Company
(58, 12, 'Personnel', 'Infantry',     NULL,         NULL,     1, 1, 5,    5),

-- Template 13: Infantry Platoon
(59, 13, 'Personnel', 'Infantry',     NULL,         NULL,     1, 1, 4,    4),

-- Template 14: Infantry Squad
(60, 14, 'Personnel', 'Infantry',     NULL,         NULL,     1, 1, 3,    3),
(61, 14, 'Personnel', 'Infantry',     NULL,         NULL,     1, 1, 2,    2),
(62, 14, 'Personnel', 'Infantry',     NULL,         NULL,     1, 1, 2,    2),
(63, 14, 'Personnel', 'Infantry',     NULL,         NULL,     1, 1, 1,    1),
(64, 14, 'Personnel', 'Infantry',     NULL,         NULL,     1, 1, 1,    1),
(65, 14, 'Personnel', 'Infantry',     NULL,         NULL,     1, 1, 1,    1),
(66, 14, 'Personnel', 'Infantry',     NULL,         NULL,     1, 1, 1,    1),
(67, 14, 'Equipment', NULL,           'APC',        'Light',  1, 1, NULL, NULL),

-- Template 15: 1st ALAG (Regiment) — no direct slots, structure via subunits
(68, 15, 'Personnel', 'Officer',      NULL,         NULL,     1, 1, 11,   11),

-- Template 18: Light Striker Lance
(70, 18, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 4,    4),
(71, 18, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 2,    3),
(72, 18, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 2,    3),
(73, 18, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 2,    3),
(79, 18, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),
(80, 18, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),
(81, 18, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),
(82, 18, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),

-- Template 19: Light Recon Lance
(83, 19, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 4,    4),
(84, 19, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 2,    3),
(85, 19, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 2,    3),
(86, 19, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 2,    3),
(87, 19, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),
(88, 19, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),
(89, 19, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),
(90, 19, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),

-- Template 21: ALAG Command Lance
(91, 21, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 4,    8),
(94, 21, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    4),
(95, 21, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    4),
(96, 21, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 3,    4),
(97, 21, 'Equipment', NULL,           'BattleMech', 'Heavy',  1, 1, NULL, NULL),
(98, 21, 'Equipment', NULL,           'BattleMech', 'Medium', 1, 1, NULL, NULL),
(99, 21, 'Equipment', NULL,           'BattleMech', 'Medium', 1, 1, NULL, NULL),
(100,21, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),

-- Template 22: ALAG Light Striker
(101,22, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 4,    4),
(102,22, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 2,    3),
(103,22, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 2,    3),
(104,22, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 2,    3),
(105,22, 'Equipment', NULL,           'BattleMech', 'Medium', 1, 1, NULL, NULL),
(106,22, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),
(107,22, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),
(108,22, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),

-- Template 24: ALAG Recon Lance
(109,24, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 4,    4),
(110,24, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 2,    3),
(111,24, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 2,    3),
(112,24, 'Personnel', 'MechWarrior',  NULL,         NULL,     1, 1, 2,    3),
(113,24, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),
(114,24, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),
(115,24, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL),
(116,24, 'Equipment', NULL,           'BattleMech', 'Light',  1, 1, NULL, NULL);

-- ===========================
-- Slot Roles (Battlefield)
-- ===========================

INSERT INTO toe_slot_roles (slot_id, battlefield_role) VALUES

-- Command Lance (template 3) — heavy brawlers
(2,  'Brawler'),    (2,  'Juggernaut'),
(3,  'Brawler'),    (3,  'Juggernaut'),
(4,  'Brawler'),    (4,  'Juggernaut'),
(5,  'Brawler'),    (5,  'Juggernaut'),

-- Battle Lance (template 5) — brawlers and skirmishers
(10, 'Brawler'),    (10, 'Skirmisher'),
(11, 'Brawler'),    (11, 'Skirmisher'),
(12, 'Juggernaut'), (12, 'Brawler'),
(13, 'Juggernaut'), (13, 'Brawler'),

-- Fire Lance (template 6) — snipers and missile boats
(18, 'Sniper'),     (18, 'Missile Boat'),
(19, 'Sniper'),     (19, 'Missile Boat'),
(20, 'Sniper'),     (20, 'Missile Boat'),
(21, 'Sniper'),     (21, 'Missile Boat'),

-- Striker Lance (template 8) — skirmishers and strikers
(26, 'Skirmisher'), (26, 'Striker'),
(27, 'Skirmisher'), (27, 'Striker'),
(28, 'Skirmisher'), (28, 'Striker'),
(29, 'Skirmisher'), (29, 'Striker'),

-- Fire Lance (template 9) — snipers and missile boats
(34, 'Sniper'),     (34, 'Missile Boat'),
(35, 'Sniper'),     (35, 'Missile Boat'),
(36, 'Sniper'),     (36, 'Missile Boat'),
(37, 'Sniper'),     (37, 'Missile Boat'),

-- Light Striker Lance (template 18) — strikers
(79, 'Striker'),
(80, 'Striker'),
(81, 'Striker'),
(82, 'Striker'),

-- Light Recon Lance (template 19) — scouts
(87, 'Scout'),
(88, 'Scout'),
(89, 'Scout'),
(90, 'Scout'),

-- ALAG Command Lance (template 21)
(97,  'Skirmisher'), (97,  'Striker'),
(98,  'Skirmisher'), (98,  'Striker'),
(99,  'Skirmisher'), (99,  'Striker'),
(100, 'Striker'),

-- ALAG Light Striker (template 22)
(105, 'Striker'),
(106, 'Striker'),
(107, 'Striker'),
(108, 'Striker'),

-- ALAG Recon Lance (template 24)
(113, 'Scout'),
(114, 'Scout'),
(115, 'Scout'),
(116, 'Striker');

-- ===========================
-- Slot Crews (Equipment → Personnel links)
-- ===========================

INSERT INTO toe_slot_crews (equipment_slot_id, personnel_slot_id, crew_role) VALUES

-- Command Lance (template 3)
(2,  6,  'Pilot'),
(3,  7,  'Pilot'),
(4,  8,  'Pilot'),
(5,  9,  'Pilot'),

-- Battle Lance (template 5)
(10, 14, 'Pilot'),
(11, 15, 'Pilot'),
(12, 16, 'Pilot'),
(13, 17, 'Pilot'),

-- Fire Lance (template 6)
(18, 22, 'Pilot'),
(19, 23, 'Pilot'),
(20, 24, 'Pilot'),
(21, 25, 'Pilot'),

-- Striker Lance (template 8)
(26, 30, 'Pilot'),
(27, 31, 'Pilot'),
(28, 32, 'Pilot'),
(29, 33, 'Pilot'),

-- Fire Lance (template 9)
(34, 38, 'Pilot'),
(35, 39, 'Pilot'),
(36, 40, 'Pilot'),
(37, 41, 'Pilot'),

-- Vehicle Lance (template 11)
(42, 43, 'Commander'), (42, 44, 'Driver'), (42, 45, 'Gunner'),
(46, 47, 'Commander'), (46, 48, 'Driver'), (46, 49, 'Gunner'),
(50, 51, 'Commander'), (50, 52, 'Driver'), (50, 53, 'Gunner'),
(54, 55, 'Commander'), (54, 56, 'Driver'), (54, 57, 'Gunner'),

-- Infantry Squad APC (template 14)
(67, 63, 'Driver'),
(67, 64, 'Gunner'),
(67, 60, 'Dismount'),
(67, 61, 'Dismount'),
(67, 62, 'Dismount'),
(67, 65, 'Dismount'),
(67, 66, 'Dismount'),

-- Light Striker Lance (template 18)
(79, 70, 'Pilot'),
(80, 71, 'Pilot'),
(81, 72, 'Pilot'),
(82, 73, 'Pilot'),

-- Light Recon Lance (template 19)
(87, 83, 'Pilot'),
(88, 84, 'Pilot'),
(89, 85, 'Pilot'),
(90, 86, 'Pilot'),

-- ALAG Command Lance (template 21)
(97,  91, 'Pilot'),
(98,  94, 'Pilot'),
(99,  95, 'Pilot'),
(100, 96, 'Pilot'),

-- ALAG Light Striker (template 22)
(105, 101, 'Pilot'),
(106, 102, 'Pilot'),
(107, 103, 'Pilot'),
(108, 104, 'Pilot'),

-- ALAG Recon Lance (template 24)
(113, 109, 'Pilot'),
(114, 110, 'Pilot'),
(115, 111, 'Pilot'),
(116, 112, 'Pilot');

-- ===========================
-- Subunits (parent → child relationships)
-- ===========================

INSERT INTO toe_subunits (parent_template_id, child_template_id, quantity, is_core, is_command) VALUES

-- 1st Davion Guards Regiment
(1,  2,  1, 1, 0),   -- 1x Combined Arms Battalion

-- Combined Arms Battalion
(2,  3,  1, 1, 1),   -- 1x Command Lance (command)
(2,  4,  2, 1, 0),   -- 2x Battle Company
(2,  7,  1, 1, 0),   -- 1x Striker Company
(2,  10, 1, 1, 0),   -- 1x Reinforced Vehicle Company
(2,  12, 1, 1, 0),   -- 1x Infantry Company

-- Battle Company
(4,  5,  2, 1, 0),   -- 2x Battle Lance
(4,  6,  1, 1, 0),   -- 1x Fire Lance

-- Striker Company
(7,  8,  2, 1, 0),   -- 2x Striker Lance
(7,  9,  1, 1, 0),   -- 1x Fire Lance

-- Reinforced Vehicle Company
(10, 11, 4, 1, 0),   -- 4x Vehicle Lance

-- Infantry Company
(12, 13, 3, 1, 0),   -- 3x Infantry Platoon

-- Infantry Platoon
(13, 14, 4, 1, 0),   -- 4x Infantry Squad

-- Light Striker Company (template 17)
(17, 18, 1, 1, 1),   -- 1x Light Striker Lance (command)
(17, 18, 1, 1, 0),   -- 1x Light Striker Lance
(17, 19, 1, 1, 0),   -- 1x Light Recon Lance

-- 1st ALAG Regiment
(15, 16, 3, 1, 0),   -- 3x ALAG Battalion

-- ALAG Battalion
(16, 21, 1, 1, 1),   -- 1x ALAG Command Lance (command)
(16, 23, 3, 1, 0),   -- 3x ALAG Striker Company

-- ALAG Striker Company
(23, 22, 2, 1, 0),   -- 2x ALAG Light Striker
(23, 24, 1, 1, 0);   -- 1x ALAG Recon Lance
