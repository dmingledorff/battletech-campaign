-- =============================
-- PART 1: Planets & Locations
-- =============================
use battletech;
-- Planets
INSERT INTO planets (planet_id, name, allegiance) VALUES
(1, 'New Avalon', 'Federated Suns');

-- Locations on New Avalon (all cities)
INSERT INTO locations (location_id, name, type, planet_id) VALUES
(1, 'Avalon City', 'City', 1),
(2, 'Corbin', 'City', 1),
(3, 'Barrington', 'City', 1),
(4, 'Jameston', 'City', 1),
(5, 'Ranford', 'City', 1);

-- =======================================
-- PART 1B: Chassis catalog (with tonnage, speed kph)
-- Values are representative lore-friendly approximations
-- =======================================

INSERT INTO chassis (chassis_id,name,type,weight_class,tonnage,speed,hard_attack,soft_attack,armor_value,ammo_reliance,supply_consumption) VALUES
(1,'Centurion CN9-A','BattleMech','Medium',50,64.8,70,30,120,0.75,12.0),
(2,'Enforcer ENF-4R','BattleMech','Medium',50,64.8,65,25,115,0.50,11.0),
(3,'Griffin GRF-1N','BattleMech','Medium',55,64.8,68,22,110,0.40,11.0),
(4,'Shadow Hawk SHD-2H','BattleMech','Medium',55,64.8,64,24,105,0.45,10.5),
(5,'Wolverine WVR-6R','BattleMech','Medium',55,64.8,66,24,110,0.45,10.8),
(6,'Hunchback HBK-4G','BattleMech','Heavy',50,54.0,80,20,140,0.60,13.0),
(7,'Jenner JR7-D','BattleMech','Light',35,118.8,50,15,70,0.30,8.0),
(8,'Phoenix Hawk PXH-1','BattleMech','Light',45,97.2,52,18,80,0.35,8.5),
(9,'Spider SDR-5V','BattleMech','Light',30,118.8,45,12,65,0.20,7.5),
(10,'Locust LCT-1V','BattleMech','Light',20,129.6,40,10,60,0.20,7.0),
(11,'Javelin JVN-10N','BattleMech','Light',30,108.0,46,13,70,0.40,7.8),
(12,'Panther PNT-9R','BattleMech','Light',35,64.8,48,14,80,0.30,8.0),
(13,'Assassin ASN-21','BattleMech','Medium',40,97.2,58,18,90,0.35,9.5),
(14,'Blackjack BJ-1','BattleMech','Medium',45,64.8,60,20,100,0.55,10.0),
(15,'Trebuchet TBT-5N','BattleMech','Medium',50,64.8,62,22,105,0.70,11.5),
(16,'Rifleman RFL-3N','BattleMech','Heavy',60,54.0,72,24,120,0.70,12.5),
(17,'Catapult CPLT-C1','BattleMech','Heavy',65,54.0,76,26,130,0.80,13.0),
(18,'JagerMech JM6-A','BattleMech','Heavy',65,54.0,70,20,125,0.80,12.8),
(19,'Quickdraw QKD-4G','BattleMech','Heavy',60,64.8,74,22,135,0.60,12.2),
(20,'Archer ARC-2R','BattleMech','Heavy',70,54.0,78,28,140,0.85,13.5),
(21,'Grasshopper GHR-5H','BattleMech','Heavy',70,64.8,76,24,150,0.40,12.7),
(22,'Marauder MAD-3R','BattleMech','Heavy',75,64.8,82,26,155,0.60,13.6),
(23,'Orion ON1-K','BattleMech','Heavy',75,54.0,80,25,160,0.55,13.2),
(24,'Thunderbolt TDR-5S','BattleMech','Heavy',65,54.0,78,22,150,0.60,12.9),
(25,'Warhammer WHM-6R','BattleMech','Heavy',70,54.0,84,28,160,0.70,13.8),
(26,'Manticore Heavy Tank','Vehicle','Heavy',60,54.0,60,20,150,0.65,10.0),
(27,'Bulldog Medium Tank','Vehicle','Medium',50,54.0,50,15,120,0.60,8.0),
(28,'Von Luckner Heavy Tank','Vehicle','Heavy',75,43.2,70,25,160,0.70,11.0),
(29,'Scorpion Light Tank','Vehicle','Light',25,64.8,35,12,80,0.50,6.8),
(30,'Vedette Medium Tank','Vehicle','Medium',50,54.0,48,15,115,0.55,7.8),
(31,'Pegasus Scout Hover','Vehicle','Light',35,118.8,40,14,75,0.50,6.5),
(32,'APC (Wheeled)','Vehicle','Light',10,97.2,10,8,60,0.40,4.0);

-- =============================
-- PART 2: Units (location_id = 1 -> Avalon City)
-- Easy Company added under 1st Battalion, and Mech Inf Platoon moved under Easy as 1st Platoon
-- =============================

-- Regiment & Battalions
INSERT INTO units (unit_id,name,unit_type,nickname,allegiance,parent_unit_id,commander_id,location_id) VALUES
(1,'1st Davion Guards','Regiment','The Strength of Alexander','Federated Suns',NULL,NULL,1),
(2,'1st Battalion','Battalion','Iron Fists',NULL,1,NULL,1),
(26,'2nd Battalion','Battalion','Steel Hammers',NULL,1,NULL,1),
(27,'3rd Battalion','Battalion','Falcon Guard',NULL,1,NULL,1),
(28,'4th Battalion','Battalion','Red Lions',NULL,1,NULL,1);

-- BattleMech companies (unchanged structure, just with location_id)
INSERT INTO units (unit_id,name,unit_type,nickname,allegiance,parent_unit_id,commander_id,location_id) VALUES
(3,'Able Company','Company','Iron Lancers',NULL,2,NULL,1),
(4,'Baker Company','Company','Wardogs',NULL,2,NULL,1),
(5,'Charlie Company','Company','Ghosts',NULL,2,NULL,1),

-- Dog Company (vehicles)
(6,'Dog Company','Company','Steel Hounds',NULL,2,NULL,1),

-- Battalion command lance (heavier lance)
(7,'Battalion Command Lance','Lance',NULL,NULL,2,NULL,1);

-- Easy Company (Infantry, mechanized)
INSERT INTO units (unit_id,name,unit_type,nickname,allegiance,parent_unit_id,commander_id,location_id) VALUES
(29,'Easy Company','Company','Mud Dogs','Federated Suns',2,NULL,1);

-- 1st Mechanized Platoon (moved under Easy Company)
INSERT INTO units (unit_id,name,unit_type,nickname,allegiance,parent_unit_id,commander_id,location_id) VALUES
(8,'1st Mechanized Platoon','InfantryPlatoon',NULL,'Federated Suns',29,NULL,1);

