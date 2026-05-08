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
}
