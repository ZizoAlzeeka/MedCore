<?php
class TestCatalog extends Model
{
    protected $table = 'tests_catalog';

    public function search($q)
    {
        $q = "%$q%";
        return Database::fetchAll(
            "SELECT * FROM tests_catalog
             WHERE name_ar LIKE ? OR name_en LIKE ? OR loinc_code LIKE ? OR category LIKE ?
             ORDER BY name_ar LIMIT 30",
            [$q, $q, $q, $q]
        );
    }

    public function findByLoinc($code)
    {
        return Database::fetch("SELECT * FROM tests_catalog WHERE loinc_code = ?", [$code]);
    }
}
