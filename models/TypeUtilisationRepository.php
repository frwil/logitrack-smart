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
}
