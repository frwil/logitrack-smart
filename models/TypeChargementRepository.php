<?php
class TypeChargementRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->select("SELECT * FROM type_chargement_voyage WHERE 1", []);
    }

    public function findById(int $id): ?array
    {
        return $this->selectOne("SELECT * FROM type_chargement_voyage WHERE id_type_chargement = ?", [$id]);
    }

    public function create(string $libelle, string $uniteMesure, float $valMin, float $valMax): int|string
    {
        return $this->insertGetId(
            "INSERT INTO type_chargement_voyage (lib_type_chargement, unite_mesure, valeur_min, valeur_max) VALUES (?, ?, ?, ?)",
            [$libelle, $uniteMesure, $valMin, $valMax]
        );
    }

    public function update(int $id, string $libelle, string $uniteMesure, float $valMin, float $valMax): bool
    {
        return $this->exec(
            "UPDATE type_chargement_voyage SET lib_type_chargement = ?, unite_mesure = ?, valeur_min = ?, valeur_max = ? WHERE id_type_chargement = ?",
            [$libelle, $uniteMesure, $valMin, $valMax, $id]
        );
    }

    public function delete(int $id): bool
    {
        return $this->exec("DELETE FROM type_chargement_voyage WHERE id_type_chargement = ?", [$id]);
    }
}
