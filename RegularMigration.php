<?php
class RegularMigration extends CommonMigration {

    private $schema;
    private $saved_data;

    public function __construct($table, $migrations){
        parent::__construct($table, $migrations);
    }

    public function normalMigrate() {

        $this->buildSchema();
        $this->setData();
        $schema = $this->schema;

        foreach ($this->saved_data['data'] as $data) {
            $query = $schema['query'];
            $query = str_replace('{COLUMNS}', $this->getColumns($schema['columns']), $query);
            $query = str_replace('{VALUES}', $this->getValues($data, $schema['values']), $query);

            $parent = $this->new_db->query($query);
            if ($parent) {
                $parent = $this->new_db->query("SELECT * FROM {$schema['table']} WHERE id={$this->new_db->insert_id}");
                $this->saved_data['parent'] = $parent->fetch_assoc();

                foreach ($this->migrations['after'] as $table => $migration) {
                    $after = $this->migrateAfter($table, $migration['migration'], $this->getData($data, $migration['data']));
                    $this->migrateAfterRelationship($this->saved_data['parent'], $after, $migration['relationship']);
                }
            }
        }
    }

    public function migrateAsBatch($data) {

    }

    private function setData() {
        $query = "SELECT * FROM {$this->table}";
        $data = $this->old_db->query($query);

        if ($data) {
            $this->saved_data['data'] = $data->fetch_all(1);
        }
    }

    private function buildSchema() {

        $table = $this->migrations['table'];
        $this->schema = [
            'table' => $table,
            'query' => "INSERT INTO {$table} {COLUMNS} VALUES {VALUES}",
            'columns' => [],
            'values' => [],
        ];

        foreach ($this->migrations['migrations'] as $schema) {
            $this->schema['columns'][] = $schema[1];
            $this->schema['values'][] = $schema[0];
        }
    }
}