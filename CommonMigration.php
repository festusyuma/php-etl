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

    protected function migrateAfterRelationship($parent, $child, $relationship) {
        if ($relationship['type'] == 'internal') {
            $parent_field = explode('.', $relationship['field'][0], 2);
            $child_field = $relationship['field'][1];

            $query = "UPDATE {$parent_field[0]} SET {$parent_field[1]} = {$child[$child_field]} WHERE id = {$parent['id']}";
            $this->new_db->query($query);
        }
    }

    private function getVal($data, $value) {
        if (is_array($value)) {
            $temp_val = $data[$value[0]];
            $temp_val = $value[1]($temp_val);
        } else $temp_val = $data[$value];

        return $temp_val;
    }

    protected function getData($data, $values) {
        $temp_data = [];

        foreach ($values as $value) {

            $temp_val = $this->getVal($data, $value);
            $temp_data[] = $temp_val;
        }

        return $temp_data;
    }

    protected function getColumns($columns) {
        $allColumns = $columns;

        if (isset($this->migrations['defaults'])) {
            foreach ($this->migrations['defaults'] as $key => $defaultCol) $allColumns[] = $key;
        }

        $columns_string = implode(', ', $allColumns);
        return "($columns_string)";
    }

    protected function getValues($data, $values) {
        $val = $this->getData($data, $values);
        $val = (array_map([$this, 'stringify'], $val));

        if (isset($this->migrations['defaults'])) {
            foreach ($this->migrations['defaults'] as $defaultVal) $val[] = $defaultVal;
        }

        $val_string = implode(', ', $val);
        return "($val_string)";
    }

    protected function stringify($val) {
        return ($val == '') ? 'null' : "'".$val."'";
    }
}