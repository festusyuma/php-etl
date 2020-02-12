<?php
class RegularMigration{

    private $migrations;
    private $table;

    private $queries;
    private $columns;
    private $values;
    private $saved_data;

    public function __construct($table, $migrations){
        $this->migrations = $migrations;
        $this->table = $table;

        $this->queries = $this->columns = $this->values = $this->saved_data = array();
    }

    public function migrate() {

        $this->buildSchema();
        var_dump($this->queries, $this->columns, $this->values);

        return true;
    }

    private function buildSchema() {

        foreach ($this->migrations['migrations'] as $schema) {
            $old_column = $schema[0];
            $new_field = explode('.', $schema[1]);
            $new_table = $new_field[0];
            $new_column = $new_field[1];

            if (in_array($new_table, array_keys($this->queries))) {
                array_push($this->columns[$new_table], $new_column);
                array_push($this->values[$new_table], $old_column);
            } else {
                $this->queries[$new_table] = "INSERT INTO $new_table {COLUMNS} VALUES {VALUES}";
                $this->columns[$new_table] = [$new_column];
                $this->values[$new_table] = [$old_column];
            }
        }
    }
}