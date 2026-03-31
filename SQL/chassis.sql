-- ============================================================
-- Chassis Insert List — Alpha Strike stats, legacy columns removed
-- ============================================================

INSERT INTO chassis
(name, variant, type, weight_class, battlefield_role, supply_consumption, tonnage, speed,
 as_pv, as_type, as_size, as_tmm, as_mv, as_mv_type,
 as_dmg_s, as_dmg_m, as_dmg_l, as_dmg_e, as_ov,
 as_armor, as_structure, as_threshold, as_specials) VALUES

-- Light BattleMechs
('Jenner','JR7-D','BattleMech','Light','Striker',8.0,35,118.8,25,'BM',1,3,10,'g',3,2,0,0,0,3,3,1,'SRCH,SRM2/2'),
('Spider','SDR-5V','BattleMech','Light','Scout',7.5,30,129.6,17,'BM',1,3,10,'j',1,1,0,0,0,2,2,1,'SRCH'),
('Locust','LCT-1V','BattleMech','Light','Scout',7.0,20,129.6,13,'BM',1,3,12,'g',1,1,0,0,0,2,2,1,'SRCH'),
('Javelin','JVN-10N','BattleMech','Light','Striker',7.8,30,97.2,18,'BM',1,3,8,'g',2,2,0,0,0,3,3,1,'SRM2/2'),
('Panther','PNT-9R','BattleMech','Light','Brawler',8.0,35,64.8,23,'BM',1,2,6,'g',2,2,1,0,0,4,3,1,'IF1'),
('Panther','PNT-9ALAG','BattleMech','Light','Striker',7.5,35,86.4,22,'BM',1,2,10,'j',2,2,1,0,0,3,3,1,NULL),
('Stinger','STG-3R','BattleMech','Light','Scout',8.0,35,91.6,15,'BM',1,3,8,'j',1,1,0,0,0,2,2,1,'SRCH'),
('Valkyrie','VLK-QA','BattleMech','Light','Missile Boat',8.0,30,86.4,17,'BM',1,3,8,'j',1,1,1,0,0,3,3,1,'IF1,LRM1/1/1'),
('Raven','RVN-1X','BattleMech','Light','Scout',8.0,35,86.4,20,'BM',1,3,8,'g',1,1,0,0,0,3,3,1,'ECM,SRCH,TAG'),
('Firestarter','FS9-H','BattleMech','Light','Scout',8.0,35,97.2,18,'BM',1,3,8,'g',2,1,0,0,0,2,2,1,'SRCH,HT1'),
('Wasp','WSP-1L','BattleMech','Light','Scout',6.5,20,97.2,14,'BM',1,3,8,'j',1,1,0,0,0,2,2,1,'SRCH'),
('Commando','COM-2D','BattleMech','Light','Striker',7.0,25,97.2,17,'BM',1,2,12,'g',2,2,0,0,0,2,2,1,'SRM1/1'),
('Mongoose','MON-66','BattleMech','Light','Scout',7.0,25,129.6,18,'BM',1,3,12,'g',1,1,0,0,0,2,2,1,'SRCH'),
('UrbanMech','UM-R60','BattleMech','Light','Ambusher',7.5,30,32.0,15,'BM',1,0,4,'g',2,1,1,0,0,3,2,1,'AC2/1/-'),
('UrbanMech','UM-R60L','BattleMech','Light','Ambusher',7.5,30,32.0,16,'BM',1,0,4,'g',2,2,1,0,0,3,2,1,'AC2/2/-'),
('Ostscout','OTT-7J','BattleMech','Light','Scout',6.0,35,129.6,16,'BM',1,3,10,'j',0,0,0,0,0,2,2,1,'ECM,SRCH,PRB,RCN'),
('Flea','FLE-4','BattleMech','Light','Scout',5.5,20,129.6,10,'BM',1,3,12,'g',1,0,0,0,0,1,1,1,'SRCH'),

