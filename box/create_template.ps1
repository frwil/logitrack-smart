$ErrorActionPreference = "Continue"
$boxDir = "d:\logitrack-smart\box"
$srcFile = Get-ChildItem $boxDir -Filter "*.xlsx" | Where-Object { $_.Name -notlike "Template*" } | Select-Object -First 1
if (-not $srcFile) { Write-Error "No source Excel file found in $boxDir"; exit 1 }
$src = $srcFile.FullName
$dst = Join-Path $boxDir "Template_Flotte_NJS.xlsx"

Write-Host "Source: $src"
Write-Host "Destination: $dst"
Write-Host "=== Phase 1: Reading source data ==="
$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

function Get-CellText($ws, $r, $c) {
    $v = $ws.Cells.Item($r, $c).Text
    if ($v) { return $v.Trim() } else { return "" }
}

# ---- Read SPC ----
$wb = $excel.Workbooks.Open($src)
$ws = $wb.Sheets.Item("SPC")
$spcVehicles = @()
# Data rows: 10-18 (Bafoussam), 20-21 (Douala), 24 (Yaounde), 27 (Bertoua), 31-32 (Ngaoundere)
$spcRows = @(10,11,12,13,14,15,16,17,18, 20,21, 24, 27, 31,32)
$spcCity = ""
for ($ri = 0; $ri -lt $spcRows.Count; $ri++) {
    $r = $spcRows[$ri]
    # Detect city from preceding rows
    $cityCheck = Get-CellText $ws ($r - 1) 6
    if ($cityCheck -and $cityCheck -ne "BAFOUSSAM" -and $cityCheck -ne "DOUALA" -and $cityCheck -ne "YAOUNDE" -and $cityCheck -ne "BERTOUA" -and $cityCheck -ne "NGAOUNDERE") {
        # check row-1 col6 for city
    }
    $imm = Get-CellText $ws $r 2
    if (-not $imm) { continue }
    $marque = Get-CellText $ws $r 3
    $entite = Get-CellText $ws $r 4
    $chauffeur = Get-CellText $ws $r 5
    $tel = Get-CellText $ws $r 6
    $ville = Get-CellText $ws $r 7
    $activite = Get-CellText $ws $r 8
    $etat = Get-CellText $ws $r 9
    # Determine city from context
    if ($r -le 18) { $villeContext = "Bafoussam" }
    elseif ($r -le 21) { $villeContext = "Douala" }
    elseif ($r -le 24) { $villeContext = "Yaoundé" }
    elseif ($r -le 27) { $villeContext = "Bertoua" }
    else { $villeContext = "Ngaoundéré" }

    $spcVehicles += @{
        Immat = $imm
        Marque = $marque
        Entite = if ($entite) { $entite } else { "SPC" }
        Chauffeur = $chauffeur
        Tel = $tel
        Ville = if ($ville) { $ville } else { $villeContext }
        Activite = $activite
        Etat = $etat
        Source = "SPC"
    }
}
$wb.Close($false)
Write-Host "SPC vehicles: $($spcVehicles.Count)"

# ---- Read AGR ----
$wb = $excel.Workbooks.Open($src)
$ws = $wb.Sheets.Item("AGR")
$agrVehicles = @()
# Data rows: skip headers and section labels (rows 7,8,9,10,19,30,36,37,39,43,45,46 are labels or headers)
$agrDataRows = @(11,12,13,14,15,16,17,18, 20,21,22,23,24,25,26,27,28,29, 31,32,33,34,35, 38, 40,41,42, 44, 47,48)
foreach ($r in $agrDataRows) {
    $imm = Get-CellText $ws $r 3
    if (-not $imm -or $imm -eq "IMMAT") { continue }
    $chassis = Get-CellText $ws $r 2
    $cu = Get-CellText $ws $r 4
    $marque = Get-CellText $ws $r 5
    $mec = Get-CellText $ws $r 6
    $entite = Get-CellText $ws $r 7
    $places = Get-CellText $ws $r 8
    $energie = Get-CellText $ws $r 9
    $cg = Get-CellText $ws $r 10
    $assurance = Get-CellText $ws $r 11
    $visite = Get-CellText $ws $r 13
    $chauffeur = Get-CellText $ws $r 16
    $permisCh = Get-CellText $ws $r 17
    $expPermisCh = Get-CellText $ws $r 18
    $cniCh = Get-CellText $ws $r 19
    $assistant = Get-CellText $ws $r 20
    $permisAss = Get-CellText $ws $r 21
    $expPermisAss = Get-CellText $ws $r 22
    $cniAss = Get-CellText $ws $r 23
    $utilisation = Get-CellText $ws $r 24

    # Normalize entité
    if (-not $entite) { $entite = "AGROCAM" }

    $agrVehicles += @{
        Immat = $imm
        Chassis = $chassis
        CU = $cu
        Marque = $marque
        MEC = $mec
        Entite = $entite
        Places = $places
        Energie = $energie
        CG = $cg
        Assurance = $assurance
        Visite = $visite
        Chauffeur = $chauffeur
        PermisCh = $permisCh
        ExpPermisCh = $expPermisCh
        CNICh = $cniCh
        Assistant = $assistant
        PermisAss = $permisAss
        ExpPermisAss = $expPermisAss
        CNIAss = $cniAss
        Utilisation = $utilisation
        Source = "AGR"
    }
}
$wb.Close($false)
Write-Host "AGR vehicles: $($agrVehicles.Count)"