-- Existing 1st Platoon squads
INSERT INTO units (unit_id,name,unit_type,parent_unit_id,location_id) VALUES
(9,'1st Squad','Squad',8,1),
(10,'2nd Squad','Squad',8,1),
(11,'3rd Squad','Squad',8,1),
(12,'4th Squad','Squad',8,1);

-- 2nd Mechanized Platoon
INSERT INTO units (unit_id,name,unit_type,nickname,allegiance,parent_unit_id,commander_id,location_id) VALUES
(30,'2nd Mechanized Platoon','InfantryPlatoon',NULL,'Federated Suns',29,NULL,1);

-- 2nd Platoon squads
INSERT INTO units (unit_id,name,unit_type,parent_unit_id,location_id) VALUES
(32,'1st Squad','Squad',30,1),
(33,'2nd Squad','Squad',30,1),
(34,'3rd Squad','Squad',30,1),
(35,'4th Squad','Squad',30,1);

-- 3rd Mechanized Platoon
INSERT INTO units (unit_id,name,unit_type,nickname,allegiance,parent_unit_id,commander_id,location_id) VALUES
(31,'3rd Mechanized Platoon','InfantryPlatoon',NULL,'Federated Suns',29,NULL,1);

-- 3rd Platoon squads
INSERT INTO units (unit_id,name,unit_type,parent_unit_id,location_id) VALUES
(36,'1st Squad','Squad',31,1),
(37,'2nd Squad','Squad',31,1),
(38,'3rd Squad','Squad',31,1),
(39,'4th Squad','Squad',31,1);


-- Lances under companies (as before)
INSERT INTO units (unit_id,name,unit_type,parent_unit_id,location_id) VALUES
(13,'1st Lance','Lance',3,1),
(14,'2nd Lance','Lance',3,1),
(15,'3rd Lance','Lance',3,1),
(16,'1st Lance','Lance',4,1),
(17,'2nd Lance','Lance',4,1),
(18,'3rd Lance','Lance',4,1),
(19,'1st Lance','Lance',5,1),
(20,'2nd Lance','Lance',5,1),
(21,'3rd Lance','Lance',5,1),
(22,'1st Lance','Lance',6,1),
(23,'2nd Lance','Lance',6,1),
(24,'3rd Lance','Lance',6,1),
(25,'4th Lance','Lance',6,1);

-- =============================
-- PART 3: Personnel (enriched)
-- Columns: (personnel_id, first_name, last_name, grade, status, gender, callsign, mos, experience, missions_completed)
-- =============================

-- Command: Regiment & Battalion (Officers)
INSERT INTO personnel (personnel_id, first_name, last_name, grade, status, gender, callsign, mos, experience, missions_completed) VALUES
(1,'Stephen','Davion','Marshal','Active','Male',NULL,'Officer','Elite',15),
(2,'Edward','Fairfax','Captain','Active','Male',NULL,'Officer','Veteran',10);

-- Company commanders (Officers)
INSERT INTO personnel VALUES
(3,'William','Douglas','Captain','Active','Male',NULL,'Officer','Veteran',9),
(4,'Robert','MacLeod','Captain','Active','Male',NULL,'Officer','Veteran',9),
(5,'James','Stewart','Captain','Active','Male',NULL,'Officer','Veteran',9),
(6,'Henry','Neville','Captain','Active','Male',NULL,'Officer','Veteran',9);

-- Battalion Cmd Lance (MechWarriors + lance)
-- Lt + Sgts (pilot roles appear in personnel_equipment later)
INSERT INTO personnel VALUES
(7,'Victoria','Fraser','Lieutenant','Active','Female','VALKYRIE','MechWarrior','Veteran',8),
(8,'Alice','Sandoval','Sergeant','Active','Female','VIPER','MechWarrior','Regular',5),
(9,'Nicholas','Fraser','Sergeant','Active','Male','LONGSHOT','MechWarrior','Regular',5),
(10,'Alan','Forbes','Sergeant','Active','Male','BULLDOG','MechWarrior','Regular',5);

-- Easy Company leadership (Officer)
INSERT INTO personnel VALUES
(11,'George','Sortek','Lieutenant','Active','Male',NULL,'Officer','Veteran',7);

-- Infantry squads/platoons (Mechanized) – existing IDs 12–47 mapped as infantry
-- (No callsigns; MOS=Infantry; mostly Regular/Green)
INSERT INTO personnel VALUES
(12,'Sarah','Neville','Sergeant','Active','Female',NULL,'Infantry','Regular',5),
(13,'Nicholas','Forbes','Corporal','Active','Male',NULL,'Infantry','Regular',4),
(14,'Thomas','Llewellyn','Corporal','Active','Male',NULL,'Infantry','Regular',4),
(15,'Christina','Hasek','Private','Active','Female',NULL,'Infantry','Green',1),
(16,'Peter','Hasek','Private','Active','Male',NULL,'Infantry','Green',1),
(17,'Edward','Neville','Private','Active','Male',NULL,'Infantry','Green',1),
(18,'Simon','Fraser','Private','Active','Male',NULL,'Infantry','Regular',2),
(19,'Alexander','Douglas','Private','Active','Male',NULL,'Infantry','Regular',3),
(20,'Victoria','Sandoval','Private','Active','Female',NULL,'Infantry','Green',1),
(21,'Elizabeth','Sortek','Sergeant','Active','Female',NULL,'Infantry','Regular',5),
(22,'Victoria','Stewart','Corporal','Active','Female',NULL,'Infantry','Regular',4),
(23,'Alexandra','Summers','Corporal','Active','Female',NULL,'Infantry','Regular',4),
(24,'Eleanor','Kavanagh','Private','Active','Female',NULL,'Infantry','Green',1),
(25,'Alice','Llewellyn','Private','Active','Female',NULL,'Infantry','Green',1),
(26,'Mary','Wallace','Private','Active','Female',NULL,'Infantry','Green',1),
(27,'David','Hasek','Private','Active','Male',NULL,'Infantry','Regular',2),
(28,'Mary','Neville','Private','Active','Female',NULL,'Infantry','Green',1),
(29,'Hugh','Kerr','Private','Active','Male',NULL,'Infantry','Regular',2),
(30,'Sarah','Allard','Sergeant','Active','Female',NULL,'Infantry','Regular',5),
(31,'Edward','Sinclair','Corporal','Active','Male',NULL,'Infantry','Regular',4),
(32,'George','Fletcher','Corporal','Active','Male',NULL,'Infantry','Regular',4),
(33,'Malcolm','MacLeod','Private','Active','Male',NULL,'Infantry','Regular',2),
(34,'Thomas','Hamilton','Private','Active','Male',NULL,'Infantry','Green',1),
(35,'Robert','Sinclair','Private','Active','Male',NULL,'Infantry','Green',1),
(36,'Katherine','Wallace','Private','Active','Female',NULL,'Infantry','Regular',2),
(37,'Alexandra','Griffiths','Private','Active','Female',NULL,'Infantry','Green',1),
(38,'Christina','Hamilton','Private','Active','Female',NULL,'Infantry','Green',1),
(39,'Edward','Mercer','Sergeant','Active','Male',NULL,'Infantry','Regular',5),
(40,'Anne','Griffiths','Corporal','Active','Female',NULL,'Infantry','Regular',4),
(41,'Arthur','Connor','Corporal','Active','Male',NULL,'Infantry','Regular',4),
(42,'Edward','Fletcher','Private','Active','Male',NULL,'Infantry','Green',1),
(43,'Mary','Forbes','Private','Active','Female',NULL,'Infantry','Green',1),
(44,'Hugh','Munro','Private','Active','Male',NULL,'Infantry','Regular',2),
(45,'Fiona','Wallace','Private','Active','Female',NULL,'Infantry','Green',1),
(46,'Christopher','Allard','Private','Active','Male',NULL,'Infantry','Green',1),
(47,'Anne','Sortek','Private','Active','Female',NULL,'Infantry','Green',1);