-- Medium BattleMechs
('Centurion','CN9-A','BattleMech','Medium','Brawler',12.0,50,64.8,28,'BM',2,1,8,'g',2,3,1,0,0,5,4,1,'AC1/1/-,IF1,REAR1/1/-'),
('Centurion','CN9-AL','BattleMech','Medium','Brawler',11.5,50,64.8,26,'BM',2,1,8,'g',2,2,1,0,0,5,4,1,'IF1,LRM1/1/1,REAR1/1/-'),
('Enforcer','ENF-4R','BattleMech','Medium','Skirmisher',11.0,50,64.8,24,'BM',2,1,8,'g',3,2,1,0,0,5,4,1,'AC2/1/-'),
('Griffin','GRF-1N','BattleMech','Medium','Sniper',11.0,55,86.4,28,'BM',2,2,6,'j',2,2,2,0,0,5,4,1,'IF1,LRM1/1/1'),
('Shadow Hawk','SHD-2H','BattleMech','Medium','Skirmisher',10.5,55,86.4,26,'BM',2,2,6,'j',2,2,1,0,0,5,4,1,'IF1,LRM1/1/1'),
('Wolverine','WVR-6R','BattleMech','Medium','Skirmisher',10.8,55,86.4,27,'BM',2,1,8,'j',3,2,1,0,0,5,4,1,'SRM1/1'),
('Wolverine','WVR-6M','BattleMech','Medium','Skirmisher',10.5,55,86.4,26,'BM',2,1,8,'j',2,2,1,0,0,5,4,1,'SRM1/1'),
('Phoenix Hawk','PXH-1','BattleMech','Medium','Skirmisher',8.5,45,97.2,24,'BM',2,2,10,'j',2,2,0,0,0,4,3,1,'SRCH'),
('Hunchback','HBK-4G','BattleMech','Medium','Juggernaut',13.0,50,64.8,26,'BM',2,1,6,'g',4,3,0,0,0,6,5,2,'AC4/3/-'),
('Assassin','ASN-21','BattleMech','Medium','Scout',9.5,40,118.8,20,'BM',2,2,10,'j',2,1,0,0,0,3,3,1,'IF1,LRM1/1/1'),
('Blackjack','BJ-1','BattleMech','Medium','Sniper',10.0,45,64.8,22,'BM',2,1,6,'g',2,2,1,0,0,4,3,1,'AC2/2/-'),
('Trebuchet','TBT-5N','BattleMech','Medium','Missile Boat',11.5,50,86.4,26,'BM',2,2,6,'g',2,2,2,0,0,5,4,1,'IF1,LRM2/2/2'),
('Clint','CLNT-2-3T','BattleMech','Medium','Striker',9.0,40,97.0,20,'BM',2,2,8,'g',2,2,0,0,0,3,3,1,'AC1/1/-'),
('Clint','CLNT-2-4T','BattleMech','Medium','Sniper',10.0,40,97.0,21,'BM',2,2,8,'g',2,2,1,0,0,3,3,1,'AC1/1/-'),
('Vulcan','VL-5T','BattleMech','Medium','Striker',10.0,40,97.0,19,'BM',2,2,8,'g',2,1,0,0,0,3,3,1,'HT1'),
('Dervish','DV-6M','BattleMech','Medium','Missile Boat',10.5,55,86.4,25,'BM',2,2,6,'g',2,2,2,0,0,4,4,1,'IF2,LRM2/2/2,SRM1/1'),
('Cicada','CDA-2A','BattleMech','Medium','Scout',9.0,40,129.6,20,'BM',2,3,12,'g',2,1,0,0,0,3,3,1,NULL),
('Cicada','CDA-3C','BattleMech','Medium','Striker',9.5,40,129.6,22,'BM',2,3,12,'g',2,2,0,0,0,3,3,1,NULL),
('Vindicator','VND-1R','BattleMech','Medium','Sniper',10.0,45,64.8,22,'BM',2,1,6,'g',2,2,1,0,0,4,4,1,'IF1'),
('Kintaro','KTO-18','BattleMech','Medium','Missile Boat',11.0,55,86.4,27,'BM',2,1,8,'g',4,3,0,0,0,5,4,1,'SRM2/2,SRCH'),
('Crab','CRB-20','BattleMech','Medium','Brawler',11.0,50,64.8,28,'BM',2,1,8,'g',3,3,0,0,0,6,5,2,NULL),

