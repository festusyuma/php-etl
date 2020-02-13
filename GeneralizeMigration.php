<?php


class GeneralizeMigration{

    private $migrations;
    private $table;

    private $schema;
    private $childSchema;
    private $childRelationshipSchema;
    private $saved_data;

    private $old_db;
    private $new_db;

    public function __construct($table, $migrations){
        $this->migrations = $migrations;
        $this->table = $table;
        $this->old_db = Db::getInstance('sams_db_old');
        $this->new_db = Db::getInstance('sams_db_new');

        $this->schema = $this->childSchema = $this->saved_data = array();
    }

    public function migrate() {

        $this->getGeneralizationValues();
        $this->buildSchema();
        $this->buildChildSchema();
        $this->buildChildRelationshipSchema();

        foreach ($this->saved_data['generalization_values'] as $generalization) {
            $query = $this->saved_data['generalization_query'];
            $query = str_replace('{VALUE}', $this->generalizationToString($generalization), $query);
            $children = $this->old_db->query($query);

            if ($children) {
                $children = $children->fetch_all(1);
                $this->saved_data['children'] = $children;
                $temp_parent = $children[0];

                $parent = $this->migrateParent($temp_parent, $this->schema);

                if ($parent) {
                    $this->saved_data['parent'] = $parent;
                    $this->migrateChildren();
                }
            }
        }
    }

    public function migrateParent($temp_parent, $schema) {
        $query = $schema['query'];
        $query = str_replace('{COLUMNS}', $this->getColumns($schema['columns']), $query);
        $query = str_replace('{VALUES}', $this->getValues($temp_parent, $schema['values']), $query);

        if ($this->new_db->query($query)) {
            return $this->new_db->query("SELECT * FROM {$schema['table']} WHERE `id` = {$this->new_db->insert_id}")->fetch_assoc();
        }else return false;
    }

    public function migrateChildren() {
        foreach ($this->saved_data['children'] as $child) {
            $schema = $this->childSchema;

            $query = $schema['query'];
            $query = str_replace('{COLUMNS}', $this->getColumns($schema['columns']), $query);
            $query = str_replace('{VALUES}', $this->getValues($child, $schema['values']), $query);

            $this->new_db->query($query);
            $child_data = $this->new_db->query("SELECT * FROM {$schema['table']} WHERE id={$this->new_db->insert_id}");
            if ($child_data) $this->migrateChildRelationship($child_data->fetch_assoc());
        }
    }

    public function migrateChildRelationship($child) {
        $query = $this->childRelationshipSchema['query'];
        $parent_key = $this->childRelationshipSchema['values'][0];
        $child_key = $this->childRelationshipSchema['values'][0];

        $query = str_replace('{VALUES}', "('{$this->saved_data['parent'][$parent_key]}', '{$child[$child_key]}')", $query);
        $this->new_db->query($query);
    }

    private function buildSchema() {
        $table = $this->migrations['table'];

        $this->schema = [
            'table' => $table,
            'query' => "INSERT INTO {$table} {COLUMNS} VALUES {VALUES}",
            'columns' => [],
            'values' => []
        ];

        foreach ($this->migrations['migrations'] as $migration) {
            $schema = $this->getMigrationSchema($migration, $table);

            $this->schema['columns'][] = $schema['column'];
            $this->schema['values'][] = $schema['value'];
        }

    }

    private function buildChildSchema() {

        $table = $this->migrations['child_migrations']['table'];
        $this->childSchema = [
            'table' => $table,
            'query' => "INSERT INTO {$table} {COLUMNS} VALUES {VALUES}",
            'columns' => [],
            'values' => []
        ];

        foreach ($this->migrations['child_migrations']['migrations'] as $migration) {
            $schema = $this->getMigrationSchema($migration, $table);

            $this->childSchema['columns'][] = $schema['column'];
            $this->childSchema['values'][] = $schema['value'];
        }
    }

    private function buildChildRelationshipSchema($child = null) {
        $relationship = $this->migrations['child_migrations']['relationship'];

        if ($relationship['type'] == 'external') {
            $table = $relationship['table'];
            $parent_rel_field = $relationship['field'][0];
            $child_rel_field = $relationship['field'][1];

            $this->childRelationshipSchema = [
                'query' => "INSERT INTO {$table} ({$parent_rel_field[1]}, {$child_rel_field[1]}) VALUES {VALUES}",
                'values' => [
                    $parent_rel_field[0],
                    $child_rel_field[0],
                ],
            ];
        }else {
            $table = $child['table'];
            $field = $relationship['field'][0];
            $query = "UPDATE {$table} SET {$field[1]} = {VALUE} WHERE id = {$child['id']}";

            //todo implement same table relationship
        }
    }

    private function getMigrationSchema($migration, $table) {
        $old_column = $migration[0];
        $new_field = $migration[1];

        return [
            'query' => "INSERT INTO {$table} {COLUMNS} VALUES {VALUES}",
            'column' => $new_field,
            'value' => $old_column
        ];
    }

    private function getGeneralizationValues() {
        $db = Db::getInstance('sams_db_old');
        $generalization = implode(", ", $this->migrations['generalization']);
        $query = $db->query("SELECT DISTINCT {$generalization} FROM {$this->table}");
        $this->saved_data['generalization_values'] = array();

        foreach ($query->fetch_all(1) as $g_type) {
            $this->saved_data['generalization_values'][] = $g_type;
        }

        $this->saved_data['generalization_query'] = "SELECT * FROM {$this->table} WHERE {VALUE}";
    }

    private function generalizationToString($generalization) {
        $generalizations = [];

        foreach ($generalization as $col=>$val) {
            if ($val == '') $val = null; else $val = "'${val}'";
            $generalizations[] = "{$col} = {$val}";
        }

        return implode(' AND ', $generalizations);
    }

    private function getColumns($columns) {
        $columns_string = implode(', ', $columns);
        return "($columns_string)";
    }

    private function getValues($data, $values) {
        $val = [];

        foreach ($values as $value) {

            if (is_array($value)) {
                $temp_val = $data[$value[0]];
                $temp_val = $value[1]($temp_val);
            } else $temp_val = $data[$value];

            $val[] = $temp_val;
        }

        $val = (array_map([$this, 'stringify'], $val));
        $val_string = implode(', ', $val);

        return "($val_string)";
    }

    private function stringify($val) {
        return ($val == '') ? 'null' : "'".$val."'";
    }
}