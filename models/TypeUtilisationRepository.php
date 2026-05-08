<?php
/**
 * Type utilisation vehicule repository.
 */
class TypeUtilisationRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->select("SELECT * FROM type_utilisation_vehicule WHERE 1", []);
    }

    public function insertIgnore(string $lib): bool
    {
        return $this->insertIgnore(
            "INSERT IGNORE INTO type_utilisation_vehicule (lib_type_utilisation) VALUES (?)",
            [$lib]
        );
    }
}