-- Heavy BattleMechs
('Rifleman','RFL-3N','BattleMech','Heavy','Sniper',12.5,60,64.8,32,'BM',3,1,6,'g',3,3,3,0,0,5,5,2,'AC2/2/-,REAR1/1/-'),
('Rifleman','RFL-3C','BattleMech','Heavy','Sniper',12.0,60,64.8,30,'BM',3,1,6,'g',2,2,2,0,0,5,5,2,'AC2/2/-'),
('Catapult','CPLT-C1','BattleMech','Heavy','Missile Boat',13.0,65,64.8,34,'BM',3,1,6,'g',0,4,4,0,0,6,5,2,'IF2,LRM4/4/4'),
('JagerMech','JM6-S','BattleMech','Heavy','Sniper',12.8,65,64.8,32,'BM',3,1,6,'g',3,3,3,0,0,5,5,2,'AC2/2/-,AC2/2/-'),
('Quickdraw','QKD-4G','BattleMech','Heavy','Skirmisher',12.2,60,86.4,30,'BM',3,2,6,'j',3,3,1,0,1,5,5,2,'SRM2/2'),
('Archer','ARC-2R','BattleMech','Heavy','Missile Boat',13.5,70,64.8,38,'BM',3,1,6,'g',2,4,4,0,0,6,5,2,'IF2,LRM4/4/4,REAR1/1/-'),
('Grasshopper','GHR-5H','BattleMech','Heavy','Skirmisher',12.7,70,64.8,33,'BM',3,2,6,'j',4,4,1,0,0,7,6,2,NULL),
('Marauder','MAD-3R','BattleMech','Heavy','Sniper',13.6,75,64.8,35,'BM',3,1,8,'g',2,3,3,0,1,7,6,2,NULL),
('Orion','ON1-K','BattleMech','Heavy','Brawler',13.2,75,64.8,38,'BM',3,1,6,'g',3,4,2,0,0,7,6,2,'IF1,LRM1/1/1,SRM1/1'),
('Thunderbolt','TDR-5S','BattleMech','Heavy','Brawler',12.9,65,64.8,33,'BM',3,1,6,'g',4,4,1,0,0,7,6,2,'IF1,LRM1/1/1'),
('Warhammer','WHM-6R','BattleMech','Heavy','Brawler',13.8,70,64.8,36,'BM',3,1,6,'g',4,4,2,0,1,6,5,2,'REAR1/1/-'),
('Black Knight','BL-6-KNT','BattleMech','Heavy','Brawler',8.0,75,64.8,34,'BM',3,1,6,'g',4,4,2,0,1,7,6,2,NULL),
('Black Knight','BL-7-KNT','BattleMech','Heavy','Brawler',13.5,75,64.8,37,'BM',3,1,6,'g',5,5,2,0,2,7,6,2,NULL),
('Dragon','DRG-1N','BattleMech','Heavy','Brawler',12.5,60,86.4,30,'BM',3,2,8,'g',3,3,1,0,0,6,5,2,'IF1'),
('Dragon','DRG-1G','BattleMech','Heavy','Skirmisher',12.0,60,86.4,29,'BM',3,2,8,'g',2,3,1,0,0,6,5,2,'IF1'),
('Cataphract','CTF-1X','BattleMech','Heavy','Brawler',12.8,70,64.8,33,'BM',3,1,6,'g',3,3,2,0,1,6,5,2,'AC2/2/-'),
('Crusader','CRD-3R','BattleMech','Heavy','Missile Boat',13.0,65,64.8,36,'BM',3,1,6,'g',2,3,3,0,0,6,5,2,'IF2,LRM2/2/2,SRM2/2'),
('Bombardier','BMB-12D','BattleMech','Heavy','Missile Boat',12.5,65,64.8,32,'BM',3,1,6,'g',0,3,4,0,0,5,5,2,'IF3,LRM3/3/3'),
('Lancelot','LNC25-01','BattleMech','Heavy','Sniper',12.5,60,86.4,30,'BM',3,2,8,'g',3,3,2,0,1,5,5,2,NULL),
('Ostsol','OTL-4D','BattleMech','Heavy','Sniper',12.0,60,86.4,28,'BM',3,2,8,'g',3,3,2,0,1,5,5,2,NULL),
('Ostroc','OSR-2C','BattleMech','Heavy','Skirmisher',12.3,60,86.4,28,'BM',3,2,8,'g',3,3,1,0,1,6,5,2,NULL),

