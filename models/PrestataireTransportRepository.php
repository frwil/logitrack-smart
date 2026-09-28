<?php
/**
 * Prestataire transport repository — external carriers for voyages.
 */
class PrestataireTransportRepository extends BaseRepository
{
    /** All active external carriers. */
    public function findAll(): array
    {
        return $this->select(
            "SELECT * FROM prestataire_transport WHERE is_deleted = 0 ORDER BY nom_societe",
            []
        );
    }

    /** Single carrier by id. */
    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM prestataire_transport WHERE id_prestataire_transport = ?",
            [$id]
        );
    }

    /** Insert a carrier; returns new id. Throws mysqli_sql_exception 1062 on duplicate nom_societe. */
    public function insert(string $nomSociete, ?string $adresseSociete, ?string $telephoneSociete): int|string
    {
        return $this->insertGetId(
            "INSERT INTO prestataire_transport (nom_societe, nom_chauffeur, adresse_societe, telephone_societe) VALUES (?, '', ?, ?)",
            [$nomSociete, $adresseSociete, $telephoneSociete]
        );
    }

    /**
     * Carrier whose company name matches the given one, ignoring case, accents
     * and multiple spaces — used to reject near-duplicates before insert.
     */
    public function findBySocieteSimilar(string $nomSociete): ?array
    {
        $needle = self::normalizeSociete($nomSociete);
        foreach ($this->findAll() as $r) {
            if (self::normalizeSociete((string)$r['nom_societe']) === $needle) return $r;
        }
        return null;
    }

    /** Normalized company name for similarity checks: lowercase, no accents, single spaces. */
    private static function normalizeSociete(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = strtr($s, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'á' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i',
            'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'ó' => 'o', 'ò' => 'o',
            'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ù' => 'u',
            'ç' => 'c', 'ñ' => 'n', 'ý' => 'y', 'ÿ' => 'y',
        ]);
        return preg_replace('/\s+/', ' ', $s);
    }
}
