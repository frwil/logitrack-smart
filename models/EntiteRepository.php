<?php
/**
 * Entite repository.
 */
class EntiteRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->select("SELECT * FROM entite WHERE 1", []);
    }
}
