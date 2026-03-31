-- ============================================================
-- Combat & Time System Game State Properties
-- ============================================================

INSERT INTO game_state (property_name, property_value) VALUES

-- Time system
('current_date', 				  '3025-01-01'),
('current_hour',                  '0'),
('hours_per_tick',                '3'),

-- Armor/structure scaling multipliers
('combat_armor_multiplier',       '3'),
('combat_structure_multiplier',   '3'),

-- Damage bracket transitions (skirmish round thresholds)
('skirmish_l_to_m_round',         '3'),   -- use L damage rounds 1-2, M from round 3+
('melee_m_to_s_round',            '2'),   -- use M damage round 1, S from round 2+
('skirmish_max_rounds',           '4'),   -- cap skirmish phase regardless of speed

-- Speed thresholds
('skirmish_close_threshold',      '2'),   -- speed diff <= this triggers melee transition
('pursuit_speed_divisor',         '4'),   -- speed diff / this = pursuit rounds

-- To-hit base
('base_to_hit',                   '7'),

-- Morale loss multipliers by experience
('morale_loss_green',             '2.0'),
('morale_loss_regular',           '1.5'),
('morale_loss_veteran',           '1.0'),
('morale_loss_elite',             '0.5'),

-- Retreat thresholds (morale % below which retreat check triggers)
('retreat_threshold_green',       '40'),
('retreat_threshold_regular',     '30'),
('retreat_threshold_veteran',     '20'),
('retreat_threshold_elite',       '15'),

-- Retreat chance multipliers (threshold - morale) * multiplier = % chance
('retreat_chance_green',          '3.0'),
('retreat_chance_regular',        '2.0'),
('retreat_chance_veteran',        '1.5'),
('retreat_chance_elite',          '1.0'),

-- Friendly destruction morale hit (flat penalty to lance)
('morale_loss_friendly_destroyed','10'),

-- Ejection chances at crippled threshold (%)
('eject_crippled_green',          '60'),
('eject_crippled_regular',        '35'),
('eject_crippled_veteran',        '20'),
('eject_crippled_elite',          '10'),

-- Pilot survival chances when mech destroyed (%)
('eject_destroyed_green',         '40'),
('eject_destroyed_regular',       '55'),
('eject_destroyed_veteran',       '70'),
('eject_destroyed_elite',         '80'),

-- Salvage
('salvage_base_chance',           '60'),

-- Infantry casualty rates
('infantry_kia_chance',           '30'),   -- % of casualties that are KIA vs Injured
('infantry_ht_multiplier',        '1.5'),  -- HT special casualty multiplier
('infantry_bm_urban_multiplier',  '0.5'),  -- BM hard attack vs infantry in urban/fortified

-- Commander bonus thresholds
('commander_bonus_grade',         '8'),    -- grade >= this gives +1 to-hit all lances
('commander_morale_penalty',      '40'),   -- morale < this gives -1 to-hit all lances

-- Global combat morale loss scaling (tune overall combat intensity)
('combat_morale_loss_multiplier', '5.0');
