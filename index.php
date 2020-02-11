<?php
require_once ('Db.php');
require_once ('transformers.php');

class Migration {

    private $old_db;
    private $new_db;
    private $migrations;

    private $queries;
    private $columns;
    private $values;

    public function __construct(){
        $this->old_db = Db::getInstance('sams_db_old');
        $this->new_db = Db::getInstance('test_etl');
        $this->queries = $this->columns = $this->values = array();
    }

    public function setMigration($migrations) {
        $this->migrations = $migrations;
    }

    public function runMigration() {

        foreach ($this->migrations as $table=>$migration) {

            $built_schema = $this->buildSchema($migration);

            $this->queries[$table] = $built_schema['queries'];
            $this->columns[$table] = $built_schema['columns'];
            $this->values[$table] = $built_schema['values'];
        }

        $this->migrate();
    }

    private function buildSchema($migration) {
        $queries = [];
        $columns = [];
        $values = [];

        foreach ($migration as $schema) {
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

    private function migrate() {
        $tables = array_keys($this->migrations);

        foreach ($tables as $table) {
            $data = $this->old_db->query("SELECT * FROM $table")->fetch_all(1);
            $queries = $this->queries[$table];
            $columns = $this->columns[$table];
            $values = $this->values[$table];

            foreach ($data as $row) {
                foreach ($queries as $t => $query) {
                    $this->runQuery($row, $query, $columns[$t], $values[$t]);
                }
            }
        }
    }

    private function runQuery($data, $query, $columns, $values) {

        $columns = $this->getColumns($columns);
        $values = $this->getValues($data, $values);

        $query = str_replace('{COLUMNS}', $columns, $query);
        $query = str_replace('{VALUES}', $values, $query);

        $this->new_db->query($query);
    }

    private function getColumns($columns) {
        $columns_string = implode(', ', $columns);
        return "($columns_string)";
    }

    private function getValues($data, $values) {
        $val = [];

        foreach ($values as $value) {
            $val[] = $data[$value];
        }

        $val = (array_map([$this, 'stringify'], $val));
        $val_string = implode(', ', $val);

        return "($val_string)";
    }

    private function stringify($val) {
        return "'".$val."'";
    }
}

$migrations = [
    'result' => [
        ['id', 'test_1.id'],
        ['id', 'test_2.id'],
        ['grade', 'test_1.col_one'],
        ['classCode', 'test_2.col_one'],
        ['remarks', 'test_1.col_two'],
    ]
];

$migration = new Migration();
$migration->setMigration($migrations);
$migration->runMigration();