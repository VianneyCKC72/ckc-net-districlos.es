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

    private function getStaticCards(string $type): array
    {
        $data = [
            'benefits' => [
                [
                    'title' => 'Livraison offerte dès 450€',
                    'icon'  => 'panier',
                    'text' => "Commandez l'esprit tranquille : nos experts prennent en charge l'installation et le montage, sans effort de votre part."
                ],
                [
                    'title' => 'Meilleur prix',
                    'icon'  => 'pouce',
                    'text' => "Tous nos professionnels sont soigneusement vérifiés et notés après chaque intervention, pour vous garantir une prestation de qualité."
                ],
                [
                    'title' => '100 € offert dès 1000€ d\'achat',
                    'icon'  => 'clef_molette',
                    'text' => "Chaque prestation est couverte par AXA Assurance pour une tranquillité d'esprit maximale."
                ],
                [
                    'title' => 'Reprise de marchandise',
                    'icon'  => 'teleoperateur',
                    'text' => "Notre service client est à votre écoute du lundi au vendredi de9h30 à 18h30 pour répondre à toutes vos questions."
                ],
                [
                    'title' => 'Processus simplifié',
                    'icon'  => 'carton',
                    'text' => "Dès l'expédition ou le retrait de votre commande, un expert vous contacte pour fixer votre rendez-vous d'installation."
                ],
                [
                    'title' => 'Flexibilité garantie',
                    'icon'  => 'horloge',
                    'text' => "Suivez votre prestation en temps réel et modifiez ou annulez votre rendez-vous jusqu'à 48h à l'avance sans frais."
                ],

            ],
            'services' => [
                [
                    'title' => 'Assistance technique',
                    'icon'  => 'teleoperateur',
                    'text' => "Notre équipe répond à vos interrogations du lundi au vendredi pour vous accompagner efficacement dans tous vos projets."
                ],
                [
                    'title' => 'Priorité sur les commandes',
                    'icon'  => 'entrepot',
                    'text' => "En tant que professionnel, vous bénéficiez d’un traitement prioritaire : livraison rapide et accès aux stocks grâce à notre flotte de transport dédiée."
                ],
                [
                    'title' => 'Conseils',
                    'icon'  => 'chariste',
                    'text' => "Profitez de conseils personnalisés et de recommandations techniques pour choisir les produits adaptés à vos chantiers."
                ],
                [
                    'title' => 'Explication des processus',
                    'icon'  => 'ordinateur',
                    'text' => "Nous vous accompagnons pas à pas — devis, commandes, livraison — pour vous permettre de travailler sereinement."
                ],
                [
                    'title' => 'Documents techniques',
                    'icon'  => 'billets_main',
                    'text' => "Accédez rapidement à tous les guides, fiches techniques et tutoriels indispensables à vos installations."
                ]
            ],
        ];

        return $data[$type] ?? [];
    }

}


