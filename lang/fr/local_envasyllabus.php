<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify.
// it under the terms of the GNU General Public License as published by.
// the Free Software Foundation, either version 3 of the License, or.
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,.
// but WITHOUT ANY WARRANTY; without even the implied warranty of.
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the.
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License.
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_envasyllabus
 * @category    string
 * @copyright   2022 CALL Learning - Laurent David <laurent@call-learning>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Actions';
$string['active_help'] = 'Pourcentage de méthodes pédagogiques actives mises en place au sein de l\'UC';
$string['catalog:filter_sort'] = 'Filtres et Tris';
$string['catalog:index'] = 'Catalogue';
$string['cf:uc_annee'] = 'Année';
$string['cf:uc_nombre'] = 'UC';
$string['cf:uc_semestre'] = 'Semestre';
$string['chartview'] = 'Vue graphique';
$string['competency:rep::name'] = 'Compétence ({$a})';
$string['competency:rep:percent'] = 'Pourcent Compétence ({$a})';
$string['confirm_delete'] = 'Êtes-vous sûr de vouloir supprimer ce fichier ?';
$string['confirm_delete_message'] = 'Êtes-vous sûr de vouloir supprimer le fichier "{$a}" ? Cette action ne peut pas être annulée.';
$string['confirm_delete_title'] = 'Confirmer la suppression du fichier';
$string['course:summary'] = 'Résumé';
$string['course_no_semester'] = 'Année {$a}';
$string['course_semester'] = 'Année {$a->year}, {$a->semester}';
$string['coursecard:credits'] = '{$a} crédits ECTS';
$string['coursecard:hours'] = '{$a} heures';
$string['courses:index'] = 'Le cursus à l\'ENVA';
$string['defaultmatrixid'] = 'Default Matrix ID';
$string['defaultmatrixid_desc'] = 'Default Matrix ID';
$string['delete'] = 'Supprimer';
$string['discipline:rep:name'] = 'Discipline ({$a})';
$string['discipline:rep:percent'] = 'Pourcent Discipline ({$a})';
$string['download'] = 'Télécharger';
$string['edit_teachers'] = 'Modifier l\'équipe pédagogique';
$string['edit_teachers_explanation'] = 'Pour afficher des intervenants dans l\'équipe pédagogique, attribuez-leur le rôle « Enseignant » dans la liste des participants.

Cliquez ci-dessous pour gérer les rôles.';
$string['editfield'] = 'Modifier';
$string['editsyllabusfields'] = 'Modifier les champs du syllabus';
$string['email:spreadsheet:body'] = 'Veuillez trouver en pièce jointe le rapport syllabus généré le {$a->date}.

Nom du fichier: {$a->filename}
Taille du fichier: {$a->filesize}