# ---- Read BELGO ----
$wb = $excel.Workbooks.Open($src)
$ws = $wb.Sheets.Item("BELGO")
$belgoVehicles = @()
for ($r = 7; $r -le 14; $r++) {
    $imm = Get-CellText $ws $r 6
    if (-not $imm) { continue }
    $marque = Get-CellText $ws $r 5
    $affectation = Get-CellText $ws $r 7
    $belgoVehicles += @{
        Immat = $imm
        Marque = $marque
        Entite = "BELGOCAM"
        Affectation = $affectation
        Source = "BELGO"
    }
}
$wb.Close($false)
Write-Host "BELGO vehicles: $($belgoVehicles.Count)"

# ---- Read PDC ----
$wb = $excel.Workbooks.Open($src)
$ws = $wb.Sheets.Item("PDC")
$pdcVehicles = @()
$r = 3
$imm = Get-CellText $ws $r 3
if ($imm) {
    $marque = Get-CellText $ws $r 15
    $chauffeur = Get-CellText $ws $r 4
    $tel = Get-CellText $ws $r 12
    $permis = Get-CellText $ws $r 14
    $etat = Get-CellText $ws $r 13
    $pdcVehicles += @{
        Immat = $imm
        Marque = $marque
        Entite = "PDC"
        Chauffeur = $chauffeur
        Tel = $tel
        Permis = $permis
        Etat = $etat
        Source = "PDC"
    }
}
$wb.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
Write-Host "PDC vehicles: $($pdcVehicles.Count)"
Write-Host "Total raw: $($spcVehicles.Count + $agrVehicles.Count + $belgoVehicles.Count + $pdcVehicles.Count)"

# ==== Phase 2: Merge and normalize ====

# Merge vehicles by immatriculation (normalize key: uppercase, remove spaces)
function Normalize-Immat($s) {
    return ($s -replace '\s+','').ToUpper()
}

# All merged vehicles
$allVeh = @{}  # key=normalized immat
# All unique drivers
$allDrivers = @{}  # key=normalized name
# Assignments
$allAssignments = @()

# SPC provides: driver name, phone, city, status per vehicle
# AGR provides: chassis, CU, carburant, technical dates, driver+assistant+permis+CNI
# BELGO provides: marque, affectation (no driver info)
# PDC provides: 1 vehicle with driver+phone+permis

# Process AGR first (richest technical data)
foreach ($v in $agrVehicles) {
    $key = Normalize-Immat $v.Immat
    if (-not $key -or $key -eq "") { continue }
    if (-not $allVeh.ContainsKey($key)) {
        $allVeh[$key] = @{
            Immat = $v.Immat
            Marque = $v.Marque
            Entite = $v.Entite
            Chassis = $v.Chassis
            CU = $v.CU
            Energie = $v.Energie
            MEC = $v.MEC
            Places = $v.Places
            CG = $v.CG
            Assurance = $v.Assurance
            Visite = $v.Visite
            Utilisation = $v.Utilisation
            Source = $v.Source
        }
    } else {
        # Enrich with AGR data
        $x = $allVeh[$key]
        if (-not $x.Marque -and $v.Marque) { $x.Marque = $v.Marque }
        if (-not $x.Chassis -and $v.Chassis) { $x.Chassis = $v.Chassis }
        if (-not $x.CU -and $v.CU) { $x.CU = $v.CU }
        if (-not $x.Energie -and $v.Energie) { $x.Energie = $v.Energie }
        if (-not $x.MEC -and $v.MEC) { $x.MEC = $v.MEC }
    }

    # Add chauffeur (primary driver from AGR = C16 "Chauffeurs" or "Responsable")
    $chName = $v.Chauffeur
    if ($chName -and $chName -notmatch 'Courses|Equipe|ADMINISTRATION|SAV|HOLDING|ALVEOLES|Besoin|En Panne|Pas n') {
        $dk = $chName.ToUpper().Trim()
        if (-not $allDrivers.ContainsKey($dk)) {
            $allDrivers[$dk] = @{
                Nom = $chName
                Tel = ""
                PermisPrincipal = $v.PermisCh
                ExpPermis = $v.ExpPermisCh
                CNI = $v.CNICh
                Entite = $v.Entite
            }
        } else {
            $d = $allDrivers[$dk]
            if (-not $d.PermisPrincipal -and $v.PermisCh) { $d.PermisPrincipal = $v.PermisCh }
            if (-not $d.ExpPermis -and $v.ExpPermisCh) { $d.ExpPermis = $v.ExpPermisCh }
            if (-not $d.CNI -and $v.CNICh) { $d.CNI = $v.CNICh }
        }
        $allAssignments += @{
            Immat = $key
            Chauffeur = $chName
            Role = "Principal"
            Entite = $v.Entite
        }
    }

    # Add assistant
    $assName = $v.Assistant
    if ($assName -and $assName -notmatch 'Courses|Equipe|En Panne|Besoin|Pas n') {
        $dk = $assName.ToUpper().Trim()
        if (-not $allDrivers.ContainsKey($dk)) {
            $allDrivers[$dk] = @{
                Nom = $assName
                Tel = ""
                PermisPrincipal = $v.PermisAss
                ExpPermis = $v.ExpPermisAss
                CNI = $v.CNIAss
                Entite = $v.Entite
            }
        } else {
            $d = $allDrivers[$dk]
            if (-not $d.PermisPrincipal -and $v.PermisAss) { $d.PermisPrincipal = $v.PermisAss }
            if (-not $d.ExpPermis -and $v.ExpPermisAss) { $d.ExpPermis = $v.ExpPermisAss }
            if (-not $d.CNI -and $v.CNIAss) { $d.CNI = $v.CNIAss }
        }
        $allAssignments += @{
            Immat = $key
            Chauffeur = $assName
            Role = "Assistant"
            Entite = $v.Entite
        }
    }
}

