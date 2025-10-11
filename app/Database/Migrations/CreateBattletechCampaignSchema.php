<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBattletechCampaignSchema extends Migration
{
    public function up()
    {
        // game_state
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'property_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'property_value' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('game_state');

        // Create factions
        $this->forge->addField([
            'faction_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'emblem_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => 7, // e.g. "#AABBCC"
                'null'       => true,
            ],
        ]);
        $this->forge->addKey('faction_id', true);
        $this->forge->addKey('name');
        $this->forge->createTable('factions', true);

        // star_systems
        $this->forge->addField([
            'system_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
        ]);
        $this->forge->addKey('system_id', true);
        $this->forge->createTable('star_systems');

        // planets
        $this->forge->addField([
            'planet_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 100],
            'system_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'position'         => ['type' => 'INT'],
            'time_to_jump_point'=> ['type' => 'INT'],
            'allegiance'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'map_background'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'display_order'    => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('planet_id', true);
        $this->forge->addForeignKey('system_id', 'star_systems', 'system_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('planets');

        // locations
        $this->forge->addField([
            'location_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'name'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'type'    => ['type' => "ENUM('City','Spaceport','Base','Industrial Zone')"],
            'terrain' => ['type' => "ENUM('Urban','Dense Urban','Rural','Plains','Mountains','Hills','Woods','Marsh','Desert')"],
            'planet_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'coord_x' => ['type' => 'FLOAT', 'default' => 0],
            'coord_y' => ['type' => 'FLOAT', 'default' => 0],
            'display_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('location_id', true);
        $this->forge->addForeignKey('planet_id', 'planets', 'planet_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('locations');

        // campaigns
        $this->forge->addField([
            'campaign_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'start_date' => ['type' => 'DATE', 'null' => true],
            'end_date'   => ['type' => 'DATE', 'null' => true],
            'theater'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ]);
        $this->forge->addKey('campaign_id', true);
        $this->forge->createTable('campaigns');

        // ranks
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'faction' => ['type' => 'VARCHAR', 'constraint' => 50],
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'abbreviation' => ['type' => 'VARCHAR', 'constraint' => 10],
            'grade' => ['type' => 'INT'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('ranks');

        // personnel
        $this->forge->addField([
            'personnel_id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true
            ],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'last_name'  => ['type' => 'VARCHAR', 'constraint' => 50],
            'faction_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null'=> true],
            'rank_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'     => ['type' => "ENUM('Active','KIA','Retired','Injured','MIA')", 'default' => 'Active'],
            'gender'     => ['type' => "ENUM('Male','Female','Other')", 'null' => true],
            'callsign'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'mos'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'experience' => ['type' => "ENUM('Green','Regular','Veteran','Elite')", 'default' => 'Green'],
            'date_of_birth' => ['type' => 'DATE', 'null' => true],
            'morale'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 100.00],
            'missions'   => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('personnel_id', true);
        $this->forge->addForeignKey('rank_id', 'ranks', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('faction_id', 'factions', 'faction_id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('personnel');

        // toe_templates
        $this->forge->addField([
            'template_id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true
            ],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'TEXT', 'null' => true],
            'unit_type'   => ['type' => "ENUM('Regiment','Battalion','Company','Lance','Platoon','Squad')"],
            'role'        => ['type' => "ENUM('Command','Battle','Striker','Pursuit','Fire','Security','Support','Assault','Recon','Urban Combat','Infantry')", 'null' => true],
            'mobility'    => ['type' => "ENUM('Foot','Mechanized','Motorized','Airborne','Jump','Hover')", 'null' => true],
            'faction'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'era'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ]);
        $this->forge->addKey('template_id', true);
        $this->forge->createTable('toe_templates');

        // units
        $this->forge->addField([
            'unit_id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true
            ],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'faction_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null'=> true],
            'nickname'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'current_supply'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'unit_type'       => ['type' => "ENUM('Regiment','Battalion','Company','Lance','Platoon','Squad')"],
            'role'            => ['type' => "ENUM('Command','Battle','Striker','Pursuit','Fire','Security','Support','Assault','Recon','Urban Combat')", 'null' => true],
            'allegiance'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'parent_unit_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'commander_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'location_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'template_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('unit_id', true);
        $this->forge->addForeignKey('parent_unit_id', 'units', 'unit_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('commander_id', 'personnel', 'personnel_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('location_id', 'locations', 'location_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('template_id', 'toe_templates', 'template_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('faction_id', 'factions', 'faction_id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('units');

        // chassis
        $this->forge->addField([
            'chassis_id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true
            ],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'variant'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'type'         => ['type' => "ENUM('BattleMech','Vehicle','APC')"],
            'weight_class' => ['type' => "ENUM('Light','Medium','Heavy','Assault','Infantry')"],
            'battlefield_role' => ['type' => "ENUM('Ambusher','Brawler','Missile Boat','Juggernaut','Scout','Sniper','Skirmisher','Striker','Command')", 'default' => 'Brawler'],
            'hard_attack' => ['type' => 'INT'],
            'soft_attack' => ['type' => 'INT'],
            'armor_value' => ['type' => 'INT'],
            'ammo_reliance' => ['type' => 'DECIMAL', 'constraint' => '4,2'],
            'supply_consumption' => ['type' => 'DECIMAL', 'constraint' => '6,2'],
            'tonnage' => ['type' => 'INT'],
            'speed'   => ['type' => 'DECIMAL', 'constraint' => '5,2'],
        ]);
        $this->forge->addKey('chassis_id', true);
        $this->forge->createTable('chassis');

        // equipment
        $this->forge->addField([
            'equipment_id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true
            ],
            'chassis_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'faction_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null'=> true],
            'location_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'serial_number'    => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'assigned_unit_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'damage_percentage'=> ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.0],
            'equipment_status' => ['type' => "ENUM('Active','Destroyed','Salvaged','Repair','Mothballed')", 'default' => 'Active'],
        ]);
        $this->forge->addKey('equipment_id', true);
        $this->forge->addForeignKey('chassis_id', 'chassis', 'chassis_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('assigned_unit_id', 'units', 'unit_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('faction_id', 'factions', 'faction_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('location_id', 'locations', 'location_id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('equipment');

        // personnel_assignments
        $this->forge->addField([
            'assignment_id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true
            ],
            'personnel_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'unit_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'date_assigned' => ['type' => 'DATE', 'null' => true],
            'date_released' => ['type' => 'DATE', 'null' => true],
        ]);
        $this->forge->addKey('assignment_id', true);
        $this->forge->addForeignKey('personnel_id', 'personnel', 'personnel_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'units', 'unit_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('personnel_assignments');

        // personnel_equipment
        $this->forge->addField([
            'id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true
            ],
            'personnel_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'equipment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'role'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'date_assigned' => ['type' => 'DATE', 'null' => true],
            'date_released' => ['type' => 'DATE', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('personnel_id', 'personnel', 'personnel_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('equipment_id', 'equipment', 'equipment_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('personnel_equipment');

        // toe_slots
        $this->forge->addField([
            'slot_id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true
            ],
            'template_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'slot_type'   => ['type' => "ENUM('Personnel','Equipment','SubUnit')"],
            'mos'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'min_rank_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'max_rank_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'equipment_type' => ['type' => "ENUM('BattleMech','Vehicle','APC','Aerospace','Infantry')", 'null' => true],
            'weight_class'   => ['type' => "ENUM('Light','Medium','Heavy','Assault')", 'null' => true],
            'crew_size'        => ['type' => 'INT', 'default' => 1],
            'subunit_template_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'is_core' => ['type' => 'BOOLEAN', 'default' => true],
        ]);
        $this->forge->addKey('slot_id', true);
        $this->forge->addForeignKey('template_id', 'toe_templates', 'template_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('min_rank_id', 'ranks', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('max_rank_id', 'ranks', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('toe_slots');

        // toe_subunits
        $this->forge->addField([
            'subunit_id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true
            ],
            'parent_template_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'child_template_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'quantity' => ['type' => 'INT', 'default' => 1],
            'is_core'   => ['type' => 'BOOLEAN', 'default' => true],
            'is_command'=> ['type' => 'BOOLEAN', 'default' => false],
        ]);
        $this->forge->addKey('subunit_id', true);
        $this->forge->addForeignKey('parent_template_id', 'toe_templates', 'template_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('child_template_id', 'toe_templates', 'template_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('toe_subunits');

        // toe_slot_roles
        $this->forge->addField([
            'slot_role_id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true
            ],
            'slot_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'battlefield_role' => ['type' => "ENUM('Ambusher','Brawler','Missile Boat','Juggernaut','Scout','Sniper','Skirmisher','Striker')"],
        ]);
        $this->forge->addKey('slot_role_id', true);
        $this->forge->addForeignKey('slot_id', 'toe_slots', 'slot_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('toe_slot_roles');

        // toe_slot_crews
        $this->forge->addField([
            'crew_id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true
            ],
            'equipment_slot_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'personnel_slot_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'crew_role' => ['type' => "ENUM('Commander','Driver','Gunner','Loader','Tech','Pilot','Dismount')"],
        ]);
        $this->forge->addKey('crew_id', true);
        $this->forge->addForeignKey('equipment_slot_id', 'toe_slots', 'slot_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('personnel_slot_id', 'toe_slots', 'slot_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('toe_slot_crews');

        // name_pool
        $this->forge->addField([
            'id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true,
            ],
            'faction' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name_type' => ['type' => "ENUM('first_male','first_female','last')"],
            'value' => ['type' => 'VARCHAR', 'constraint' => 50],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('name_pool');

        // callsign_pool
        $this->forge->addField([
            'id' => [
              'type'           => 'INT',
              'constraint'     => 11,
              'unsigned'       => true,
              'auto_increment' => true,
            ],
            'value' => ['type' => 'VARCHAR', 'constraint' => 50],
            'used'  => ['type' => 'BOOLEAN', 'default' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('callsign_pool');

        // lance_templates, toe_templates, etc. are done above

        // Finally, game_state initial insert
        $this->db->table('game_state')->insert([
            'property_name'  => 'current_date',
            'property_value' => '3025-01-01'
        ]);

        // Alter Shield user table
        $this->forge->addColumn('users', [
            'faction_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'username', // adjust if needed
            ]
        ]);

        // Add foreign key constraint via SQL
        $this->db->query('ALTER TABLE `users`
            ADD CONSTRAINT `fk_users_faction_id`
            FOREIGN KEY (`faction_id`) REFERENCES `factions`(`faction_id`)
            ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        // Drop in reverse order of creation to honor FK constraints
        $this->forge->dropTable('game_state');
        $this->forge->dropTable('callsign_pool');
        $this->forge->dropTable('name_pool');
        $this->forge->dropTable('toe_slot_crews');
        $this->forge->dropTable('toe_slot_roles');
        $this->forge->dropTable('toe_subunits');
        $this->forge->dropTable('toe_slots');
        $this->forge->dropTable('personnel_equipment');
        $this->forge->dropTable('personnel_assignments');
        $this->forge->dropTable('equipment');
        $this->forge->dropTable('chassis');
        $this->forge->dropTable('units');
        $this->forge->dropTable('toe_templates');
        $this->forge->dropTable('personnel');
        $this->forge->dropTable('ranks');
        $this->forge->dropTable('campaigns');
        $this->forge->dropTable('locations');
        $this->forge->dropTable('planets');
        $this->forge->dropTable('star_systems');
    }
}