<?php

require_once _PS_MODULE_DIR_ . 'ckc_espacepro/classes/CkcSectionGuidesFiches.php';

class SectionGuidesFichesServices
{
    public function addSection($idCategory, $title)
    {
        $section = new CkcSectionGuidesFiches();
        $section->id_category = $idCategory;
        $section->title_section = $title;
        $section->ordering = 0;
        $section->active = 1;
        $section->created_at = date('Y-m-d H:i:s');
        $section->updated_at = date('Y-m-d H:i:s');

        if (!$section->add()) {
            return ['error' => 'Impossible d\'ajouter la section.'];
        }

        return ['success' => 'La section a été ajoutée avec succès.'];
    }

    public function updateTitle($id, $title)
    {
        $section = new CkcSectionGuidesFiches((int)$id);

        if (!Validate::isGenericName($title)) {
            return ['error' => 'Titre invalide.'];
        }

        $section->title_section = $title;
        $section->updated_at = date('Y-m-d H:i:s');

        if (!$section->update()) {
            return ['error' => 'Échec de la mise à jour.'];
        }

        return ['success' => 'Le titre a été mis à jour avec succès.'];
    }

    public function deleteSection($id)
    {
        $section = new CkcSectionGuidesFiches((int)$id);

        if (!$section->delete()) {
            return ['error' => 'Impossible de supprimer la section.'];
        }

        return ['success' => 'La section a été supprimée avec succès.'];
    }

    public function bulkDeleteSections(array $ids)
    {
        $deleted = 0;

        foreach ($ids as $id_section) {
            $section = new CkcSectionGuidesFiches((int)$id_section);
            if (Validate::isLoadedObject($section) && $section->delete()) {
                $deleted++;
            }
        }

        if ($deleted > 0) {
            return ['success' => sprintf('%d section(s) supprimée(s) avec succès.', $deleted)];
        }

        return ['error' => 'Aucune section n\'a pu être supprimée.'];
    }

    public function toggleActive($id_section)
    {
        if (!$id_section) {
            return ['success' => false, 'error' => 'ID manquant'];
        }

        $section = new CkcSectionGuidesFiches((int)$id_section);
        if (!Validate::isLoadedObject($section)) {
            return ['success' => false, 'error' => 'Section introuvable'];
        }

        $section->active = (int)!$section->active;
        $result = $section->update();

        if ($result) {
            return ['success' => true, 'active' => (bool)$section->active];
        }

        return ['success' => false, 'error' => 'Échec de la mise à jour'];
    }

    public function updateOrdering(array $orderedIds)
    {
        if (empty($orderedIds)) {
            return ['success' => false, 'error' => 'Liste vide'];
        }

        foreach ($orderedIds as $position => $id_section) {
            $section = new CkcSectionGuidesFiches((int)$id_section);
            if (Validate::isLoadedObject($section)) {
                $section->ordering = (int)$position;
                $section->update(); // tu peux gérer le retour si besoin
            }
        }

        return ['success' => true];
    }

}