# Process SPC
foreach ($v in $spcVehicles) {
    $key = Normalize-Immat $v.Immat
    if (-not $key -or $key -eq "") { continue }
    if (-not $allVeh.ContainsKey($key)) {
        $allVeh[$key] = @{
            Immat = $v.Immat
            Marque = $v.Marque
            Entite = $v.Entite
            Chassis = ""
            CU = ""
            Energie = ""
            MEC = ""
            Utilisation = $v.Activite
            Source = $v.Source
        }
    } else {
        $x = $allVeh[$key]
        if (-not $x.Marque -and $v.Marque) { $x.Marque = $v.Marque }
        if (-not $x.Entite -or $x.Entite -eq "AGROCAM") {
            if ($v.Entite -and $v.Entite -ne "AGROCAM") { $x.Entite = $v.Entite }
        }
        if (-not $x.Utilisation -and $v.Activite) { $x.Utilisation = $v.Activite }
    }

    $chName = $v.Chauffeur
    if ($chName) {
        $dk = $chName.ToUpper().Trim()
        if (-not $allDrivers.ContainsKey($dk)) {
            $allDrivers[$dk] = @{
                Nom = $chName
                Tel = $v.Tel
                PermisPrincipal = ""
                ExpPermis = ""
                CNI = ""
                Entite = $v.Entite
            }
        } else {
            $d = $allDrivers[$dk]
            if (-not $d.Tel -and $v.Tel) { $d.Tel = $v.Tel }
        }
        # Check if assignment already exists for this immat+driver
        $found = $false
        foreach ($a in $allAssignments) {
            if ($a.Immat -eq $key -and $a.Chauffeur -eq $chName) { $found = $true; break }
        }
        if (-not $found) {
            $allAssignments += @{
                Immat = $key
                Chauffeur = $chName
                Role = "Principal"
                Entite = $v.Entite
            }
        }
    }
}

# Process BELGO
foreach ($v in $belgoVehicles) {
    $key = Normalize-Immat $v.Immat
    if (-not $key -or $key -eq "") { continue }
    if (-not $allVeh.ContainsKey($key)) {
        $allVeh[$key] = @{
            Immat = $v.Immat
            Marque = $v.Marque
            Entite = $v.Entite
            Chassis = ""
            CU = ""
            Energie = ""
            MEC = ""
            Utilisation = $v.Affectation
            Source = $v.Source
        }
    } else {
        $x = $allVeh[$key]
        if (-not $x.Marque -and $v.Marque) { $x.Marque = $v.Marque }
        if (-not $x.Utilisation -and $v.Affectation) { $x.Utilisation = $v.Affectation }
    }
}

# Process PDC
foreach ($v in $pdcVehicles) {
    $key = Normalize-Immat $v.Immat
    if (-not $key -or $key -eq "") { continue }
    if (-not $allVeh.ContainsKey($key)) {
        $allVeh[$key] = @{
            Immat = $v.Immat
            Marque = $v.Marque
            Entite = $v.Entite
            Chassis = ""
            CU = ""
            Energie = ""
            MEC = ""
            Utilisation = ""
            Source = $v.Source
        }
    } else {
        $x = $allVeh[$key]
        if (-not $x.Marque -and $v.Marque) { $x.Marque = $v.Marque }
    }

    $chName = $v.Chauffeur
    if ($chName) {
        $dk = $chName.ToUpper().Trim()
        if (-not $allDrivers.ContainsKey($dk)) {
            $allDrivers[$dk] = @{
                Nom = $chName
                Tel = $v.Tel
                PermisPrincipal = $v.Permis
                ExpPermis = ""
                CNI = ""
                Entite = $v.Entite
            }
        } else {
            $d = $allDrivers[$dk]
            if (-not $d.Tel -and $v.Tel) { $d.Tel = $v.Tel }
            if (-not $d.PermisPrincipal -and $v.Permis) { $d.PermisPrincipal = $v.Permis }
        }
        $found = $false
        foreach ($a in $allAssignments) {
            if ($a.Immat -eq $key -and $a.Chauffeur -eq $chName) { $found = $true; break }
        }
        if (-not $found) {
            $allAssignments += @{
                Immat = $key
                Chauffeur = $chName
                Role = "Principal"
                Entite = $v.Entite
            }
        }
    }
}

