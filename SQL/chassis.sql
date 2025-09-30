-- Corrected / Expanded Chassis Insert List (AUTO_INCREMENT IDs)

INSERT INTO chassis 
(name, variant, type, weight_class, battlefield_role, hard_attack, soft_attack, armor_value, ammo_reliance, supply_consumption, tonnage, speed) VALUES

-- Light Mechs
('Jenner','JR7-D','BattleMech','Light','Striker',50,15,70,0.30,8.0,35,118.8),
('Spider','SDR-5V','BattleMech','Light','Scout',45,12,65,0.20,7.5,30,129.6),
('Locust','LCT-1V','BattleMech','Light','Scout',40,10,60,0.20,7.0,20,129.6),
('Javelin','JVN-10N','BattleMech','Light','Striker',46,13,70,0.40,7.8,30,97.2),
('Panther','PNT-9R','BattleMech','Light','Brawler',48,14,80,0.30,8.0,35,64.8),
('Stinger','STG-3R','BattleMech','Light','Scout',48,14,80,0.30,8.0,35,91.6),
('Valkyrie','VLK-QA','BattleMech','Light','Missile Boat',48,14,80,0.30,8.0,30,86.4),
('Raven','RVN-1X','BattleMech','Light','Scout',48,14,80,0.30,8.0,35,86.4),
('Firestarter','FS9-H','BattleMech','Light','Scout',48,14,80,0.30,8.0,35,97.2),

-- Medium 'Mechs
('Centurion','CN9-A','BattleMech','Medium','Brawler',70,30,120,0.75,12.0,50,64.8),
('Centurion','CN9-AL','BattleMech','Medium','Brawler',68,28,115,0.70,11.5,50,64.8),
('Enforcer','ENF-4R','BattleMech','Medium','Skirmisher',65,25,115,0.50,11.0,50,64.8),
('Griffin','GRF-1N','BattleMech','Medium','Sniper',68,22,110,0.40,11.0,55,86.4),
('Shadow Hawk','SHD-2H','BattleMech','Medium','Skirmisher',64,24,105,0.45,10.5,55,86.4),
('Wolverine','WVR-6R','BattleMech','Medium','Skirmisher',66,24,110,0.45,10.8,55,86.4),
('Phoenix Hawk','PXH-1','BattleMech','Medium','Skirmisher',52,18,80,0.35,8.5,45,97.2),
('Hunchback','HBK-4G','BattleMech','Medium','Juggernaut',80,20,140,0.60,13.0,50,64.8),
('Assassin','ASN-21','BattleMech','Medium','Scout',58,18,90,0.35,9.5,40,118.8),
('Blackjack','BJ-1','BattleMech','Medium','Sniper',60,20,100,0.55,10.0,45,64.8),
('Trebuchet','TBT-5N','BattleMech','Medium','Missile Boat',62,22,105,0.70,11.5,50,86.4),
('Clint','CLNT-2-3T','BattleMech','Medium','Striker',62,22,90,0.70,9,40,97.0),
('Clint','CLNT-2-4T','BattleMech','Medium','Sniper',62,22,90,0.70,10,40,97.0),
('Vulcan','VL-5T','BattleMech','Medium','Striker',60,22,100,0.70,10,40,97.0),

-- Heavy / Assault Mechs
('Rifleman','RFL-3N','BattleMech','Heavy','Sniper',72,24,120,0.70,12.5,60,64.8),
('Catapult','CPLT-C1','BattleMech','Heavy','Missile Boat',76,26,130,0.80,13.0,65,64.8),
('JagerMech','JM6-S','BattleMech','Heavy','Sniper',70,20,125,0.80,12.8,65,64.8),
('Quickdraw','QKD-4G','BattleMech','Heavy','Skirmisher',74,22,135,0.60,12.2,60,86.4),
('Archer','ARC-2R','BattleMech','Heavy','Missile Boat',78,28,140,0.85,13.5,70,64.8),
('Grasshopper','GHR-5H','BattleMech','Heavy','Skirmisher',76,24,150,0.40,12.7,70,64.8),
('Marauder','MAD-3R','BattleMech','Heavy','Sniper',82,26,155,0.60,13.6,75,64.8),
('Orion','ON1-K','BattleMech','Heavy','Brawler',80,25,160,0.55,13.2,75,64.8),
('Thunderbolt','TDR-5S','BattleMech','Heavy','Brawler',78,22,150,0.60,12.9,65,64.8),
('Warhammer','WHM-6R','BattleMech','Heavy','Brawler',84,28,145,0.70,13.8,70,64.8),
('Black Knight','BL-6-KNT','BattleMech','Heavy','Brawler',84,28,150,0.10,8.0,75,64.8),

-- Light Vehicles
('Scorpion Light Tank','SLT-STD','Vehicle','Light','Scout',35,12,80,0.50,6.8,25,64.8),
('Pegasus Scout Hover','PSH-STD','Vehicle','Light','Striker',40,14,75,0.50,6.5,35,129.6),
('Striker','Standard','Vehicle','Light','Striker',25,5,70,0.35,5.0,35,86.4),
('Hunter LST','HNT-STD','Vehicle','Light','Missile Boat',28,8,80,0.40,5.5,35,86.4),

-- Medium Vehicles
('Goblin','GBL-STD','Vehicle','Medium','Brawler',35,10,90,0.45,6.5,45,64.8),
('Vedette Medium Tank','VMT-STD','Vehicle','Medium','Brawler',48,15,115,0.55,7.8,50,86.0),

-- Heavy Vehicles
('Von Luckner Heavy Tank','VNL-K100','Vehicle','Heavy','Juggernaut',70,25,160,0.70,11.0,75,54.0),
('Patton','PTN-STD','Vehicle','Heavy','Brawler',40,12,140,0.55,9.0,65,64.8),
('Rommel','RML-STD','Vehicle','Heavy','Juggernaut',45,15,150,0.60,10.0,65,64.80),
('Brutus','BTS-STD','Vehicle','Heavy','Juggernaut',50,15,160,0.60,10.5,75,54.0),
('Manticore','MHT-STD','Vehicle','Heavy','Brawler',60,20,150,0.65,10.0,60,54.0),
('Bulldog Medium Tank','BMT-STD','Vehicle','Heavy','Brawler',50,15,120,0.60,8.0,60,65.0),

-- Assault Vehicles
('Schrek','SHK-STD','Vehicle','Assault','Sniper',55,20,170,0.65,11.5,80,54.0),
('Partisan','PRT-STD','Vehicle','Assault','Sniper',33,9,110,0.45,7.0,45,54.0),
('Ontos','ONT-STD','Vehicle','Assault','Juggernaut',30,7,75,0.40,5.5,95,97.2),
('Demolisher','DML-STD','Vehicle','Assault','Juggernaut',45,10,155,0.55,10.0,80,54.0),
('Behemoth','BHM-STD','Vehicle','Assault','Juggernaut',80,20,200,0.60,12.0,100,32.0),

-- APCs
('APC','Wheeled','APC','Light','Scout',10,8,60,0.40,4.0,10,97.2),
('APC','Tracked','APC','Light','Scout',10,8,65,0.40,4.2,10,97.2),
('APC','Hover','APC','Light','Scout',10,8,55,0.40,3.8,10,162.0);