-- Assault BattleMechs
('Thug','THG-11E','BattleMech','Assault','Brawler',13.0,80,64.8,38,'BM',3,1,6,'g',4,4,0,0,0,8,6,2,NULL),
('Atlas','AS7-D','BattleMech','Assault','Juggernaut',15.0,100,54.0,52,'BM',4,1,6,'g',5,5,2,0,0,10,8,3,'AC2/2/-,IF1,LRM1/1/1,REAR1/1/-'),
('Awesome','AWS-8Q','BattleMech','Assault','Sniper',14.5,80,54.0,43,'BM',4,1,6,'g',0,4,4,0,0,9,7,3,NULL),
('BattleMaster','BLR-1G','BattleMech','Assault','Brawler',14.2,85,54.0,45,'BM',4,1,6,'g',4,5,2,0,1,9,7,3,'REAR1/1/-'),
('Stalker','STK-3H','BattleMech','Assault','Missile Boat',13.5,85,54.0,44,'BM',4,1,4,'g',3,5,4,0,0,9,7,3,'IF2,LRM2/2/2,SRM2/2'),
('Zeus','ZEU-6S','BattleMech','Assault','Brawler',13.0,80,54.0,42,'BM',4,1,6,'g',4,4,2,0,0,8,7,3,'IF1,LRM1/1/1'),
('Victor','VTR-9B','BattleMech','Assault','Skirmisher',12.5,80,64.8,42,'BM',4,2,6,'j',4,4,1,0,0,8,6,3,'IF1,SRM2/2'),
('Cyclops','CP-10-Z','BattleMech','Assault','Command',12.0,90,54.0,43,'BM',4,1,6,'g',4,4,2,0,0,8,7,3,'SRCH,MHQ1'),
('Mauler','MAL-1R','BattleMech','Assault','Sniper',13.0,90,54.0,43,'BM',4,1,4,'g',2,4,4,0,0,9,7,3,'AC2/2/-,IF1'),
('Banshee','BNC-3E','BattleMech','Assault','Juggernaut',11.5,95,54.0,36,'BM',4,1,6,'g',3,2,0,0,0,10,8,3,NULL),
('Highlander','HGN-732','BattleMech','Assault','Juggernaut',14.0,90,54.0,48,'BM',4,1,4,'j',4,5,3,0,0,10,8,3,'IF2,LRM2/2/2,SRM2/2'),
('King Crab','KGC-0000','BattleMech','Assault','Juggernaut',15.5,100,43.2,54,'BM',4,1,4,'g',6,6,2,0,0,10,8,3,'AC4/4/-'),
('Charger','CGR-1A1','BattleMech','Assault','Scout',10.0,80,86.4,21,'BM',4,2,8,'g',1,0,0,0,0,8,6,2,NULL),
('Charger','CGR-1L','BattleMech','Assault','Brawler',12.0,80,64.8,34,'BM',4,2,6,'g',3,3,0,0,0,9,7,3,NULL),
('Goliath','GOL-1H','BattleMech','Assault','Missile Boat',13.5,80,43.2,42,'BM',4,0,4,'g',3,4,4,0,0,8,7,3,'IF2,LRM2/2/2'),
('Longbow','LGB-7Q','BattleMech','Assault','Missile Boat',13.0,85,54.0,42,'BM',4,1,4,'g',1,5,5,0,0,8,7,3,'IF3,LRM5/5/5'),

-- Light Vehicles
('Scorpion Light Tank','SLT-STD','Vehicle','Light','Scout',6.8,25,64.8,14,'CV',1,2,8,'t',2,1,0,0,0,2,2,1,NULL),
('Pegasus Scout Hover','PSH-STD','Vehicle','Light','Striker',6.5,35,129.6,17,'CV',1,3,14,'h',2,1,0,0,0,2,2,1,'SRCH'),
('Striker','Standard','Vehicle','Light','Striker',5.0,35,86.4,12,'CV',1,2,10,'w',0,2,2,0,0,2,2,1,'LRM2/2/2'),
('Hunter LST','HNT-STD','Vehicle','Light','Missile Boat',5.5,35,86.4,14,'CV',1,2,10,'w',0,2,2,0,0,2,2,1,'IF1,LRM2/2/2'),
('Harasser','HAR-STD','Vehicle','Light','Striker',5.5,15,162.0,12,'CV',1,3,16,'h',2,2,0,0,0,1,1,1,'SRM2/2'),
('Skulker','SKU-STD','Vehicle','Light','Scout',4.5,20,129.6,10,'CV',1,3,14,'w',1,0,0,0,0,1,1,1,'SRCH'),