# Convert hashtables to sorted arrays
$vehList = $allVeh.Values | Sort-Object { Normalize-Immat $_.Immat }
$drvList = $allDrivers.Values | Sort-Object { $_.Nom }
$asgList = $allAssignments | Sort-Object { Normalize-Immat $_.Immat }

Write-Host "Merged vehicles: $($vehList.Count)"
Write-Host "Unique drivers: $($drvList.Count)"
Write-Host "Assignments: $($asgList.Count)"

# ==== Phase 2b: Enrich with DB data ====
Write-Host ""
Write-Host "=== Phase 2b: Enriching with DB data ==="
$dbJsonPath = Join-Path $boxDir "db_data.json"
if (Test-Path $dbJsonPath) {
    $dbData = Get-Content $dbJsonPath -Raw -Encoding UTF8 | ConvertFrom-Json

    # Build DB vehicle lookup by immat
    $dbVehByImmat = @{}
    foreach ($dv in $dbData.vehicules) {
        $key = Normalize-Immat $dv.immatriculation_vehicule
        if ($key) { $dbVehByImmat[$key] = $dv }
    }

    # Build DB driver lookup by normalized name
    $dbDrvByName = @{}
    foreach ($dd in $dbData.chauffeurs) {
        $key = $dd.nom_chauffeur.ToUpper().Trim()
        $dbDrvByName[$key] = $dd
    }

    # Build DB affectation lookup by immat
    $dbAffByImmat = @{}
    foreach ($da in $dbData.affectations) {
        $key = Normalize-Immat $da.immatriculation_vehicule
        if ($key) {
            if (-not $dbAffByImmat.ContainsKey($key)) { $dbAffByImmat[$key] = @() }
            $dbAffByImmat[$key] += $da
        }
    }

    Write-Host "DB vehicles: $($dbData.vehicules_count), drivers: $($dbData.chauffeurs_count), affectations: $($dbData.affectations_count)"

    # Enrich vehicle list from DB (add vehicles that are in DB but not in Excel)
    foreach ($dv in $dbData.vehicules) {
        $key = Normalize-Immat $dv.immatriculation_vehicule
        if (-not $key) { continue }
        if (-not $allVeh.ContainsKey($key)) {
            $allVeh[$key] = @{
                Immat = $dv.immatriculation_vehicule
                Marque = $dv.nom_marque
                Modele = $dv.nom_modele_vehicule
                Entite = $dv.nom_entite
                Chassis = $dv.chassis_vehicule
                Energie = $dv.type_carburant
                MEC = if ($dv.premiere_utilisation) { $dv.premiere_utilisation } else { "" }
                CU = ""
                Utilisation = ""
                Source = "DB"
            }
        } else {
            # Enrich existing with DB data (DB takes priority for structured fields)
            $x = $allVeh[$key]
            if ($dv.nom_marque) { $x.Marque = $dv.nom_marque }
            if ($dv.nom_modele_vehicule) { $x.Modele = $dv.nom_modele_vehicule }
            if ($dv.chassis_vehicule) { $x.Chassis = $dv.chassis_vehicule }
            if ($dv.type_carburant) { $x.Energie = $dv.type_carburant }
            if ($dv.nom_entite) { $x.Entite = $dv.nom_entite }
            if ($dv.premiere_utilisation) { $x.MEC = $dv.premiere_utilisation }
        }
    }

    # Enrich driver list from DB
    foreach ($dd in $dbData.chauffeurs) {
        $key = $dd.nom_chauffeur.ToUpper().Trim()
        if (-not $allDrivers.ContainsKey($key)) {
            $allDrivers[$key] = @{
                Nom = $dd.nom_chauffeur
                Tel = ""
                PermisPrincipal = if ($dd.permis_lib) { $dd.permis_lib } else { "" }
                ExpPermis = ""
                CNI = ""
                Entite = ""
            }
        } else {
            $d = $allDrivers[$key]
            if (-not $d.PermisPrincipal -and $dd.permis_lib) { $d.PermisPrincipal = $dd.permis_lib }
        }
    }

    # Enrich assignments from DB (add affectations from DB not in Excel)
    foreach ($da in $dbData.affectations) {
        $key = Normalize-Immat $da.immatriculation_vehicule
        if (-not $key) { continue }
        $chName = $da.nom_chauffeur
        if (-not $chName) { continue }

        # Add chauffeur if not already known
        $dk = $chName.ToUpper().Trim()
        if (-not $allDrivers.ContainsKey($dk)) {
            $allDrivers[$dk] = @{
                Nom = $chName
                Tel = ""
                PermisPrincipal = ""
                ExpPermis = ""
                CNI = ""
                Entite = if ($da.nom_entite) { $da.nom_entite } else { "" }
            }
        }

        # Check if assignment already exists
        $found = $false
        foreach ($a in $allAssignments) {
            if ((Normalize-Immat $a.Immat) -eq $key -and $a.Chauffeur -eq $chName) {
                $found = $true
                # Enrich with DB region/entite
                if (-not $a.Entite -and $da.nom_entite) { $a.Entite = $da.nom_entite }
                break
            }
        }
        if (-not $found) {
            $allAssignments += @{
                Immat = $key
                Chauffeur = $chName
                Role = "Principal"
                Entite = if ($da.nom_entite) { $da.nom_entite } else { "" }
                Region = if ($da.nom_region) { $da.nom_region } else { "" }
            }
        }
    }

    # Filter out entries with obviously invalid immatriculations
$badKeys = @()
foreach ($k in $allVeh.Keys) {
    $v = $allVeh[$k]
    $imm = $v.Immat
    if (-not $imm) { $badKeys += $k; continue }
    # Filter: valid immat should have letters+numbers, min 4 chars
    if ($imm.Length -lt 4) { $badKeys += $k; continue }
    # Filter out entries that are just numbers or generic words
    if ($imm -match '^[0-9]+$') { $badKeys += $k; continue }
    if ($imm -eq 'BUS') { $badKeys += $k; continue }
}
foreach ($k in $badKeys) { $allVeh.Remove($k) }
if ($badKeys.Count -gt 0) { Write-Host "Removed $($badKeys.Count) invalid vehicles: $($badKeys -join ', ')" }

# Rebuild lists
    $vehList = $allVeh.Values | Sort-Object { Normalize-Immat $_.Immat }
    $drvList = $allDrivers.Values | Sort-Object { $_.Nom }
    # Also filter assignments for removed vehicles
    $cleanAssignments = @()
    foreach ($a in $allAssignments) {
        if ($allVeh.ContainsKey($a.Immat)) { $cleanAssignments += $a }
    }
    $allAssignments = $cleanAssignments

    $asgList = $allAssignments | Sort-Object { Normalize-Immat $_.Immat }

    Write-Host "After DB merge: $($vehList.Count) vehicles, $($drvList.Count) drivers, $($asgList.Count) assignments"
} else {
    Write-Host "No db_data.json found - skipping DB enrichment"
}

