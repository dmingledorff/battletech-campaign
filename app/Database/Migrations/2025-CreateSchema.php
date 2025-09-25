<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSchema extends Migration
{
    public function up()
    {
        // =========================
        // Planets
        // =========================
        $this->forge->addField([
            'planet_id'   => ['type' => 'INT', 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'allegiance'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ]);
        $this->forge->addKey('planet_id', true);
        $this->forge->createTable('planets');

        // =========================
        // Campaigns
        // =========================
        $this->forge->addField([
            'campaign_id' => ['type' => 'INT', 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'start_date'  => ['type' => 'DATE', 'null' => true],
            'end_date'    => ['type' => 'DATE', 'null' => true],
            'theater'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ]);
        $this->forge->addKey('campaign_id', true);
        $this->forge->createTable('campaigns');

        // =========================
        // Personnel
        // =========================
        $this->forge->addField([
            'personnel_id' => ['type' => 'INT', 'auto_increment' => true],
            'first_name'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'last_name'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'grade'        => ['type' => 'VARCHAR', 'constraint' => 30],
            'gender'       => ['type' => 'ENUM("Male","Female")', 'default' => 'Male'],
            'callsign'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'mos'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'experience'   => ['type' => 'INT', 'default' => 0],
            'missions'     => ['type' => 'INT', 'default' => 0],
            'status'       => ['type' => 'ENUM("Active","KIA","Retired")', 'default' => 'Active'],
        ]);
        $this->forge->addKey('personnel_id', true);
        $this->forge->createTable('personnel');

        // =========================
        // Locations
        // =========================
        $this->forge->addField([
            'location_id' => ['type' => 'INT', 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'planet_id'   => ['type' => 'INT'],
        ]);
        $this->forge->addKey('location_id', true);
        $this->forge->addForeignKey('planet_id', 'planets', 'planet_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('locations');

        // =========================
        // Units
        // =========================
        $this->forge->addField([
            'unit_id'        => ['type' => 'INT', 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'nickname'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'unit_type'      => ['type' => 'ENUM("Regiment","Battalion","Company","Lance","InfantryPlatoon","Squad")'],
            'current_supply' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'allegiance'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'parent_unit_id' => ['type' => 'INT', 'null' => true],
            'commander_id'   => ['type' => 'INT', 'null' => true],
            'location_id'    => ['type' => 'INT', 'null' => true],
        ]);
        $this->forge->addKey('unit_id', true);
        $this->forge->addKey('parent_unit_id');   // 🔹 Index
        $this->forge->addKey('commander_id');     // 🔹 Index
        $this->forge->addKey('location_id');      // 🔹 Index
        $this->forge->addForeignKey('parent_unit_id', 'units', 'unit_id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('commander_id', 'personnel', 'personnel_id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('location_id', 'locations', 'location_id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('units');

        // =========================
        // Chassis
        // =========================
        $this->forge->addField([
            'chassis_id'        => ['type' => 'INT', 'auto_increment' => true],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'type'              => ['type' => 'ENUM("BattleMech","Vehicle","InfantryWeapon")'],
            'weight_class'      => ['type' => 'ENUM("Light","Medium","Heavy","Assault","Infantry")'],
            'hard_attack'       => ['type' => 'INT'],
            'soft_attack'       => ['type' => 'INT'],
            'armor_value'       => ['type' => 'INT'],
            'ammo_reliance'     => ['type' => 'DECIMAL', 'constraint' => '4,2'],
            'supply_consumption'=> ['type' => 'DECIMAL', 'constraint' => '6,2'],
            'tonnage'           => ['type' => 'INT'],
            'speed'             => ['type' => 'DECIMAL', 'constraint' => '6,2'],
        ]);
        $this->forge->addKey('chassis_id', true);
        $this->forge->createTable('chassis');

        // =========================
        // Equipment
        // =========================
        $this->forge->addField([
            'equipment_id'    => ['type' => 'INT', 'auto_increment' => true],
            'chassis_id'      => ['type' => 'INT'],
            'serial_number'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'assigned_unit_id'=> ['type' => 'INT', 'null' => true],
            'damage_percentage'=>['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.0],
            'equipment_status'=> ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'Active'],
        ]);
        $this->forge->addKey('equipment_id', true);
        $this->forge->addKey('assigned_unit_id');  // 🔹 Index
        $this->forge->addForeignKey('chassis_id', 'chassis', 'chassis_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('assigned_unit_id', 'units', 'unit_id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('equipment');

        // =========================
        // Personnel Assignments
        // =========================
        $this->forge->addField([
            'assignment_id' => ['type' => 'INT', 'auto_increment' => true],
            'personnel_id'  => ['type' => 'INT'],
            'unit_id'       => ['type' => 'INT'],
            'date_assigned' => ['type' => 'DATE'],
            'date_released' => ['type' => 'DATE', 'null' => true],
        ]);
        $this->forge->addKey('assignment_id', true);
        $this->forge->addKey('personnel_id');      // 🔹 Index
        $this->forge->addKey('unit_id');           // 🔹 Index
        $this->forge->addForeignKey('personnel_id', 'personnel', 'personnel_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'units', 'unit_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('personnel_assignments');

        // =========================
        // Personnel Equipment
        // =========================
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true],
            'personnel_id' => ['type' => 'INT'],
            'equipment_id' => ['type' => 'INT'],
            'role'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'date_assigned'=> ['type' => 'DATE'],
            'date_released'=> ['type' => 'DATE', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('personnel_id');      // 🔹 Index
        $this->forge->addKey('equipment_id');      // 🔹 Index
        $this->forge->addForeignKey('personnel_id', 'personnel', 'personnel_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('equipment_id', 'equipment', 'equipment_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('personnel_equipment');
    }

    public function down()
    {
        $this->forge->dropTable('personnel_equipment');
        $this->forge->dropTable('personnel_assignments');
        $this->forge->dropTable('equipment');
        $this->forge->dropTable('chassis');
        $this->forge->dropTable('units');
        $this->forge->dropTable('locations');
        $this->forge->dropTable('personnel');
        $this->forge->dropTable('campaigns');
        $this->forge->dropTable('planets');
    }
}
