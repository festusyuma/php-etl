<?php
class RegularMigration{

    private $migrations;
    private $table;

    public function __construct($table, $migrations){
        $this->migrations = $migrations;
        $this->table = $table;
    }

    public function buildSchema() {
        $queries = [];
        $columns = [];
        $values = [];

        foreach ($this->migrations['migrations'] as $schema) {
            $old_column = $schema[0];
            $new_field = explode('.', $schema[1]);
            $new_table = $new_field[0];
            $new_column = $new_field[1];

            if (in_array($new_table, array_keys($queries))) {
                array_push($columns[$new_table], $new_column);
                array_push($values[$new_table], $old_column);
            } else {
                $queries[$new_table] = "INSERT INTO $new_table {COLUMNS} VALUES {VALUES}";
                $columns[$new_table] = [$new_column];
                $values[$new_table] = [$old_column];
            }
        }

        return [
            'queries' => $queries,
            'columns' => $columns,
            'values' => $values,
        ];
    }
}