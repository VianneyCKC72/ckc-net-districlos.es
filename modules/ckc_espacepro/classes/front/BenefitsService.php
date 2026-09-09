<?php

class BenefitsService
{
    public function getBenefitsForCustomer(): array
    {
        return $this->getStaticCards('benefits');
    }

    public function getServicesForCustomer(): array
    {
        return $this->getStaticCards('services');
    }

    private function trans(string $string): string
    {
        $module = Module::getInstanceByName('ckc_espacepro');

        return $module ? $module->l($string, 'BenefitsService') : $string;
    }

    private function getStaticCards(string $type): array
    {
        $data = [
            'benefits' => [
                [
                    'title' => $this->trans('Livraison offerte dès 450€'),
                    'icon'  => 'panier',
                    'text' => $this->trans("Profitez d'une livraison gratuite dès 450 € d'achat pour optimiser vos coûts sur chaque chantier.."),
                ],
                [
                    'title' => $this->trans('Meilleur prix'),
                    'icon'  => 'pouce',
                    'text' => $this->trans('Districlos vous garantit des tarifs ultra-compétitifs, spécialement pensés pour les pros du jardin.'),
                ],
                [
                    'title' => $this->trans('Reprise de marchandise'),
                    'icon'  => 'teleoperateur',
                    'text' => $this->trans('Districlos reprend vos marchandises inutilisées pour vous offrir plus de flexibilité dans votre gestion de stock.'),
                ],
                [
                    'title' => $this->trans('Processus simplifié'),
                    'icon'  => 'carton',
                    'text' => $this->trans("Commandez l'esprit tranquille : nos experts prennent en charge l'installation et le montage, sans effort de votre part."),
                ],
                [
                    'title' => $this->trans('Flexibilité garantie'),
                    'icon'  => 'horloge',
                    'text' => $this->trans("Suivez votre prestation en temps réel et modifiez ou annulez votre rendez-vous jusqu'à 48h à l'avance sans frais."),
                ],
            ],
            'services' => [
                [
                    'title' => $this->trans('Assistance technique'),
                    'icon'  => 'teleoperateur',
                    'text' => $this->trans('Notre équipe répond à vos interrogations du lundi au vendredi pour vous accompagner efficacement dans tous vos projets.'),
                ],
                [
                    'title' => $this->trans('Priorité sur les commandes'),
                    'icon'  => 'entrepot',
                    'text' => $this->trans("En tant que professionnel, vous bénéficiez d'un traitement prioritaire : livraison rapide et accès aux stocks grâce à notre flotte de transport dédiée."),
                ],
                [
                    'title' => $this->trans('Conseils'),
                    'icon'  => 'chariste',
                    'text' => $this->trans('Profitez de conseils personnalisés et de recommandations techniques pour choisir les produits adaptés à vos chantiers.'),
                ],
                [
                    'title' => $this->trans('Explication des processus'),
                    'icon'  => 'ordinateur',
                    'text' => $this->trans('Nous vous accompagnons pas à pas — devis, commandes, livraison — pour vous permettre de travailler sereinement.'),
                ],
                [
                    'title' => $this->trans('Documents techniques'),
                    'icon'  => 'billets_main',
                    'text' => $this->trans('Accédez rapidement à tous les guides, fiches techniques et tutoriels indispensables à vos installations.'),
                ],
            ],
        ];

        return $data[$type] ?? [];
    }
}