-- Company/Battalion MechWarriors across Able/Baker/Charlie (callsigns only for pilots)
-- (Sample subset shown; the rest continue similarly)
INSERT INTO personnel VALUES
(48,'Robert','Fairfax','Lieutenant','Active','Male','RANGER','MechWarrior','Veteran',7),
(49,'Catherine','Fletcher','Sergeant','Active','Female','EAGLE','MechWarrior','Regular',5),
(50,'Helena','MacGregor','Sergeant','Active','Female','SPITFIRE','MechWarrior','Regular',5),
(51,'Juliana','Sandoval','Sergeant','Active','Female','WRAITH','MechWarrior','Regular',5),
(52,'Margaret','MacGregor','Lieutenant','Active','Female','NOMAD','MechWarrior','Veteran',7),
(53,'Hugh','Davion','Sergeant','Active','Male','IRONHAND','MechWarrior','Regular',5),
(54,'Alexandra','Connor','Sergeant','Active','Female','FALCON','MechWarrior','Regular',5),
(55,'Alexander','MacGregor','Sergeant','Active','Male','SABER','MechWarrior','Regular',5),
(56,'Catherine','Douglas','Lieutenant','Active','Female','VALKYRIE','MechWarrior','Veteran',7),
(57,'Katherine','Grant','Sergeant','Active','Female','SWIFT','MechWarrior','Regular',5),
(58,'Alice','Kerr','Sergeant','Active','Female','VIPER','MechWarrior','Regular',5),
(59,'Mary','Allard','Sergeant','Active','Female','ORBIT','MechWarrior','Regular',5),
(60,'John','Mercer','Lieutenant','Active','Male','LANCER','MechWarrior','Veteran',7),
(61,'Robert','Llewellyn','Sergeant','Active','Male','BLAZE','MechWarrior','Regular',5),
(62,'Henry','Sandoval','Sergeant','Active','Male','NIGHTOWL','MechWarrior','Regular',5),
(63,'Thomas','Fletcher','Sergeant','Active','Male','JACKAL','MechWarrior','Regular',5),
(64,'Robert','Griffiths','Lieutenant','Active','Male','RAPTOR','MechWarrior','Veteran',7),
(65,'Henry','Sinclair','Sergeant','Active','Male','HOWLER','MechWarrior','Regular',5),
(66,'Martin','Wallace','Sergeant','Active','Male','HUNTER','MechWarrior','Regular',5),
(67,'Sarah','Kerr','Sergeant','Active','Female','SPARROW','MechWarrior','Regular',5),
(68,'Stephen','Kavanagh','Lieutenant','Active','Male','TITAN','MechWarrior','Veteran',7),
(69,'Catherine','Wallace','Sergeant','Active','Female','SIREN','MechWarrior','Regular',5),
(70,'Christina','Sandoval','Sergeant','Active','Female','GHOST','MechWarrior','Regular',5),
(71,'Stephen','Griffiths','Sergeant','Active','Male','HAWK','MechWarrior','Regular',5),
(72,'Eleanor','Fairfax','Lieutenant','Active','Female','RAVEN','MechWarrior','Veteran',7),
(73,'Edward','Griffiths','Sergeant','Active','Male','RIDGEBACK','MechWarrior','Regular',5),
(74,'Arthur','Sortek','Sergeant','Active','Male','SHIELD','MechWarrior','Regular',5),
(75,'Sarah','Harding','Sergeant','Active','Female','FURY','MechWarrior','Regular',5),
(76,'Helena','Neville','Lieutenant','Active','Female','BANSHEE','MechWarrior','Veteran',7),
(77,'Charles','Douglas','Sergeant','Active','Male','COYOTE','MechWarrior','Regular',5),
(78,'Fiona','Allard','Sergeant','Active','Female','TEMPEST','MechWarrior','Regular',5),
(79,'Nicholas','Griffiths','Sergeant','Active','Male','ECHO','MechWarrior','Regular',5),
(80,'Davinia','Neville','Lieutenant','Active','Female','SPARK','MechWarrior','Veteran',7),
(81,'Arthur','Allard','Sergeant','Active','Male','ANVIL','MechWarrior','Regular',5),
(82,'Alexandra','Wallace','Sergeant','Active','Female','LARK','MechWarrior','Regular',5),
(83,'Mary','Sandoval','Sergeant','Active','Female','EMBER','MechWarrior','Regular',5);

-- Vehicle company (Dog) – Tankers (no callsigns)
INSERT INTO personnel VALUES
(84,'Edward','Gordon','Sergeant','Active','Male',NULL,'Tanker','Regular',5),
(85,'Elizabeth','Gordon','Corporal','Active','Female',NULL,'Tanker','Regular',4),
(86,'Alan','Campbell','Private','Active','Male',NULL,'Tanker','Green',1),
(87,'Isobel','Sinclair','Private','Active','Female',NULL,'Tanker','Green',1),

