-- ==========================
-- Common BattleMechs
-- ==========================

INSERT INTO chassis (name, variant, type, weight_class, tonnage, speed, hard_attack, soft_attack, armor_value, ammo_reliance, supply_consumption) VALUES
('Locust','LCT-1V','BattleMech','Light',20,97.2,40,10,60,0.20,7.0),
('Javelin','JVN-10N','BattleMech','Light',30,86.4,46,13,70,0.40,7.8),
('Panther','PNT-9R','BattleMech','Light',35,64.8,48,14,80,0.30,8.0),
('Spider','SDR-5V','BattleMech','Light',30,129.6,45,12,65,0.20,7.5),
('Jenner','JR7-D','BattleMech','Light',35,118.8,50,15,70,0.30,8.0),
('Assassin','ASN-21','BattleMech','Medium',40,97.2,58,18,90,0.35,9.5),
('Blackjack','BJ-1','BattleMech','Medium',45,64.8,60,20,100,0.55,10.0),
('Centurion','CN9-A','BattleMech','Medium',50,64.8,70,30,120,0.75,12.0),
('Centurion','CN9-AL','BattleMech','Medium',50,64.8,68,28,115,0.70,11.5),
('Enforcer','ENF-4R','BattleMech','Medium',50,64.8,65,25,115,0.50,11.0),
('Griffin','GRF-1N','BattleMech','Medium',55,86.4,68,22,110,0.40,11.0),
('Shadow Hawk','SHD-2H','BattleMech','Medium',55,86.4,64,24,105,0.45,10.5),
('Wolverine','WVR-6R','BattleMech','Medium',55,86.4,66,24,110,0.45,10.8),
('Trebuchet','TBT-5N','BattleMech','Medium',50,64.8,62,22,105,0.70,11.5),
('Rifleman','RFL-3N','BattleMech','Heavy',60,64.8,72,24,120,0.70,12.5),
('Catapult','CPLT-C1','BattleMech','Heavy',65,64.8,76,26,130,0.80,13.0),
('JagerMech','JM6-A','BattleMech','Heavy',65,64.8,70,20,125,0.80,12.8),
('Quickdraw','QKD-4G','BattleMech','Heavy',60,86.4,74,22,135,0.60,12.2),
('Archer','ARC-2R','BattleMech','Heavy',70,64.8,78,28,140,0.85,13.5),
('Grasshopper','GHR-5H','BattleMech','Heavy',70,64.8,76,24,150,0.40,12.7),
('Marauder','MAD-3R','BattleMech','Heavy',75,64.8,82,26,155,0.60,13.6),
('Orion','ON1-K','BattleMech','Heavy',75,64.8,80,25,160,0.55,13.2),
('Thunderbolt','TDR-5S','BattleMech','Heavy',65,64.8,78,22,150,0.60,12.9),
('Warhammer','WHM-6R','BattleMech','Heavy',70,64.8,84,28,160,0.70,13.8);

-- ==========================
-- Common Vehicles
-- ==========================

INSERT INTO chassis (name, variant, type, weight_class, tonnage, speed, hard_attack, soft_attack, armor_value, ammo_reliance, supply_consumption) VALUES
('Manticore','Standard','Vehicle','Heavy',60,54.0,60,20,150,0.65,10.0),
('Bulldog','Medium Tank','Vehicle','Medium',60,43.2,50,15,120,0.60,8.0),
('Von Luckner','Heavy Tank','Vehicle','Heavy',75,43.2,70,25,160,0.70,11.0),
('Scorpion','Light Tank','Vehicle','Light',25,64.8,35,12,80,0.50,6.8),
('Vedette','Standard','Vehicle','Medium',50,54.0,48,15,115,0.55,7.8),
('Pegasus','Scout Hover','Vehicle','Light',35,97.2,40,14,75,0.50,6.5),
('APC','Wheeled','APC','Light',10,97.2,10,8,60,0.40,4.0),
('APC','Hover','APC','Light',10,97.2,10,8,60,0.40,4.0),
('APC','Tracked','APC','Light',10,162,10,8,60,0.40,4.0);
