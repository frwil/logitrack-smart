<?php
/**
 * Mode utilisation vehicule repository.
 */
class ModeUtilisationRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->select("SELECT * FROM mode_utilisation_vehicule WHERE 1", []);
    }

    public function insertIgnore(string $nom): bool
    {
        return $this->execIgnore(
            "INSERT IGNORE INTO mode_utilisation_vehicule (nom_mode_utilisation) VALUES (?)",
            [$nom]
        );
    }
}