-- (Continue the rest of IDs 88–132 similarly with appropriate MOS and genders:)
(88,'Alice','Stewart','Sergeant','Active','Female','COMET','MechWarrior','Regular',5),
(89,'Alice','Summers','Corporal','Active','Female',NULL,'Infantry','Regular',4),
(90,'Stephen','Neville','Private','Active','Male',NULL,'Infantry','Green',1),
(91,'Mary','Llewellyn','Sergeant','Active','Female','QUILL','MechWarrior','Regular',5),
(92,'Moira','Sinclair','Corporal','Active','Female',NULL,'Infantry','Regular',4),
(93,'Charles','Douglas','Private','Active','Male',NULL,'Infantry','Green',1),
(94,'Duncan','Kavanagh','Sergeant','Active','Male','GAEL','MechWarrior','Regular',5),
(95,'John','Fletcher','Corporal','Active','Male',NULL,'Infantry','Regular',4),
(96,'Andrew','Kavanagh','Private','Active','Male',NULL,'Infantry','Green',1),
(97,'Nicholas','Fraser','Sergeant','Active','Male','SCOUT','MechWarrior','Regular',5),
(98,'Charles','Stewart','Corporal','Active','Male',NULL,'Infantry','Regular',4),
(99,'Peter','MacGregor','Private','Active','Male',NULL,'Infantry','Green',1),
(100,'Anne','MacGregor','Sergeant','Active','Female','GLASS','MechWarrior','Regular',5),
(101,'Fiona','Stewart','Corporal','Active','Female',NULL,'Infantry','Regular',4),
(102,'Helena','Hasek','Private','Active','Female',NULL,'Infantry','Green',1),
(103,'Moira','Kavanagh','Sergeant','Active','Female','BRIAR','MechWarrior','Regular',5),
(104,'Alexandra','MacGregor','Corporal','Active','Female',NULL,'Infantry','Regular',4),
(105,'Christina','Hamilton','Private','Active','Female',NULL,'Infantry','Green',1),
(106,'Eilidh','Fletcher','Sergeant','Active','Female','ASTER','MechWarrior','Regular',5),
(107,'Moira','Harding','Corporal','Active','Female',NULL,'Infantry','Regular',4),
(108,'William','Fletcher','Private','Active','Male',NULL,'Infantry','Green',1),
(109,'Alexandra','Stewart','Sergeant','Active','Female','SWAN','MechWarrior','Regular',5),
(110,'Charles','Stewart','Corporal','Active','Male',NULL,'Infantry','Regular',4),
(111,'Anne','Hamilton','Private','Active','Female',NULL,'Infantry','Green',1),
(112,'Charles','Mercer','Sergeant','Active','Male','ARGUS','MechWarrior','Regular',5),
(113,'Janet','Munro','Corporal','Active','Female',NULL,'Infantry','Regular',4),
(114,'Arthur','Summers','Private','Active','Male',NULL,'Infantry','Green',1),
(115,'David','Campbell','Sergeant','Active','Male','STONE','MechWarrior','Regular',5),
(116,'Janet','Hamilton','Corporal','Active','Female',NULL,'Infantry','Regular',4),
(117,'Nicholas','Stewart','Private','Active','Male',NULL,'Infantry','Green',1),
(118,'Victoria','MacGregor','Sergeant','Active','Female','WIND','MechWarrior','Regular',5),
(119,'Katherine','Campbell','Corporal','Active','Female',NULL,'Infantry','Regular',4),
(120,'Janet','Wallace','Private','Active','Female',NULL,'Infantry','Green',1),
(121,'Elizabeth','MacGregor','Sergeant','Active','Female','FROST','MechWarrior','Regular',5),
(122,'Christopher','Hamilton','Corporal','Active','Male',NULL,'Infantry','Regular',4),
(123,'Thomas','Griffiths','Private','Active','Male',NULL,'Infantry','Green',1),
(124,'Nicholas','Summers','Sergeant','Active','Male','DAGGER','MechWarrior','Regular',5),
(125,'Christopher','Kerr','Corporal','Active','Male',NULL,'Infantry','Regular',4),
(126,'Christina','Summers','Private','Active','Female',NULL,'Infantry','Green',1),
(127,'David','Kerr','Sergeant','Active','Male','HOUND','MechWarrior','Regular',5),
(128,'Christopher','MacLeod','Corporal','Active','Male',NULL,'Infantry','Regular',4),
(129,'Mary','Hasek','Private','Active','Female',NULL,'Infantry','Green',1),
(130,'Eilidh','Harding','Sergeant','Active','Female','SPARK','MechWarrior','Regular',5),
(131,'Richard','Forbes','Corporal','Active','Male',NULL,'Infantry','Regular',4),
(132,'Janet','Summers','Private','Active','Female',NULL,'Infantry','Green',1);

-- =============================
-- NEW PERSONNEL for Easy Company 2nd & 3rd Mech Platoons
-- (IDs 133–190)
-- Pattern: Each platoon has 1 Lt (platoon leader, MOS=Infantry), and 4 squads each:
--   1 Sgt (leader), 2 Cpls, 4 Pvts per squad
-- =============================

-- 2nd Mechanized Platoon (Platoon Leader)
INSERT INTO personnel VALUES
(133,'Gareth','Armstrong','Lieutenant','Active','Male',NULL,'Infantry','Regular',4);

-- 2nd Platoon: 1st Squad (7)
INSERT INTO personnel VALUES
(134,'Megan','Price','Sergeant','Active','Female',NULL,'Infantry','Regular',4),
(135,'Owen','Patel','Corporal','Active','Male',NULL,'Infantry','Regular',3),
(136,'Lena','Hughes','Corporal','Active','Female',NULL,'Infantry','Regular',3),
(137,'Ivan','Novak','Private','Active','Male',NULL,'Infantry','Green',1),
(138,'Priya','Singh','Private','Active','Female',NULL,'Infantry','Green',1),
(139,'Diego','Martinez','Private','Active','Male',NULL,'Infantry','Green',1),
(140,'Yuki','Tanaka','Private','Active','Female',NULL,'Infantry','Green',1);

-- 2nd Platoon: 2nd Squad (7)
INSERT INTO personnel VALUES
(141,'Callum','Reid','Sergeant','Active','Male',NULL,'Infantry','Regular',4),
(142,'Aoife','Murphy','Corporal','Active','Female',NULL,'Infantry','Regular',3),
(143,'Noah','Cohen','Corporal','Active','Male',NULL,'Infantry','Regular',3),
(144,'Elena','Mikhailova','Private','Active','Female',NULL,'Infantry','Green',1),
(145,'Omar','Hassan','Private','Active','Male',NULL,'Infantry','Green',1),
(146,'Lucas','Moreau','Private','Active','Male',NULL,'Infantry','Green',1),
(147,'Sofia','Kowalska','Private','Active','Female',NULL,'Infantry','Green',1);

-- 2nd Platoon: 3rd Squad (7)
INSERT INTO personnel VALUES
(148,'Hannah','Levy','Sergeant','Active','Female',NULL,'Infantry','Regular',4),
(149,'Ronan','Gallagher','Corporal','Active','Male',NULL,'Infantry','Regular',3),
(150,'Asha','Rao','Corporal','Active','Female',NULL,'Infantry','Regular',3),
(151,'Mateo','Silva','Private','Active','Male',NULL,'Infantry','Green',1),
(152,'Iris','Nguyen','Private','Active','Female',NULL,'Infantry','Green',1),
(153,'Jasper','Wong','Private','Active','Male',NULL,'Infantry','Green',1),
(154,'Marta','Sokolov','Private','Active','Female',NULL,'Infantry','Green',1);

