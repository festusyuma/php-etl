<?php


class GeneralizeMigration{

    private $migrations;
    private $table;

    private $schema;
    private $childSchema;
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

        foreach ($this->saved_data['generalization_values'] as $generalization) {
            $query = $this->saved_data['generalization_query'];
            $query = str_replace('{VALUE}', $this->generalizationToString($generalization), $query);
            $children = $this->old_db->query($query);

            if ($children) {
                $children = $children->fetch_all(1);
                $this->saved_data['children'] = $children;
                $temp_parent = $children[0];

                foreach ($this->schema as $schema) {
                    $parent = $this->migrateParent($temp_parent, $schema);

                    if ($parent) {
                        $this->saved_data['parent'] = $parent;
                        $this->migrateChildren();
                    }
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
            foreach ($this->childSchema as $schema) {
                $query = $schema['query'];
                $query = str_replace('{COLUMNS}', $this->getColumns($schema['columns']), $query);
                $query = str_replace('{VALUES}', $this->getValues($child, $schema['values']), $query);

                $this->new_db->query($query);
            }
        }
    }

    private function buildSchema() {

        foreach ($this->migrations['migrations'] as $migration) {
            $schema = $this->getMigrationSchema($migration);

            if (in_array($schema['table'], array_keys($this->schema))) {
                $this->schema[$schema['table']]['columns'][] = $schema['column'];
                $this->schema[$schema['table']]['values'][] = $schema['value'];
            }else {
                $this->schema[$schema['table']] = [
                    'table' => $schema['table'],
                    'query' => $schema['query'],
                    'columns' => [$schema['column']],
                    'values' => [$schema['value']]
                ];
            }
        }

    }

    private function buildChildSchema() {

        foreach ($this->migrations['child_migrations']['migrations'] as $migration) {
            $schema = $this->getMigrationSchema($migration);

            if (in_array($schema['table'], array_keys($this->childSchema))) {
                $this->childSchema[$schema['table']]['columns'][] = $schema['column'];
                $this->childSchema[$schema['table']]['values'][] = $schema['value'];
            }else {
                $this->childSchema[$schema['table']] = [
                    'table' => $schema['table'],
                    'query' => $schema['query'],
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