-- Medium Vehicles
('Goblin','GBL-STD','Vehicle','Medium','Brawler',6.5,45,64.8,18,'CV',2,1,6,'t',2,1,0,0,0,3,3,1,'IT'),
('Vedette Medium Tank','VMT-STD','Vehicle','Medium','Brawler',7.8,50,86.0,22,'CV',2,1,8,'t',3,2,0,0,0,4,3,1,NULL),
('Hetzer','HTZ-STD','Vehicle','Medium','Brawler',7.0,40,64.8,16,'CV',2,1,6,'w',3,2,0,0,0,3,3,1,'AC3/2/-'),
('Maxim','MXM-STD','Vehicle','Medium','Striker',6.0,50,129.6,22,'CV',2,2,12,'h',2,2,0,0,0,3,2,1,'IT,SRM2/2'),

-- Heavy Vehicles
('Von Luckner Heavy Tank','VNL-K100','Vehicle','Heavy','Juggernaut',11.0,75,54.0,38,'CV',3,1,4,'t',5,5,2,0,0,6,5,2,'AC5/5/-'),
('Patton','PTN-STD','Vehicle','Heavy','Brawler',9.0,65,64.8,26,'CV',2,1,6,'t',3,3,1,0,0,5,4,2,'AC3/3/-'),
('Rommel','RML-STD','Vehicle','Heavy','Juggernaut',10.0,65,64.80,28,'CV',2,1,6,'t',4,4,1,0,0,5,4,2,'AC4/3/-'),
('Brutus','BTS-STD','Vehicle','Heavy','Juggernaut',10.5,75,54.0,30,'CV',3,1,4,'t',4,4,1,0,0,6,5,2,'AC3/3/-'),
('Manticore','MHT-STD','Vehicle','Heavy','Brawler',10.0,60,54.0,32,'CV',3,1,4,'t',3,4,2,0,0,6,5,2,'IF1,LRM1/1/1,SRM2/2'),
('Bulldog Medium Tank','BMT-STD','Vehicle','Heavy','Brawler',8.0,60,64.80,24,'CV',2,1,6,'t',3,3,0,0,0,5,4,2,'AC3/2/-'),
('SRM Carrier','SRM-STD','Vehicle','Heavy','Missile Boat',8.0,60,54.0,20,'CV',3,1,4,'t',4,4,0,0,0,2,3,1,'SRM4/4,SRM4/4'),
('LRM Carrier','LRM-STD','Vehicle','Heavy','Missile Boat',7.5,60,54.0,18,'CV',3,1,4,'t',0,3,4,0,0,2,3,1,'IF2,LRM4/4/4'),

-- Assault Vehicles
('Schrek','SHK-STD','Vehicle','Assault','Sniper',11.5,80,54.0,36,'CV',4,1,4,'t',5,5,4,0,0,5,5,2,'AC5/5/-,AC5/5/-'),
('Partisan','PRT-STD','Vehicle','Assault','Sniper',7.0,45,54.0,22,'CV',3,1,6,'t',3,3,2,0,0,4,4,2,'AC2/2/-,AC2/2/-'),
('Ontos','ONT-STD','Vehicle','Assault','Juggernaut',5.5,95,97.2,28,'CV',3,1,8,'t',4,4,0,0,0,4,4,2,'AC2/2/-'),
('Demolisher','DML-STD','Vehicle','Assault','Juggernaut',10.0,80,54.0,30,'CV',3,1,4,'t',6,6,0,0,0,5,5,2,'AC6/6/-'),
('Behemoth','BHM-STD','Vehicle','Assault','Juggernaut',12.0,100,32.0,45,'CV',4,1,2,'t',6,6,2,0,0,8,7,3,'AC4/4/-'),
('Devastator','DVS-STD','Vehicle','Heavy','Juggernaut',11.0,80,54.0,34,'CV',3,1,4,'t',5,5,2,0,0,6,5,2,'AC4/4/-'),
('Marsden II','MRS-STD','Vehicle','Assault','Juggernaut',11.0,90,32.4,35,'CV',4,1,2,'t',5,5,2,0,0,7,6,2,'AC4/4/-'),