-- 2nd Platoon: 4th Squad (7)
INSERT INTO personnel VALUES
(155,'Seamus','O\'Brien','Sergeant','Active','Male',NULL,'Infantry','Regular',4),
(156,'Hyejin','Park','Corporal','Active','Female',NULL,'Infantry','Regular',3),
(157,'Colin','Barker','Corporal','Active','Male',NULL,'Infantry','Regular',3),
(158,'Arjun','Kapoor','Private','Active','Male',NULL,'Infantry','Green',1),
(159,'Giulia','Romano','Private','Active','Female',NULL,'Infantry','Green',1),
(160,'Marek','Zielinski','Private','Active','Male',NULL,'Infantry','Green',1),
(161,'Leila','Farouk','Private','Active','Female',NULL,'Infantry','Green',1);

-- 3rd Mechanized Platoon (Platoon Leader)
INSERT INTO personnel VALUES
(162,'Rowan','Campbell','Lieutenant','Active','Male',NULL,'Infantry','Regular',4);

-- 3rd Platoon: 1st Squad (7)
INSERT INTO personnel VALUES
(163,'Aileen','MacRae','Sergeant','Active','Female',NULL,'Infantry','Regular',4),
(164,'Ethan','Williams','Corporal','Active','Male',NULL,'Infantry','Regular',3),
(165,'Sara','Carvalho','Corporal','Active','Female',NULL,'Infantry','Regular',3),
(166,'Hector','Torres','Private','Active','Male',NULL,'Infantry','Green',1),
(167,'Nadia','Kuznetsova','Private','Active','Female',NULL,'Infantry','Green',1),
(168,'Amir','Rahman','Private','Active','Male',NULL,'Infantry','Green',1),
(169,'Jade','Bennett','Private','Active','Female',NULL,'Infantry','Green',1);

-- 3rd Platoon: 2nd Squad (7)
INSERT INTO personnel VALUES
(170,'Fraser','Burns','Sergeant','Active','Male',NULL,'Infantry','Regular',4),
(171,'Nora','Khan','Corporal','Active','Female',NULL,'Infantry','Regular',3),
(172,'Dylan','Olsen','Corporal','Active','Male',NULL,'Infantry','Regular',3),
(173,'Leo','Rossi','Private','Active','Male',NULL,'Infantry','Green',1),
(174,'Camila','Gomez','Private','Active','Female',NULL,'Infantry','Green',1),
(175,'Tomas','Nowak','Private','Active','Male',NULL,'Infantry','Green',1),
(176,'Rina','Sato','Private','Active','Female',NULL,'Infantry','Green',1);

-- 3rd Platoon: 3rd Squad (7)
INSERT INTO personnel VALUES
(177,'Iona','Fraser','Sergeant','Active','Female',NULL,'Infantry','Regular',4),
(178,'Hugo','Dupont','Corporal','Active','Male',NULL,'Infantry','Regular',3),
(179,'Zara','Haddad','Corporal','Active','Female',NULL,'Infantry','Regular',3),
(180,'Rafael','Costa','Private','Active','Male',NULL,'Infantry','Green',1),
(181,'Lucia','Marin','Private','Active','Female',NULL,'Infantry','Green',1),
(182,'Kenji','Ito','Private','Active','Male',NULL,'Infantry','Green',1),
(183,'Emily','Jones','Private','Active','Female',NULL,'Infantry','Green',1);

-- 3rd Platoon: 4th Squad (7)
INSERT INTO personnel VALUES
(184,'Gregor','Ivanov','Sergeant','Active','Male',NULL,'Infantry','Regular',4),
(185,'Maeve','O\'Connell','Corporal','Active','Female',NULL,'Infantry','Regular',3),
(186,'Karl','Hofmann','Corporal','Active','Male',NULL,'Infantry','Regular',3),
(187,'Ana','Pereira','Private','Active','Female',NULL,'Infantry','Green',1),
(188,'Youssef','Ali','Private','Active','Male',NULL,'Infantry','Green',1),
(189,'Dominik','Krajewski','Private','Active','Male',NULL,'Infantry','Green',1),
(190,'Olivia','Turner','Private','Active','Female',NULL,'Infantry','Green',1);

-- Personnel Assignments: Regiment & Battalion HQ
INSERT INTO personnel_assignments (personnel_id, unit_id, date_assigned) VALUES
(1,1,'3025-01-01'),  -- Marshal Stephen Davion -> Regiment
(2,2,'3025-01-01'),  -- Captain Edward Fairfax -> 1st Battalion
(7,7,'3025-01-01'),  -- Lt Victoria Fraser -> Battalion Command Lance
(8,7,'3025-01-01'),
(9,7,'3025-01-01'),
(10,7,'3025-01-01');

-- Personnel Assignments: Company Commanders
INSERT INTO personnel_assignments (personnel_id, unit_id, date_assigned) VALUES
(3,3,'3025-01-01'),  -- Capt William Douglas -> Able
(4,4,'3025-01-01'),  -- Capt Robert MacLeod -> Baker
(5,5,'3025-01-01'),  -- Capt James Stewart -> Charlie
(6,6,'3025-01-01'),  -- Capt Henry Neville -> Dog
(48,29,'3025-01-01'); -- Lt Robert Fairfax -> Easy Company

-- Personnel Assignments: Able Company
INSERT INTO personnel_assignments (personnel_id, unit_id, date_assigned) VALUES
(48,13,'3025-01-01'),  -- Blackjack pilot
(49,13,'3025-01-01'),  -- Centurion pilot
(50,13,'3025-01-01'),
(51,13,'3025-01-01'),
(52,14,'3025-01-01'),
(53,14,'3025-01-01'),
(54,14,'3025-01-01'),
(55,14,'3025-01-01'),
(56,15,'3025-01-01'),
(57,15,'3025-01-01'),
(58,15,'3025-01-01'),
(59,15,'3025-01-01');

-- Personnel Assignments: Baker Company
INSERT INTO personnel_assignments (personnel_id, unit_id, date_assigned) VALUES
(60,16,'3025-01-01'),
(61,16,'3025-01-01'),
(62,16,'3025-01-01'),
(63,16,'3025-01-01'),
(64,17,'3025-01-01'),
(65,17,'3025-01-01'),
(66,17,'3025-01-01'),
(67,17,'3025-01-01'),
(68,18,'3025-01-01'),
(69,18,'3025-01-01'),
(70,18,'3025-01-01'),
(71,18,'3025-01-01');

-- Personnel Assignments: Charlie Company
INSERT INTO personnel_assignments (personnel_id, unit_id, date_assigned) VALUES
(72,19,'3025-01-01'),
(73,19,'3025-01-01'),
(74,19,'3025-01-01'),
(75,19,'3025-01-01'),
(76,20,'3025-01-01'),
(77,20,'3025-01-01'),
(78,20,'3025-01-01'),
(79,20,'3025-01-01'),
(80,21,'3025-01-01'),
(81,21,'3025-01-01'),
(82,21,'3025-01-01'),
(83,21,'3025-01-01');

