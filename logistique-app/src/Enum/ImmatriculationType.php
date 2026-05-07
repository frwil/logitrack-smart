<?php

namespace App\Enum;

enum ImmatriculationType: string
{
    case Civile = 'civile';
    case Attache = 'attache';
    case Etat = 'etat';
    case Diplomatique = 'diplomatique';
    case AssistanceTechnique = 'assistance_technique';
    case Essai = 'essai';
    case Transit = 'transit';
    case PosteTelecommunication = 'poste_telecommunication';
    case DroitsReduits = 'droits_reduits';
    case Chassis = 'chassis';
}