-- Support Vehicles
('MASH Truck','MSH-STD','Vehicle','Light','MASH',0.5,20,86.4,5,'CV',1,0,8,'w',0,0,0,0,0,1,1,1,NULL),
('Flatbed Truck','FBD-STD','Vehicle','Light','Supply',0.3,10,86.4,3,'CV',1,0,8,'w',0,0,0,0,0,1,1,1,NULL),
('Repair Vehicle','RPR-STD','Vehicle','Heavy','Repair',0.3,60,86.4,8,'CV',2,0,4,'t',0,0,0,0,0,3,3,1,'SRCH'),
('Transport Truck','TRK-5T','Vehicle','Light','Transport',0.50,5,86.4,4,'CV',1,0,8,'w',0,0,0,0,0,1,1,1,NULL),

-- APCs
('APC','Wheeled','APC','Light','Scout',4.0,10,97.2,8,'CV',1,2,10,'w',1,0,0,0,0,2,2,1,'IT'),
('APC','Tracked','APC','Light','Scout',4.2,10,97.2,9,'CV',1,2,8,'t',1,0,0,0,0,3,2,1,'IT'),
('APC','Hover','APC','Light','Scout',3.8,10,162.0,10,'CV',1,3,14,'h',1,0,0,0,0,2,2,1,'IT');

-- ============================================================
-- Crew Requirements (unchanged)
-- ============================================================
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Pilot', TRUE, 'MechWarrior' FROM chassis WHERE type = 'BattleMech';

INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Driver', TRUE, 'Tanker' FROM chassis WHERE type = 'Vehicle';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Gunner', TRUE, 'Tanker' FROM chassis WHERE type = 'Vehicle';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Commander', FALSE, 'Tanker' FROM chassis WHERE type = 'Vehicle';

INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Driver', TRUE, 'Infantry' FROM chassis WHERE type = 'APC';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Gunner', TRUE, 'Infantry' FROM chassis WHERE type = 'APC';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Dismount', FALSE, 'Infantry' FROM chassis WHERE type = 'APC';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Dismount', FALSE, 'Infantry' FROM chassis WHERE type = 'APC';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Dismount', FALSE, 'Infantry' FROM chassis WHERE type = 'APC';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Dismount', FALSE, 'Infantry' FROM chassis WHERE type = 'APC';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Dismount', FALSE, 'Infantry' FROM chassis WHERE type = 'APC';

INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Driver', TRUE, 'Tech' FROM chassis WHERE battlefield_role = 'Supply';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Tech' FROM chassis WHERE battlefield_role = 'Supply';

INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Driver', TRUE, 'Medic' FROM chassis WHERE battlefield_role = 'MASH';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Medic' FROM chassis WHERE battlefield_role = 'MASH';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Medic' FROM chassis WHERE battlefield_role = 'MASH';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Medic' FROM chassis WHERE battlefield_role = 'MASH';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Medic' FROM chassis WHERE battlefield_role = 'MASH';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Medic' FROM chassis WHERE battlefield_role = 'MASH';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Medic' FROM chassis WHERE battlefield_role = 'MASH';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Medic' FROM chassis WHERE battlefield_role = 'MASH';

INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Driver', TRUE, 'Tech' FROM chassis WHERE battlefield_role = 'Repair';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Tech' FROM chassis WHERE battlefield_role = 'Repair';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Tech' FROM chassis WHERE battlefield_role = 'Repair';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Tech' FROM chassis WHERE battlefield_role = 'Repair';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Tech' FROM chassis WHERE battlefield_role = 'Repair';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Tech' FROM chassis WHERE battlefield_role = 'Repair';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Tech' FROM chassis WHERE battlefield_role = 'Repair';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Crew', FALSE, 'Tech' FROM chassis WHERE battlefield_role = 'Repair';

INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT chassis_id, 'Driver', 1, NULL FROM chassis WHERE variant = 'TRK-5T';
INSERT INTO chassis_crew_requirements (chassis_id, crew_role, is_required, required_mos)
SELECT c.chassis_id, 'Crew', 0, NULL
FROM chassis c
CROSS JOIN (
    SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
    UNION SELECT 11 UNION SELECT 12 UNION SELECT 13
) AS nums
WHERE c.variant = 'TRK-5T';