-- Personnel Assignments: Dog Company
INSERT INTO personnel_assignments (personnel_id, unit_id, date_assigned) VALUES
(84,22,'3025-01-01'),
(85,22,'3025-01-01'),
(86,22,'3025-01-01'),
(87,22,'3025-01-01'),
(88,23,'3025-01-01'),
(89,23,'3025-01-01'),
(90,23,'3025-01-01'),
(91,23,'3025-01-01'),
(92,24,'3025-01-01'),
(93,24,'3025-01-01'),
(94,24,'3025-01-01'),
(95,24,'3025-01-01'),
(96,25,'3025-01-01'),
(97,25,'3025-01-01'),
(98,25,'3025-01-01'),
(99,25,'3025-01-01');

-- Personnel Assignments: Easy Company, 1st Platoon (Mechanized)
INSERT INTO personnel_assignments (personnel_id, unit_id, date_assigned) VALUES
(11,8,'3025-01-01'),  -- Platoon Leader
(12,9,'3025-01-01'),  -- 1st Squad Leader
(13,9,'3025-01-01'),
(14,9,'3025-01-01'),
(15,9,'3025-01-01'),
(16,9,'3025-01-01'),
(17,9,'3025-01-01'),
(18,9,'3025-01-01'),
(19,9,'3025-01-01'),
(20,10,'3025-01-01'),
(21,10,'3025-01-01'),
(22,10,'3025-01-01'),
(23,10,'3025-01-01'),
(24,10,'3025-01-01'),
(25,10,'3025-01-01'),
(26,10,'3025-01-01'),
(27,10,'3025-01-01'),
(28,11,'3025-01-01'),
(29,11,'3025-01-01'),
(30,11,'3025-01-01'),
(31,11,'3025-01-01'),
(32,11,'3025-01-01'),
(33,11,'3025-01-01'),
(34,11,'3025-01-01'),
(35,11,'3025-01-01'),
(36,12,'3025-01-01'),
(37,12,'3025-01-01'),
(38,12,'3025-01-01'),
(39,12,'3025-01-01'),
(40,12,'3025-01-01'),
(41,12,'3025-01-01'),
(42,12,'3025-01-01'),
(43,12,'3025-01-01');

-- Personnel Assignments: Easy Company, 2nd Mechanized Platoon
INSERT INTO personnel_assignments (personnel_id, unit_id, date_assigned) VALUES
(44,30,'3025-01-01'),  -- Lt, Platoon Leader
(45,32,'3025-01-01'),  -- 1st Squad Leader
(46,32,'3025-01-01'),
(47,32,'3025-01-01'),
(48,32,'3025-01-01'),
(49,32,'3025-01-01'),
(50,32,'3025-01-01'),
(51,32,'3025-01-01'),
(52,32,'3025-01-01'),
(53,33,'3025-01-01'),  -- 2nd Squad Leader
(54,33,'3025-01-01'),
(55,33,'3025-01-01'),
(56,33,'3025-01-01'),
(57,33,'3025-01-01'),
(58,33,'3025-01-01'),
(59,33,'3025-01-01'),
(60,33,'3025-01-01'),
(61,34,'3025-01-01'),  -- 3rd Squad Leader
(62,34,'3025-01-01'),
(63,34,'3025-01-01'),
(64,34,'3025-01-01'),
(65,34,'3025-01-01'),
(66,34,'3025-01-01'),
(67,34,'3025-01-01'),
(68,34,'3025-01-01'),
(69,35,'3025-01-01'),  -- 4th Squad Leader
(70,35,'3025-01-01'),
(71,35,'3025-01-01'),
(72,35,'3025-01-01'),
(73,35,'3025-01-01'),
(74,35,'3025-01-01'),
(75,35,'3025-01-01'),
(76,35,'3025-01-01');

-- Personnel Assignments: Easy Company, 3rd Mechanized Platoon
INSERT INTO personnel_assignments (personnel_id, unit_id, date_assigned) VALUES
(77,31,'3025-01-01'),  -- Lt, Platoon Leader
(78,36,'3025-01-01'),  -- 1st Squad Leader
(79,36,'3025-01-01'),
(80,36,'3025-01-01'),
(81,36,'3025-01-01'),
(82,36,'3025-01-01'),
(83,36,'3025-01-01'),
(84,36,'3025-01-01'),
(85,36,'3025-01-01'),
(86,37,'3025-01-01'),  -- 2nd Squad Leader
(87,37,'3025-01-01'),
(88,37,'3025-01-01'),
(89,37,'3025-01-01'),
(90,37,'3025-01-01'),
(91,37,'3025-01-01'),
(92,37,'3025-01-01'),
(93,37,'3025-01-01'),
(94,38,'3025-01-01'),  -- 3rd Squad Leader
(95,38,'3025-01-01'),
(96,38,'3025-01-01'),
(97,38,'3025-01-01'),
(98,38,'3025-01-01'),
(99,38,'3025-01-01'),
(100,38,'3025-01-01'),
(101,38,'3025-01-01'),
(102,39,'3025-01-01'), -- 4th Squad Leader
(103,39,'3025-01-01'),
(104,39,'3025-01-01'),
(105,39,'3025-01-01'),
(106,39,'3025-01-01'),
(107,39,'3025-01-01'),
(108,39,'3025-01-01'),
(109,39,'3025-01-01');

-- ========================
-- Part 5 – Equipment Inserts
-- ========================

-- ========================
-- Command Lance Equipment
-- ========================
INSERT INTO equipment (equipment_id, chassis_id, serial_number, assigned_unit_id, damage_percentage) VALUES
(1,22,'MARAUDER_0000000001',7,0.0),
(2,25,'WARHAMMER_0000000001',7,0.0),
(3,20,'ARCHER_0000000001',7,0.0),
(4,24,'THUNDERBOLT_0000000001',7,0.0);

-- ========================
-- Easy Company APCs
-- ========================
INSERT INTO equipment (equipment_id, chassis_id, serial_number, assigned_unit_id, damage_percentage) VALUES
(5,32,'APC_0000000001',9,0.0),
(6,32,'APC_0000000002',10,0.0),
(7,32,'APC_0000000003',11,0.0),
(8,32,'APC_0000000004',12,0.0),
(9,32,'APC_0000000005',33,0.0),
(10,32,'APC_0000000006',34,0.0),
(11,32,'APC_0000000007',35,0.0),
(12,32,'APC_0000000008',36,0.0),
(13,32,'APC_0000000009',37,0.0),
(14,32,'APC_0000000010',38,0.0),
(15,32,'APC_0000000011',39,0.0),
(16,32,'APC_0000000012',40,0.0);

