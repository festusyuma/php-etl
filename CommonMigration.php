<?php


class CommonMigration{

    protected $migrations;
    protected $table;

    protected $old_db;
    protected $new_db;

    public function __construct($table, $migrations){
        $this->migrations = $migrations;
        $this->table = $table;
        $this->old_db = Db::getInstance('sams_db_old');
        $this->new_db = Db::getInstance('sams_db_new');
    }

    protected function migrateAfter($table, $migration, $data) {

        if ($migration['type'] == 'GENERALIZE') {
            $migration_obj = new GeneralizeMigration($table, $migration);
            return $migration_obj->migrateAsBatch($data);
        }else if ($migration['type'] == 'REGULAR') {
            $migration_obj = new RegularMigration($table, $migration);
            return $migration_obj->migrateAsBatch($data);
        }

        return null;
    }

    protected function getData($data, $values) {
        $temp_data = [];

        foreach ($values as $value) {

            if (is_array($value)) {
                $temp_val = $data[$value[0]];
                $temp_val = $value[1]($temp_val);
            } else $temp_val = $data[$value];

            $temp_data[] = $temp_val;
        }

        return $temp_data;
    }

    protected function getColumns($columns) {
        $columns_string = implode(', ', $columns);
        return "($columns_string)";
    }

    protected function getValues($data, $values) {
        $val = $this->getData($data, $values);
        $val = (array_map([$this, 'stringify'], $val));
        $val_string = implode(', ', $val);

        return "($val_string)";
    }

    protected function stringify($val) {
        return ($val == '') ? 'null' : "'".$val."'";
    }
}