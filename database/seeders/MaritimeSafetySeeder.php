<?php

namespace Database\Seeders;

use App\Models\CountryRegulation;
use App\Models\EmergencyContact;
use Illuminate\Database\Seeder;

/**
 * Numéros de secours maritime et obligations documentaires par pays.
 *
 * Ces données ne figurent pas dans OpenStreetMap : elles proviennent des
 * autorités maritimes nationales (CROSS pour la France, Salvamento Marítimo
 * pour l'Espagne, Guardia Costiera pour l'Italie). Le 112 est le numéro
 * d'urgence unique européen ; le canal VHF 16 est la fréquence internationale
 * de détresse.
 */
class MaritimeSafetySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRegulations();
        $this->seedEmergencyContacts();
    }

    private function seedRegulations(): void
    {
        $regulations = [
            [
                'country_code' => 'FR',
                'country_name' => 'France',
                'flag_emoji' => '🇫🇷',
                'required_documents' => ['Titre de navigation', 'Permis plaisance (bateau à moteur)', 'Assurance responsabilité civile', 'Licence station radio VHF'],
                'rules' => "Équipement de sécurité selon la division 240, adapté à la distance d'un abri. Feux de navigation obligatoires de nuit et par visibilité réduite.",
                'anchoring_rules' => 'Mouillage sur herbier de posidonie interdit en Méditerranée pour les navires de plus de 24 m, et réglementé dans les aires marines protégées.',
            ],
            [
                'country_code' => 'ES',
                'country_name' => 'Espagne',
                'flag_emoji' => '🇪🇸',
                'required_documents' => ['Titre de propriété du navire', 'Assurance responsabilité civile', 'Pavillon national visible', 'Titre de conduite'],
                'rules' => "Déclaration d'entrée auprès des autorités portuaires pour les navires étrangers hors Union européenne. Pavillon de courtoisie recommandé.",
                'anchoring_rules' => 'Mouillage interdit sur les herbiers de posidonie et réglementé dans les parcs naturels (Cabrera, Ses Salines, Cap de Creus).',
            ],
            [
                'country_code' => 'IT',
                'country_name' => 'Italie',
                'flag_emoji' => '🇮🇹',
                'required_documents' => ['Titre de navigation', 'Assurance responsabilité civile', 'Licence radio', 'Permis de conduire nautique si requis'],
                'rules' => "Annonce VHF recommandée à l'approche des ports. Taxe de mouillage applicable dans certaines aires marines protégées.",
                'anchoring_rules' => 'Mouillage réglementé dans les aires marines protégées ; bouées écologiques obligatoires dans plusieurs zones de Sardaigne et de Toscane.',
            ],
            [
                'country_code' => 'PT',
                'country_name' => 'Portugal',
                'flag_emoji' => '🇵🇹',
                'required_documents' => ['Titre de navigation', 'Assurance responsabilité civile', 'Rôle d’équipage', 'Licence radio'],
                'rules' => "Déclaration d'arrivée auprès de la capitainerie (Capitania) dans les ports d'entrée.",
                'anchoring_rules' => 'Mouillage soumis aux instructions des capitaineries locales, notamment dans les estuaires du Tage et du Douro.',
            ],
            [
                'country_code' => 'GB',
                'country_name' => 'Royaume-Uni',
                'flag_emoji' => '🇬🇧',
                'required_documents' => ['Preuve de propriété', 'Assurance responsabilité civile', 'Licence radio VHF', 'Déclaration douanière à l’arrivée'],
                'rules' => "Déclaration d'arrivée obligatoire auprès des douanes pour les navires venant de l'étranger.",
                'anchoring_rules' => "Mouillage libre hors chenaux et zones portuaires, sous réserve des règlements d'estuaire.",
            ],
            [
                'country_code' => 'BE',
                'country_name' => 'Belgique',
                'flag_emoji' => '🇧🇪',
                'required_documents' => ['Lettre de pavillon', 'Assurance responsabilité civile', 'Brevet de conduite', 'Licence radio'],
                'rules' => 'Veille VHF obligatoire dans les chenaux du littoral et à proximité des ports.',
                'anchoring_rules' => 'Mouillage interdit dans les chenaux d’accès et les zones de dragage.',
            ],
            [
                'country_code' => 'NL',
                'country_name' => 'Pays-Bas',
                'flag_emoji' => '🇳🇱',
                'required_documents' => ['Preuve de propriété', 'Assurance responsabilité civile', 'Certificat ICC si requis'],
                'rules' => 'Respect des règles de priorité en eaux intérieures et veille VHF sur les canaux de trafic.',
                'anchoring_rules' => 'Mouillage réglementé en mer des Wadden, zone protégée UNESCO.',
            ],
        ];

        foreach ($regulations as $regulation) {
            CountryRegulation::updateOrCreate(
                ['country_code' => $regulation['country_code']],
                $regulation,
            );
        }
    }

    private function seedEmergencyContacts(): void
    {
        $contacts = [
            // France — CROSS (Centres régionaux opérationnels de surveillance et de sauvetage)
            ['FR', 'France', 'Urgence européenne', 'Numéro d’urgence unique', '112', null, 'Accessible depuis tout téléphone, y compris sans carte SIM.', 1],
            ['FR', 'France', 'CROSS', 'Secours maritime depuis un mobile', '196', null, 'Met en relation directe avec le CROSS compétent.', 2],
            ['FR', 'France', 'CROSS', 'Détresse VHF', null, '16', 'Canal international de détresse et d’appel, veillé en permanence.', 3],
            ['FR', 'France', 'CROSS', 'Appel sélectif numérique ASN', null, '70', 'Canal ASN/DSC pour les alertes automatiques.', 4],

            // Espagne — Salvamento Marítimo
            ['ES', 'Espagne', 'Urgence européenne', 'Numéro d’urgence unique', '112', null, null, 1],
            ['ES', 'Espagne', 'Salvamento Marítimo', 'Secours maritime', '900 202 202', null, 'Centre national de coordination du sauvetage maritime.', 2],
            ['ES', 'Espagne', 'Salvamento Marítimo', 'Détresse VHF', null, '16', 'Canal international de détresse.', 3],

            // Italie — Guardia Costiera
            ['IT', 'Italie', 'Urgence européenne', 'Numéro d’urgence unique', '112', null, null, 1],
            ['IT', 'Italie', 'Guardia Costiera', 'Secours maritime', '1530', null, 'Numéro bleu du sauvetage en mer.', 2],
            ['IT', 'Italie', 'Guardia Costiera', 'Détresse VHF', null, '16', 'Canal international de détresse.', 3],

            // Portugal
            ['PT', 'Portugal', 'Urgence européenne', 'Numéro d’urgence unique', '112', null, null, 1],
            ['PT', 'Portugal', 'MRCC Lisboa', 'Secours maritime', '+351 214 401 919', null, 'Centre de coordination du sauvetage maritime.', 2],
            ['PT', 'Portugal', 'MRCC Lisboa', 'Détresse VHF', null, '16', 'Canal international de détresse.', 3],

            // Royaume-Uni — HM Coastguard
            ['GB', 'Royaume-Uni', 'Urgence', 'Numéro d’urgence', '999', null, 'Le 112 fonctionne également.', 1],
            ['GB', 'Royaume-Uni', 'HM Coastguard', 'Secours maritime', '999', null, 'Demander « Coastguard » à l’opérateur.', 2],
            ['GB', 'Royaume-Uni', 'HM Coastguard', 'Détresse VHF', null, '16', 'Canal international de détresse.', 3],

            // Belgique
            ['BE', 'Belgique', 'Urgence européenne', 'Numéro d’urgence unique', '112', null, null, 1],
            ['BE', 'Belgique', 'MRCC Ostende', 'Secours maritime', '+32 59 70 10 00', null, 'Centre de coordination du sauvetage maritime.', 2],
            ['BE', 'Belgique', 'MRCC Ostende', 'Détresse VHF', null, '16', 'Canal international de détresse.', 3],

            // Pays-Bas
            ['NL', 'Pays-Bas', 'Urgence européenne', 'Numéro d’urgence unique', '112', null, null, 1],
            ['NL', 'Pays-Bas', 'Kustwacht', 'Secours maritime', '+31 900 0111', null, 'Garde-côtes néerlandais.', 2],
            ['NL', 'Pays-Bas', 'Kustwacht', 'Détresse VHF', null, '16', 'Canal international de détresse.', 3],
        ];

        foreach ($contacts as [$code, $country, $authority, $label, $phone, $vhf, $note, $position]) {
            EmergencyContact::updateOrCreate(
                ['country_code' => $code, 'label' => $label],
                [
                    'country_name' => $country,
                    'authority' => $authority,
                    'phone' => $phone,
                    'vhf_channel' => $vhf,
                    'note' => $note,
                    'position' => $position,
                ],
            );
        }
    }
}
