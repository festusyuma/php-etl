<?php
require_once ('Db.php');

class Migration {

    private $old_db;
    private $new_db;
    private $migrations;

    public function __construct(){
        $this->old_db = Db::getInstance('sams_db_old');
        $this->new_db = Db::getInstance('sams_db_new');
    }

    public function setMigration($migrations) {
        $this->migrations = $migrations;
    }

    public function runMigration() {
        foreach ($this->migrations as $table=>$migration) {
            $old_table = $table;

            foreach ($migration as $schema) {
                $old_col = $schema[0];
                $new_col = explode('.', $schema[1]);

                $this->regularMigration($old_table, $old_col, $new_col);
            }
        }
    }

    private function regularMigration($old_table, $old_column, $new_field) {

        $new_table = $new_field[0];
        $new_column = $new_field[1];
        $data = $this->old_db->query("SELECT id, $old_column FROM $old_table");

        if ($data) {
            $old_fields = $data->fetch_all(1);

            foreach ($old_fields as $field) {
                $id = $field[0];
                $value = $field[1];
                $found_field = $this->new_db->query("SELECT * FROM $new_table");

                if ($found_field and $found_field->num_rows > 0) {
                    $q = "UPDATE $new_table SET $new_column = $value";
                }else $q = "INSERT INTO $new_table  ";
            }
        }
    }
}

$migrations = [
    'result' => [
        ['grade', 'test_1.col_one'],
        ['class_code', 'test_2.col_one'],
        ['remarks', 'test_1.col_two'],
    ]
];

$migration = new Migration();
$migration->setMigration($migrations);
$migration->runMigration();