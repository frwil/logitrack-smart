<?php

?>
<!-- Bloc à onglets Bootstrap -->
<div class="container mt-4">
  <ul class="nav nav-tabs" id="reportTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="poussins-tab" data-bs-toggle="tab" data-bs-target="#poussins" type="button" role="tab" aria-controls="poussins" aria-selected="true">
        Transport et Consommations
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="remplissage-tab" data-bs-toggle="tab" data-bs-target="#remplissage" type="button" role="tab" aria-controls="remplissage" aria-selected="false">
        Taux de remplissage des camions
      </button>
    </li>
  </ul>
  <div class="tab-content" id="reportTabsContent">
    <div class="tab-pane fade show active p-3" id="poussins" role="tabpanel" aria-labelledby="poussins-tab">
      <!-- Contenu Transport poussins -->
      <form method="post" id="form_poussins">
        <div class="mb-3 row">
          <div class="col-6">
            <label for="date_debut" class="form-label">Date de début</label>
            <input type="date" class="form-control" id="date_debut" name="date_debut" required value="<?php if (isset($_POST['date_debut'])) echo h($_POST['date_debut']);
                                                                                                      else echo date("Y-m-d"); ?>">
          </div>
          <div class="col-6">
            <label for="date_fin" class="form-label">Date de fin</label>
            <input type="date" class="form-control" id="date_fin" name="date_fin" required value="<?php if (isset($_POST['date_fin'])) echo h($_POST['date_fin']);
                                                                                                  else echo date("Y-m-d"); ?>">
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Générer le rapport</button>
      </form>
      <hr>
      <table id="table_report" class="table table-striped table-bordered no-datatable" style="width:100%">
        <thead>
          <tr>
            <th>Véhicule</th>
            <th>Date</th>
            <th>Type de chargement</th>
            <th>Qté de chargement</th>
            <th>Kms parcourus</th>
            <th>Consommation</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if(!isset($_POST['date_debut']) || !isset($_POST['date_fin'])) {
            $_POST['date_debut'] = date("Y-m-d");
            $_POST['date_fin'] = date("Y-m-d");
          }
          $vehiculeRepo = new VehiculeRepository($con);
          $voyageRepo = new VoyageRepository($con);
          $vehicules = $vehiculeRepo->findAllWithDetails();
          $table = "";
          $sum_chargement = array();
          $sum_kms = array();
          $sum_conso = array();
          foreach ($vehicules as $row) {
            $id_vehicule = $row['id_vehicule'];
            $voyages = $voyageRepo->findForReportingByContext((int)$id_vehicule, $_POST['date_debut'], $_POST['date_fin'], getContextRegions(), getContextEntities());
            $countVoyages = count($voyages);
            $sum_chargement[$id_vehicule] = array();
            $sum_kms[$id_vehicule] = array();
            $sum_conso[$id_vehicule] = array();
            foreach ($voyages as $row2) {
              if(!isset($sum_chargement[$id_vehicule][$row2['id_type_chargement']])) {
                $sum_chargement[$id_vehicule][$row2['id_type_chargement']] = 0;
                $sum_kms[$id_vehicule][$row2['id_type_chargement']] = 0;
                $sum_conso[$id_vehicule][$row2['id_type_chargement']] = 0;
              }
              $sum_chargement[$id_vehicule][$row2['id_type_chargement']] += $row2['qte_chargement'];
              $sum_kms[$id_vehicule][$row2['id_type_chargement']] += $row2['distance_destination'];
              $sum_conso[$id_vehicule][$row2['id_type_chargement']] += $row2['qte_carburant'];
            }
            foreach ($voyages as $row2) {
              $table .= "<tr><td>" . h($row['immatriculation_vehicule']) . " (" . $countVoyages . " Voyages)" . "</td>";
              $table .= "<td>".($row2['date_voyage'] ? date('d/m/Y',strtotime($row2['date_voyage'])) : '')."</td><td>".h($row['immatriculation_vehicule'])." - ".h($row2['lib_type_chargement']). " (".$sum_chargement[$id_vehicule][$row2['id_type_chargement']]." - ". $sum_kms[$id_vehicule][$row2['id_type_chargement']]."kms - " .$sum_conso[$id_vehicule][$row2['id_type_chargement']]."Litres)"."</td><td>" . h($row2['qte_chargement']) . "</td><td>".h($row2['distance_destination'])."</td><td>".h($row2['qte_carburant'])."</td></tr>";
            }
            if ($countVoyages == 0) {
              $table .= "<tr><td>" . h($row['immatriculation_vehicule']) . " (0)" . "</td><td></td><td>Aucun voyage trouvé</td><td></td><td></td><td></td></tr>";
            }
          }
          echo $table;
          ?>
        </tbody>
      </table>
    </div>
    <div class="tab-pane fade p-3" id="remplissage" role="tabpanel" aria-labelledby="remplissage-tab">
      <!-- Contenu Taux de remplissage des camions -->
      <p>Contenu du rapport Taux de remplissage des camions.</p>
    </div>
  </div>
</div>
<script>
  const mainDiv = $('#reportTabs').parent().parent().parent()
  mainDiv.find('.col-2').remove()
  mainDiv.find('.col-10').removeClass('col-10').addClass('col-12')
  $('#table_report').DataTable({
    "columnDefs": [{ "targets": 0, "searchable": false }],
    "language": {
      "lengthMenu": "Afficher _MENU_ enregistrements par page",
      "zeroRecords": "Aucun enregistrement trouvé",
      "info": "Affichage de la page _PAGE_ sur _PAGES_",
      "infoEmpty": "Aucun enregistrement disponible",
      "infoFiltered": "(filtré à partir de _MAX_ enregistrements au total)",
      "search": "Rechercher :",
      "paginate": {
        "first": "Premier",
        "last": "Dernier",
        "next": "Suivant",
        "previous": "Précédent"
      }
    },
    ordering: false,
    dom: 'Bfrtip',
    buttons: [
       'excel'
    ],
    "order": [[0, "desc"]],
    rowGroup: {
        dataSrc: [0,3]
    },
    columnDefs:[
      {
        targets:[0,3],
        visible:false,
      }
    ]
  });
</script>