-- ========================
-- Able Company Equipment
-- ========================
INSERT INTO equipment (equipment_id, chassis_id, serial_number, assigned_unit_id, damage_percentage) VALUES
(17,14,'BLACKJACK_0000000001',13,0.0),
(18,1,'CENTURION_0000000001',13,0.0),
(19,1,'CENTURION_0000000002',13,0.0),
(20,2,'ENFORCER_0000000001',13,0.0),
(21,14,'BLACKJACK_0000000002',14,0.0),
(22,6,'HUNCHBACK_0000000001',14,0.0),
(23,3,'GRIFFIN_0000000001',14,0.0),
(24,4,'SHADOW_0000000001',14,0.0),
(25,9,'SPIDER_0000000001',15,0.0),
(26,1,'CENTURION_0000000003',15,0.0),
(27,6,'HUNCHBACK_0000000002',15,0.0),
(28,14,'BLACKJACK_0000000003',15,0.0);

-- ========================
-- Baker Company Equipment
-- ========================
INSERT INTO equipment (equipment_id, chassis_id, serial_number, assigned_unit_id, damage_percentage) VALUES
(29,7,'JENNER_0000000001',16,0.0),
(30,9,'SPIDER_0000000002',16,0.0),
(31,8,'PHOENIX_HAWK_0000000001',16,0.0),
(32,1,'CENTURION_0000000004',16,0.0),
(33,4,'SHADOW_HAWK_0000000002',17,0.0),
(34,9,'SPIDER_0000000003',17,0.0),
(35,9,'SPIDER_0000000004',17,0.0),
(36,6,'HUNCHBACK_0000000003',17,0.0),
(37,8,'PHOENIX_HAWK_0000000002',18,0.0),
(38,2,'ENFORCER_0000000002',18,0.0),
(39,9,'SPIDER_0000000005',18,0.0),
(40,2,'ENFORCER_0000000003',18,0.0);

-- ========================
-- Charlie Company Equipment
-- ========================
INSERT INTO equipment (equipment_id, chassis_id, serial_number, assigned_unit_id, damage_percentage) VALUES
(41,9,'SPIDER_0000000006',19,0.0),
(42,11,'JAVELIN_0000000001',19,0.0),
(43,14,'BLACKJACK_0000000004',19,0.0),
(44,11,'JAVELIN_0000000002',19,0.0),
(45,14,'BLACKJACK_0000000005',20,0.0),
(46,4,'SHADOW_HAWK_0000000003',20,0.0),
(47,13,'ASSASSIN_0000000001',20,0.0),
(48,15,'TREBUCHET_0000000001',20,0.0),
(49,16,'RIFLEMAN_0000000001',21,0.0),
(50,17,'CATAPULT_0000000001',21,0.0),
(51,15,'TREBUCHET_0000000002',21,0.0),
(52,14,'BLACKJACK_0000000006',21,0.0);

-- ========================
-- Dog Company Equipment
-- ========================
INSERT INTO equipment (equipment_id, chassis_id, serial_number, assigned_unit_id, damage_percentage) VALUES
(53,28,'VON_LUCKNER_0000000001',22,0.0),
(54,26,'MANTICORE_0000000001',22,0.0),
(55,26,'MANTICORE_0000000002',22,0.0),
(56,27,'BULLDOG_0000000001',22,0.0),
(57,29,'SCORPION_0000000001',23,0.0),
(58,29,'SCORPION_0000000002',23,0.0),
(59,30,'VEDETTE_0000000001',23,0.0),
(60,30,'VEDETTE_0000000002',23,0.0),
(61,26,'MANTICORE_0000000003',24,0.0),
(62,26,'MANTICORE_0000000004',24,0.0),
(63,27,'BULLDOG_0000000002',24,0.0),
(64,27,'BULLDOG_0000000003',24,0.0),
(65,27,'BULLDOG_0000000004',25,0.0),
(66,29,'SCORPION_0000000003',25,0.0),
(67,29,'SCORPION_0000000004',25,0.0),
(68,30,'VEDETTE_0000000003',25,0.0);

-- ========================
-- Command Lance
-- ========================
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(7,1,'Pilot','3025-01-01',NULL),
(8,2,'Pilot','3025-01-01',NULL),
(9,3,'Pilot','3025-01-01',NULL),
(10,4,'Pilot','3025-01-01',NULL);

-- ========================
-- Easy Company – 1st Platoon
-- ========================
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(19,5,'Driver','3025-01-01',NULL),
(20,5,'Gunner','3025-01-01',NULL),
(28,6,'Driver','3025-01-01',NULL),
(29,6,'Gunner','3025-01-01',NULL),
(37,7,'Driver','3025-01-01',NULL),
(38,7,'Gunner','3025-01-01',NULL),
(46,8,'Driver','3025-01-01',NULL),
(47,8,'Gunner','3025-01-01',NULL);

-- Easy Company – 2nd Platoon
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(48,9,'Driver','3025-01-01',NULL),
(49,9,'Gunner','3025-01-01',NULL),
(50,10,'Driver','3025-01-01',NULL),
(51,10,'Gunner','3025-01-01',NULL),
(52,11,'Driver','3025-01-01',NULL),
(53,11,'Gunner','3025-01-01',NULL),
(54,12,'Driver','3025-01-01',NULL),
(55,12,'Gunner','3025-01-01',NULL);

-- Easy Company – 3rd Platoon
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(56,13,'Driver','3025-01-01',NULL),
(57,13,'Gunner','3025-01-01',NULL),
(58,14,'Driver','3025-01-01',NULL),
(59,14,'Gunner','3025-01-01',NULL),
(60,15,'Driver','3025-01-01',NULL),
(61,15,'Gunner','3025-01-01',NULL),
(62,16,'Driver','3025-01-01',NULL),
(63,16,'Gunner','3025-01-01',NULL);

-- ========================
-- Able Company – 1st Lance
-- ========================
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(64,17,'Pilot','3025-01-01',NULL),
(65,18,'Pilot','3025-01-01',NULL),
(66,19,'Pilot','3025-01-01',NULL),
(67,20,'Pilot','3025-01-01',NULL);

-- Able Company – 2nd Lance
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(68,21,'Pilot','3025-01-01',NULL),
(69,22,'Pilot','3025-01-01',NULL),
(70,23,'Pilot','3025-01-01',NULL),
(71,24,'Pilot','3025-01-01',NULL);

-- Able Company – 3rd Lance
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(72,25,'Pilot','3025-01-01',NULL),
(73,26,'Pilot','3025-01-01',NULL),
(74,27,'Pilot','3025-01-01',NULL),
(75,28,'Pilot','3025-01-01',NULL);

-- ========================
-- Baker Company – 1st Lance
-- ========================
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(76,29,'Pilot','3025-01-01',NULL),
(77,30,'Pilot','3025-01-01',NULL),
(78,31,'Pilot','3025-01-01',NULL),
(79,32,'Pilot','3025-01-01',NULL);

-- Baker Company – 2nd Lance
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(80,33,'Pilot','3025-01-01',NULL),
(81,34,'Pilot','3025-01-01',NULL),
(82,35,'Pilot','3025-01-01',NULL),
(83,36,'Pilot','3025-01-01',NULL);