# ==== Phase 3: Create Template Workbook ====
Write-Host ""
Write-Host "=== Phase 3: Creating template workbook ==="

$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

# Start with a new workbook (has 1 sheet by default)
$wb = $excel.Workbooks.Add()

# Rename default sheet to VEHICULES, add other sheets
$wsVeh = $wb.Sheets.Item(1)
$wsVeh.Name = "VEHICULES"

$wsCha = $wb.Sheets.Add()
$wsCha.Name = "CHAUFFEURS"

$wsAff = $wb.Sheets.Add()
$wsAff.Name = "AFFECTATIONS"

$wsKm = $wb.Sheets.Add()
$wsKm.Name = "SUIVI_KM"

$wsMnt = $wb.Sheets.Add()
$wsMnt.Name = "SUIVI_MAINTENANCE"

# ==== Phase 4: Headers and formatting ====

# Colors
$blueHdr = 0x4472C4    # Blue for VEHICULES
$orangeHdr = 0xED7D31 # Orange for CHAUFFEURS
$greenHdr = 0x50AD4F  # Green for AFFECTATIONS
$grayHdr = 0xC4C4C4   # Gray for SUIVI_KM
$redHdr = 0xAB4A4A    # Red for SUIVI_MAINTENANCE

function Set-Headers($ws, $headers, $color) {
    for ($c = 0; $c -lt $headers.Count; $c++) {
        $cell = $ws.Cells.Item(1, $c + 1)
        $cell.Value = $headers[$c]
        $cell.Font.Bold = $true
        $cell.Font.Color = 16777215  # White text
        $cell.Font.Size = 11
        $cell.Interior.Color = $color
        $cell.HorizontalAlignment = -4108  # xlCenter
        $cell.VerticalAlignment = -4108
    }
}

# Define headers
$vehHeaders = @("N°","Entité","Région","Immatriculation","Châssis","Marque","Modèle","Type_Véhicule","Catégorie_Permis_Requise","Année","Carburant","État","Observation")
$chaHeaders = @("N°","Entité","Nom_Prénoms","Téléphone","No_Permis","Permis_Principal","Date_Exp_Permis","No_CNI","Date_Exp_CNI","Statut")
$affHeaders = @("N°","Entité","Immatriculation","Chauffeur_Principal","Chauffeur_Assistant","Ville","Région","Date_Début","Date_Fin","Statut")
$kmHeaders  = @("N°","Immatriculation","Date_Relevé","KM_Compteur","KM_Parcourus","Début_Période","Fin_Période","Observation")
$mntHeaders = @("N°","Immatriculation","Date","Type_Intervention","Prestataire","Coût","Nb_Jours_Immobilisation","Description")

