<?php


class GeneralizeMigration{

    private $migrations;
    private $table;

    private $schema;
    private $childSchema;
    private $saved_data;

    public function __construct($table, $migrations){
        $this->migrations = $migrations;
        $this->table = $table;

        $this->schema = $this->childSchema = $this->saved_data = array();
    }

    public function migrate() {

        $this->getGeneralizationValues();
        $this->buildSchema();
        $this->buildChildSchema();

        var_dump($this->saved_data);
        var_dump($this->schema);
        var_dump($this->childSchema);
    }

    public function buildSchema() {

        foreach ($this->migrations['migrations'] as $migration) {
            $schema = $this->getMigrationSchema($migration);

            if (in_array($schema['table'], array_keys($this->schema))) {
                $this->schema[$schema['table']]['columns'][] = $schema['column'];
                $this->schema[$schema['table']]['values'][] = $schema['value'];
            }else {
                $this->schema[$schema['table']] = [
                    'queries' => [$schema['query']],
                    'columns' => [$schema['column']],
                    'values' => [$schema['value']]
                ];
            }
        }

    }

    function buildChildSchema() {

        foreach ($this->migrations['child_migrations']['migrations'] as $migration) {
            $schema = $this->getMigrationSchema($migration);

            if (in_array($schema['table'], array_keys($this->childSchema))) {
                $this->childSchema[$schema['table']]['columns'][] = $schema['column'];
                $this->childSchema[$schema['table']]['values'][] = $schema['value'];
            }else {
                $this->childSchema[$schema['table']] = [
                    'queries' => [$schema['query']],
                    'columns' => [$schema['column']],
                    'values' => [$schema['value']]
                ];
            }
        }
    }

    private function getMigrationSchema($migration) {
        $old_column = $migration[0];
        $new_field = explode('.', $migration[1], 2);

        return [
            'table' => $new_field[0],
            'query' => "INSERT INTO {$new_field[0]} {COLUMNS} VALUES {VALUES}",
            'column' => $new_field[1],
            'value' => $old_column
        ];
    }

    private function getGeneralizationValues() {
        $db = Db::getInstance('sams_db_old');
        $generalization = implode(", ", $this->migrations['generalization']);
        $query = $db->query("SELECT DISTINCT {$generalization} FROM grade");
        $this->saved_data['generalization_values'] = array();

        foreach ($query->fetch_all(1) as $g_type) {
            $this->saved_data['generalization_values'][] = $g_type;
        }

        $this->saved_data['generalization_query'] = "SELECT * FROM {$this->table} WHERE {$generalization}={VALUE}";
    }
}