Ceci est un message automatique.';
$string['email:spreadsheet:subject'] = 'Rapport Syllabus - {$a}';
$string['enableenvasyllabus'] = 'Active le module de syllabus de l\'ENVA';
$string['enableenvasyllabus_help'] = 'Active le module de syllabus de l\'ENVA (menu additionnel dans les cours)';
$string['enablenewprogramme'] = 'Activate new programme';
$string['enablenewprogramme_desc'] = 'Activate new programme for all courses';
$string['enablenewprogrammeforcourse'] = 'Active le nouveau programme pour des cours spécifiques';
$string['enablenewprogrammeforcourse_desc'] = 'Active le nouveau programme pour des cours spécifiques.
Si aucun cours n\'est spécifié, l\'ancien champs est utilisé.';
$string['enddate'] = 'Date de fin';
$string['englishversion'] = 'Version anglaise : {$a}';
$string['entity:programme_with_customfields'] = 'Programme avec champs personnalisés';
$string['exportexcel'] = 'Export Excel';
$string['exportexcel_help'] = 'Exporter les données du catalogue au format Excel';
$string['fieldnotfound'] = 'Champ personnalisé introuvable';
$string['fieldnotvisible'] = 'Le champ personnalisé n\'est pas visible';
$string['fieldupdated'] = 'Le champ "{$a}" a été mis à jour avec succès';
$string['file_not_found'] = 'Le fichier demandé n\'a pas pu être trouvé.';
$string['filedeleted'] = 'Fichier supprimé avec succès';
$string['filedeleted_success'] = 'Le fichier "{$a}" a été supprimé avec succès.';
$string['filename'] = 'Nom du fichier';
$string['filesize'] = 'Taille du fichier';
$string['frenchversion'] = 'Version française : {$a}';
$string['generalsettings'] = 'Enva Syllabus settings';
$string['invalidcourse'] = 'Cours invalide';
$string['manage_participants'] = 'Gérer les participants';
$string['manage_spreadsheets'] = 'Gérer les fichiers Excel';
$string['multilanguagefields'] = 'Champs multilingues';
$string['no_files_found'] = 'Aucun fichier Excel trouvé';
$string['perso_help'] = 'Temps de travail personnel minimal estimé nécessaire pour acquérir les compétences visées par cette UC';
$string['pluginname'] = 'ENVA Syllabus';
$string['publicfields'] = 'Public fields';
$string['publicfields_desc'] = 'Course field visible to guest users';
$string['report:competencies'] = 'Rapport des compétences Syllabus';
$string['report:disciplines'] = 'Rapport des disciplines Syllabus';
$string['report:historyrfc'] = 'History RFC report';
$string['report:historyrfctotals'] = 'History RFC totals report';
$string['report:programme'] = 'Report sur les programmes Syllabus';
$string['reports'] = 'Rapports';
$string['rootcategoryid'] = 'Root Category';
$string['rootcategoryid_desc'] = 'Root Category Description';
$string['sort'] = 'Tri';
$string['sort:customfield_uc_annee'] = 'Année';
$string['sort:fullname'] = 'Titre';
$string['sortorderasc'] = 'Ascendant';
$string['sortorderdesc'] = 'Descendant';
$string['spreadsheet_email_enabled'] = 'Activer les emails Excel';
$string['spreadsheet_email_enabled_desc'] = 'Envoyer des rapports Excel hebdomadaires par email';
$string['spreadsheet_email_settings'] = 'Rapports Excel par email';
$string['spreadsheet_email_settings_desc'] = 'Configurer les rapports Excel automatiques par email';
$string['spreadsheet_extended_mode'] = 'Mode étendu';
$string['spreadsheet_extended_mode_desc'] = 'Inclure les colonnes de programme dans le tableur';
$string['spreadsheet_files'] = 'Fichiers Excel';
$string['spreadsheet_lang'] = 'Langue du rapport';
$string['spreadsheet_lang_desc'] = 'Langue pour le contenu du tableur';
$string['spreadsheet_recipients'] = 'Destinataires des emails';
$string['spreadsheet_recipients_desc'] = 'Adresses email pour l\'envoi des rapports (séparées par des virgules)';
$string['startdate'] = 'Date de début';
$string['summary'] = 'Résumé';
$string['syllabus:lang:english'] = 'Anglais';
$string['syllabus:lang:label'] = 'Langage';
$string['syllabus:lang:system'] = 'Français';
$string['syllabuspage:additionalinfos'] = 'Information complémentaires';
$string['syllabuspage:competencies'] = 'Compétences générales visées';
$string['syllabuspage:header'] = 'Fiche de description de l\'unité de compétences (UC)';
$string['syllabuspage:manager'] = 'Responsable';
$string['syllabuspage:menu'] = 'Voir Syllabus';
$string['syllabuspage:prerequisites'] = 'Pré-requis';
$string['syllabuspage:program'] = 'Programme';
$string['syllabuspage:student_aash'] = 'Nombre total d\'heures de travail';
$string['syllabuspage:student_ects'] = 'Crédit ECTS';
$string['syllabuspage:student_grand_total_hours'] = 'Nombre total d\'heures de travail';
$string['syllabuspage:student_total_hours'] = 'Nombre d\'heures à l\'emploi du temps';
$string['syllabuspage:student_total_hours_he'] = 'Nombre d\'heures hors emploi du temps';
$string['syllabuspage:teachers'] = 'Equipe pédagogique';
$string['syllabuspage:title'] = 'Fiche de description de l\'unité de compétences (UC)';
$string['syllabuspage:uc_departement'] = 'Département de rattachement';
$string['syllabuspage:uc_heures_cm_etudiant'] = 'Cours Magistraux (CM)';
$string['syllabuspage:uc_heures_fmp_etudiant'] = 'Formation en milieu professionnel (FMP)';
$string['syllabuspage:uc_heures_he_aas_etudiant'] = 'Auto-apprentissage supervisé (AAS)';
$string['syllabuspage:uc_heures_he_tpers_etudiant'] = 'Travail personnel';
$string['syllabuspage:uc_heures_tc_etudiant'] = 'Travaux cliniques (TC)';
$string['syllabuspage:uc_heures_td_etudiant'] = 'Travaux dirigés (TD)';
$string['syllabuspage:uc_heures_tp_etudiant'] = 'Travaux pratiques (TP)';
$string['syllabuspage:uc_heures_tpa_etudiant'] = 'TP sur animaux sains (TPa)';
$string['syllabuspage:vaq'] = 'Validation des acquis';
$string['syllabusreports'] = 'Rapports Syllabus';
$string['table_filename'] = 'Nom du fichier';
$string['table_filesize'] = 'Taille du fichier';
$string['task:send_spreadsheet'] = 'Envoyer les rapports Excel par email';
$string['task:warm_course_cache'] = 'Réchauffer le cache des cours';
$string['th:acronym'] = 'Acronyme';
$string['th:responsible'] = 'Responsable';
$string['total_help'] = 'Total du volume horaire consacré à l\'UC, incluant l\'estimation du travail personnel';
$string['viewcourse'] = 'Voir contenu du cours';
