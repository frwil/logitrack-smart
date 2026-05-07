<?php require_once __DIR__ . '/env_loader.php'; ?>
<?php require_once __DIR__ . '/db.php'; ?>
<?php $con = mysqli_connect(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME')); ?>
<?php
if (isset($_POST['lignes'])):

    $lignes = json_decode($_POST['lignes']);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        for ($i = 0; $i < count($lignes); $i++):
            $q = db_exec($con, "INSERT ignore INTO `modele_vehicule` (`id_modele_vehicule`, `nom_modele_vehicule`) VALUES (NULL, ?)", [$lignes[$i]->{"Types de véhicule"}]);
            $q = db_exec($con, "INSERT ignore INTO `marque_vehicule` (`id_marque`, `nom_marque`) VALUES (NULL, ?)", [$lignes[$i]->Marque]);
            $date_1_utilisation = explode("/", $lignes[$i]->{"1ere mise en circulation"});
            $date_carte_grise = explode("/", $lignes[$i]->{"Carte grise expiry"});
            $date1util = date('Y-m-d', strtotime($date_1_utilisation[2] . "-" . $date_1_utilisation[0] . "-" . $date_1_utilisation[1]));
            $dateCg = date('Y-m-d', strtotime($date_carte_grise[2] . "-" . $date_carte_grise[0] . "-" . $date_carte_grise[1]));
            $q = db_exec($con, "INSERT ignore INTO `vehicule` (`id_vehicule`, `puissance_vehicule`, `chassis_vehicule`, `premiere_utilisation`, `expiration_carte_grise`, `nb_place`, `type_carburant`, `id_marque`, `id_modele_vehicule`, `id_entite`, `immatriculation_vehicule`, `capacite_consommation_vehicule`) VALUES (NULL, ?, ?, ?, ?, ?, ?, (select id_marque from marque_vehicule where nom_marque=?), (select id_modele_vehicule from modele_vehicule where nom_modele_vehicule=?), NULL, ?, ?)", [($lignes[$i]->Puissance == '-' ? 0 : (int)$lignes[$i]->Puissance), $lignes[$i]->Chassis, $date1util, $dateCg, ($lignes[$i]->Places == '-' ? 0 : (int)$lignes[$i]->Places), $lignes[$i]->{"Type carburant"}, $lignes[$i]->Marque, $lignes[$i]->{"Types de véhicule"}, $lignes[$i]->Immatriculation, $lignes[$i]->{"Capacité"}]);
            $q=db_exec($con,"INSERT ignore INTO `chauffeur` (`id_chauffeur`, `nom_chauffeur`) VALUES (NULL, upper(?))", [$lignes[$i]->{"Nom chauffeur"}]);
            $q=db_exec($con,"INSERT ignore INTO `type_utilisation_vehicule` (`id_type_utilisation`, `lib_type_utilisation`) VALUES (NULL, upper(?))", [$lignes[$i]->{"Type utilisation"}]);
            $q=db_exec($con,"INSERT ignore INTO `mode_utilisation_vehicule` (`id_mode_utilisation`, `nom_mode_utilisation`) VALUES (NULL, upper(?))", [$lignes[$i]->Utilisation]);
            print_r($lignes[$i]);
            $q=db_exec($con,"INSERT ignore INTO `entite` (`id_entite`, `nom_entite`) VALUES (NULL, ?)", [trim($lignes[$i]->{"Entité"})]);
            $q=db_exec($con,"INSERT ignore INTO `affectation_vehicule` (`id_affectation`, `id_vehicule`, `id_chauffeur`, `id_type_utilisation`, `id_mode_utilisation`, `id_entite`, `objet_affectation`, `date_debut_affectation`, `date_fin_affectation`, `id_region`, `date_affectation`, `is_ferme`) VALUES (NULL, (select id_vehicule from vehicule where immatriculation_vehicule=?), (select id_chauffeur from chauffeur where nom_chauffeur=?), (select id_type_utilisation from type_utilisation_vehicule where lib_type_utilisation=?), (select id_mode_utilisation from mode_utilisation_vehicule where nom_mode_utilisation=?), (select id_entite from entite where nom_entite=?), NULL, CURRENT_TIMESTAMP, NULL, NULL, CURRENT_TIMESTAMP, '0')", [$lignes[$i]->Immatriculation, $lignes[$i]->{"Nom chauffeur"}, $lignes[$i]->{"Type utilisation"}, $lignes[$i]->Utilisation, trim($lignes[$i]->{"Entité"})]);
        endfor;
        mysqli_commit($con);
        die("UpdVoyage%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        print_r($e);
        die("UpdVoyage%%%%%%0");
    }

endif;
?>
<html>

<head></head>

<body>
    <form class="input-group col-4 mb-5">
        <input type="file" class="form-control" id="myFile" />
        <button class="btn ml-2" type="submit" id="upload-btn">
            Upload
        </button>
    </form>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script
        src="https://cdn.jsdelivr.net/npm/xlsx@0.16.8/dist/xlsx.full.min.js"
        integrity="sha256-Ic7HP804IrYks4vUqX1trFF1Nr33RjONeJESZnYxsOY="
        crossorigin="anonymous">
    </script>
    <script>
        let selectedFile;

        // Get the selected file when input changes
        document.getElementById("myFile").addEventListener("change", (event) => {
            selectedFile = event.target.files[0];
        });

        // Handle upload button click
        document.getElementById("upload-btn").addEventListener("click", (e) => {
            e.preventDefault();
            let fileReader = new FileReader();

            // Read the selected file as binary string
            fileReader.readAsBinaryString(selectedFile);

            // Process the file data when it's loaded
            fileReader.onload = (event) => {
                let fileData = event.target.result;

                // Read the Excel workbook
                let workbook = XLSX.read(
                    fileData, {
                        type: "binary"
                    }, {
                        dateNF: "mm/dd/yyyy"
                    }
                );

                // Change each sheet in the workbook to json
                workbook.SheetNames.forEach(async (sheet) => {
                    const result = XLSX.utils.sheet_to_json(workbook.Sheets[sheet], {
                        raw: false,
                    });

                    $.ajax({
                        type: 'post',
                        data: 'lignes=' + JSON.stringify(result)
                    })
                });
            };
        });
    </script>
</body>

</html>