Set-Headers $wsVeh $vehHeaders $blueHdr
Set-Headers $wsCha $chaHeaders $orangeHdr
Set-Headers $wsAff $affHeaders $greenHdr
Set-Headers $wsKm  $kmHeaders  $grayHdr
Set-Headers $wsMnt $mntHeaders $redHdr

# Helper: map city to region
function CityToRegion($city) {
    if (-not $city) { return "" }
    $c = $city.ToUpper()
    if ($c -match 'BAFOUSSAM|BAMENDA|DSCHANG|FOURAMBAN|FOTO|MBOUDA|OUEST') { return "Ouest" }
    if ($c -match 'DOUALA|NKONGSAMBA|BONABERI|LITTORAL|PENJA') { return "Littoral" }
    if ($c -match 'YAOUNDÉ|YAOUNDE|MBALMAYO|CENTRE') { return "Centre" }
    if ($c -match 'BERTOUA|GAROUA BOULAÏ|EST') { return "Est" }
    if ($c -match 'NGAOUNDÉRÉ|NGAOUNDERE|ADAMAOUA') { return "Adamaoua" }
    if ($c -match 'GAROUA|NORD') { return "Nord" }
    if ($c -match 'MAROUA|EXTREME|EXTRÊME') { return "Extrême-Nord" }
    if ($c -match 'EDEA|LIMBE|BUÉA|SUD-OUEST|SUD OUEST') { return "Sud-Ouest" }
    return ""
}

# ==== Phase 5: Populate VEHICULES ====
Write-Host "Populating VEHICULES..."
$r = 2
foreach ($v in $vehList) {
    $key = Normalize-Immat $v.Immat

    # Collect ville and region from source data
    $ville = ""
    $region = ""
    foreach ($sv in $spcVehicles) {
        if (Normalize-Immat $sv.Immat -eq $key) { $ville = $sv.Ville; break }
    }
    if ($ville) { $region = CityToRegion $ville }

    # Collect état
    $etat = ""
    foreach ($sv in $spcVehicles) {
        if (Normalize-Immat $sv.Immat -eq $key) { $etat = $sv.Etat; break }
    }
    if (-not $etat) {
        foreach ($pv in $pdcVehicles) {
            if (Normalize-Immat $pv.Immat -eq $key) { $etat = $pv.Etat; break }
        }
    }

    # Infer type from marque
    $typeVeh = ""
    $m = $v.Marque.ToUpper()
    if ($m -match 'TRICYCLE|TRIICYCLE|MOTO') { $typeVeh = "Tricycle/Moto" }
    elseif ($m -match 'BUS|HIACE|MINI BUS') { $typeVeh = "Bus/Minibus" }
    elseif ($m -match 'HINO|MERCEDES|CAMION') { $typeVeh = "Camion" }
    elseif ($m -match 'LAND CRUISER|PRADO|FORTUNER|RUSH|TXL|LAND C') { $typeVeh = "Véhicule Léger" }
    elseif ($m -match 'PICK UP|PICK-UP|ISUZU') { $typeVeh = "Pick-up" }
    elseif ($m -match 'SUZUKI|VITARA|AVANZA|MILDLIUM') { $typeVeh = "Véhicule Léger" }
    else { $typeVeh = "Véhicule Léger" }

    # Année from MEC
    $annee = ""
    if ($v.MEC) {
        if ($v.MEC -match '(\d{4})') { $annee = $Matches[1] }
        elseif ($v.MEC -match '(\d{2})/(\d{2})/(\d{4})') { $annee = $Matches[3] }
    }

    $wsVeh.Cells.Item($r, 1).Value = [string]($r - 1)   # N°
    $wsVeh.Cells.Item($r, 2).Value = $v.Entite           # Entité
    $wsVeh.Cells.Item($r, 3).Value = $region             # Région
    $wsVeh.Cells.Item($r, 4).Value = $v.Immat            # Immatriculation
    $wsVeh.Cells.Item($r, 5).Value = $v.Chassis          # Châssis
    $wsVeh.Cells.Item($r, 6).Value = $v.Marque           # Marque
    $wsVeh.Cells.Item($r, 7).Value = ""                  # Modèle (to fill)
    $wsVeh.Cells.Item($r, 8).Value = $typeVeh            # Type_Véhicule
    $wsVeh.Cells.Item($r, 9).Value = ""                  # Catégorie Permis Requise (to fill)
    $wsVeh.Cells.Item($r, 10).Value = $annee             # Année
    $wsVeh.Cells.Item($r, 11).Value = $v.Energie         # Carburant
    $wsVeh.Cells.Item($r, 12).Value = $etat              # État
    $wsVeh.Cells.Item($r, 13).Value = ""                 # Observation (to fill)
    $r++
}
$lastVehRow = $r - 1
Write-Host "  $($lastVehRow - 1) vehicles populated"