-- Baker Company – 3rd Lance
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(84,37,'Pilot','3025-01-01',NULL),
(85,38,'Pilot','3025-01-01',NULL),
(86,39,'Pilot','3025-01-01',NULL),
(87,40,'Pilot','3025-01-01',NULL);

-- ========================
-- Charlie Company – 1st Lance
-- ========================
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(88,41,'Pilot','3025-01-01',NULL),
(89,42,'Pilot','3025-01-01',NULL),
(90,43,'Pilot','3025-01-01',NULL),
(91,44,'Pilot','3025-01-01',NULL);

-- Charlie Company – 2nd Lance
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(92,45,'Pilot','3025-01-01',NULL),
(93,46,'Pilot','3025-01-01',NULL),
(94,47,'Pilot','3025-01-01',NULL),
(95,48,'Pilot','3025-01-01',NULL);

-- Charlie Company – 3rd Lance (Fire Support)
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(96,49,'Pilot','3025-01-01',NULL),
(97,50,'Pilot','3025-01-01',NULL),
(98,51,'Pilot','3025-01-01',NULL),
(99,52,'Pilot','3025-01-01',NULL);

-- ========================
-- Dog Company – 1st Lance (Von Luckner, Manticores, Bulldog)
-- ========================
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(100,53,'Commander','3025-01-01',NULL),
(101,53,'Driver','3025-01-01',NULL),
(102,53,'Gunner','3025-01-01',NULL),
(103,53,'Loader','3025-01-01',NULL),
(104,54,'Commander','3025-01-01',NULL),
(105,54,'Driver','3025-01-01',NULL),
(106,54,'Gunner','3025-01-01',NULL),
(107,55,'Commander','3025-01-01',NULL),
(108,55,'Driver','3025-01-01',NULL),
(109,55,'Gunner','3025-01-01',NULL),
(110,56,'Commander','3025-01-01',NULL),
(111,56,'Driver','3025-01-01',NULL),
(112,56,'Gunner','3025-01-01',NULL);

-- Dog Company – 2nd Lance
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(113,57,'Commander','3025-01-01',NULL),
(114,57,'Driver','3025-01-01',NULL),
(115,57,'Gunner','3025-01-01',NULL),
(116,58,'Commander','3025-01-01',NULL),
(117,58,'Driver','3025-01-01',NULL),
(118,58,'Gunner','3025-01-01',NULL),
(119,59,'Commander','3025-01-01',NULL),
(120,59,'Driver','3025-01-01',NULL),
(121,59,'Gunner','3025-01-01',NULL),
(122,60,'Commander','3025-01-01',NULL),
(123,60,'Driver','3025-01-01',NULL),
(124,60,'Gunner','3025-01-01',NULL);

-- Dog Company – 3rd Lance
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(125,61,'Commander','3025-01-01',NULL),
(126,61,'Driver','3025-01-01',NULL),
(127,61,'Gunner','3025-01-01',NULL),
(128,62,'Commander','3025-01-01',NULL),
(129,62,'Driver','3025-01-01',NULL),
(130,62,'Gunner','3025-01-01',NULL),
(131,63,'Commander','3025-01-01',NULL),
(132,63,'Driver','3025-01-01',NULL),
(133,63,'Gunner','3025-01-01',NULL),
(134,64,'Commander','3025-01-01',NULL),
(135,64,'Driver','3025-01-01',NULL),
(136,64,'Gunner','3025-01-01',NULL);

-- Dog Company – 4th Lance
INSERT INTO personnel_equipment (personnel_id, equipment_id, role, date_assigned, date_released) VALUES
(137,65,'Commander','3025-01-01',NULL),
(138,65,'Driver','3025-01-01',NULL),
(139,65,'Gunner','3025-01-01',NULL),
(140,66,'Commander','3025-01-01',NULL),
(141,66,'Driver','3025-01-01',NULL),
(142,66,'Gunner','3025-01-01',NULL),
(143,67,'Commander','3025-01-01',NULL),
(144,67,'Driver','3025-01-01',NULL),
(145,67,'Gunner','3025-01-01',NULL),
(146,68,'Commander','3025-01-01',NULL),
(147,68,'Driver','3025-01-01',NULL),
(148,68,'Gunner','3025-01-01',NULL);

-- ================================
-- Part 7: Set Unit Commanders
-- ================================

-- Regiment
UPDATE units SET commander_id = 1 WHERE unit_id = 1;   -- Marshal Stephen Davion

-- 1st Battalion
UPDATE units SET commander_id = 2 WHERE unit_id = 2;   -- Captain Edward Fairfax

-- Companies
UPDATE units SET commander_id = 3 WHERE unit_id = 3;   -- Able Company
UPDATE units SET commander_id = 4 WHERE unit_id = 4;   -- Baker Company
UPDATE units SET commander_id = 5 WHERE unit_id = 5;   -- Charlie Company
UPDATE units SET commander_id = 6 WHERE unit_id = 6;   -- Dog Company
UPDATE units SET commander_id = 11 WHERE unit_id = 29; -- Easy Company

-- Battalion Command Lance
UPDATE units SET commander_id = 7 WHERE unit_id = 7;

-- Easy Company Platoons
UPDATE units SET commander_id = 11 WHERE unit_id = 8;   -- Mechanized Infantry (1st Platoon)
UPDATE units SET commander_id = 48 WHERE unit_id = 30;  -- 2nd Platoon
UPDATE units SET commander_id = 52 WHERE unit_id = 31;  -- 3rd Platoon

-- Able Company Lances
UPDATE units SET commander_id = 48 WHERE unit_id = 13;  -- 1st Lance
UPDATE units SET commander_id = 52 WHERE unit_id = 14;  -- 2nd Lance
UPDATE units SET commander_id = 56 WHERE unit_id = 15;  -- 3rd Lance

-- Baker Company Lances
UPDATE units SET commander_id = 60 WHERE unit_id = 16;  -- 1st Lance
UPDATE units SET commander_id = 64 WHERE unit_id = 17;  -- 2nd Lance
UPDATE units SET commander_id = 68 WHERE unit_id = 18;  -- 3rd Lance

-- Charlie Company Lances
UPDATE units SET commander_id = 72 WHERE unit_id = 19;  -- 1st Lance
UPDATE units SET commander_id = 76 WHERE unit_id = 20;  -- 2nd Lance
UPDATE units SET commander_id = 80 WHERE unit_id = 21;  -- 3rd Lance

-- Dog Company Lances
UPDATE units SET commander_id = 84 WHERE unit_id = 22;  -- 1st Lance
UPDATE units SET commander_id = 97 WHERE unit_id = 23;  -- 2nd Lance
UPDATE units SET commander_id = 109 WHERE unit_id = 24; -- 3rd Lance
UPDATE units SET commander_id = 121 WHERE unit_id = 25; -- 4th Lance

