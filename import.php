<?php require_once __DIR__ . '/env_loader.php'; ?>
<?php require_once __DIR__ . '/db.php'; ?>
<?php require_once __DIR__ . '/models/autoload.php'; ?>
<?php $con = mysqli_connect(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME')); ?>
<?php
if (isset($_POST['lignes'])):

    $lignes = json_decode($_POST['lignes']);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $modeleRepo = new ModeleRepository($con);
        $marqueRepo = new MarqueRepository($con);
        $vehiculeRepo = new VehiculeRepository($con);
        $chauffeurRepo = new ChauffeurRepository($con);
        $typeUtilRepo = new TypeUtilisationRepository($con);
        $modeUtilRepo = new ModeUtilisationRepository($con);
        $entiteRepo = new EntiteRepository($con);
        $affectationRepo = new AffectationRepository($con);

        for ($i = 0; $i < count($lignes); $i++):
            $row = $lignes[$i];

            $modeleRepo->insertIgnore($row->{"Types de véhicule"});
            $marqueRepo->insertIgnore($row->Marque);

            $d = explode("/", $row->{"1ere mise en circulation"});
            $date1util = date('Y-m-d', strtotime("$d[2]-$d[0]-$d[1]"));
            $d = explode("/", $row->{"Carte grise expiry"});
            $dateCg = date('Y-m-d', strtotime("$d[2]-$d[0]-$d[1]"));

            $vehiculeRepo->insertIgnore(
                ($row->Puissance == '-' ? 0 : (int)$row->Puissance),
                $row->Chassis,
                $date1util,
                $dateCg,
                ($row->Places == '-' ? 0 : (int)$row->Places),
                $row->{"Type carburant"},
                $row->Marque,
                $row->{"Types de véhicule"},
                $row->Immatriculation,
                $row->{"Capacité"}
            );

            $chauffeurRepo->insertIgnore(mb_strtoupper($row->{"Nom chauffeur"}));
            $typeUtilRepo->insertIgnore(mb_strtoupper($row->{"Type utilisation"}));
            $modeUtilRepo->insertIgnore(mb_strtoupper($row->Utilisation));
            $entiteRepo->insertIgnore(trim($row->{"Entité"}));

            $affectationRepo->insertIgnore(
                $row->Immatriculation,
                mb_strtoupper($row->{"Nom chauffeur"}),
                mb_strtoupper($row->{"Type utilisation"}),
                mb_strtoupper($row->Utilisation),
                trim($row->{"Entité"})
            );

            print_r($row);
        endfor;
        mysqli_commit($con);
        die(json_encode(['success' => true]));
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        print_r($e);
        die(json_encode(['success' => false, 'error' => 'Erreur lors de l\'import']));
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
                        data: 'lignes=' + JSON.stringify(result),
                        dataType: 'json'
                    }).done((e) => {
                        if (e.success) {
                            showSuccess('Import réussi');
                        } else {
                            showError(e.error || 'Erreur import');
                        }
                    }).fail((jqXHR) => {
                        showError(jqXHR.responseJSON?.error || 'Erreur serveur');
                    })
                });
            };
        });
    </script>
</body>

</html>