# ==== Phase 6: Populate CHAUFFEURS ====
Write-Host "Populating CHAUFFEURS..."
$r = 2
foreach ($d in $drvList) {
    $wsCha.Cells.Item($r, 1).Value = [string]($r - 1)  # N°
    $wsCha.Cells.Item($r, 2).Value = $d.Entite         # Entité
    $wsCha.Cells.Item($r, 3).Value = $d.Nom            # Nom_Prénoms
    $wsCha.Cells.Item($r, 4).Value = $d.Tel            # Téléphone
    $wsCha.Cells.Item($r, 5).Value = ""                # No_Permis (to fill)
    $wsCha.Cells.Item($r, 6).Value = $d.PermisPrincipal # Permis_Principal
    $wsCha.Cells.Item($r, 7).Value = $d.ExpPermis      # Date_Exp_Permis
    $wsCha.Cells.Item($r, 8).Value = ""                # No_CNI (to fill)
    $wsCha.Cells.Item($r, 9).Value = $d.CNI            # Date_Exp_CNI (from CNI expiry date)
    $wsCha.Cells.Item($r, 10).Value = ""               # Statut (to fill)
    $r++
}
$lastChaRow = $r - 1
Write-Host "  $($lastChaRow - 1) drivers populated"

# ==== Phase 7: Populate AFFECTATIONS ====
Write-Host "Populating AFFECTATIONS..."
# Group assignments by immat, then list principal+assistant per row
# First pass: build a map of immat -> { principal: [names], assistant: [names], entite, ville }
$affMap = @{}
foreach ($a in $asgList) {
    $key = $a.Immat
    if (-not $affMap.ContainsKey($key)) {
        # Find ville from SPC data
        $ville = ""
        foreach ($sv in $spcVehicles) {
            if (Normalize-Immat $sv.Immat -eq $key) { $ville = $sv.Ville; break }
        }
        $entite = $a.Entite
        # Find entite from vehicle list
        if ($allVeh.ContainsKey($key)) { $entite = $allVeh[$key].Entite }
        $affMap[$key] = @{
            Principals = @()
            Assistants = @()
            Entite = $entite
            Ville = $ville
        }
    }
    if ($a.Role -eq "Principal") {
        if ($a.Chauffeur -notin $affMap[$key].Principals) {
            $affMap[$key].Principals += $a.Chauffeur
        }
    } else {
        if ($a.Chauffeur -notin $affMap[$key].Assistants) {
            $affMap[$key].Assistants += $a.Chauffeur
        }
    }
}

$r = 2
$affKeys = $affMap.Keys | Sort-Object
foreach ($key in $affKeys) {
    $a = $affMap[$key]
    # Find the actual immatriculation string
    $immStr = $key
    if ($allVeh.ContainsKey($key)) { $immStr = $allVeh[$key].Immat }

    # One row per principal driver
    $principals = $a.Principals
    $assistants = $a.Assistants

    if ($principals.Count -eq 0) { $principals = @("") }
    $maxCount = [Math]::Max($principals.Count, 1)
    if ($assistants.Count -gt $maxCount) { $maxCount = $assistants.Count }

    for ($i = 0; $i -lt $maxCount; $i++) {
        $villeVal = $a.Ville
        $regionVal = CityToRegion $villeVal
        $wsAff.Cells.Item($r, 1).Value = [string]($r - 1)  # N°
        $wsAff.Cells.Item($r, 2).Value = $a.Entite          # Entité
        $wsAff.Cells.Item($r, 3).Value = $immStr            # Immatriculation
        $wsAff.Cells.Item($r, 4).Value = if ($i -lt $principals.Count) { $principals[$i] } else { "" }
        $wsAff.Cells.Item($r, 5).Value = if ($i -lt $assistants.Count) { $assistants[$i] } else { "" }
        $wsAff.Cells.Item($r, 6).Value = $villeVal          # Ville
        $wsAff.Cells.Item($r, 7).Value = $regionVal         # Région
        $wsAff.Cells.Item($r, 8).Value = ""                 # Date_Début (to fill)
        $wsAff.Cells.Item($r, 9).Value = ""                 # Date_Fin (to fill)
        $wsAff.Cells.Item($r, 10).Value = ""                # Statut (to fill)
        $r++
    }
}
$lastAffRow = $r - 1
Write-Host "  $($lastAffRow - 1) assignment rows populated"

# ==== Phase 8: Format as tables, add borders, freeze panes, data validation ====

function Format-Sheet($ws, $lastRow, $colCount, $color) {
    if ($lastRow -lt 2) { return }
    $range = $ws.Range($ws.Cells.Item(1, 1), $ws.Cells.Item($lastRow, $colCount))
    # Add borders
    $range.Borders.Weight = 2  # xlThin
    # Auto-fit columns
    $ws.Columns.AutoFit() | Out-Null
    # Freeze header row
    $ws.Activate()
    $ws.Application.ActiveWindow.SplitRow = 1
    $ws.Application.ActiveWindow.FreezePanes = $true
    # Add table/listobject
    $tblRange = $ws.Range($ws.Cells.Item(1, 1), $ws.Cells.Item($lastRow, $colCount))
    $tbl = $ws.ListObjects.Add(1, $tblRange, $null, 1)  # xlSrcRange, xlYes
    $tbl.Name = "Tbl_" + $ws.Name
    $tbl.TableStyle = "TableStyleMedium2"
}

# Add data validation lists for common fields
function Add-ListValidation($ws, $col, $lastRow, $values) {
    if ($lastRow -lt 2) { return }
    $range = $ws.Range($ws.Cells.Item(2, $col), $ws.Cells.Item($lastRow, $col))
    $range.Validation.Add(3, 1, 1, ($values -join ',')) | Out-Null  # xlValidateList
}

# Entité list
$entites = "SPC,BELGOCAM,AGROCAM,CEMAVET,HYDROCHEM,PDC"

$regions = "Ouest,Littoral,Centre,Est,Adamaoua,Nord,Extrême-Nord,Sud-Ouest,Nord-Ouest,Sud"

# VEHICULES formatting
Format-Sheet $wsVeh $lastVehRow $vehHeaders.Count $blueHdr
Add-ListValidation $wsVeh 2 $lastVehRow $entites  # Entité
Add-ListValidation $wsVeh 3 $lastVehRow $regions  # Région
Add-ListValidation $wsVeh 8 $lastVehRow "Véhicule Léger,Pick-up,Bus/Minibus,Camion,Tricycle/Moto"  # Type
Add-ListValidation $wsVeh 11 $lastVehRow "GASOIL,SUPER,ÉLECTRIQUE"  # Carburant
Add-ListValidation $wsVeh 12 $lastVehRow "FONCTIONNEL,EN PANNE,EN RÉPARATION,REFORMÉ"  # État

# CHAUFFEURS formatting
Format-Sheet $wsCha $lastChaRow $chaHeaders.Count $orangeHdr
Add-ListValidation $wsCha 2 $lastChaRow $entites  # Entité
Add-ListValidation $wsCha 6 $lastChaRow "A,B,C,D,E,BE,CE,DE,A-B,A-B-BE-C-CE-D-DE,B-C,B-BE-C-CE"  # Permis_Principal
Add-ListValidation $wsCha 10 $lastChaRow "Actif,Inactif,Suspendu"  # Statut

# AFFECTATIONS formatting
Format-Sheet $wsAff $lastAffRow $affHeaders.Count $greenHdr
Add-ListValidation $wsAff 2 $lastAffRow $entites  # Entité
Add-ListValidation $wsAff 7 $lastAffRow $regions  # Région
Add-ListValidation $wsAff 10 $lastAffRow "En cours,Terminé,Planifié"  # Statut

# SUIVI_KM - just headers (no data)
$wsKm.Columns.AutoFit() | Out-Null
$wsKm.Activate()
$wsKm.Application.ActiveWindow.SplitRow = 1
$wsKm.Application.ActiveWindow.FreezePanes = $true
$kmRange = $wsKm.Range($wsKm.Cells.Item(1, 1), $wsKm.Cells.Item(1, $kmHeaders.Count))
$tblKm = $wsKm.ListObjects.Add(1, $kmRange, $null, 1)
$tblKm.Name = "Tbl_SUIVI_KM"
$tblKm.TableStyle = "TableStyleMedium2"

# SUIVI_MAINTENANCE - just headers (no data)
$wsMnt.Columns.AutoFit() | Out-Null
$wsMnt.Activate()
$wsMnt.Application.ActiveWindow.SplitRow = 1
$wsMnt.Application.ActiveWindow.FreezePanes = $true
$mntRange = $wsMnt.Range($wsMnt.Cells.Item(1, 1), $wsMnt.Cells.Item(1, $mntHeaders.Count))
$tblMnt = $wsMnt.ListObjects.Add(1, $mntRange, $null, 1)
$tblMnt.Name = "Tbl_SUIVI_MAINTENANCE"
$tblMnt.TableStyle = "TableStyleMedium2"

# Set VEHICULES as active sheet
$wsVeh.Activate()

# ==== Save ====
Write-Host ""
Write-Host "Saving template..."
# Delete existing if present
if (Test-Path $dst) { Remove-Item $dst -Force }
$wb.SaveAs($dst, 51)  # 51 = xlOpenXMLWorkbook
$wb.Close($false)
$excel.Quit()

[System.Runtime.Interopservices.Marshal]::ReleaseComObject($wsMnt) | Out-Null
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($wsKm) | Out-Null
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($wsAff) | Out-Null
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($wsCha) | Out-Null
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($wsVeh) | Out-Null
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($wb) | Out-Null
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
[System.GC]::Collect()

Write-Host ""
Write-Host "=== Template created: $dst ==="
Write-Host "VEHICULES: $($lastVehRow - 1) vehicles"
Write-Host "CHAUFFEURS: $($lastChaRow - 1) drivers"
Write-Host "AFFECTATIONS: $($lastAffRow - 1) rows"
Write-Host "SUIVI_KM: headers only"
Write-Host "SUIVI_MAINTENANCE: headers only"
